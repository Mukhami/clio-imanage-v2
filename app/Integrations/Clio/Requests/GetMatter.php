<?php

declare(strict_types=1);

namespace App\Integrations\Clio\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetMatter extends Request
{
    protected Method $method = Method::GET;

    public function __construct(protected readonly int $id) {}

    public function resolveEndpoint(): string
    {
        return "/matters/{$this->id}.json";
    }

    protected function defaultQuery(): array
    {
        return [
            'fields' => implode(',', [
                'id',
                'display_number',
                'description',
                'status',
                'open_date',
                'close_date',
                'practice_area',
                'client',
                'responsible_attorney',
                'originating_attorney',
                'custom_field_values',
                'matter_stage',
                'group',
                'location',
            ]),
        ];
    }
}
