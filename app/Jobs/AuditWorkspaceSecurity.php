<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStage;
use App\Models\ImanageTemplate;
use App\Models\ImanageWorkspace;
use App\Models\Tenant;
use App\Models\WebhookRequest;
use App\Models\WorkspaceSecurityAudit;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class AuditWorkspaceSecurity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly int $webhookRequestId,
        public readonly int $tenantId,
    ) {
    }

    public function backoff(): array
    {
        return [60];
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
            $templateSecurity = null;
            $templateWsId     = null;

            $template = $workspace->imanage_template_id
                ? ImanageTemplate::find($workspace->imanage_template_id)
                : null;

            if ($template && $template->imanage_template_id) {
                $templateWsId = $template->imanage_template_id;

                $templateSecResponse = $imanage->getWorkspaceSecurityPolicy($customerId, $libraryId, $templateWsId);
                $templateSecurity    = data_get($templateSecResponse, 'data', []);
            }

            // Fetch the current (post-apply) target security
            $targetSecResponse = $imanage->getWorkspaceSecurityPolicy($customerId, $libraryId, $targetWorkspaceId);
            $targetSecurity    = data_get($targetSecResponse, 'data', []);

            // Compute diff: members expected from template but missing or with different access in target
            $diff = $this->computeDiff($templateSecurity ?? [], $targetSecurity);

            $auditPassed = empty($diff['missing']) && empty($diff['access_mismatch']);

            WorkspaceSecurityAudit::updateOrCreate(
                [
                    'tenant_id'          => $tenant->id,
                    'webhook_request_id' => $wr->id,
                    'imanage_workspace_id' => $workspace->id,
                ],
                [
                    'template_workspace_id' => $templateWsId,
                    'target_workspace_id'   => $targetWorkspaceId,
                    'template_security'     => $templateSecurity,
                    'target_security'       => $targetSecurity,
                    'diff'                  => $diff,
                    'status'                => $auditPassed ? 'passed' : 'failed',
                    'resolved_at'           => $auditPassed ? now() : null,
                ],
            );

            $wr->processing_stage = ProcessingStage::Completed;
            $wr->completed_at     = now();
            $wr->save();

        } catch (Throwable $e) {
            $wr->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Compute a diff between expected (template) and actual (target) security members.
     *
     * Returns an array with:
     *  - 'missing'          Members in template not present in target
     *  - 'access_mismatch'  Members present in both but with different access level
     *  - 'extra'            Members in target not in template
     *
     * @param  array<string,mixed>  $templateSecurity
     * @param  array<string,mixed>  $targetSecurity
     * @return array<string, array<int, array<string,mixed>>>
     */
    private function computeDiff(array $templateSecurity, array $targetSecurity): array
    {
        $templateMembers = data_get($templateSecurity, 'members', []);
        $targetMembers   = data_get($targetSecurity, 'members', []);

        $templateMap = $this->memberMap($templateMembers);
        $targetMap   = $this->memberMap($targetMembers);

        $missing        = [];
        $accessMismatch = [];
        $extra          = [];

        foreach ($templateMap as $key => $member) {
            if (! isset($targetMap[$key])) {
                $missing[] = $member;
            } elseif (($member['access'] ?? null) !== ($targetMap[$key]['access'] ?? null)) {
                $accessMismatch[] = [
                    'expected' => $member,
                    'actual'   => $targetMap[$key],
                ];
            }
        }

        foreach ($targetMap as $key => $member) {
            if (! isset($templateMap[$key])) {
                $extra[] = $member;
            }
        }

        return [
            'missing'          => $missing,
            'access_mismatch'  => $accessMismatch,
            'extra'            => $extra,
            'default_security' => [
                'expected' => data_get($templateSecurity, 'default_security'),
                'actual'   => data_get($targetSecurity, 'default_security'),
            ],
        ];
    }

    /**
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
