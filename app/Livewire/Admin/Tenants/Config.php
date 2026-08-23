<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Models\ClioGroup;
use App\Models\ClioPracticeArea;
use App\Models\ClioUser;
use App\Models\GroupMapping;
use App\Models\ImanageCustomFieldConfig;
use App\Models\ImanageGroup;
use App\Models\ImanagePracticeArea;
use App\Models\ImanageSubPracticeArea;
use App\Models\ImanageTemplate;
use App\Models\ImanageUser;
use App\Models\Library;
use App\Models\PracticeAreaMapping;
use App\Models\TemplateMapping;
use App\Models\Tenant;
use App\Models\UserMapping;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Config extends Component
{
    public Tenant $tenant;

    // -------------------------------------------------------------------------
    // Default Settings form
    // -------------------------------------------------------------------------

    public string $libraryId            = '';
    public string $imanageTemplateId    = '';
    public string $replicaTemplateId    = '';
    public bool   $defaultHipaa         = false;
    public bool   $defaultEnabled       = true;
    public bool   $hasReplicaWorkspaces = false;
    public bool   $hasWorkspaceLinkCustomField    = false;
    public string $workspaceLinkCustomFieldName   = '';

    // -------------------------------------------------------------------------
    // Practice Area Mapping form
    // -------------------------------------------------------------------------

    public string $paClioPracticeAreaId      = '';
    public string $paImanagePracticeAreaId   = '';
    public string $paImanageSubPracticeAreaId = '';
    public string $paCustomFieldConfigId     = '';

    // -------------------------------------------------------------------------
    // Template Mapping form
    // -------------------------------------------------------------------------

    public string $tmClioPracticeAreaId  = '';
    public string $tmImanageTemplateId   = '';

    // -------------------------------------------------------------------------
    // Group Mapping form
    // -------------------------------------------------------------------------

    public string $gmClioGroupId    = '';
    public string $gmImanageGroupId = '';

    // -------------------------------------------------------------------------
    // User Mapping form
    // -------------------------------------------------------------------------

    public string $umClioUserId    = '';
    public string $umImanageUserId = '';

    // -------------------------------------------------------------------------
    // Custom Field Config form
    // -------------------------------------------------------------------------

    public string $cfIdentifier  = '';
    public string $cfDescription = '';

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(int $id): void
    {
        $this->tenant = Tenant::findOrFail($id);
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $setting = $this->tenant->tenantSetting;

        if (! $setting) {
            return;
        }

        $this->libraryId                   = (string) ($setting->library_id ?? '');
        $this->imanageTemplateId           = (string) ($setting->imanage_template_id ?? '');
        $this->replicaTemplateId           = (string) ($setting->replica_template_id ?? '');
        $this->defaultHipaa                = (bool) $setting->default_hipaa;
        $this->defaultEnabled              = (bool) $setting->default_enabled;
        $this->hasReplicaWorkspaces        = (bool) $setting->has_replica_workspaces;
        $this->hasWorkspaceLinkCustomField = (bool) $setting->has_workspace_link_custom_field;
        $this->workspaceLinkCustomFieldName = $setting->workspace_link_custom_field_name ?? '';
    }

    // -------------------------------------------------------------------------
    // Default Settings
    // -------------------------------------------------------------------------

    public function saveSettings(): void
    {
        $this->validate([
            'libraryId'                  => 'nullable|exists:libraries,id',
            'imanageTemplateId'          => 'nullable|exists:imanage_templates,id',
            'replicaTemplateId'          => 'nullable|exists:imanage_templates,id',
            'workspaceLinkCustomFieldName' => 'nullable|string|max:255',
        ]);

        $this->tenant->tenantSetting()->updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'library_id'                      => $this->libraryId ?: null,
                'imanage_template_id'             => $this->imanageTemplateId ?: null,
                'replica_template_id'             => $this->replicaTemplateId ?: null,
                'default_hipaa'                   => $this->defaultHipaa,
                'default_enabled'                 => $this->defaultEnabled,
                'has_replica_workspaces'          => $this->hasReplicaWorkspaces,
                'has_workspace_link_custom_field' => $this->hasWorkspaceLinkCustomField,
                'workspace_link_custom_field_name' => $this->workspaceLinkCustomFieldName ?: null,
            ]
        );

        session()->flash('success', 'Settings saved.');
    }

    // -------------------------------------------------------------------------
    // Practice Area Mappings
    // -------------------------------------------------------------------------

    public function addPracticeAreaMapping(): void
    {
        $this->validate([
            'paClioPracticeAreaId'       => 'required|exists:clio_practice_areas,id',
            'paImanagePracticeAreaId'    => 'required|exists:imanage_practice_areas,id',
            'paImanageSubPracticeAreaId' => 'nullable|exists:imanage_sub_practice_areas,id',
            'paCustomFieldConfigId'      => 'nullable|exists:imanage_custom_field_configs,id',
        ]);

        PracticeAreaMapping::create([
            'tenant_id'                    => $this->tenant->id,
            'clio_practice_area_id'        => $this->paClioPracticeAreaId,
            'imanage_practice_area_id'     => $this->paImanagePracticeAreaId,
            'imanage_sub_practice_area_id' => $this->paImanageSubPracticeAreaId ?: null,
            'imanage_custom_field_config_id' => $this->paCustomFieldConfigId ?: null,
        ]);

        $this->resetPaMappingForm();
        $this->dispatch('close-modal', name: 'add-pa-mapping');
    }

    public function deletePracticeAreaMapping(int $id): void
    {
        PracticeAreaMapping::where('tenant_id', $this->tenant->id)->findOrFail($id)->delete();
    }

    private function resetPaMappingForm(): void
    {
        $this->paClioPracticeAreaId       = '';
        $this->paImanagePracticeAreaId    = '';
        $this->paImanageSubPracticeAreaId = '';
        $this->paCustomFieldConfigId      = '';
    }

    // -------------------------------------------------------------------------
    // Template Mappings
    // -------------------------------------------------------------------------

    public function addTemplateMapping(): void
    {
        $this->validate([
            'tmClioPracticeAreaId' => 'required|exists:clio_practice_areas,id',
            'tmImanageTemplateId'  => 'required|exists:imanage_templates,id',
        ]);

        TemplateMapping::create([
            'tenant_id'              => $this->tenant->id,
            'clio_practice_area_id'  => $this->tmClioPracticeAreaId,
            'imanage_template_id'    => $this->tmImanageTemplateId,
        ]);

        $this->tmClioPracticeAreaId = '';
        $this->tmImanageTemplateId  = '';
        $this->dispatch('close-modal', name: 'add-template-mapping');
    }

    public function deleteTemplateMapping(int $id): void
    {
        TemplateMapping::where('tenant_id', $this->tenant->id)->findOrFail($id)->delete();
    }

    // -------------------------------------------------------------------------
    // Group Mappings
    // -------------------------------------------------------------------------

    public function addGroupMapping(): void
    {
        $this->validate([
            'gmClioGroupId'    => 'required|exists:clio_groups,id',
            'gmImanageGroupId' => 'required|exists:imanage_groups,id',
        ]);

        GroupMapping::create([
            'tenant_id'       => $this->tenant->id,
            'clio_group_id'   => $this->gmClioGroupId,
            'imanage_group_id' => $this->gmImanageGroupId,
        ]);

        $this->gmClioGroupId    = '';
        $this->gmImanageGroupId = '';
        $this->dispatch('close-modal', name: 'add-group-mapping');
    }

    public function deleteGroupMapping(int $id): void
    {
        GroupMapping::where('tenant_id', $this->tenant->id)->findOrFail($id)->delete();
    }

    // -------------------------------------------------------------------------
    // User Mappings
    // -------------------------------------------------------------------------

    public function addUserMapping(): void
    {
        $this->validate([
            'umClioUserId'    => 'required|exists:clio_users,id',
            'umImanageUserId' => 'required|exists:imanage_users,id',
        ]);

        UserMapping::create([
            'tenant_id'       => $this->tenant->id,
            'clio_user_id'    => $this->umClioUserId,
            'imanage_user_id' => $this->umImanageUserId,
        ]);

        $this->umClioUserId    = '';
        $this->umImanageUserId = '';
        $this->dispatch('close-modal', name: 'add-user-mapping');
    }

    public function deleteUserMapping(int $id): void
    {
        UserMapping::where('tenant_id', $this->tenant->id)->findOrFail($id)->delete();
    }

    // -------------------------------------------------------------------------
    // iManage Custom Field Configs
    // -------------------------------------------------------------------------

    public function addCustomFieldConfig(): void
    {
        $this->validate([
            'cfIdentifier'  => 'required|string|max:255',
            'cfDescription' => 'nullable|string|max:255',
        ]);

        ImanageCustomFieldConfig::updateOrCreate(
            [
                'tenant_id'               => $this->tenant->id,
                'custom_field_identifier' => $this->cfIdentifier,
            ],
            [
                'description' => $this->cfDescription ?: null,
            ]
        );

        $this->cfIdentifier  = '';
        $this->cfDescription = '';
        $this->dispatch('close-modal', name: 'add-custom-field-config');
    }

    public function deleteCustomFieldConfig(int $id): void
    {
        ImanageCustomFieldConfig::where('tenant_id', $this->tenant->id)->findOrFail($id)->delete();
    }

    // -------------------------------------------------------------------------
    // Computed — dropdown data (all scoped to this tenant)
    // -------------------------------------------------------------------------

    #[Computed]
    public function libraries(): Collection
    {
        return Library::where('tenant_id', $this->tenant->id)->orderBy('name')->get();
    }

    #[Computed]
    public function imanageTemplates(): Collection
    {
        return ImanageTemplate::where('tenant_id', $this->tenant->id)->orderBy('description')->get();
    }

    #[Computed]
    public function clioPracticeAreas(): Collection
    {
        return ClioPracticeArea::where('tenant_id', $this->tenant->id)->orderBy('name')->get();
    }

    #[Computed]
    public function imanagePracticeAreas(): Collection
    {
        return ImanagePracticeArea::where('tenant_id', $this->tenant->id)->orderBy('description')->get();
    }

    #[Computed]
    public function subPracticeAreas(): Collection
    {
        if (! $this->paImanagePracticeAreaId) {
            return collect();
        }

        return ImanageSubPracticeArea::where('imanage_practice_area_id', $this->paImanagePracticeAreaId)
            ->orderBy('description')
            ->get();
    }

    #[Computed]
    public function customFieldConfigs(): Collection
    {
        return ImanageCustomFieldConfig::where('tenant_id', $this->tenant->id)
            ->orderBy('custom_field_identifier')
            ->get();
    }

    #[Computed]
    public function clioGroups(): Collection
    {
        return ClioGroup::where('tenant_id', $this->tenant->id)->orderBy('name')->get();
    }

    #[Computed]
    public function imanageGroups(): Collection
    {
        return ImanageGroup::where('tenant_id', $this->tenant->id)->orderBy('name')->get();
    }

    #[Computed]
    public function clioUsers(): Collection
    {
        return ClioUser::where('tenant_id', $this->tenant->id)->orderBy('name')->get();
    }

    #[Computed]
    public function imanageUsers(): Collection
    {
        return ImanageUser::where('tenant_id', $this->tenant->id)->orderBy('full_name')->get();
    }

    #[Computed]
    public function practiceAreaMappings(): Collection
    {
        return $this->tenant->practiceAreaMappings()
            ->with(['clioPracticeArea', 'imanagePracticeArea', 'imanageSubPracticeArea', 'imanageCustomFieldConfig'])
            ->get();
    }

    #[Computed]
    public function templateMappings(): Collection
    {
        return $this->tenant->templateMappings()
            ->with(['clioPracticeArea', 'imanageTemplate'])
            ->get();
    }

    #[Computed]
    public function groupMappings(): Collection
    {
        return $this->tenant->groupMappings()
            ->with(['clioGroup', 'imanageGroup'])
            ->get();
    }

    #[Computed]
    public function userMappings(): Collection
    {
        return $this->tenant->userMappings()
            ->with(['clioUser', 'imanageUser'])
            ->get();
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return view('livewire.admin.tenants.config');
    }
}
