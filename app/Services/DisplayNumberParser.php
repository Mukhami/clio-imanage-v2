<?php

namespace App\Services;

use App\Models\DisplayNumberParsingConfig;
use App\Enums\ParsingStrategy;
use App\ValueObjects\ParsedIds;

class DisplayNumberParser
{
    public function __construct(
        private readonly DisplayNumberParsingConfig $config,
    ) {}

    public function parse(string $displayNumber, array $payload): ParsedIds
    {
        if (! $this->config->enabled) {
            return ParsedIds::invalid(['Parser is disabled']);
        }

        if ($this->config->validation_regex && ! preg_match($this->config->validation_regex, $displayNumber)) {
            return ParsedIds::invalid(['Display number failed validation regex']);
        }

        $processed = $this->applyPreProcessing($displayNumber);

        $ids = match ($this->config->strategy) {
            ParsingStrategy::SplitDelimiter => $this->applySplitDelimiter($processed),
            ParsingStrategy::SplitDelimiterNested => $this->applySplitDelimiterNested($processed),
            ParsingStrategy::Regex => $this->applyRegex($processed),
            ParsingStrategy::BracketExtraction => $this->applyBracketExtraction($processed),
            ParsingStrategy::ClioIds => $this->applyClioIds($payload),
            ParsingStrategy::CustomFieldExtraction => $this->applyCustomFieldExtraction($payload),
            ParsingStrategy::DisplayNumberAsMatter => ParsedIds::valid('', $processed),
            ParsingStrategy::DisplayNumberAsClient => ParsedIds::valid($processed, ''),
            ParsingStrategy::LegacyAliasLookup => $this->applyLegacyAliasLookup($payload),
            ParsingStrategy::SequenceAuto, ParsingStrategy::Custom => $this->applyRegex($processed),
        };

        if (! $ids->isValid && $this->config->fallback_strategy) {
            return $this->applyFallback($displayNumber, $payload);
        }

        return $this->applyPostProcessing($ids);
    }

    /**
     * Preview parsing without side effects (for admin UI).
     */
    public function test(string $displayNumber, array $payload): ParsedIds
    {
        return $this->parse($displayNumber, $payload);
    }

    private function applySplitDelimiter(string $dn): ParsedIds
    {
        $delimiter = $this->config->delimiter ?? '-';
        $parts = explode($delimiter, $dn);

        $clientPos = $this->config->client_position ?? 0;
        $matterPos = $this->config->matter_position ?? 1;

        $clientId = $parts[$clientPos] ?? '';
        $matterId = $parts[$matterPos] ?? '';

        if ($clientId === '' && $matterId === '') {
            return ParsedIds::invalid(['Could not split display number on delimiter: '.$delimiter]);
        }

        return ParsedIds::valid(trim($clientId), trim($matterId));
    }

    private function applySplitDelimiterNested(string $dn): ParsedIds
    {
        $primary = $this->config->delimiter ?? '-';
        $secondary = $this->config->secondary_delimiter ?? '.';

        $primaryParts = explode($primary, $dn);
        $clientId = trim($primaryParts[$this->config->client_position ?? 0] ?? '');

        $matterPart = $primaryParts[$this->config->matter_position ?? 1] ?? '';
        $secondaryParts = explode($secondary, $matterPart);
        $matterId = trim($secondaryParts[0] ?? '');

        return ParsedIds::valid($clientId, $matterId);
    }

    private function applyRegex(string $dn): ParsedIds
    {
        if (! $this->config->regex_pattern) {
            return ParsedIds::invalid(['No regex pattern configured']);
        }

        if (! preg_match($this->config->regex_pattern, $dn, $matches)) {
            return ParsedIds::invalid(['Regex did not match display number']);
        }

        $clientGroup = $this->config->client_capture_group ?? 'client';
        $matterGroup = $this->config->matter_capture_group ?? 'matter';

        $clientId = $matches[$clientGroup] ?? '';
        $matterId = $matches[$matterGroup] ?? '';

        return ParsedIds::valid(trim($clientId), trim($matterId));
    }

