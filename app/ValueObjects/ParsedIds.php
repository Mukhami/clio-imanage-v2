<?php

namespace App\ValueObjects;

final class ParsedIds
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $matterId,
        public readonly bool $isValid,
        public readonly array $errors = [],
    ) {}

    public static function invalid(array $errors): self
    {
        return new self('', '', false, $errors);
    }

    public static function valid(string $clientId, string $matterId): self
    {
        return new self($clientId, $matterId, true);
    }
}
