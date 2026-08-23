<?php

declare(strict_types=1);

namespace App\Integrations\Imanage;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Saloon\Http\Connector;

class ImanageConnector extends Connector
{
    private readonly ?string $accessToken;

    public function __construct(
        private readonly Tenant $tenant,
    ) {
        // Prefer a stored, non-expired OAuth token
        $storedToken = $tenant->imanageOAuthAccessTokens()
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($storedToken) {
            $this->accessToken = $storedToken->access_token;
        } elseif ($tenant->password_authentication) {
            // No stored token — fetch a fresh one via OAuth2 password grant
            $this->accessToken = $this->fetchAccessToken($tenant);
        } else {
            $this->accessToken = null;
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

        if ($this->accessToken !== null) {
            $headers['X-Auth-Token'] = $this->accessToken;
        }

        return $headers;
    }

    private function fetchAccessToken(Tenant $tenant): ?string
    {
        $url = rtrim((string) $tenant->imanage_cloud_url, '/').'/auth/oauth2/token';

        $response = Http::asForm()->post($url, [
            'username'      => (string) $tenant->imanage_username,
            'password'      => (string) $tenant->imanage_password,
            'grant_type'    => 'password',
            'client_id'     => (string) $tenant->imanage_app_id,
            'client_secret' => (string) $tenant->imanage_app_secret,
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        return null;
    }
}
