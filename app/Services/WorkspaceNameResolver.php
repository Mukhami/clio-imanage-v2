<?php

namespace App\Services;

use App\Models\WorkspaceNamingConfig;

class WorkspaceNameResolver
{
    public function __construct(
        private readonly WorkspaceNamingConfig $config,
    ) {}

    public function resolve(array $context): string
    {
        return $this->replaceTokens($this->config->template_pattern, $context);
    }

    public function availableTokens(): array
    {
        return [
            '{client_id}',
            '{matter_id}',
            '{client_description}',
            '{matter_description}',
            '{display_number}',
            '{practice_area}',
            '{sub_practice_area}',
            '{open_date}',
            '{responsible_attorney}',
            '{originating_attorney}',
        ];
    }

    public function replaceTokens(string $template, array $context): string
    {
        // Replace simple tokens
        $simple = [
            '{client_id}' => $context['client_id'] ?? '',
            '{matter_id}' => $context['matter_id'] ?? '',
            '{client_description}' => $context['client_description'] ?? '',
            '{matter_description}' => $context['matter_description'] ?? '',
            '{display_number}' => $context['display_number'] ?? '',
            '{practice_area}' => $context['practice_area'] ?? '',
            '{sub_practice_area}' => $context['sub_practice_area'] ?? '',
            '{open_date}' => $context['open_date'] ?? '',
            '{responsible_attorney}' => $context['responsible_attorney'] ?? '',
            '{originating_attorney}' => $context['originating_attorney'] ?? '',
        ];

        $result = str_replace(array_keys($simple), array_values($simple), $template);

        // Replace display_number_part_N tokens
        $displayNumber = $context['display_number'] ?? '';
        $delimiter = $context['display_number_delimiter'] ?? '-';
        $parts = explode($delimiter, $displayNumber);

        $result = preg_replace_callback(
            '/\{display_number_part_(\d+)\}/',
            fn ($matches) => $parts[(int) $matches[1]] ?? '',
            $result,
        );

        // Replace custom_field tokens: {custom_field:NAME}
        $customFields = $context['custom_fields'] ?? [];
        $result = preg_replace_callback(
            '/\{custom_field:([^}]+)\}/',
            fn ($matches) => $customFields[$matches[1]] ?? '',
            $result,
        );

        return $result;
    }
}
