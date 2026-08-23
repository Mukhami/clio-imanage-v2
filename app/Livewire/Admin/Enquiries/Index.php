<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Enquiries;

use App\Models\Enquiry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    #[Computed]
    public function enquiries(): LengthAwarePaginator
    {
        return Enquiry::query()
            ->when($this->search, fn ($q) => $q->where(function ($inner) {
                $inner->where('firm_name', 'like', '%' . $this->search . '%')
                      ->orWhere('contact_name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);
    }

    public function render(): View
    {
        return view('livewire.admin.enquiries.index');
    }
}
