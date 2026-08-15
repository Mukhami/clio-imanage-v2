<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Enums\ProcessingStage;
use App\Models\WebhookRequest;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];
    public array $recentRequests = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $tenantId = auth()->user()->tenant_id;

        $this->stats = [
            'received_today'  => WebhookRequest::whereDate('created_at', today())
                ->where('tenant_id', $tenantId)
                ->count(),
            'completed_today' => WebhookRequest::whereDate('created_at', today())
                ->where('tenant_id', $tenantId)
                ->where('processing_stage', ProcessingStage::Completed->value)
                ->count(),
            'failed_today'    => WebhookRequest::whereDate('created_at', today())
                ->where('tenant_id', $tenantId)
                ->where('processing_stage', ProcessingStage::Failed->value)
                ->count(),
            'pending_today'   => WebhookRequest::whereDate('created_at', today())
                ->where('tenant_id', $tenantId)
                ->whereNotIn('processing_stage', [
                    ProcessingStage::Completed->value,
                    ProcessingStage::Failed->value,
                    ProcessingStage::Skipped->value,
                ])
                ->count(),
        ];

        $this->recentRequests = WebhookRequest::where('tenant_id', $tenantId)
            ->select([
                'id', 'correlation_id', 'processing_stage',
                'retrieved_client_id', 'retrieved_matter_id', 'created_at',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.portal.dashboard');
    }
}
