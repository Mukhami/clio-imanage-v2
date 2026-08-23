<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class GetOAuthToken extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/token';
    }

    protected function defaultBody(): array
    {
        return [
            'username'      => $this->username,
            'password'      => $this->password,
            'grant_type'    => 'password',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }
}
