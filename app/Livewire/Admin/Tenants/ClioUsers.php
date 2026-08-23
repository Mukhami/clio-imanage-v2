<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Jobs\SyncClioData;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ClioUsers extends Component
{
    use WithPagination;

    public Tenant $tenant;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filter = 'all'; // all | enabled | disabled

    public function mount(int $id): void
    {
        $this->tenant = Tenant::findOrFail($id);
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return $this->tenant->clioUsers()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('initials', 'like', "%{$this->search}%");
            }))
            ->when($this->filter === 'enabled', fn ($q) => $q->where('enabled', true))
            ->when($this->filter === 'disabled', fn ($q) => $q->where('enabled', false))
            ->orderBy('name')
            ->paginate(25);
    }

    #[Computed]
    public function counts(): array
    {
        // Single query for both totals instead of two separate count() calls
        $row = $this->tenant->clioUsers()
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END) as enabled_count")
            ->first();

        return [
            'total'   => (int) ($row->total         ?? 0),
            'enabled' => (int) ($row->enabled_count ?? 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function syncUsers(): void
    {
        SyncClioData::dispatch($this->tenant->id);
        session()->flash('success', 'Clio user sync queued. Refresh in a moment.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.clio-users');
    }
}
