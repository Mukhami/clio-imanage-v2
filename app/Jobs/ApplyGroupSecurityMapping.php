<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ClioGroup;
use App\Models\ClioUser;
use App\Models\ImanageUser;
use App\Models\ImanageWorkspace;
use App\Models\Tenant;
use App\Models\WebhookRequest;
use App\Services\ClioApiService;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ApplyGroupSecurityMapping implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $webhookRequestId,
        public readonly int $tenantId,
    ) {}

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

        $workspace = ImanageWorkspace::where('webhook_request_id', $wr->id)
            ->where('replica', false)
            ->first();

        if (! $workspace || ! $workspace->imanage_workspace_id) {
            throw new RuntimeException("No target workspace found for WebhookRequest {$wr->id}");
        }

        $targetWorkspaceId = $workspace->imanage_workspace_id;
        $imanage           = new ImanageApiService($tenant);

        try {
            // Resolve Clio group from stored payload, fall back to live API if absent
            $payload       = $wr->body;
            $clioGroupData = data_get($payload, 'data.group');

            if (empty($clioGroupData['id'])) {
                $clioMatterId = data_get($payload, 'data.id');
                if ($clioMatterId) {
                    $clio           = new ClioApiService($tenant);
                    $matterResponse = $clio->getMatter((int) $clioMatterId);
                    $clioGroupData  = data_get($matterResponse, 'data.group');
                }
            }

            $mappedEntries   = [];
            $unmappedEntries = [];
            $sentPayload     = [];
            $securityApplied = false;

            if (! empty($clioGroupData['id'])) {
                $include = $this->resolveSecurityEntries(
                    $tenant,
                    $clioGroupData,
                    $mappedEntries,
                    $unmappedEntries,
                );

                if (! empty($include)) {
                    $imanage->applyWorkspaceSecurity($customerId, $libraryId, $targetWorkspaceId, [
                        'default_security' => 'private',
                        'members'          => ['include' => $include],
                    ]);
                    $securityApplied = true;
                    $sentPayload     = $include;
                }
            } else {
                Log::warning("ApplyGroupSecurityMapping: No Clio group on matter for WebhookRequest [{$wr->id}] — skipping group security.");
            }

            $wr->security_activity_complete = $securityApplied;
            $wr->save();

            Log::info("ApplyGroupSecurityMapping: WebhookRequest [{$wr->id}] — applied=" . ($securityApplied ? 'yes' : 'no') . ', mapped=' . count($mappedEntries) . ', unmapped=' . count($unmappedEntries));

            AuditWorkspaceSecurity::dispatch($wr->id, $tenant->id);

        } catch (Throwable $e) {
            Log::error("ApplyGroupSecurityMapping: failed for tenant [{$tenant->name}] — {$e->getMessage()}", [
                'webhook_request_id' => $wr->id,
                'exception'          => $e,
            ]);
            $wr->markFailed($e->getMessage());
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Resolve the iManage security entries to include for the given Clio group.
     *
     * Strategy:
     *   1. Direct ClioGroup → GroupMapping → ImanageGroup match.
     *   2. Comma-split name-part fallback: match each part against ClioGroup names
     *      and ClioUser names/emails via their mappings, then fall back to a
     *      direct ImanageUser name/email search.
     *
     * @param  array<string,mixed>  $clioGroupData
     * @param  array<int,mixed>  $mappedEntries   (mutated by reference)
     * @param  array<int,mixed>  $unmappedEntries (mutated by reference)
     * @return array<int, array<string,mixed>>
     */
    private function resolveSecurityEntries(
        Tenant $tenant,
        array $clioGroupData,
        array &$mappedEntries,
        array &$unmappedEntries,
    ): array {
        $clioGroupId = $clioGroupData['id'];
        $groupName   = $clioGroupData['name'] ?? '';

        // 1. Direct mapping
        $clioGroup    = ClioGroup::with('groupMapping.imanageGroup')
            ->where('tenant_id', $tenant->id)
            ->where('clio_id', $clioGroupId)
            ->first();

        $directMapping = $clioGroup?->groupMapping?->imanageGroup;

        if ($directMapping) {
            $mappedEntries[] = [
                'type'         => 'group',
                'match_method' => 'direct_group_mapping',
                'clio_name'    => $clioGroup->name,
                'imanage_id'   => $directMapping->imanage_group_id,
            ];

            return [['id' => $directMapping->imanage_group_id, 'access_level' => 'full_access', 'type' => 'group']];
        }

        // 2. Name-part fallback
        return $this->resolveByNameParts($tenant, $groupName, $mappedEntries, $unmappedEntries);
    }

    /**
     * Split the group name by comma and try to map each part to iManage groups/users.
     *
     * @param  array<int,mixed>  $mappedEntries   (mutated by reference)
     * @param  array<int,mixed>  $unmappedEntries (mutated by reference)
     * @return array<int, array<string,mixed>>
     */
    private function resolveByNameParts(
        Tenant $tenant,
        string $groupName,
        array &$mappedEntries,
        array &$unmappedEntries,
    ): array {
        $parts   = array_values(array_filter(array_map('trim', explode(',', $groupName))));
        $include = [];

        foreach ($parts as $part) {
            // 2a. ClioGroup name-part → GroupMapping → ImanageGroup
            $matchingGroups = ClioGroup::with('groupMapping.imanageGroup')
                ->whereHas('groupMapping')
                ->where('tenant_id', $tenant->id)
                ->where('name', 'like', "%{$part}%")
                ->get();

            foreach ($matchingGroups as $match) {
                $imanageGroup = $match->groupMapping?->imanageGroup;
                if ($imanageGroup) {
                    $include[]       = ['id' => $imanageGroup->imanage_group_id, 'access_level' => 'full_access', 'type' => 'group'];
                    $mappedEntries[] = ['type' => 'group', 'match_method' => 'name_part_match', 'clio_name' => $part, 'imanage_id' => $imanageGroup->imanage_group_id];
                }
            }

            // 2b. ClioUser name/email → UserMapping → ImanageUser
            $clioUsers = ClioUser::with('userMapping.imanageUser')
                ->whereHas('userMapping')
                ->where('tenant_id', $tenant->id)
                ->where(fn ($q) => $q->where('name', 'like', "%{$part}%")->orWhere('email', 'like', "%{$part}%"))
                ->get();

            $matchedViaMapping = false;
            foreach ($clioUsers as $clioUser) {
                $imanageUser = $clioUser->userMapping?->imanageUser;
                if ($imanageUser) {
                    $include[]         = ['id' => $imanageUser->imanage_user_id, 'access_level' => 'full_access', 'type' => 'user'];
                    $mappedEntries[]   = ['type' => 'user', 'match_method' => 'clio_user_mapping', 'clio_name' => $clioUser->name, 'imanage_id' => $imanageUser->imanage_user_id];
                    $matchedViaMapping = true;
                }
            }

            // 2c. Direct ImanageUser name/email match (no mapping needed)
            if (! $matchedViaMapping) {
                $imanageUsers = ImanageUser::where('tenant_id', $tenant->id)
                    ->where(fn ($q) => $q->where('full_name', 'like', "%{$part}%")->orWhere('email', 'like', "%{$part}%"))
                    ->get();

                foreach ($imanageUsers as $user) {
                    $include[]       = ['id' => $user->imanage_user_id, 'access_level' => 'full_access', 'type' => 'user'];
                    $mappedEntries[] = ['type' => 'user', 'match_method' => 'direct_name_match', 'clio_name' => $part, 'imanage_id' => $user->imanage_user_id];
                }
            }
        }

        // Deduplicate by type:id
        $seen   = [];
        $unique = [];
        foreach ($include as $entry) {
            $key = $entry['type'] . ':' . $entry['id'];
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $entry;
            }
        }

        if (empty($unique)) {
            $unmappedEntries[] = [
                'clio_group_name' => $groupName,
                'reason'          => 'No iManage group or user mapping found via direct mapping or name-part matching. Configure group mappings in the admin panel.',
            ];
        }

        return $unique;
    }
}
