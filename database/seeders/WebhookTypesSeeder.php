<?php

namespace Database\Seeders;

use App\Models\WebhookType;
use Illuminate\Database\Seeder;

class WebhookTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Matter Created',  'model' => 'Matter', 'event' => 'created'],
            ['name' => 'Matter Updated',  'model' => 'Matter', 'event' => 'updated'],
            ['name' => 'Matter Deleted',  'model' => 'Matter', 'event' => 'deleted'],
            ['name' => 'Matter Closed',   'model' => 'Matter', 'event' => 'matter_closed'],
        ];

        foreach ($types as $type) {
            WebhookType::firstOrCreate(
                ['model' => $type['model'], 'event' => $type['event']],
                ['name' => $type['name']],
            );
        }
    }
}
