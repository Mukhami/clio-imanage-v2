<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStage;
use App\Models\ClioPracticeArea;
use App\Models\ImanageClient;
use App\Models\ImanageMatter;
use App\Models\ImanageTemplate;
use App\Models\ImanageWorkspace;
use App\Models\Tenant;
use App\Models\TenantJobLock;
use App\Models\User;
use App\Models\WebhookRequest;
use App\Notifications\TenantLockTimedOut;
use Illuminate\Support\Facades\Notification;
use App\Services\ClioApiService;
use App\Services\ImanageApiService;
use App\Services\TenantConfigurationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
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
    }

    public function backoff(): array
    {
        return [15, 60, 300];
    }

    public function handle(): void
    {
        // 1. Load required records
        $wr = WebhookRequest::findOrFail($this->webhookRequestId);

        // 2. Load tenant with eager-loaded relationships
        $tenant = Tenant::with(['tenantSetting.library', 'clioLocation'])->findOrFail($this->tenantId);

        // 3. Acquire per-tenant job lock to serialise iManage operations
        $locked = false;

        DB::transaction(function () use ($tenant, &$locked) {
            $lock = TenantJobLock::where('tenant_id', $tenant->id)->lockForUpdate()->first();

            if ($lock) {
                if ($lock->created_at->lt(now()->subHour())) {
                    // Stale lock (>1 hour old) — clear it and notify admins
                    $lock->delete();

                    $admins = User::role(['Super Admin', 'Admin'])->get();
                    Notification::send($admins, new TenantLockTimedOut($tenant, $lock->created_at->toDateTimeString()));
                } else {
                    $locked = true;

                    return;
                }
            }

            TenantJobLock::firstOrCreate(['tenant_id' => $tenant->id]);
        });

        if ($locked) {
            $this->release(15);

            return;
        }

        // 4. Mark as processing
        $wr->processing_stage = ProcessingStage::Processing;
        $wr->started_at       = now();
        $wr->save();

        try {
            // 5. Extract payload fields
            $payload                = $wr->body;
            $clioMatterId           = data_get($payload, 'data.id');
            $clioClientId           = data_get($payload, 'data.client.id');
            $rawClientName          = data_get($payload, 'data.client.name');
            $rawMatterDescription   = data_get($payload, 'data.description', '');
            $displayNumber          = data_get($payload, 'data.display_number', '');
            $contactType            = data_get($payload, 'data.client.type', 'Company');
            $clioMatterStatus       = data_get($payload, 'data.status', 'Open');
            $clioPracticeAreaId     = data_get($payload, 'data.practice_area.id');
            $clioCustomFields       = collect(data_get($payload, 'data.custom_field_values', []))
                ->keyBy('field_name')
                ->map(fn ($f) => $f['value'])
                ->toArray();

            // 6 & 7. Validate tenant settings and library
            $setting = $tenant->tenantSetting;
            if (! $setting) {
                throw new RuntimeException("No TenantSetting found for tenant ID: {$tenant->id}");
            }

            $library = $setting->library;
            if (! $library) {
                throw new RuntimeException("No Library linked to TenantSetting for tenant ID: {$tenant->id}");
            }

            $libraryId  = $library->imanage_library_id;
            $customerId = (string) $tenant->imanage_customer_id;

            // 8. Instantiate services
            $imanage = new ImanageApiService($tenant);
            $config  = new TenantConfigurationService($tenant);

            // 9. Resolve practice area mapping
            $imanagePracticeArea    = null;
            $imanageSubPracticeArea = null;

            if ($clioPracticeAreaId) {
                $clioPracticeArea = ClioPracticeArea::where('tenant_id', $tenant->id)
                    ->where('clio_id', $clioPracticeAreaId)
                    ->with(['practiceAreaMapping.imanagePracticeArea', 'practiceAreaMapping.imanageSubPracticeArea'])
                    ->first();

                if ($clioPracticeArea && $clioPracticeArea->practiceAreaMapping) {
                    $mapping                = $clioPracticeArea->practiceAreaMapping;
                    $imanagePracticeArea    = $mapping->imanagePracticeArea;
                    $imanageSubPracticeArea = $mapping->imanageSubPracticeArea;
                }
            }

            // 10. Resolve template
            $template = $setting->imanage_template_id
                ? ImanageTemplate::find($setting->imanage_template_id)
                : null;

            if ($clioPracticeAreaId) {
                $clioPracticeAreaForTemplate = $clioPracticeAreaForTemplate ?? ClioPracticeArea::where('tenant_id', $tenant->id)
                    ->where('clio_id', $clioPracticeAreaId)
                    ->with(['templateMapping.imanageTemplate'])
                    ->first();

                if (isset($clioPracticeAreaForTemplate) && $clioPracticeAreaForTemplate->templateMapping) {
                    $mappedTemplate = $clioPracticeAreaForTemplate->templateMapping->imanageTemplate;
                    if ($mappedTemplate) {
                        $template = $mappedTemplate;
                    }
                }
            }

            // 11. Resolve client name
            if (empty($rawClientName) && $clioClientId) {
                $clioService   = new ClioApiService($tenant);
                $clientResp    = $clioService->getClient((int) $clioClientId);
                $rawClientName = data_get($clientResp, 'data.name', '');
            }

            $clientDescription = $config->resolveClientName((string) $rawClientName, $contactType);

            // 12. Resolve matter description
            $matterDescription = $config->resolveMatterDescription((string) $rawMatterDescription, [
                'display_number'     => $displayNumber,
                'client_description' => $clientDescription,
                'matter_id'          => $wr->retrieved_matter_id,
                'client_id'          => $wr->retrieved_client_id,
            ]);

            // 13. Find/create client in iManage
            $clientData = $imanage->findOrUpsertClient(
                $customerId,
                $libraryId,
                $wr->retrieved_client_id,
                $clientDescription,
                $setting->default_enabled ?? true,
                $setting->default_hipaa ?? false,
            );

            $imanageClient = ImanageClient::updateOrCreate(
                [
                    'tenant_id'  => $tenant->id,
                    'key'        => $wr->retrieved_client_id,
                    'library_id' => $library->id,
                ],
                [
                    'ssid'               => $clientData['ssid'] ?? null,
                    'description'        => $clientData['description'] ?? $clientDescription,
                    'enabled'            => $clientData['enabled'] ?? ($setting->default_enabled ?? true),
                    'hipaa'              => $clientData['hipaa'] ?? ($setting->default_hipaa ?? false),
                    'wstype'             => $clientData['wstype'] ?? null,
                    'clio_client_id'     => $clioClientId,
                    'webhook_request_id' => $wr->id,
                ],
            );

            $wr->client_activity_complete = true;
            $wr->save();

            // 14. Find/create matter in iManage (only if matter ID is present)
            $imanageMatter = null;

            if (! empty($wr->retrieved_matter_id)) {
                $matterData = $imanage->findOrUpsertMatter(
                    $customerId,
                    $libraryId,
                    $wr->retrieved_matter_id,
                    $wr->retrieved_client_id,
                    $matterDescription,
                    $setting->default_enabled ?? true,
                    $setting->default_hipaa ?? false,
                );

                $imanageMatter = ImanageMatter::updateOrCreate(
                    [
                        'tenant_id'        => $tenant->id,
                        'key'              => $wr->retrieved_matter_id,
                        'imanage_client_id' => $imanageClient->id,
                        'library_id'       => $library->id,
                    ],
                    [
                        'ssid'                    => $matterData['ssid'] ?? null,
                        'description'             => $matterData['description'] ?? $matterDescription,
                        'enabled'                 => $matterData['enabled'] ?? ($setting->default_enabled ?? true),
                        'hipaa'                   => $matterData['hipaa'] ?? ($setting->default_hipaa ?? false),
                        'wstype'                  => $matterData['wstype'] ?? null,
                        'closed'                  => $clioMatterStatus === 'Closed',
                        'clio_client_id'          => $clioClientId,
                        'clio_matter_id'          => $clioMatterId,
                        'clio_practice_area_id'   => $clioPracticeAreaId,
                        'iman_practice_area_id'   => $imanagePracticeArea?->id,
                        'iman_sub_practice_area_id' => $imanageSubPracticeArea?->id,
                        'parent_id'               => $matterData['parent']['id'] ?? null,
                        'parent_ssid'             => $matterData['parent']['ssid'] ?? null,
                        'webhook_request_id'      => $wr->id,
                    ],
                );

                $wr->matter_activity_complete = true;
                $wr->save();
            }

            // 15. Resolve workspace name
            $context = [
                'client_id'              => $wr->retrieved_client_id,
                'matter_id'              => $wr->retrieved_matter_id ?? '',
                'client_description'     => $clientDescription,
                'matter_description'     => $matterDescription,
                'display_number'         => $displayNumber,
                'practice_area_key'      => $imanagePracticeArea?->key ?? '',
                'sub_practice_area_key'  => $imanageSubPracticeArea?->key ?? '',
            ];

            $workspaceName = $config->resolveWorkspaceName($context);

            // 16. Resolve custom field mappings
            $additionalCustomFields = $config->resolveCustomFieldMappings($payload, $clioCustomFields);

            // 17. Build workspace payload
            $workspacePayload = [
                'name'             => $workspaceName,
                'description'      => $workspaceName,
                'default_security' => 'public',
                'custom1'          => $wr->retrieved_client_id,
            ];

            if (! empty($wr->retrieved_matter_id)) {
                $workspacePayload['custom2'] = $wr->retrieved_matter_id;
            }

            if ($imanagePracticeArea?->key) {
                $workspacePayload['custom29'] = $imanagePracticeArea->key;
            }

            if ($imanageSubPracticeArea?->key) {
                $workspacePayload['custom30'] = $imanageSubPracticeArea->key;
            }

            if (! empty($additionalCustomFields)) {
                $workspacePayload = array_merge($workspacePayload, $additionalCustomFields);
            }

            // 18. Find existing workspace in DB
            $workspaceQuery = ImanageWorkspace::where('tenant_id', $tenant->id)
                ->where('database', $libraryId)
                ->where('custom1', $wr->retrieved_client_id)
                ->where('replica', false);

            if (! empty($wr->retrieved_matter_id)) {
                $workspaceQuery->where('custom2', $wr->retrieved_matter_id);
            }

            $existingWorkspace = $workspaceQuery->first();

            // 19. Update or create workspace
            $wasCreated     = false;
            $updatedWorkspace = null;

            if ($existingWorkspace) {
                // Update existing workspace via iManage API
                $wsResponse = $imanage->updateWorkspace(
                    $customerId,
                    $libraryId,
                    $existingWorkspace->imanage_workspace_id,
                    $workspacePayload,
                );

                $wsData = data_get($wsResponse, 'data', $wsResponse);

                $existingWorkspace->fill(array_filter([
                    'name'             => $wsData['name'] ?? $workspaceName,
                    'description'      => $wsData['description'] ?? $workspaceName,
                    'default_security' => $wsData['default_security'] ?? 'public',
                    'imanage_template_id'      => $template?->id,
                    'imanage_matter_id'        => $imanageMatter?->id,
                    'imanage_client_id'        => $imanageClient->id,
                    'iman_practice_area_id'    => $imanagePracticeArea?->id,
                    'iman_sub_practice_area_id' => $imanageSubPracticeArea?->id,
                    'webhook_request_id'       => $wr->id,
                ], fn ($v) => $v !== null));

                // Merge any custom fields from response
                foreach ($wsData as $field => $value) {
                    if (preg_match('/^custom\d+$/', $field)) {
                        $existingWorkspace->$field = $value;
                    }
                }

                $existingWorkspace->save();
                $updatedWorkspace = $existingWorkspace;
            } else {
                // Create workspace via iManage API
                if ($template?->imanage_template_id) {
                    $workspacePayload['template'] = $template->imanage_template_id;
                }

                $wsResponse = $imanage->createWorkspace($customerId, $libraryId, $workspacePayload);
                $wsData     = data_get($wsResponse, 'data', $wsResponse);

                $workspaceAttributes = [
                    'tenant_id'                => $tenant->id,
                    'library_id'               => $library->id,
                    'imanage_workspace_id'     => $wsData['id'] ?? $wsData['wsid'] ?? null,
                    'name'                     => $wsData['name'] ?? $workspaceName,
                    'description'              => $wsData['description'] ?? $workspaceName,
                    'database'                 => $wsData['database'] ?? $libraryId,
                    'default_security'         => $wsData['default_security'] ?? 'public',
                    'has_subfolders'           => $wsData['has_subfolders'] ?? false,
                    'owner'                    => $wsData['owner'] ?? null,
                    'document_number'          => $wsData['document_number'] ?? null,
                    'is_declared'              => $wsData['is_declared'] ?? false,
                    'is_hipaa'                 => $wsData['is_hipaa'] ?? ($setting->default_hipaa ?? false),
                    'iwl'                      => $wsData['iwl'] ?? null,
                    'custom1'                  => $wr->retrieved_client_id,
                    'custom2'                  => $wr->retrieved_matter_id ?? null,
                    'replica'                  => false,
                    'imanage_template_id'      => $template?->id,
                    'imanage_matter_id'        => $imanageMatter?->id,
                    'imanage_client_id'        => $imanageClient->id,
                    'iman_practice_area_id'    => $imanagePracticeArea?->id,
                    'iman_sub_practice_area_id' => $imanageSubPracticeArea?->id,
                    'webhook_request_id'       => $wr->id,
                ];

                // Merge additional custom field values from API response
                foreach ($wsData as $field => $value) {
                    if (preg_match('/^custom\d+$/', $field)) {
                        $workspaceAttributes[$field] = $value;
                    }
                }

                $createdWorkspace = ImanageWorkspace::create($workspaceAttributes);
                $updatedWorkspace = $createdWorkspace;
                $wasCreated       = true;

                // Dispatch folder creation job for newly created workspaces
                CreateWorkspaceFolders::dispatch($wr->id, $tenant->id)
                    ;
            }

            $wr->workspace_activity_complete = true;
            $wr->save();

            // 20. Handle replica workspaces (only on creation)
            if ($wasCreated && $setting->has_replica_workspaces && $setting->replica_template_id) {
                $replicaTemplate = ImanageTemplate::find($setting->replica_template_id);

                $replicaPayload                = $workspacePayload;
                $replicaPayload['description'] = $workspaceName;
                $replicaPayload['name']        = $workspaceName;

                if ($replicaTemplate?->imanage_template_id) {
                    $replicaPayload['template'] = $replicaTemplate->imanage_template_id;
                }

                $replicaResponse = $imanage->createWorkspace($customerId, $libraryId, $replicaPayload);
                $replicaData     = data_get($replicaResponse, 'data', $replicaResponse);

                $replicaAttributes = [
                    'tenant_id'                => $tenant->id,
                    'library_id'               => $library->id,
                    'imanage_workspace_id'     => $replicaData['id'] ?? $replicaData['wsid'] ?? null,
                    'name'                     => $replicaData['name'] ?? $workspaceName,
                    'description'              => $replicaData['description'] ?? $workspaceName,
                    'database'                 => $replicaData['database'] ?? $libraryId,
                    'default_security'         => $replicaData['default_security'] ?? 'public',
                    'has_subfolders'           => $replicaData['has_subfolders'] ?? false,
                    'owner'                    => $replicaData['owner'] ?? null,
                    'document_number'          => $replicaData['document_number'] ?? null,
                    'is_declared'              => $replicaData['is_declared'] ?? false,
                    'is_hipaa'                 => $replicaData['is_hipaa'] ?? ($setting->default_hipaa ?? false),
                    'iwl'                      => $replicaData['iwl'] ?? null,
                    'custom1'                  => $wr->retrieved_client_id,
                    'custom2'                  => $wr->retrieved_matter_id ?? null,
                    'replica'                  => true,
                    'imanage_template_id'      => $replicaTemplate?->id,
                    'imanage_matter_id'        => $imanageMatter?->id,
                    'imanage_client_id'        => $imanageClient->id,
                    'iman_practice_area_id'    => $imanagePracticeArea?->id,
                    'iman_sub_practice_area_id' => $imanageSubPracticeArea?->id,
                    'webhook_request_id'       => $wr->id,
                ];

                foreach ($replicaData as $field => $value) {
                    if (preg_match('/^custom\d+$/', $field)) {
                        $replicaAttributes[$field] = $value;
                    }
                }

                ImanageWorkspace::create($replicaAttributes);
            }

            // 21. Dispatch downstream jobs
            if ($tenant->has_group_security_mapping) {
                ApplyGroupSecurityMapping::dispatch($wr->id, $tenant->id);
            } else {
                PostWorkspaceSecurity::dispatch($wr->id, $tenant->id);
            }

            if ($tenant->enable_workspace_link_custom_field) {
                PopulateWorkspaceLinkCustomField::dispatch($wr->id)
                    ;
            }

            $wr->processing_stage = ProcessingStage::PostProcessing;
            $wr->save();

        } catch (Throwable $e) {
            // 22. On any exception, mark the request failed and re-throw
            $wr->markFailed($e->getMessage());
            throw $e;
        } finally {
            // 23. Always release the per-tenant lock
            TenantJobLock::where('tenant_id', $tenant->id)->delete();
        }
    }
}