    private function applyBracketExtraction(string $dn): ParsedIds
    {
        if (! preg_match('/\[([^\]]+)\]/', $dn, $bracketMatch)) {
            return ParsedIds::invalid(['No bracket content found']);
        }

        return $this->applySplitDelimiter($bracketMatch[1]);
    }

    private function applyClioIds(array $payload): ParsedIds
    {
        $clientId = (string) ($payload['data']['client']['id'] ?? '');
        $matterId = (string) ($payload['data']['id'] ?? '');

        if ($clientId === '' || $matterId === '') {
            return ParsedIds::invalid(['Could not extract Clio IDs from payload']);
        }

        return ParsedIds::valid($clientId, $matterId);
    }

    private function applyCustomFieldExtraction(array $payload): ParsedIds
    {
        $fieldName = $this->config->custom_field_name;
        $customFields = $payload['data']['custom_field_values'] ?? [];

        foreach ($customFields as $field) {
            if (($field['custom_field']['name'] ?? '') === $fieldName) {
                $value = (string) ($field['value'] ?? '');
                return $this->applySplitDelimiter($value);
            }
        }

        return ParsedIds::invalid(['Custom field not found in payload: '.$fieldName]);
    }

    private function applyLegacyAliasLookup(array $payload): ParsedIds
    {
        // Delegate to TenantConfigurationService at runtime
        return ParsedIds::invalid(['Legacy alias lookup must be resolved via TenantConfigurationService']);
    }

    private function applyPreProcessing(string $dn): string
    {
        $rules = $this->config->pre_processing_rules ?? [];

        foreach ($rules as $rule) {
            $dn = match ($rule['op'] ?? '') {
                'ltrim' => ltrim($dn, $rule['chars'] ?? ''),
                'rtrim' => rtrim($dn, $rule['chars'] ?? ''),
                'trim' => trim($dn),
                'uppercase' => strtoupper($dn),
                'lowercase' => strtolower($dn),
                'strip_prefix' => str_starts_with($dn, $rule['prefix'] ?? '')
                    ? substr($dn, strlen($rule['prefix']))
                    : $dn,
                'split_take' => explode($rule['delimiter'] ?? '-', $dn)[$rule['position'] ?? 0] ?? $dn,
                'regex_replace' => preg_replace($rule['pattern'] ?? '//', $rule['replacement'] ?? '', $dn) ?? $dn,
                default => $dn,
            };
        }

        return $dn;
    }

    private function applyPostProcessing(ParsedIds $ids): ParsedIds
    {
        $rules = $this->config->post_processing_rules ?? [];
        $clientId = $ids->clientId;
        $matterId = $ids->matterId;

        foreach ($rules as $rule) {
            $target = $rule['target'] ?? 'matter_id';
            $value = $target === 'client_id' ? $clientId : $matterId;

            $value = match ($rule['op'] ?? '') {
                'trim' => trim($value),
                'uppercase' => strtoupper($value),
                'lowercase' => strtolower($value),
                'prefix' => ($rule['value'] ?? '').$value,
                'pad_left' => str_pad($value, (int) ($rule['length'] ?? 0), $rule['char'] ?? '0', STR_PAD_LEFT),
                'max_length' => substr($value, 0, (int) ($rule['length'] ?? strlen($value))),
                default => $value,
            };

            if ($target === 'client_id') {
                $clientId = $value;
            } else {
                $matterId = $value;
            }
        }

        return ParsedIds::valid($clientId, $matterId);
    }

    private function applyFallback(string $dn, array $payload): ParsedIds
    {
        $fallbackStrategy = ParsingStrategy::tryFrom($this->config->fallback_strategy ?? '');

        if (! $fallbackStrategy) {
            return ParsedIds::invalid(['Primary parsing failed and no valid fallback strategy']);
        }

        $fallbackConfig = $this->config->fallback_config ?? [];
        // Create a temporary config with fallback settings
        $tempConfig = clone $this->config;
        $tempConfig->strategy = $fallbackStrategy;
        foreach ($fallbackConfig as $key => $value) {
            $tempConfig->{$key} = $value;
        }

        return (new self($tempConfig))->parse($dn, $payload);
    }
}
