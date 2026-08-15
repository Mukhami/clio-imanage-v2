<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetWorkspace extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $libraryId,
        private readonly string $key,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/libraries/{$this->libraryId}/workspaces/{$this->key}";
    }
}
