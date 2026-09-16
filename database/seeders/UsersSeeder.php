<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin — full platform access
        $email    = env('SUPER_ADMIN_EMAIL', 'superadmin@clio-imanage.test');
        $password = env('SUPER_ADMIN_PASSWORD', app()->isProduction() ? Str::random(24) : 'password');

        $superAdmin = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles(['Super Admin']);

        if ($superAdmin->wasRecentlyCreated) {
            $this->command->info("Super Admin created: {$email}");

            if (app()->isProduction()) {
                $this->command->warn("Generated password: {$password}");
                $this->command->warn('Change this password immediately after first login.');
            }
        }

        // Admin — only in non-production environments
        if (! app()->isProduction()) {
            $admin = User::firstOrCreate(
                ['email' => 'admin@clio-imanage.test'],
                [
                    'name'              => 'Admin User',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $admin->syncRoles(['Admin']);
        }
    }
}
