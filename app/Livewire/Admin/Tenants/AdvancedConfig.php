<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Models\Tenant;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AdvancedConfig extends Component
{
    public Tenant $tenant;

    // -------------------------------------------------------------------------
    // Display Number Parsing
    // -------------------------------------------------------------------------

    public string $parsingStrategy           = 'split_delimiter';
    public string $parsingDelimiter          = '/';
    public string $parsingSecondaryDelimiter = '';
    public string $parsingClientPosition     = '0';
    public string $parsingMatterPosition     = '1';
    public string $parsingRegexPattern       = '';
    public string $parsingClientCaptureGroup = '';
    public string $parsingMatterCaptureGroup = '';
    public string $parsingValidationRegex    = '';
    public string $parsingCustomFieldName    = '';
    public string $parsingFallbackStrategy   = '';
    public bool   $parsingEnabled            = true;

    // -------------------------------------------------------------------------
    // Workspace Naming
    // -------------------------------------------------------------------------

    public string $workspaceNamingTemplate    = '';
    public string $workspaceNamingDescription = '';

    // -------------------------------------------------------------------------
    // Client Name Transformation
    // -------------------------------------------------------------------------

    public string $clientNameStrategy            = 'none';
    public string $clientNameTemplate            = '';
    public bool   $clientNameApplyToPersonsOnly  = true;
    public bool   $clientNameEnabled             = true;

    // -------------------------------------------------------------------------
    // Matter Description Transformation
    // -------------------------------------------------------------------------

    public string $matterDescStrategy    = 'none';
    public string $matterDescSourceField = '';
    public string $matterDescTemplate    = '';
    public bool   $matterDescEnabled     = true;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(int $id): void
    {
        $this->tenant = Tenant::findOrFail($id);
        $this->loadConfigs();
    }

    private function loadConfigs(): void
    {
        if ($cfg = $this->tenant->displayNumberParsingConfig) {
            $this->parsingStrategy           = $cfg->strategy->value;
            $this->parsingDelimiter          = $cfg->delimiter ?? '/';
            $this->parsingSecondaryDelimiter = $cfg->secondary_delimiter ?? '';
            $this->parsingClientPosition     = (string) ($cfg->client_position ?? 0);
            $this->parsingMatterPosition     = (string) ($cfg->matter_position ?? 1);
            $this->parsingRegexPattern       = $cfg->regex_pattern ?? '';
            $this->parsingClientCaptureGroup = $cfg->client_capture_group ?? '';
            $this->parsingMatterCaptureGroup = $cfg->matter_capture_group ?? '';
            $this->parsingValidationRegex    = $cfg->validation_regex ?? '';
            $this->parsingCustomFieldName    = $cfg->custom_field_name ?? '';
            $this->parsingFallbackStrategy   = $cfg->fallback_strategy ?? '';
            $this->parsingEnabled            = (bool) $cfg->enabled;
        }

        if ($cfg = $this->tenant->workspaceNamingConfig) {
            $this->workspaceNamingTemplate    = $cfg->template_pattern;
            $this->workspaceNamingDescription = $cfg->description ?? '';
        }

        if ($cfg = $this->tenant->clientNameTransformationConfig) {
            $this->clientNameStrategy           = $cfg->strategy->value;
            $this->clientNameTemplate           = $cfg->template_pattern ?? '';
            $this->clientNameApplyToPersonsOnly = (bool) $cfg->apply_to_persons_only;
            $this->clientNameEnabled            = (bool) $cfg->enabled;
        }

        if ($cfg = $this->tenant->matterDescriptionTransformationConfig) {
            $this->matterDescStrategy    = $cfg->strategy->value;
            $this->matterDescSourceField = $cfg->source_field ?? '';
            $this->matterDescTemplate    = $cfg->template_pattern ?? '';
            $this->matterDescEnabled     = (bool) $cfg->enabled;
        }
    }

    // -------------------------------------------------------------------------
    // Save actions
    // -------------------------------------------------------------------------

    public function saveParsingConfig(): void
    {
        $this->validate([
            'parsingStrategy'           => 'required|string',
            'parsingDelimiter'          => 'nullable|string|max:10',
            'parsingSecondaryDelimiter' => 'nullable|string|max:10',
            'parsingClientPosition'     => 'nullable|integer|min:0',
            'parsingMatterPosition'     => 'nullable|integer|min:0',
            'parsingRegexPattern'       => 'nullable|string|max:512',
            'parsingClientCaptureGroup' => 'nullable|string|max:50',
            'parsingMatterCaptureGroup' => 'nullable|string|max:50',
            'parsingValidationRegex'    => 'nullable|string|max:512',
            'parsingCustomFieldName'    => 'nullable|string|max:255',
            'parsingFallbackStrategy'   => 'nullable|string|max:50',
        ]);

        $this->tenant->displayNumberParsingConfig()->updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'strategy'             => $this->parsingStrategy,
                'delimiter'            => $this->parsingDelimiter ?: null,
                'secondary_delimiter'  => $this->parsingSecondaryDelimiter ?: null,
                'client_position'      => $this->parsingClientPosition !== '' ? (int) $this->parsingClientPosition : null,
                'matter_position'      => $this->parsingMatterPosition !== '' ? (int) $this->parsingMatterPosition : null,
                'regex_pattern'        => $this->parsingRegexPattern ?: null,
                'client_capture_group' => $this->parsingClientCaptureGroup ?: null,
                'matter_capture_group' => $this->parsingMatterCaptureGroup ?: null,
                'validation_regex'     => $this->parsingValidationRegex ?: null,
                'custom_field_name'    => $this->parsingCustomFieldName ?: null,
                'fallback_strategy'    => $this->parsingFallbackStrategy ?: null,
                'enabled'              => $this->parsingEnabled,
            ]
        );

        session()->flash('parsing_saved', 'Display number parsing saved.');
    }

    public function saveWorkspaceNaming(): void
    {
        $this->validate([
            'workspaceNamingTemplate'    => 'required|string|max:1024',
            'workspaceNamingDescription' => 'nullable|string|max:255',
        ]);

        $this->tenant->workspaceNamingConfig()->updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'template_pattern' => $this->workspaceNamingTemplate,
                'description'      => $this->workspaceNamingDescription ?: null,
            ]
        );

        session()->flash('naming_saved', 'Workspace naming saved.');
    }

    public function saveClientNameConfig(): void
    {
        $this->validate([
            'clientNameStrategy' => 'required|string',
            'clientNameTemplate' => 'nullable|string|max:512',
        ]);

        $this->tenant->clientNameTransformationConfig()->updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'strategy'              => $this->clientNameStrategy,
                'template_pattern'      => $this->clientNameTemplate ?: null,
                'apply_to_persons_only' => $this->clientNameApplyToPersonsOnly,
                'enabled'               => $this->clientNameEnabled,
            ]
        );

        session()->flash('client_name_saved', 'Client name transformation saved.');
    }

    public function saveMatterDescConfig(): void
    {
        $this->validate([
            'matterDescStrategy'    => 'required|string',
            'matterDescSourceField' => 'nullable|string|max:255',
            'matterDescTemplate'    => 'nullable|string|max:512',
        ]);

        $this->tenant->matterDescriptionTransformationConfig()->updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'strategy'         => $this->matterDescStrategy,
                'source_field'     => $this->matterDescSourceField ?: null,
                'template_pattern' => $this->matterDescTemplate ?: null,
                'enabled'          => $this->matterDescEnabled,
            ]
        );

        session()->flash('matter_desc_saved', 'Matter description transformation saved.');
    }

    // -------------------------------------------------------------------------
    // Computed — strategy-dependent field visibility
    // -------------------------------------------------------------------------

    #[Computed]
    public function showDelimiterFields(): bool
    {
        return in_array($this->parsingStrategy, ['split_delimiter', 'split_delimiter_nested']);
    }

    #[Computed]
    public function showRegexFields(): bool
    {
        return $this->parsingStrategy === 'regex';
    }

    #[Computed]
    public function showCustomFieldName(): bool
    {
        return $this->parsingStrategy === 'custom_field_extraction';
    }

    #[Computed]
    public function showClientNameTemplate(): bool
    {
        return $this->clientNameStrategy === 'custom_template';
    }

    #[Computed]
    public function showMatterDescTemplate(): bool
    {
        return in_array($this->matterDescStrategy, ['composite_template']);
    }

    #[Computed]
    public function showMatterDescSourceField(): bool
    {
        return in_array($this->matterDescStrategy, ['use_display_number', 'use_client_description', 'strip_prefix', 'composite_template']);
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return view('livewire.admin.tenants.advanced-config');
    }
}
