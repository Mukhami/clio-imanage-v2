<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SubscriptionStatus;
use App\Models\TenantSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireSubscriptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_expires_past_end_date_active_subscriptions(): void
    {
        $sub = TenantSubscription::factory()->active()->create([
            'end_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame(
            SubscriptionStatus::Expired,
            $sub->fresh()->status,
        );
    }

    public function test_command_does_not_expire_future_subscriptions(): void
    {
        $sub = TenantSubscription::factory()->active()->create([
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame(SubscriptionStatus::Active, $sub->fresh()->status);
    }

    public function test_command_does_not_touch_already_void_subscriptions(): void
    {
        $sub = TenantSubscription::factory()->void()->create([
            'end_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame(SubscriptionStatus::Void, $sub->fresh()->status);
    }
}
