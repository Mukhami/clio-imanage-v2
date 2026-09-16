<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ApplyFolderSecurity extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $customerId,
        private readonly string $libraryId,
        private readonly string $folderId,
        private readonly array $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customers/{$this->customerId}/libraries/{$this->libraryId}/folders/{$this->folderId}/security";
    }

    protected function defaultBody(): array
    {
        return $this->data;
    }
}
