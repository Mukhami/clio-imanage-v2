<?php

declare(strict_types=1);

namespace App\Integrations\Clio\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetCustomFields extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/custom_fields.json';
    }
}
