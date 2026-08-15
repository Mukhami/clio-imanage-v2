<?php

namespace App\ValueObjects;

use App\Enums\FilterAction;

final class FilterResult
{
    public function __construct(
        public readonly FilterAction $action,
        public readonly string $reason = '',
    ) {}

    public static function proceed(): self
    {
        return new self(FilterAction::Proceed);
    }

    public static function skip(string $reason): self
    {
        return new self(FilterAction::Skip, $reason);
    }

    public function shouldSkip(): bool
    {
        return $this->action === FilterAction::Skip;
    }
}
