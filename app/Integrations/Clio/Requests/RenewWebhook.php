<?php

declare(strict_types=1);

namespace App\Integrations\Clio\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class RenewWebhook extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    public function __construct(
        private readonly int $id,
        private readonly array $data = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return "/webhooks/{$this->id}.json";
    }

    protected function defaultBody(): array
    {
        return $this->data;
    }
}
