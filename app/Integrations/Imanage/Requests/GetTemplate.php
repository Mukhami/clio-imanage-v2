<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetTemplate extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $customerId,
        private readonly string $libraryId,
        private readonly string $templateId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customers/{$this->customerId}/libraries/{$this->libraryId}/templates/{$this->templateId}";
    }
}
