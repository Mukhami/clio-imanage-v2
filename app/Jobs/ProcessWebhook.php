<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStage;
use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookRequest;
use App\Services\TenantConfigurationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  int     $tenantId       Internal tenant ID
     * @param  string  $webhookId      Clio's webhook ID from the URL segment
     * @param  string  $rawPayload     Raw JSON string of the request body
     * @param  array   $headers        Request headers
     * @param  string  $correlationId  UUID for end-to-end tracing
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly string $webhookId,
        public readonly string $rawPayload,
        public readonly array $headers,
        public readonly string $correlationId,
    ) {
        $this->onQueue('webhooks');
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(TenantConfigurationService $config): void
    {
        // 1. Load tenant — silently discard if not found
        $tenant = Tenant::find($this->tenantId);
        if ($tenant === null) {
            return;
        }

        // 2. Load the Webhook model matching tenant + Clio webhook ID
        $webhook = Webhook::where('tenant_id', $this->tenantId)
            ->where('clio_id', $this->webhookId)
            ->first();

        if ($webhook === null) {
            return;
        }

        // Initialise $webhookRequest outside try so the catch block can reference it
        $webhookRequest = null;

        try {
            // 3. Create the WebhookRequest record at stage Received
            $webhookRequest = WebhookRequest::create([
                'tenant_id'        => $this->tenantId,
                'webhook_id'       => $webhook->id,
                'url'              => '',
                'headers'          => $this->headers,
                'body'             => json_decode($this->rawPayload, true),
                'correlation_id'   => $this->correlationId,
                'processing_stage' => ProcessingStage::Received,
                'started_at'       => now(),
            ]);

            // 4. Advance to Validated
            $webhookRequest->processing_stage = ProcessingStage::Validated;
            $webhookRequest->save();

            // 5. Decode payload
            $data = json_decode($this->rawPayload, true);

            // 6. Advance to Parsed
            $webhookRequest->processing_stage = ProcessingStage::Parsed;
            $webhookRequest->save();

            // 7. Evaluate processing filters via TenantConfigurationService
            //    shouldProcess() receives the decoded payload and checks enabled filters
            $filterResult = $config->shouldProcess($data);

            if ($filterResult->shouldSkip()) {
                $webhookRequest->processing_stage = ProcessingStage::Skipped;
                $webhookRequest->save();

                return;
            }

            // 8. Advance to Filtered
            $webhookRequest->processing_stage = ProcessingStage::Filtered;
            $webhookRequest->save();

            // 9. Parse display number from payload
            $displayNumber = $data['data']['display_number'] ?? $data['data']['number'] ?? null;

            // 10. Resolve client/matter IDs from the display number
            $parsedIds = $config->resolveDisplayNumber((string) ($displayNumber ?? ''), $data);

            // 11. Store retrieved IDs on the WebhookRequest
            $webhookRequest->retrieved_client_id = $parsedIds->clientId;
            $webhookRequest->retrieved_matter_id = $parsedIds->matterId;
            $webhookRequest->save();

            // 12. Advance to Enqueued
            $webhookRequest->processing_stage = ProcessingStage::Enqueued;
            $webhookRequest->save();

            // 13. Hand off to UpdateMatter on the imanage queue
            UpdateMatter::dispatch($webhookRequest->id, $this->tenantId)
                ->onQueue('imanage');

        } catch (Throwable $e) {
            if ($webhookRequest !== null) {
                $webhookRequest->markFailed($e->getMessage());
            }

            throw $e;
        }
    }
}
