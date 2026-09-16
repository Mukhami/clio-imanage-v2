<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Contracts\Authenticator;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class AuthenticatePassword extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $username,
        private readonly string $password,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/session';
    }

    protected function defaultBody(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    protected function defaultAuth(): ?Authenticator
    {
        return null;
    }
}
