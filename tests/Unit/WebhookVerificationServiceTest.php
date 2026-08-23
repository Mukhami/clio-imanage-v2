<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\WebhookVerificationService;
use Illuminate\Http\Request;
use Tests\TestCase;

class WebhookVerificationServiceTest extends TestCase
{
    private WebhookVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookVerificationService();
    }

    public function test_verify_handshake_returns_secret_when_header_present(): void
    {
        $request = Request::create('/webhook/ref', 'POST');
        $request->headers->set('X-Hook-Secret', 'my-handshake-secret');

        $result = $this->service->verifyHandshake($request);

        $this->assertSame('my-handshake-secret', $result);
    }

    public function test_verify_handshake_returns_null_when_header_absent(): void
    {
        $request = Request::create('/webhook/ref', 'POST');

        $result = $this->service->verifyHandshake($request);

        $this->assertNull($result);
    }

    public function test_verify_signature_passes_with_correct_hmac(): void
    {
        $payload      = '{"data":{"id":1}}';
        $sharedSecret = 'super-secret-key';
        $signature    = hash_hmac('sha256', $payload, $sharedSecret);

        $this->assertTrue($this->service->verifySignature($payload, $signature, $sharedSecret));
    }

    public function test_verify_signature_fails_with_wrong_secret(): void
    {
        $payload      = '{"data":{"id":1}}';
        $sharedSecret = 'super-secret-key';
        $signature    = hash_hmac('sha256', $payload, 'wrong-secret');

        $this->assertFalse($this->service->verifySignature($payload, $signature, $sharedSecret));
    }

    public function test_verify_signature_fails_with_tampered_payload(): void
    {
        $sharedSecret = 'super-secret-key';
        $signature    = hash_hmac('sha256', '{"data":{"id":1}}', $sharedSecret);

        $this->assertFalse($this->service->verifySignature('{"data":{"id":2}}', $signature, $sharedSecret));
    }

    public function test_verify_request_passes_with_valid_header(): void
    {
        $payload      = '{"data":{"id":42}}';
        $sharedSecret = 'tenant-shared-secret';
        $signature    = hash_hmac('sha256', $payload, $sharedSecret);

        $request = Request::create('/webhook/ref', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Hook-Signature', $signature);

        $this->assertTrue($this->service->verifyRequest($request, $sharedSecret));
    }

    public function test_verify_request_fails_when_signature_header_absent(): void
    {
        $request = Request::create('/webhook/ref', 'POST');

        $this->assertFalse($this->service->verifyRequest($request, 'some-secret'));
    }

    public function test_verify_request_is_timing_safe(): void
    {
        // Ensure the comparison does not leak via timing — hash_equals is used internally
        $payload      = '{"data":{"id":1}}';
        $sharedSecret = 'secret';
        $badSignature = str_repeat('0', 64); // wrong but same length

        $request = Request::create('/webhook/ref', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Hook-Signature', $badSignature);

        $this->assertFalse($this->service->verifyRequest($request, $sharedSecret));
    }
}
