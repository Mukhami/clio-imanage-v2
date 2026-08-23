<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhook;
use App\Models\Tenant;
use App\Models\Webhook;
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
            // Return 200 to prevent Clio from retrying; tenant simply not found
            return response('', 200);
        }

        // Enforce active subscription gate
        $activeSubscription = $tenant->tenantSubscriptions()
            ->where('status', 'active')
            ->exists();

        if (! $activeSubscription) {
            return response('', 200);
        }

        // Find the webhook registration to get shared_secret for verification
        $webhook = Webhook::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        // Verify HMAC signature if a webhook with a shared secret exists
        if ($webhook && $webhook->shared_secret) {
            if (! $this->verification->verifyRequest($request, $webhook->shared_secret)) {
                return response('', 200);
            }
        }

        // Dispatch asynchronously — return 200 immediately
        ProcessWebhook::dispatch(
            tenantId: $tenant->id,
            webhookId: (string) ($webhook?->clio_id ?? ''),
            rawPayload: $request->getContent(),
            headers: $request->headers->all(),
            correlationId: $request->header('X-Correlation-Id') ?? (string) Str::uuid(),
        )->onQueue('webhooks');

        return response('', 200);
    }
}
