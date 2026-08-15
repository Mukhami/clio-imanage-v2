<?php

namespace App\Services;

use App\Enums\ClientNameStrategy;
use App\Enums\FilterAction;
use App\Enums\MatterDescriptionStrategy;
use App\Models\Tenant;
use App\ValueObjects\FilterResult;
use App\ValueObjects\ParsedIds;

class TenantConfigurationService
{
    public function __construct(
        private readonly Tenant $tenant,
    ) {}

    public function resolveDisplayNumber(string $displayNumber, array $payload): ParsedIds
    {
        $config = $this->tenant->displayNumberParsingConfig;

        if (! $config) {
            return ParsedIds::invalid(['No display number parsing config for tenant']);
        }

        $parser = new DisplayNumberParser($config);

        return $parser->parse($displayNumber, $payload);
    }

    public function resolveClientName(string $rawName, string $contactType): string
    {
        $config = $this->tenant->clientNameTransformationConfig;

        if (! $config || ! $config->enabled) {
            return $rawName;
        }

        if ($config->apply_to_persons_only && $contactType !== 'Person') {
            return $rawName;
        }

        return match ($config->strategy) {
            ClientNameStrategy::None => $rawName,
            ClientNameStrategy::LastNameFirst => $this->transformLastNameFirst($rawName),
            ClientNameStrategy::ReverseWords => implode(' ', array_reverse(explode(' ', $rawName))),
            ClientNameStrategy::CustomTemplate => $this->applyNameTemplate($config->template_pattern ?? '', $rawName),
        };
    }

    public function resolveMatterDescription(string $rawDescription, array $context): string
    {
        $config = $this->tenant->matterDescriptionTransformationConfig;

        if (! $config || ! $config->enabled) {
            return $rawDescription;
        }

        return match ($config->strategy) {
            MatterDescriptionStrategy::None => $rawDescription,
            MatterDescriptionStrategy::UseDisplayNumber => $context['display_number'] ?? $rawDescription,
            MatterDescriptionStrategy::UseClientDescription => $context['client_description'] ?? $rawDescription,
            MatterDescriptionStrategy::CompositeTemplate => $this->applyDescriptionTemplate(
                $config->template_pattern ?? '',
                array_merge($context, ['description' => $rawDescription]),
            ),
            MatterDescriptionStrategy::StripPrefix => $this->stripMatterPrefix($rawDescription, $context),
        };
    }

    public function resolveWorkspaceName(array $context): string
    {
        $config = $this->tenant->workspaceNamingConfig;

        if (! $config) {
            return ($context['matter_description'] ?? '');
        }

        $resolver = new WorkspaceNameResolver($config);

        return $resolver->resolve($context);
    }

    public function resolveCustomFieldMappings(array $payload, array $clioCustomFields): array
    {
        $rules = $this->tenant->customFieldMappingRules()
            ->enabled()
            ->byPriority()
            ->get();

        $result = [];

        foreach ($rules as $rule) {
            $sourceValue = $this->extractSourceValue($rule, $payload, $clioCustomFields);

            if ($sourceValue === null) {
                continue;
            }

            $imanageValue = $this->mapValue($rule, $sourceValue);

            if ($imanageValue !== null && $rule->imanage_custom_field_config_id) {
                $result[$rule->imanage_custom_field_config_id] = $imanageValue;
            }
        }

        return $result;
    }

    public function shouldProcess(array $payload): FilterResult
    {
        $filters = $this->tenant->webhookProcessingFilters()
            ->enabled()
            ->byPriority()
            ->get();

        foreach ($filters as $filter) {
            $fieldValue = data_get($payload, str_replace('.', '\.', $filter->field_path));
            $matches = $this->evaluateFilter($filter, (string) ($fieldValue ?? ''));

            if ($matches && $filter->action === FilterAction::Skip) {
                return FilterResult::skip("Filter matched: {$filter->field_path} {$filter->operator->value} {$filter->value}");
            }

            if ($matches && $filter->action === FilterAction::Proceed) {
                return FilterResult::proceed();
            }
        }

        return FilterResult::proceed();
    }

