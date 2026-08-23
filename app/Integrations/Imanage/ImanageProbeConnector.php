<?php

declare(strict_types=1);

namespace App\Integrations\Imanage;

use Saloon\Http\Connector;

/**
 * Lightweight connector for probing iManage credentials without a persisted Tenant.
 * Used by the tenant create/edit form credential test action.
 */
class ImanageProbeConnector extends Connector
{
    public function __construct(
        private readonly string $cloudUrl,
        private readonly ?string $accessToken = null,
    ) {}

    public function resolveBaseUrl(): string
    {
        return rtrim($this->cloudUrl, '/').'/work/api/v2';
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
}
