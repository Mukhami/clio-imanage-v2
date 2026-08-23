<?php

declare(strict_types=1);

namespace App\Integrations\Imanage\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

// TODO: Confirm the correct iManage endpoint for custom field definitions.
// This endpoint does not appear in the v1 helper or the practice-panther iManageService reference.
// Possible correct path: /customers/{customerId}/libraries/{libraryId}/customs/custom-field-definitions
class GetCustomFields extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $customerId,
        private readonly string $libraryId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customers/{$this->customerId}/libraries/{$this->libraryId}/customs/custom-field-definitions";
    }
}
