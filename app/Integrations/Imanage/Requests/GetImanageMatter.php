<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetImanageMatter extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $customerId,
        private readonly string $libraryId,
        private readonly string $matterKey,
        private readonly string $clientKey,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customers/{$this->customerId}/libraries/{$this->libraryId}/customs/custom2/" . urlencode($this->matterKey);
    }

    protected function defaultQuery(): array
    {
        return [
            'parent_alias' => $this->clientKey,
        ];
    }
}
