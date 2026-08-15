<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\WebhookRequest;
use App\Services\ClioApiService;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PopulateWorkspaceLinkCustomField implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $webhookRequestId,
    ) {
        $this->onQueue('long_term');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(ClioApiService $clio, ImanageApiService $imanage): void
    {
        // 1. Load the WebhookRequest
        $wr = WebhookRequest::findOrFail($this->webhookRequestId);

        // 2. Load the owning tenant
        $tenant = Tenant::findOrFail($wr->tenant_id);

        // 3. TODO: Retrieve the iManage workspace link (IWL) and write it back to the
        //    corresponding Clio matter custom field.
        //
        //    Suggested approach:
        //    a. Resolve the ImanageWorkspace record associated with this WebhookRequest.
        //    b. Construct the iManage Work Link (IWL) URL for the workspace.
        //       The IWL format is typically:
        //       "iwl:dms={customer_id}&&lib={library_id}&&num={workspace_id}&&ver=1"
        //    c. Determine the target Clio custom field ID from tenant settings
        //       (e.g. $tenant->tenantSetting->workspace_link_custom_field_id).
        //    d. Write the IWL back to Clio via ClioApiService:
        //       $clio->updateMatterCustomField(
        //           $wr->retrieved_matter_id,
        //           $customFieldId,
        //           $iwlUrl,
        //       );
        //    Note: ClioApiService will need an updateMatterCustomField() method added.

        // 4. Mark the custom field population as complete
        $wr->workspace_link_custom_field_populated = true;
        $wr->save();
    }
}
