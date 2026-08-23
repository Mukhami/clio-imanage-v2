<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPracticeAreas extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $customerId,
        private readonly string $libraryId,
        private readonly int $limit = 9999,
        private readonly int $offset = 0,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customers/{$this->customerId}/libraries/{$this->libraryId}/customs/custom29";
    }

    protected function defaultQuery(): array
    {
        return [
            'total'  => 'true',
            'limit'  => $this->limit,
            'offset' => $this->offset,
        ];
    }
}
