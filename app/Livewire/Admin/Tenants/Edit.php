<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Models\ClioLocation;
use App\Models\Tenant;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Edit extends Component
{
    public Tenant $tenant;

    public string $name = '';

    public string $slug = '';

    public string $reference = '';

    public string $status = 'pending';

    public string $clioLocationId = '';

    public string $clioAppId = '';

    public string $clioAppSecret = '';

    public string $imanageCloudUrl = '';

    public string $imanageCustomerId = '';

    public string $imanageAppId = '';

    public string $imanageAppSecret = '';

    public string $imanageUsername = '';

    public string $imanagePassword = '';

    public bool $passwordAuthentication = false;

    public bool $hasGroupSecurityMapping = false;

    public bool $enableWorkspaceLinkCustomField = false;

    public function mount(int $id): void
    {
        $this->tenant = Tenant::with('clioLocation')->findOrFail($id);

        $this->name            = $this->tenant->name;
        $this->slug            = $this->tenant->slug;
        $this->reference       = $this->tenant->reference ?? '';
        $this->status          = $this->tenant->status->value;
        $this->clioLocationId  = (string) ($this->tenant->clio_location_id ?? '');
        $this->imanageCloudUrl = $this->tenant->imanage_cloud_url ?? '';
        $this->imanageCustomerId = $this->tenant->imanage_customer_id ?? '';

        // Encrypted credential fields — leave blank so user must re-enter to change
        $this->clioAppId      = '';
        $this->clioAppSecret  = '';
        $this->imanageAppId   = '';
        $this->imanageAppSecret = '';
        $this->imanageUsername = '';
        $this->imanagePassword = '';

        $this->passwordAuthentication          = (bool) $this->tenant->password_authentication;
        $this->hasGroupSecurityMapping         = (bool) $this->tenant->has_group_security_mapping;
        $this->enableWorkspaceLinkCustomField  = (bool) $this->tenant->enable_workspace_link_custom_field;
    }

    public function save(): void
    {
        $this->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'required|string|max:255|unique:tenants,slug,' . $this->tenant->id,
            'reference'         => 'nullable|uuid|unique:tenants,reference,' . $this->tenant->id,
            'status'            => 'required|in:active,pending,suspended,archived',
            'clioLocationId'    => 'required|exists:clio_locations,id',
            'imanageCloudUrl'   => 'nullable|url',
            'clioAppId'         => 'nullable|string',
            'clioAppSecret'     => 'nullable|string',
            'imanageCustomerId' => 'nullable|string',
            'imanageAppId'      => 'nullable|string',
            'imanageAppSecret'  => 'nullable|string',
            'imanageUsername'   => 'nullable|string',
            'imanagePassword'   => 'nullable|string',
        ]);

        $data = [
            'name'                             => $this->name,
            'slug'                             => $this->slug,
            // keep existing reference if field was cleared — column is NOT NULL
            'reference'                        => $this->reference ?: $this->tenant->reference,
            'status'                           => $this->status,
            'clio_location_id'                 => $this->clioLocationId,
            'imanage_cloud_url'                => $this->imanageCloudUrl ?: null,
            'imanage_customer_id'              => $this->imanageCustomerId ?: null,
            'password_authentication'          => $this->passwordAuthentication,
            'has_group_security_mapping'       => $this->hasGroupSecurityMapping,
            'enable_workspace_link_custom_field' => $this->enableWorkspaceLinkCustomField,
        ];

        if ($this->clioAppId !== '') {
            $data['clio_app_id'] = $this->clioAppId;
        }

        if ($this->clioAppSecret !== '') {
            $data['clio_app_secret'] = $this->clioAppSecret;
        }

        if ($this->imanageAppId !== '') {
            $data['imanage_app_id'] = $this->imanageAppId;
        }

        if ($this->imanageAppSecret !== '') {
            $data['imanage_app_secret'] = $this->imanageAppSecret;
        }

        if ($this->imanageUsername !== '') {
            $data['imanage_username'] = $this->imanageUsername;
        }

        if ($this->imanagePassword !== '') {
            $data['imanage_password'] = $this->imanagePassword;
        }

        $this->tenant->update($data);

        session()->flash('success', 'Tenant updated successfully.');

        $this->redirect(route('admin.tenants.show', $this->tenant->id), navigate: true);
    }

    #[Computed]
    public function clioLocations()
    {
        return ClioLocation::orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.edit');
    }
}
