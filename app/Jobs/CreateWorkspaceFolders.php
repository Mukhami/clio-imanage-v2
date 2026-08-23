<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImanageTemplate;
use App\Models\ImanageWorkspace;
use App\Models\Tenant;
use App\Models\WebhookRequest;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class CreateWorkspaceFolders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int $webhookRequestId,
        public readonly int $tenantId,
    ) {
        $this->onQueue('long_term');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(): void
    {
        $wr     = WebhookRequest::findOrFail($this->webhookRequestId);
        $tenant = Tenant::with(['tenantSetting.library'])->findOrFail($this->tenantId);

        $setting = $tenant->tenantSetting;
        if (! $setting) {
            throw new RuntimeException("No TenantSetting for tenant {$tenant->id}");
        }

        $library = $setting->library;
        if (! $library) {
            throw new RuntimeException("No Library linked to TenantSetting for tenant {$tenant->id}");
        }

        $libraryId  = $library->imanage_library_id;
        $customerId = (string) $tenant->imanage_customer_id;

        // Find the non-replica workspace created for this webhook request
        $workspace = ImanageWorkspace::where('webhook_request_id', $wr->id)
            ->where('replica', false)
            ->first();

        if (! $workspace || ! $workspace->imanage_workspace_id) {
            throw new RuntimeException("No target workspace found for WebhookRequest {$wr->id}");
        }

        $targetWorkspaceId = $workspace->imanage_workspace_id;

        // Resolve the template workspace ID
        $template = $workspace->imanage_template_id
            ? ImanageTemplate::find($workspace->imanage_template_id)
            : null;

        if (! $template || ! $template->imanage_template_id) {
            // No template configured — nothing to copy, mark complete
            $wr->folder_activity_complete = true;
            $wr->save();
            return;
        }

        $templateWorkspaceId = $template->imanage_template_id;

        $imanage = new ImanageApiService($tenant);

        // Build the folder profile context (custom fields inherited from workspace)
        $folderProfile = array_filter([
            'custom1'  => $workspace->custom1,
            'custom2'  => $workspace->custom2,
            'custom29' => $workspace->custom29,
            'custom30' => $workspace->custom30,
        ]);

        try {
            // Get top-level children of the template workspace
            $childrenResponse = $imanage->getWorkspaceChildren($customerId, $libraryId, $templateWorkspaceId);
            $children         = data_get($childrenResponse, 'data', []);

            foreach ($children as $child) {
                $this->copyFolderNode(
                    $imanage,
                    $customerId,
                    $libraryId,
                    $child,
                    $targetWorkspaceId,
                    $folderProfile,
                    isWorkspaceChild: true,
                );
            }

            $wr->folder_activity_complete = true;
            $wr->save();
        } catch (Throwable $e) {
            $wr->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Recursively copy a folder node (and its children) into the target workspace.
     *
     * @param  array<string,mixed>  $node           The source folder object from the iManage API
     * @param  array<string,mixed>  $folderProfile  Custom field values to stamp onto each folder
     */
    private function copyFolderNode(
        ImanageApiService $imanage,
        string $customerId,
        string $libraryId,
        array $node,
        string $targetWorkspaceId,
        array $folderProfile,
        bool $isWorkspaceChild = false,
        ?string $parentFolderId = null,
    ): void {
        $sourceFolderId = $node['id'] ?? null;
        if (! $sourceFolderId) {
            return;
        }

        // Get profile (name-value-pairs) to determine class/subclass and optional flag
        $nvpResponse = $imanage->getFolderNameValuePairs($customerId, $libraryId, (string) $sourceFolderId);
        $nvpData     = data_get($nvpResponse, 'data', []);

        // Build a key→value map from the name-value-pairs array
        $nvp = [];
        foreach ($nvpData as $pair) {
            $nvp[$pair['name'] ?? ''] = $pair['value'] ?? null;
        }

        // Skip optional folders
        if (filter_var($nvp['IMCC_IsOptional'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $class    = $nvp['iMan___8'] ?? null;
        $subclass = $nvp['iMan___9'] ?? null;

        // Build the folder creation payload
        $folderData = [
            'name'             => $node['name'] ?? 'Folder',
            'description'      => $node['description'] ?? ($node['name'] ?? 'Folder'),
            'default_security' => 'inherit',
        ];

        $profile = $folderProfile;
        if ($class !== null) {
            $profile['class'] = $class;
        }
        if ($subclass !== null) {
            $profile['subclass'] = $subclass;
        }

        if (! empty($profile)) {
            $folderData['profile'] = $profile;
        }

        // Create folder in target workspace
        $createResponse = $imanage->createFolder($customerId, $libraryId, $targetWorkspaceId, $folderData);
        $createdFolder  = data_get($createResponse, 'data', []);
        $newFolderId    = $createdFolder['id'] ?? null;

        if ($newFolderId) {
            // Copy security from template folder to new folder
            $this->applyFolderSecurityFromSource(
                $imanage,
                $customerId,
                $libraryId,
                (string) $sourceFolderId,
                (string) $newFolderId,
            );

            // Recurse into sub-folders
            $subChildrenResponse = $imanage->getFolderChildren($customerId, $libraryId, (string) $sourceFolderId);
            $subChildren         = data_get($subChildrenResponse, 'data', []);

            foreach ($subChildren as $subChild) {
                $this->copyFolderNode(
                    $imanage,
                    $customerId,
                    $libraryId,
                    $subChild,
                    $targetWorkspaceId,
                    $folderProfile,
                    isWorkspaceChild: false,
                    parentFolderId: (string) $newFolderId,
                );
            }
        }
    }

    /**
     * Copy the security policy from a source folder to a target folder via a diff.
     * Members in the source go to `include`; this resets the target to match the template.
     */
    private function applyFolderSecurityFromSource(
        ImanageApiService $imanage,
        string $customerId,
        string $libraryId,
        string $sourceFolderId,
        string $targetFolderId,
    ): void {
        $sourceSecResponse = $imanage->getFolderSecurity($customerId, $libraryId, $sourceFolderId);
        $sourceSecData     = data_get($sourceSecResponse, 'data', []);

        $sourceMembers      = data_get($sourceSecData, 'members', []);
        $sourceDefaultSec   = data_get($sourceSecData, 'default_security', 'inherit');

        // Nothing to apply if source has no explicit members
        if (empty($sourceMembers)) {
            return;
        }

        $targetSecResponse = $imanage->getFolderSecurity($customerId, $libraryId, $targetFolderId);
        $targetSecData     = data_get($targetSecResponse, 'data', []);
        $targetMembers     = data_get($targetSecData, 'members', []);

        // Build keyed maps by user_id for diffing
        $sourceMemberMap = $this->memberMap($sourceMembers);
        $targetMemberMap = $this->memberMap($targetMembers);

        // Include: all source members (adds new + updates existing)
        $include = array_values($sourceMemberMap);

        // Remove: target members not present in source
        $remove = [];
        foreach ($targetMemberMap as $key => $member) {
            if (! isset($sourceMemberMap[$key])) {
                $remove[] = $member;
            }
        }

        $payload = ['default_security' => $sourceDefaultSec];
        if (! empty($include) || ! empty($remove)) {
            $payload['members'] = array_filter([
                'include' => $include ?: null,
                'remove'  => $remove  ?: null,
            ]);
        }

        $imanage->applyFolderSecurity($customerId, $libraryId, $targetFolderId, $payload);
    }

    /**
     * Build a lookup map from a members array, keyed by "type:id".
     *
     * @param  array<int, array<string,mixed>>  $members
     * @return array<string, array<string,mixed>>
     */
    private function memberMap(array $members): array
    {
        $map = [];
        foreach ($members as $member) {
            $userId   = $member['user_id']   ?? $member['id'] ?? null;
            $isGroup  = $member['is_group']  ?? false;
            $type     = $isGroup ? 'group' : 'user';
            $key      = "{$type}:{$userId}";
            $map[$key] = $member;
        }
        return $map;
    }
}
