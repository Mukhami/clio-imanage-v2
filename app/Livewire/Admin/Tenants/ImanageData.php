<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Jobs\SyncImanageLibraries;
use App\Models\Library;
use Flux\Flux;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ImanageData extends Component
{
    use WithPagination;

    public Tenant $tenant;

    public ?int $selectedLibraryId = null;

    public string $activeTab = 'templates';

    public string $search = '';

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
    public function templates(): ?LengthAwarePaginator
    {
        if (! $this->selectedLibraryId) {
            return null;
        }

        return $this->tenant->libraries()
            ->findOrFail($this->selectedLibraryId)
            ->imanageTemplates()
            ->when($this->search, fn ($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->orderBy('description')
            ->paginate(25, pageName: 'templates_page');
    }

    #[Computed]
    public function practiceAreas(): ?LengthAwarePaginator
    {
        if (! $this->selectedLibraryId) {
            return null;
        }

        return $this->tenant->libraries()
            ->findOrFail($this->selectedLibraryId)
            ->imanagePracticeAreas()
            ->when($this->search, fn ($q) => $q->where(fn ($q) =>
                $q->where('key', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
            ))
            ->orderBy('key')
            ->paginate(25, pageName: 'pa_page');
    }

    #[Computed]
    public function users(): ?LengthAwarePaginator
    {
        if (! $this->selectedLibraryId) {
            return null;
        }

        return $this->tenant->libraries()
            ->findOrFail($this->selectedLibraryId)
            ->imanageUsers()
            ->when($this->search, fn ($q) => $q->where(fn ($q) =>
                $q->where('full_name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('imanage_user_id', 'like', "%{$this->search}%")
            ))
            ->orderBy('full_name')
            ->paginate(25, pageName: 'users_page');
    }

    #[Computed]
    public function groups(): ?LengthAwarePaginator
    {
        if (! $this->selectedLibraryId) {
            return null;
        }

        return $this->tenant->libraries()
            ->findOrFail($this->selectedLibraryId)
            ->imanageGroups()
            ->when($this->search, fn ($q) => $q->where(fn ($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('imanage_group_id', 'like', "%{$this->search}%")
            ))
            ->orderBy('name')
            ->paginate(25, pageName: 'groups_page');
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function syncData(): void
    {
        SyncImanageLibraries::dispatch($this->tenant->id, chainDataSync: true);
        Flux::toast(text: 'iManage libraries + data sync queued.', variant: 'success');
    }

    public function selectLibrary(int $libraryId): void
    {
        if ($this->selectedLibraryId === $libraryId) {
            $this->selectedLibraryId = null;
        } else {
            $this->selectedLibraryId = $libraryId;
            $this->activeTab = 'templates';
            $this->search = '';
        }

        $this->resetPage('templates_page');
        $this->resetPage('pa_page');
        $this->resetPage('users_page');
        $this->resetPage('groups_page');

        unset($this->selectedLibrary, $this->templates, $this->practiceAreas, $this->users, $this->groups);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->search = '';

        $this->resetPage('templates_page');
        $this->resetPage('pa_page');
        $this->resetPage('users_page');
        $this->resetPage('groups_page');

        unset($this->templates, $this->practiceAreas, $this->users, $this->groups);
    }

    public function updatedSearch(): void
    {
        match ($this->activeTab) {
            'templates'      => $this->resetPage('templates_page'),
            'practice_areas' => $this->resetPage('pa_page'),
            'users'          => $this->resetPage('users_page'),
            'groups'         => $this->resetPage('groups_page'),
            default          => null,
        };

        unset($this->templates, $this->practiceAreas, $this->users, $this->groups);
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.imanage-data');
    }
}
