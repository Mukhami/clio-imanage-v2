<?php

declare(strict_types=1);

namespace App\Integrations\Clio\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPracticeAreas extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/practice_areas.json';
    }
}
