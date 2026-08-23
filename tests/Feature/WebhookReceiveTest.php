<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessWebhook;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\Webhook;
use Database\Factories\TenantFactory;
use Database\Factories\TenantSubscriptionFactory;
use Database\Factories\WebhookFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookReceiveTest extends TestCase
{
    use RefreshDatabase;

    private string $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payload = json_encode(['data' => ['id' => 1, 'display_number' => '001/001']]);
    }

    // -------------------------------------------------------------------------
    // Handshake
    // -------------------------------------------------------------------------

    public function test_handshake_echoes_x_hook_secret_header(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->post(
            route('webhook.receive', $tenant->reference),
            [],
            ['X-Hook-Secret' => 'handshake-value'],
        );

        $response->assertOk();
        $response->assertHeader('X-Hook-Secret', 'handshake-value');
    }

    // -------------------------------------------------------------------------
    // Tenant lookup
    // -------------------------------------------------------------------------

    public function test_unknown_reference_returns_200_without_dispatching(): void
    {
        Queue::fake();

        $this->postJson(route('webhook.receive', 'unknown-reference'), ['data' => []])
            ->assertOk();

        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Subscription gate
    // -------------------------------------------------------------------------

    public function test_tenant_without_active_subscription_is_silently_dropped(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        // No subscription created

        $this->post(route('webhook.receive', $tenant->reference), json_decode($this->payload, true))
            ->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_tenant_with_void_subscription_is_silently_dropped(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        TenantSubscription::factory()->void()->create(['tenant_id' => $tenant->id]);

        $this->post(route('webhook.receive', $tenant->reference), json_decode($this->payload, true))
            ->assertOk();

        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // HMAC verification
    // -------------------------------------------------------------------------

    public function test_valid_hmac_dispatches_process_webhook_job(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);

        $webhook = Webhook::factory()->active()->create(['tenant_id' => $tenant->id]);
        $signature = hash_hmac('sha256', $this->payload, $webhook->shared_secret);

        $this->call(
            'POST',
            route('webhook.receive', $tenant->reference),
            [],
            [],
            [],
            ['HTTP_X-Hook-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $this->payload,
        )->assertOk();

        Queue::assertPushed(ProcessWebhook::class);
    }

    public function test_invalid_hmac_does_not_dispatch_job(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);
        Webhook::factory()->active()->create(['tenant_id' => $tenant->id]);

        $this->call(
            'POST',
            route('webhook.receive', $tenant->reference),
            [],
            [],
            [],
            ['HTTP_X-Hook-Signature' => 'bad-signature', 'CONTENT_TYPE' => 'application/json'],
            $this->payload,
        )->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_no_shared_secret_still_dispatches_job(): void
    {
        // When no shared_secret is configured, HMAC check is skipped and the job is dispatched
        Queue::fake();

        $tenant = Tenant::factory()->create();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);
        Webhook::factory()->active()->create([
            'tenant_id'     => $tenant->id,
            'shared_secret' => null,
        ]);

        $this->post(route('webhook.receive', $tenant->reference), json_decode($this->payload, true))
            ->assertOk();

        Queue::assertPushed(ProcessWebhook::class);
    }

    // -------------------------------------------------------------------------
    // ProcessWebhook job payload
    // -------------------------------------------------------------------------

    public function test_dispatched_job_carries_correct_tenant_and_webhook_id(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id]);
        $webhook   = Webhook::factory()->active()->create(['tenant_id' => $tenant->id]);
        $signature = hash_hmac('sha256', $this->payload, $webhook->shared_secret);

        $this->call(
            'POST',
            route('webhook.receive', $tenant->reference),
            [],
            [],
            [],
            ['HTTP_X-Hook-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $this->payload,
        );

        Queue::assertPushed(ProcessWebhook::class, function (ProcessWebhook $job) use ($tenant, $webhook): bool {
            return $job->tenantId === $tenant->id
                && $job->webhookId === (string) $webhook->clio_id;
        });
    }
}
