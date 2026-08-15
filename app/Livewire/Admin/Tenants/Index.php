<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function tenants(): LengthAwarePaginator
    {
        return Tenant::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('slug', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->with('clioLocation')
            ->orderBy('name')
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.index', [
            'tenants' => $this->tenants(),
        ]);
    }
}
