<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ClioLocationsSeeder::class,
            UsersSeeder::class,
            WebhookTypesSeeder::class,
        ]);

        // Demo tenant — only in local/staging environments
        if (app()->environment('local', 'staging')) {
            $this->call(TenantSeeder::class);
        }
    }
}