    public function resolveLegacyAlias(string $clioId, string $entityType): ?string
    {
        return $this->tenant->legacyAliasMappings()
            ->where('entity_type', $entityType)
            ->where('clio_id', $clioId)
            ->value('imanage_alias');
    }

    private function transformLastNameFirst(string $name): string
    {
        $parts = explode(' ', trim($name));
        if (count($parts) < 2) {
            return $name;
        }
        $lastName = array_pop($parts);

        return $lastName.', '.implode(' ', $parts);
    }

    private function applyNameTemplate(string $template, string $rawName): string
    {
        $parts = explode(' ', trim($rawName));
        $firstName = $parts[0] ?? '';
        $lastName = $parts[count($parts) - 1] ?? '';
        $firstNames = implode(' ', array_slice($parts, 0, -1));

        return str_replace(
            ['{first_name}', '{last_name}', '{full_name}', '{first_names}'],
            [$firstName, $lastName, $rawName, $firstNames],
            $template,
        );
    }

    private function applyDescriptionTemplate(string $template, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements['{'.$key.'}'] = (string) $value;
        }

        // Handle display_number parts
        $displayNumber = $context['display_number'] ?? '';
        $delimiter = $context['display_number_delimiter'] ?? '-';
        $parts = explode($delimiter, $displayNumber);
        foreach ($parts as $i => $part) {
            $replacements['{display_number_part'.$i.'}'] = $part;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function stripMatterPrefix(string $description, array $context): string
    {
        $matterId = $context['matter_id'] ?? '';
        if ($matterId && str_starts_with($description, $matterId)) {
            return ltrim(substr($description, strlen($matterId)), ' -');
        }

        return $description;
    }

    private function extractSourceValue(mixed $rule, array $payload, array $clioCustomFields): ?string
    {
        return match ($rule->source_type) {
            \App\Enums\CustomFieldSourceType::MatterStatus => $payload['data']['status'] ?? null,
            \App\Enums\CustomFieldSourceType::ResponsibleAttorney => $payload['data']['responsible_attorney']['name'] ?? null,
            \App\Enums\CustomFieldSourceType::OriginatingAttorney => $payload['data']['originating_attorney']['name'] ?? null,
            \App\Enums\CustomFieldSourceType::PracticeArea => $payload['data']['practice_area']['name'] ?? null,
            \App\Enums\CustomFieldSourceType::Template => $payload['data']['practice_area']['id'] ?? null,
            \App\Enums\CustomFieldSourceType::OpenDate => $payload['data']['open_date'] ?? null,
            \App\Enums\CustomFieldSourceType::Static => $rule->static_value,
            \App\Enums\CustomFieldSourceType::ClioCustomField => $clioCustomFields[$rule->source_field_name] ?? null,
        };
    }

    private function mapValue(mixed $rule, string $sourceValue): ?string
    {
        return match ($rule->value_mapping_type) {
            \App\Enums\ValueMappingType::Direct => $sourceValue,
            \App\Enums\ValueMappingType::Static => $rule->static_value,
            \App\Enums\ValueMappingType::DateFormat => $this->formatDate($sourceValue, $rule->date_format ?? 'Y-m-d'),
            \App\Enums\ValueMappingType::Lookup => $sourceValue, // Resolved at runtime against iManage field config
        };
    }

    private function formatDate(string $date, string $format): string
    {
        try {
            return \Carbon\Carbon::parse($date)->format($format);
        } catch (\Throwable) {
            return $date;
        }
    }

    private function evaluateFilter(mixed $filter, string $fieldValue): bool
    {
        return match ($filter->operator) {
            \App\Enums\FilterOperator::Equals => $fieldValue === $filter->value,
            \App\Enums\FilterOperator::NotEquals => $fieldValue !== $filter->value,
            \App\Enums\FilterOperator::Contains => str_contains($fieldValue, $filter->value),
            \App\Enums\FilterOperator::MatchesRegex => (bool) preg_match($filter->value, $fieldValue),
            \App\Enums\FilterOperator::ClioPicklistEquals => $fieldValue === $filter->value, // Full resolution via ClioApiService
        };
    }
}
