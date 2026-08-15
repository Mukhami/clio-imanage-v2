<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Contracts\Authenticator;

class AuthenticatePassword extends Request
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
