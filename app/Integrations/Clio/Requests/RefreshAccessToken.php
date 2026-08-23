<?php

declare(strict_types=1);

namespace App\Integrations\Clio\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasFormBody;

class RefreshAccessToken extends SoloRequest implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly string $tokenUrl,
        protected readonly string $clientId,
        protected readonly string $clientSecret,
        protected readonly string $refreshToken,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->tokenUrl;
    }

    protected function defaultBody(): array
    {
        return [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
        ];
    }
}
