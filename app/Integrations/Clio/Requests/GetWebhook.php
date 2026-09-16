<?php

declare(strict_types=1);

namespace App\Integrations\Clio\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetWebhook extends Request
{
    protected Method $method = Method::GET;

    public function __construct(protected readonly int $id) {}

    public function resolveEndpoint(): string
    {
        return '/webhooks/' . $this->id . '.json';
    }

    protected function defaultQuery(): array
    {
        return ['fields' => 'id,url,model,events,status,shared_secret,expires_at,updated_at'];
    }
}
