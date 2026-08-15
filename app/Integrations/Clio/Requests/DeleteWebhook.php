<?php

declare(strict_types=1);

namespace App\Integrations\Clio\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteWebhook extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(protected readonly int $id) {}

    public function resolveEndpoint(): string
    {
        return "/webhooks/{$this->id}.json";
    }
}
