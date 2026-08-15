<?php

declare(strict_types=1);

namespace App\Livewire\Admin\WebhookRequests;

use App\Models\Tenant;
use App\Models\WebhookRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $stageFilter = '';
    public string $tenantFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $search = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStageFilter(): void { $this->resetPage(); }
    public function updatingTenantFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function webhookRequests(): LengthAwarePaginator
    {
        return WebhookRequest::query()
            ->with('tenant')
            ->when($this->stageFilter, fn ($q) => $q->where('processing_stage', $this->stageFilter))
            ->when($this->tenantFilter, fn ($q) => $q->where('tenant_id', $this->tenantFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('correlation_id', 'like', '%' . $this->search . '%')
                          ->orWhere('retrieved_client_id', 'like', '%' . $this->search . '%')
                          ->orWhere('retrieved_matter_id', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.admin.webhook-requests.index', [
            'webhookRequests' => $this->webhookRequests(),
            'tenants'         => Tenant::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
