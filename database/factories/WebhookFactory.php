<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WebhookStatus;
use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'tenant_id'       => Tenant::factory(),
            'webhook_type_id' => WebhookType::firstOrCreate(
                ['model' => 'Matter', 'event' => 'matter.created'],
                ['name' => 'Matter Created'],
            ),
            'clio_id'         => (string) fake()->numerify('########'),
            'url'             => fake()->url(),
            'shared_secret'   => Str::random(64),
            'status'          => WebhookStatus::Active,
            'expires_at'      => now()->addYear(),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => WebhookStatus::Active]);
    }

    public function expired(): static
    {
        return $this->state([
            'status'     => WebhookStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);
    }
}
