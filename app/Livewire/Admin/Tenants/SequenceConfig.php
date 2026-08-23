<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Models\Tenant;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SequenceConfig extends Component
{
    public Tenant $tenant;

    // Client sequence
    public string $clientPrefix          = '';
    public int    $clientStartNumber     = 1;
    public int    $clientDigits          = 5;
    public string $clientCustomFieldName = '';

    // Matter sequence
    public string $matterPrefix          = '';
    public int    $matterStartNumber     = 1;
    public int    $matterDigits          = 5;
    public string $matterCustomFieldName = '';

    public function mount(int $id): void
    {
        $this->tenant = Tenant::findOrFail($id);
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $cfg = $this->tenant->tenantSequenceConfig;

        if (! $cfg) {
            return;
        }

        $this->clientPrefix          = $cfg->client_prefix ?? '';
        $this->clientStartNumber     = (int) ($cfg->client_start_number ?? 1);
        $this->clientDigits          = (int) ($cfg->client_digits ?? 5);
        $this->clientCustomFieldName = $cfg->client_custom_field_name ?? '';

        $this->matterPrefix          = $cfg->matter_prefix ?? '';
        $this->matterStartNumber     = (int) ($cfg->matter_start_number ?? 1);
        $this->matterDigits          = (int) ($cfg->matter_digits ?? 5);
        $this->matterCustomFieldName = $cfg->matter_custom_field_name ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'clientPrefix'          => 'nullable|string|max:20',
            'clientStartNumber'     => 'required|integer|min:1',
            'clientDigits'          => 'required|integer|min:1|max:20',
            'clientCustomFieldName' => 'nullable|string|max:255',
            'matterPrefix'          => 'nullable|string|max:20',
            'matterStartNumber'     => 'required|integer|min:1',
            'matterDigits'          => 'required|integer|min:1|max:20',
            'matterCustomFieldName' => 'nullable|string|max:255',
        ]);

        $this->tenant->tenantSequenceConfig()->updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'client_prefix'           => $this->clientPrefix ?: null,
                'client_start_number'     => $this->clientStartNumber,
                'client_digits'           => $this->clientDigits,
                'client_custom_field_name' => $this->clientCustomFieldName ?: null,
                'matter_prefix'           => $this->matterPrefix ?: null,
                'matter_start_number'     => $this->matterStartNumber,
                'matter_digits'           => $this->matterDigits,
                'matter_custom_field_name' => $this->matterCustomFieldName ?: null,
            ]
        );

        session()->flash('success', 'Sequence configuration saved.');
    }

    public function deleteConfig(): void
    {
        $this->tenant->tenantSequenceConfig?->delete();

        $this->clientPrefix          = '';
        $this->clientStartNumber     = 1;
        $this->clientDigits          = 5;
        $this->clientCustomFieldName = '';
        $this->matterPrefix          = '';
        $this->matterStartNumber     = 1;
        $this->matterDigits          = 5;
        $this->matterCustomFieldName = '';

        session()->flash('success', 'Sequence configuration deleted.');
    }

    // -------------------------------------------------------------------------
    // Live previews (recompute on every property change)
    // -------------------------------------------------------------------------

    #[Computed]
    public function clientPreview(): string
    {
        $padded = str_pad((string) max(1, $this->clientStartNumber), max(1, $this->clientDigits), '0', STR_PAD_LEFT);
        return ($this->clientPrefix ? $this->clientPrefix . '-' : '') . $padded;
    }

    #[Computed]
    public function matterPreview(): string
    {
        $padded = str_pad((string) max(1, $this->matterStartNumber), max(1, $this->matterDigits), '0', STR_PAD_LEFT);
        return ($this->matterPrefix ? $this->matterPrefix . '-' : '') . $padded;
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.sequence-config');
    }
}
