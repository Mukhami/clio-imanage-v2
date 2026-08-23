<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_csp_header_is_present_on_web_responses(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('Content-Security-Policy');
    }

    public function test_x_content_type_options_is_nosniff(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_x_frame_options_is_deny(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_referrer_policy_is_set(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_permissions_policy_is_set(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_hsts_header_is_absent_outside_production(): void
    {
        // In testing (APP_ENV=testing), HSTS must NOT be sent
        $this->assertFalse(app()->isProduction());

        $response = $this->get(route('home'));

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_webhook_endpoint_is_not_rate_limited_under_threshold(): void
    {
        // The webhook endpoint has no per-IP rate limit (only OAuth callbacks do)
        // Hitting it once should not trigger a 429
        $response = $this->post(route('webhook.receive', 'nonexistent-ref'));

        $this->assertNotEquals(429, $response->status());
    }
}
