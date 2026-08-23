<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name'                               => $name,
            'slug'                               => Str::slug($name),
            'reference'                          => (string) Str::uuid(),
            'status'                             => TenantStatus::Active,
            'clio_location_id'                   => null,
            'clio_app_id'                        => Str::random(32),
            'clio_app_secret'                    => Str::random(64),
            'imanage_cloud_url'                  => 'https://cloudimanage.com',
            'imanage_customer_id'                => fake()->numerify('########'),
            'imanage_app_id'                     => Str::random(32),
            'imanage_app_secret'                 => Str::random(64),
            'imanage_username'                   => null,
            'imanage_password'                   => null,
            'password_authentication'            => false,
            'has_group_security_mapping'         => false,
            'enable_workspace_link_custom_field' => false,
            'owner_id'                           => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => TenantStatus::Active]);
    }

    public function pending(): static
    {
        return $this->state(['status' => TenantStatus::Pending]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => TenantStatus::Suspended]);
    }
}
