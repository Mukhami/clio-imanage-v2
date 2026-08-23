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

class PostWorkspaceSecurity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

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

        // Find the non-replica workspace for this request
        $workspace = ImanageWorkspace::where('webhook_request_id', $wr->id)
            ->where('replica', false)
            ->first();

        if (! $workspace || ! $workspace->imanage_workspace_id) {
            throw new RuntimeException("No target workspace found for WebhookRequest {$wr->id}");
        }

        $targetWorkspaceId = $workspace->imanage_workspace_id;

        $imanage = new ImanageApiService($tenant);

        try {
            // Resolve template workspace ID (for copying security from)
            $template = $workspace->imanage_template_id
                ? ImanageTemplate::find($workspace->imanage_template_id)
                : null;

            if ($template && $template->imanage_template_id) {
                $templateWorkspaceId = $template->imanage_template_id;

                // Fetch template security policy
                $templateSecResponse = $imanage->getWorkspaceSecurityPolicy($customerId, $libraryId, $templateWorkspaceId);
                $templateSecData     = data_get($templateSecResponse, 'data', []);

                // Fetch current target security policy
                $targetSecResponse = $imanage->getWorkspaceSecurityPolicy($customerId, $libraryId, $targetWorkspaceId);
                $targetSecData     = data_get($targetSecResponse, 'data', []);

                $templateMembers    = data_get($templateSecData, 'members', []);
                $targetMembers      = data_get($targetSecData, 'members', []);
                $templateDefaultSec = data_get($templateSecData, 'default_security', 'public');

                // Build keyed maps by "type:id"
                $templateMemberMap = $this->memberMap($templateMembers);
                $targetMemberMap   = $this->memberMap($targetMembers);

                // Include: all template members
                $include = array_values($templateMemberMap);

                // Remove: members in target that are not in template
                $remove = [];
                foreach ($targetMemberMap as $key => $member) {
                    if (! isset($templateMemberMap[$key])) {
                        $remove[] = $member;
                    }
                }

                $payload = ['default_security' => $templateDefaultSec];

                if (! empty($include) || ! empty($remove)) {
                    $payload['members'] = array_filter([
                        'include' => $include ?: null,
                        'remove'  => $remove  ?: null,
                    ]);
                }

                $imanage->applyWorkspaceSecurity($customerId, $libraryId, $targetWorkspaceId, $payload);
            }

            $wr->security_activity_complete = true;
            $wr->save();

            AuditWorkspaceSecurity::dispatch($wr->id, $tenant->id)
                ->onQueue('long_term');

        } catch (Throwable $e) {
            $wr->markFailed($e->getMessage());
            throw $e;
        }
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
            $userId  = $member['user_id']  ?? $member['id'] ?? null;
            $isGroup = $member['is_group'] ?? false;
            $type    = $isGroup ? 'group' : 'user';
            $key     = "{$type}:{$userId}";
            $map[$key] = $member;
        }
        return $map;
    }
}
