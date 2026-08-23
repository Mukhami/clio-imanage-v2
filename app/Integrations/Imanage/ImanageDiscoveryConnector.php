<?php

declare(strict_types=1);

namespace App\Integrations\Imanage;

use Saloon\Http\Connector;

/**
 * Connector for the iManage discovery endpoint (GET /api).
 * Base URL is the cloud root, separate from the Work REST API path.
 */
class ImanageDiscoveryConnector extends Connector
{
    public function __construct(
        private readonly string $cloudUrl,
        private readonly string $accessToken,
    ) {}

    public function resolveBaseUrl(): string
    {
        return rtrim($this->cloudUrl, '/');
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'X-Auth-Token' => $this->accessToken,
        ];
    }
}
