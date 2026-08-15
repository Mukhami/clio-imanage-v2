<?php

declare(strict_types=1);

namespace App\Integrations\Imanage;

use App\Models\Tenant;
use Saloon\Http\Connector;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\AccessTokenAuthenticator;
use Saloon\Http\Auth\BasicAuthenticator;

class ImanageConnector extends Connector
{
    private readonly bool $usingOAuth;

    public function __construct(
        private readonly Tenant $tenant,
    ) {
        $activeToken = $tenant->imanageOAuthAccessTokens()
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        $this->usingOAuth = $activeToken !== null;

        if ($this->usingOAuth) {
            $this->authenticate(new AccessTokenAuthenticator($activeToken->access_token));
        } else {
            $this->authenticate(new BasicAuthenticator(
                (string) $tenant->imanage_username,
                (string) $tenant->imanage_password,
            ));
        }
    }

    public function resolveBaseUrl(): string
    {
        return rtrim((string) $this->tenant->imanage_cloud_url, '/').'/work/api/v2';
    }

    protected function defaultHeaders(): array
    {
        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (! $this->usingOAuth && $this->tenant->password_authentication) {
            $headers['X-Auth-Token'] = (string) $this->tenant->imanage_password;
        }

        return $headers;
    }
}
