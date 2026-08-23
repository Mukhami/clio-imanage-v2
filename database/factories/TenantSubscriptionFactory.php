<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TenantSubscription>
 */
class TenantSubscriptionFactory extends Factory
{
    protected $model = TenantSubscription::class;

    public function definition(): array
    {
        $start = now()->subMonth();

        return [
            'tenant_id'           => Tenant::factory(),
            'reference'           => 'SUB-' . strtoupper(Str::random(8)),
            'start_date'          => $start->toDateString(),
            'end_date'            => $start->addYear()->toDateString(),
            'status'              => SubscriptionStatus::Active,
            'clio_users_at_start' => fake()->numberBetween(1, 50),
            'notes'               => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => SubscriptionStatus::Active]);
    }

    public function void(): static
    {
        return $this->state(['status' => SubscriptionStatus::Void]);
    }

    public function expired(): static
    {
        return $this->state([
            'status'     => SubscriptionStatus::Expired,
            'end_date'   => now()->subDay()->toDateString(),
        ]);
    }
}
