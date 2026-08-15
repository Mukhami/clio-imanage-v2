<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPracticeAreas extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $libraryId,
        private readonly int $limit = 100,
        private readonly int $skip = 0,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/libraries/{$this->libraryId}/practice-groups";
    }

    protected function defaultQuery(): array
    {
        return [
            'limit' => $this->limit,
            'skip'  => $this->skip,
        ];
    }
}
