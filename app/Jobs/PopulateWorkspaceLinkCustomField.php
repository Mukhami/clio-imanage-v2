<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImanageWorkspace;
use App\Models\Tenant;
use App\Models\WebhookRequest;
use App\Services\ClioApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

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

    public function handle(): void
    {
        $wr     = WebhookRequest::findOrFail($this->webhookRequestId);
        $tenant = Tenant::with('tenantSetting')->findOrFail($wr->tenant_id);

        $setting = $tenant->tenantSetting;
        if (! $setting || ! $setting->has_workspace_link_custom_field) {
            // Feature not enabled for this tenant
            $wr->workspace_link_custom_field_populated = true;
            $wr->save();
            return;
        }

        $fieldName = $setting->workspace_link_custom_field_name;
        if (! $fieldName) {
            throw new RuntimeException("workspace_link_custom_field_name not configured for tenant {$tenant->id}");
        }

        // Find the non-replica workspace created/updated for this request
        $workspace = ImanageWorkspace::where('webhook_request_id', $wr->id)
            ->where('replica', false)
            ->first();

        if (! $workspace || ! $workspace->imanage_workspace_id) {
            throw new RuntimeException("No target workspace found for WebhookRequest {$wr->id}");
        }

        // Construct the cloud iManage Work Link (IWL) URL
        $iwl = "https://cloudimanage.com/work/link/w/{$workspace->imanage_workspace_id}/";

        // Locate the custom field value entry in the webhook payload
        $payload           = $wr->body;
        $customFieldValues = data_get($payload, 'data.custom_field_values', []);

        $targetEntry = null;
        foreach ($customFieldValues as $entry) {
            if (($entry['field_name'] ?? null) === $fieldName) {
                $targetEntry = $entry;
                break;
            }
        }

        if (! $targetEntry) {
            // Field not present in the matter payload — nothing to update
            $wr->workspace_link_custom_field_populated = true;
            $wr->save();
            return;
        }

        // Skip if the field is already populated
        if (! empty($targetEntry['value'])) {
            $wr->workspace_link_custom_field_populated = true;
            $wr->save();
            return;
        }

        $customFieldValueId = $targetEntry['id'] ?? null;
        if (! $customFieldValueId) {
            throw new RuntimeException("Custom field value '{$fieldName}' has no id in payload for WebhookRequest {$wr->id}");
        }

        $clioMatterId = data_get($payload, 'data.id');
        if (! $clioMatterId) {
            throw new RuntimeException("Could not determine Clio matter ID from WebhookRequest {$wr->id} payload");
        }

        try {
            $clio = new ClioApiService($tenant);

            $clio->patchMatter((int) $clioMatterId, [
                'data' => [
                    'custom_field_values' => [
                        [
                            'id'    => $customFieldValueId,
                            'value' => $iwl,
                        ],
                    ],
                ],
            ]);

            // Persist the IWL on our workspace record too
            $workspace->iwl = $iwl;
            $workspace->save();

            $wr->workspace_link_custom_field_populated = true;
            $wr->save();

        } catch (Throwable $e) {
            $wr->markFailed($e->getMessage());
            throw $e;
        }
    }
}
