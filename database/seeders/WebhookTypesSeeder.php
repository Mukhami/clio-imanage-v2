<?php

namespace Database\Seeders;

use App\Models\WebhookType;
use Illuminate\Database\Seeder;

class WebhookTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Matter Created',  'model' => 'matter', 'event' => 'created'],
            ['name' => 'Matter Updated',  'model' => 'matter', 'event' => 'updated'],
            ['name' => 'Matter Deleted',  'model' => 'matter', 'event' => 'deleted'],
            ['name' => 'Matter Closed',   'model' => 'matter', 'event' => 'matter_closed'],
        ];

        foreach ($types as $type) {
            WebhookType::updateOrCreate(
                ['model' => $type['model'], 'event' => $type['event']],
                ['name' => $type['name']],
            );
        }

        // Fix any legacy rows that have capitalised model values (e.g. 'Matter' → 'matter')
        WebhookType::where('model', 'Matter')->update(['model' => 'matter']);
        WebhookType::where('model', 'Contact')->update(['model' => 'contact']);
    }
}
