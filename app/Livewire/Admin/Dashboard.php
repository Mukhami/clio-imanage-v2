<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\ProcessingStage;
use App\Models\Tenant;
use App\Models\WebhookRequest;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $this->stats = [
            'active_tenants'                  => Tenant::active()->count(),
            'webhook_requests_today'          => WebhookRequest::whereDate('created_at', today())->count(),
            'webhook_requests_failed_today'   => WebhookRequest::whereDate('created_at', today())
                ->where('processing_stage', ProcessingStage::Failed->value)->count(),
            'webhook_requests_completed_today' => WebhookRequest::whereDate('created_at', today())
                ->where('processing_stage', ProcessingStage::Completed->value)->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
