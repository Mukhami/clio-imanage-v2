<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\WebhookRequest;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

    public function handle(ImanageApiService $imanage): void
    {
        // 1. Load required records
        $wr = WebhookRequest::findOrFail($this->webhookRequestId);
        $tenant = Tenant::findOrFail($this->tenantId);

        // 2. TODO: Fetch security policy from template workspace and apply to target workspace.
        //
        //    Suggested approach:
        //    a. Resolve template workspace ID from tenant configuration.
        //    b. Fetch the template's security policy:
        //       $templatePolicy = $imanage->getWorkspaceSecurityPolicy(
        //           $tenant->defaultLibraryId,
        //           $templateWorkspaceId,
        //       );
        //    c. Apply (POST/PUT) that policy to the target workspace:
        //       $imanage->applyWorkspaceSecurityPolicy(
        //           $tenant->defaultLibraryId,
        //           $targetWorkspaceId,
        //           $templatePolicy,
        //       );
        //    Note: ImanageApiService will need an applyWorkspaceSecurityPolicy() method added.

        // 3. Mark security activity complete
        $wr->security_activity_complete = true;
        $wr->save();

        // 4. Chain the security audit
        AuditWorkspaceSecurity::dispatch($wr->id, $tenant->id)
            ->onQueue('long_term');
    }
}
