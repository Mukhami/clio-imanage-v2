<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Models\WebhookRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookActivity extends Component
{
    use WithPagination;

    public string $stageFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $search = '';

    public function updatingStageFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }

    public function webhookRequests(): LengthAwarePaginator
    {
        $tenantId = auth()->user()->tenant_id;

        return WebhookRequest::query()
            ->select([
                'id', 'correlation_id', 'processing_stage',
                'retrieved_client_id', 'retrieved_matter_id',
                'created_at', 'tenant_id',
            ])
            ->where('tenant_id', $tenantId)
            ->when($this->stageFilter, fn ($q) => $q->where('processing_stage', $this->stageFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search, fn ($q) => $q->where(function ($inner) {
                $inner->where('correlation_id', 'like', '%' . $this->search . '%')
                      ->orWhere('retrieved_client_id', 'like', '%' . $this->search . '%')
                      ->orWhere('retrieved_matter_id', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.portal.webhook-activity', [
            'webhookRequests' => $this->webhookRequests(),
        ]);
    }
}
