<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStage;
use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookRequest;
use App\Services\ClioApiService;
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
        public readonly ?int $webhookRequestId = null,
    ) {
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(): void
    {
        // 1. Load tenant — silently discard if not found
        $tenant = Tenant::find($this->tenantId);
        if ($tenant === null) {
            return;
        }

        $config = new TenantConfigurationService($tenant);

        // 2. Load the Webhook model matching tenant + Clio webhook ID
        $webhook = Webhook::where('tenant_id', $this->tenantId)
            ->when($this->webhookId !== '', fn ($q) => $q->where('clio_id', $this->webhookId))
            ->first();

        // Initialise $webhookRequest outside try so the catch block can reference it
        $webhookRequest = null;

        try {
            // 3. Use the pre-created WebhookRequest record from the controller, or create one as fallback
            $webhookRequest = $this->webhookRequestId
                ? WebhookRequest::find($this->webhookRequestId)
                : null;

            if (! $webhookRequest) {
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
            }

            // 4. Advance to Validated
            $webhookRequest->processing_stage = ProcessingStage::Validated;
            $webhookRequest->save();

            // 5. Decode payload
            $data     = json_decode($this->rawPayload, true);
            $matterId = data_get($data, 'data.id');

            // 6. If the webhook payload is thin (missing client or practice_area),
            //    fetch the full matter from Clio. This covers old webhooks registered
            //    without fields config and any edge cases where Clio omits fields.
            $hasFullData = data_get($data, 'data.client') && data_get($data, 'data.practice_area');

            if ($matterId && ! $hasFullData) {
                $clio         = new ClioApiService($tenant);
                $fullMatter   = $clio->getMatter((int) $matterId);
                $data['data'] = array_merge($data['data'], $fullMatter['data'] ?? []);

                $webhookRequest->body = $data;
                $webhookRequest->save();
            }

            // 7. Advance to Parsed
            $webhookRequest->processing_stage = ProcessingStage::Parsed;
            $webhookRequest->save();

            // 7. Evaluate processing filters via TenantConfigurationService
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
                ;

        } catch (Throwable $e) {
            if ($webhookRequest !== null) {
                $webhookRequest->markFailed($e->getMessage());
            }

            throw $e;
        }
    }
}
