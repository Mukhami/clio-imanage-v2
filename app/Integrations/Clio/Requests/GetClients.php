<?php

declare(strict_types=1);

namespace App\Integrations\Clio\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetClients extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected readonly int $page = 1,
        protected readonly int $limit = 200,
        protected readonly ?string $updatedSince = null,
        protected readonly ?string $type = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/contacts.json';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'page'          => $this->page,
            'limit'         => $this->limit,
            'updated_since' => $this->updatedSince,
            'type'          => $this->type,
        ], fn ($value) => $value !== null);
    }
}
