<?php

declare(strict_types=1);

namespace App\Integrations\Clio;

use App\Models\Tenant;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\AccessTokenAuthenticator;
use Saloon\Http\Connector;

class ClioConnector extends Connector
{
    public function __construct(protected readonly Tenant $tenant) {}

    public function resolveBaseUrl(): string
    {
        return rtrim($this->tenant->clioLocation->api_url, '/');
    }

    protected function defaultAuth(): ?Authenticator
    {
        $token = $this->tenant->clioOAuthAccessTokens()->active()->latest()->first();

        return new AccessTokenAuthenticator($token->access_token);
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
