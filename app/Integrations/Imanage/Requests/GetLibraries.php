<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetLibraries extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/libraries';
    }
}
