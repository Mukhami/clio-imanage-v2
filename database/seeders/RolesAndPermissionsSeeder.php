<?php

namespace Database\Seeders;

use App\Console\Commands\SyncRolesAndPermissions;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->call(SyncRolesAndPermissions::class);
    }
}
