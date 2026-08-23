<?php

declare(strict_types=1);

namespace App\Integrations\Imanage;

use Saloon\Http\Connector;

/**
 * Connector for the iManage OAuth2 authorization server.
 * Base URL is separate from the Work REST API.
 */
class ImanageAuthConnector extends Connector
{
    public function __construct(
        private readonly string $cloudUrl,
    ) {}

    public function resolveBaseUrl(): string
    {
        return rtrim($this->cloudUrl, '/').'/auth/oauth2';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
