<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateImanageClient extends Request
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    public function __construct(
        private readonly string $customerId,
        private readonly string $libraryId,
        private readonly string $clientKey,
        private readonly array $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customers/{$this->customerId}/libraries/{$this->libraryId}/customs/custom1/" . urlencode($this->clientKey);
    }

    protected function defaultBody(): array
    {
        return $this->data;
    }
}
