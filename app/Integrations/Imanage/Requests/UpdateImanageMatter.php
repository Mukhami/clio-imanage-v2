<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateImanageMatter extends Request
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    public function __construct(
        private readonly string $customerId,
        private readonly string $libraryId,
        private readonly string $matterKey,
        private readonly string $clientKey,
        private readonly array $data,
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

    protected function defaultBody(): array
    {
        return $this->data;
    }
}
