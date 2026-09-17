<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProcessingStage;
use App\Jobs\ProcessWebhook;
use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookRequest;
use App\Services\WebhookVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function __construct(private readonly WebhookVerificationService $verification) {}

    /**
     * Receive a Clio webhook payload for a given tenant.
     *
     * Route: POST /webhook/{reference}
     *
     * Clio first sends a handshake with X-Hook-Secret header.
     * Subsequent payloads include X-Hook-Signature for HMAC verification.
     */
    public function receive(Request $request, string $reference): Response
    {
        // Handshake: echo back X-Hook-Secret immediately
        if ($secret = $this->verification->verifyHandshake($request)) {
            return response('', 200, ['X-Hook-Secret' => $secret]);
        }

        // Lookup tenant by UUID reference
        $tenant = Tenant::where('reference', $reference)->first();

        if (! $tenant) {
            return response('', 200);
        }

        $correlationId = $request->header('X-Correlation-Id') ?? (string) Str::uuid();
        $rawPayload    = $request->getContent();
        $payload       = json_decode($rawPayload, true);
        $payloadHash   = hash('sha256', $rawPayload);
        $clioWebhookId = data_get($payload, 'meta.webhook_id');
        $clioMatterId  = data_get($payload, 'data.id');

        $webhook = Webhook::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->when($clioWebhookId, fn ($q) => $q->where('clio_id', $clioWebhookId))
            ->first();

        // Deduplication: skip if we already received a webhook for the same matter within 30 seconds
        // Clio fires multiple events (with different correlation IDs) for a single matter change
        $duplicate = $clioMatterId && WebhookRequest::where('tenant_id', $tenant->id)
            ->where('clio_matter_id', $clioMatterId)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->whereNotIn('processing_stage', [ProcessingStage::Skipped->value, ProcessingStage::Failed->value])
            ->exists();

        if ($duplicate) {
            WebhookRequest::create([
                'tenant_id'        => $tenant->id,
                'webhook_id'       => $webhook?->id,
                'url'              => $request->fullUrl(),
                'headers'          => $request->headers->all(),
                'body'             => $payload,
                'payload_hash'     => $payloadHash,
                'correlation_id'   => $correlationId,
                'clio_matter_id'   => $clioMatterId,
                'processing_stage' => ProcessingStage::Skipped,
                'skip_reason'      => "Duplicate webhook for matter {$clioMatterId} received within 30 seconds.",
                'completed_at'     => now(),
            ]);

            return response('', 200);
        }

        // Record every incoming request immediately — before any further checks
        $webhookRequest = WebhookRequest::create([
            'tenant_id'        => $tenant->id,
            'webhook_id'       => $webhook?->id,
            'url'              => $request->fullUrl(),
            'headers'          => $request->headers->all(),
            'body'             => $payload,
            'payload_hash'     => $payloadHash,
            'correlation_id'   => $correlationId,
            'clio_matter_id'   => $clioMatterId,
            'processing_stage' => ProcessingStage::Received,
            'started_at'       => now(),
        ]);

        // Enforce active subscription gate
        if (! $tenant->tenantSubscriptions()->where('status', 'active')->exists()) {
            $webhookRequest->markFailed('Tenant does not have an active subscription.');
            return response('', 200);
        }

        // Verify HMAC signature against the matched webhook's shared secret
        if ($webhook && $webhook->shared_secret) {
            if (! $this->verification->verifyRequest($request, $webhook->shared_secret)) {
                $webhookRequest->markFailed('HMAC signature verification failed.');
                return response('', 200);
            }
        }

        // Dispatch asynchronously — pass the already-created record ID
        ProcessWebhook::dispatch(
            tenantId:         $tenant->id,
            webhookId:        (string) ($webhook?->clio_id ?? ''),
            rawPayload:       $rawPayload,
            headers:          $request->headers->all(),
            correlationId:    $correlationId,
            webhookRequestId: $webhookRequest->id,
        )->onQueue('webhooks');

        return response('', 200);
    }
}
