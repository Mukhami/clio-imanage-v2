<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStage;
use App\Models\ImanageClient;
use App\Models\ImanageMatter;
use App\Models\ImanageWorkspace;
use App\Models\Tenant;
use App\Models\TenantJobLock;
use App\Models\WebhookRequest;
use App\Services\ImanageApiService;
use App\Services\SequenceNumberService;
use App\Services\TenantConfigurationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateMatter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $webhookRequestId,
        public readonly int $tenantId,
    ) {
        $this->onQueue('imanage');
    }

    public function backoff(): array
    {
        return [15, 60, 300];
    }

    public function handle(
        ImanageApiService $imanage,
        TenantConfigurationService $config,
        SequenceNumberService $sequence,
    ): void {
        // 1. Load required records
        $wr = WebhookRequest::findOrFail($this->webhookRequestId);
        $tenant = Tenant::findOrFail($this->tenantId);

        // 3. Acquire the per-tenant job lock to serialise iManage operations
        $lock = DB::transaction(
            fn () => TenantJobLock::firstOrCreate(['tenant_id' => $tenant->id])
        );

        // 4 & 5. Mark as processing with timestamp
        $wr->processing_stage = ProcessingStage::Processing;
        $wr->started_at = now();
        $wr->save();

        try {
            // ----------------------------------------------------------------
            // findOrCreateClient
            // ----------------------------------------------------------------
            // TODO: Call $imanage->getClient($tenant, $wr->retrieved_client_id)
            //       and upsert an ImanageClient record.
            //       Example skeleton:
            //
            //       $clientData = $imanage->getWorkspace(
            //           $tenant->defaultLibraryId,
            //           $wr->retrieved_client_id,
            //       );
            //       $imanageClient = ImanageClient::updateOrCreate(
            //           ['tenant_id' => $tenant->id, 'imanage_id' => $clientData['id']],
            //           [...$clientData, 'tenant_id' => $tenant->id],
            //       );
            /** @var ImanageClient|null $imanageClient */
            $imanageClient = null; // TODO: replace with actual upsert result

            $wr->client_activity_complete = true;
            $wr->save();

            // ----------------------------------------------------------------
            // findOrCreateMatter
            // ----------------------------------------------------------------
            // TODO: Call $imanage->getWorkspace($libraryId, $wr->retrieved_matter_id)
            //       and upsert an ImanageMatter record.
            //
            //       $matterData = $imanage->getWorkspace(
            //           $tenant->defaultLibraryId,
            //           $wr->retrieved_matter_id,
            //       );
            //       $imanageMatter = ImanageMatter::updateOrCreate(
            //           ['tenant_id' => $tenant->id, 'imanage_id' => $matterData['id']],
            //           [...$matterData, 'tenant_id' => $tenant->id],
            //       );
            /** @var ImanageMatter|null $imanageMatter */
            $imanageMatter = null; // TODO: replace with actual upsert result

            $wr->matter_activity_complete = true;
            $wr->save();

            // ----------------------------------------------------------------
            // Resolve workspace name
            // ----------------------------------------------------------------
            // TODO: Build $context from client, matter, and webhook payload data.
            //       Example context shape:
            //       $context = [
            //           'client_id'          => $wr->retrieved_client_id,
            //           'matter_id'          => $wr->retrieved_matter_id,
            //           'matter_description' => $wr->body['data']['description'] ?? '',
            //           'display_number'     => $wr->body['data']['display_number'] ?? '',
            //           'client_name'        => $imanageClient->name ?? '',
            //       ];
            $context = []; // TODO: populate from resolved client/matter data

            $workspaceName = $config->resolveWorkspaceName($context);

            // ----------------------------------------------------------------
            // findOrCreateWorkspace / updateWorkspace
            // ----------------------------------------------------------------
            // TODO: Determine whether the workspace already exists for this matter,
            //       then call createWorkspace or updateWorkspace accordingly.
            //       Upsert an ImanageWorkspace record.
            //
            //       $workspaceData = $imanage->createWorkspace(
            //           $tenant->defaultLibraryId,
            //           [
            //               'name'         => $workspaceName,
            //               'client'       => $wr->retrieved_client_id,
            //               'matter'       => $wr->retrieved_matter_id,
            //               'description'  => $wr->body['data']['description'] ?? '',
            //               // ... additional mapped custom fields
            //           ],
            //       );
            //       $imanageWorkspace = ImanageWorkspace::updateOrCreate(
            //           ['tenant_id' => $tenant->id, 'imanage_id' => $workspaceData['id']],
            //           [...$workspaceData, 'tenant_id' => $tenant->id],
            //       );
            /** @var ImanageWorkspace|null $imanageWorkspace */
            $imanageWorkspace = null; // TODO: replace with actual result

            $wr->workspace_activity_complete = true;
            $wr->save();

            // ----------------------------------------------------------------
            // Advance stage and dispatch downstream jobs
            // ----------------------------------------------------------------
            $wr->processing_stage = ProcessingStage::PostProcessing;
            $wr->save();

            CreateWorkspaceFolders::dispatch($wr->id, $tenant->id)
                ->onQueue('long_term');

            PostWorkspaceSecurity::dispatch($wr->id, $tenant->id)
                ->onQueue('long_term');

            if ($tenant->enable_workspace_link_custom_field) {
                PopulateWorkspaceLinkCustomField::dispatch($wr->id)
                    ->onQueue('long_term');
            }

        } catch (Throwable $e) {
            $wr->markFailed($e->getMessage());
            throw $e;
        } finally {
            // Always release the per-tenant lock
            TenantJobLock::where('tenant_id', $tenant->id)->delete();
        }
    }
}
