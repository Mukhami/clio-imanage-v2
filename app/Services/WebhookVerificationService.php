<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

class WebhookVerificationService
{
    /**
     * Clio sends X-Hook-Secret header on first handshake request.
     * We must echo it back verbatim in the response header.
     * Returns the secret string if this is a handshake, null otherwise.
     */
    public function verifyHandshake(Request $request): ?string
    {
        $secret = $request->header('X-Hook-Secret');

        return $secret ?: null;
    }

    /**
     * Verify the HMAC-SHA256 signature Clio includes in X-Hook-Signature
     * on every subsequent webhook payload request.
     *
     * Clio computes: HMAC-SHA256(rawBody, sharedSecret) and hex-encodes it.
     */
    public function verifySignature(string $payload, string $signature, string $sharedSecret): bool
    {
        $expected = hash_hmac('sha256', $payload, $sharedSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Convenience method: extract the signature from the request header
     * and verify it against the raw body and shared secret.
     */
    public function verifyRequest(Request $request, string $sharedSecret): bool
    {
        $signature = $request->header('X-Hook-Signature');

        if (! $signature) {
            return false;
        }

        return $this->verifySignature($request->getContent(), $signature, $sharedSecret);
    }
}
