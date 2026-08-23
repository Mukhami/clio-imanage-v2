<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Jobs\SyncImanageLibraries;
use App\Models\Library;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ImanageData extends Component
{
    public Tenant $tenant;

    public ?int $selectedLibraryId = null;

    public function mount(int $id): void
    {
        $this->tenant = Tenant::findOrFail($id);
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    #[Computed]
    public function libraries(): Collection
    {
        return $this->tenant->libraries()
            ->withCount(['imanageTemplates', 'imanagePracticeAreas', 'imanageUsers', 'imanageGroups'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedLibrary(): ?Library
    {
        if (! $this->selectedLibraryId) {
            return null;
        }

        return $this->tenant->libraries()->find($this->selectedLibraryId);
    }

    #[Computed]
    public function templates(): Collection
    {
        if (! $this->selectedLibraryId) {
            return collect();
        }

        return $this->tenant->libraries()
            ->findOrFail($this->selectedLibraryId)
            ->imanageTemplates()
            ->orderBy('description')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function syncData(): void
    {
        SyncImanageLibraries::dispatch($this->tenant->id, chainDataSync: true);
        session()->flash('success', 'iManage libraries + data sync queued.');
    }

    public function selectLibrary(int $libraryId): void
    {
        $this->selectedLibraryId = ($this->selectedLibraryId === $libraryId) ? null : $libraryId;
        unset($this->selectedLibrary, $this->templates);
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.imanage-data');
    }
}
