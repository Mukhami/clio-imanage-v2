<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStage;
use App\Models\Tenant;
use App\Models\WebhookRequest;
use App\Models\WorkspaceSecurityAudit;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AuditWorkspaceSecurity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly int $webhookRequestId,
        public readonly int $tenantId,
    ) {
        $this->onQueue('long_term');
    }

    public function backoff(): array
    {
        return [60];
    }

    public function handle(ImanageApiService $imanage): void
    {
        // 1. Load required records
        $wr = WebhookRequest::findOrFail($this->webhookRequestId);
        $tenant = Tenant::findOrFail($this->tenantId);

        // 2. TODO: Compare expected vs actual security policy and create an audit record.
        //
        //    Suggested approach:
        //    a. Fetch the template security policy (expected):
        //       $expectedPolicy = $imanage->getWorkspaceSecurityPolicy(
        //           $tenant->defaultLibraryId,
        //           $templateWorkspaceId,
        //       );
        //    b. Fetch the target workspace's current security policy (actual):
        //       $actualPolicy = $imanage->getWorkspaceSecurityPolicy(
        //           $tenant->defaultLibraryId,
        //           $targetWorkspaceId,
        //       );
        //    c. Diff the two and record the result:
        //       WorkspaceSecurityAudit::create([
        //           'tenant_id'          => $tenant->id,
        //           'webhook_request_id' => $wr->id,
        //           'workspace_id'       => $targetWorkspaceId,
        //           'expected_policy'    => $expectedPolicy,
        //           'actual_policy'      => $actualPolicy,
        //           'passed'             => $expectedPolicy === $actualPolicy,
        //           'audited_at'         => now(),
        //       ]);

        // 3. Advance to Completed
        $wr->processing_stage = ProcessingStage::Completed;

        // 4. Record completion timestamp
        $wr->completed_at = now();
        $wr->save();
    }
}
