<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Enums\ProcessingStage;
use App\Models\WebhookRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        Cache::forget("portal.dashboard.stats.{$tenantId}");

        $this->stats = Cache::remember("portal.dashboard.stats.{$tenantId}", 60, function () use ($tenantId) {
            // Single query for all today's stage counts
            $row = DB::table('webhook_requests')
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', today())
                ->selectRaw("
                    COUNT(*) as received_today,
                    SUM(CASE WHEN processing_stage = ? THEN 1 ELSE 0 END) as completed_today,
                    SUM(CASE WHEN processing_stage = ? THEN 1 ELSE 0 END) as failed_today,
                    SUM(CASE WHEN processing_stage NOT IN (?,?,?) THEN 1 ELSE 0 END) as pending_today
                ", [
                    ProcessingStage::Completed->value,
                    ProcessingStage::Failed->value,
                    ProcessingStage::Completed->value,
                    ProcessingStage::Failed->value,
                    ProcessingStage::Skipped->value,
                ])
                ->first();

            return [
                'received_today'  => (int) ($row->received_today  ?? 0),
                'completed_today' => (int) ($row->completed_today ?? 0),
                'failed_today'    => (int) ($row->failed_today    ?? 0),
                'pending_today'   => (int) ($row->pending_today   ?? 0),
            ];
        });

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
