<?php

namespace Database\Seeders;

use App\Models\ClioLocation;
use Illuminate\Database\Seeder;

class ClioLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name'    => 'United States',
                'region'  => 'US',
                'api_url' => 'https://app.clio.com/api/v4/',
                'app_url' => 'https://app.clio.com',
            ],
            [
                'name'    => 'Europe',
                'region'  => 'EU',
                'api_url' => 'https://eu.app.clio.com/api/v4/',
                'app_url' => 'https://eu.app.clio.com',
            ],
            [
                'name'    => 'Canada',
                'region'  => 'CA',
                'api_url' => 'https://ca.app.clio.com/api/v4/',
                'app_url' => 'https://ca.app.clio.com',
            ],
            [
                'name'    => 'Australia',
                'region'  => 'AU',
                'api_url' => 'https://au.app.clio.com/api/v4/',
                'app_url' => 'https://au.app.clio.com',
            ],
        ];

        foreach ($locations as $location) {
            ClioLocation::firstOrCreate(
                ['region' => $location['region']],
                $location,
            );
        }
    }
}
