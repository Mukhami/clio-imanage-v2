<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateWorkspace extends Request
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $libraryId,
        private readonly array $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/libraries/{$this->libraryId}/workspaces";
    }

    protected function defaultBody(): array
    {
        return $this->data;
    }
}
