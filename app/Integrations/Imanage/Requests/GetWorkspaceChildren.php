<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetWorkspaceChildren extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $customerId,
        private readonly string $libraryId,
        private readonly string $workspaceId,
        private readonly int $limit = 9999,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customers/{$this->customerId}/libraries/{$this->libraryId}/workspaces/{$this->workspaceId}/children";
    }

    protected function defaultQuery(): array
    {
        return [
            'exclude_docs' => 'true',
            'total'        => 'true',
            'limit'        => $this->limit,
        ];
    }
}
