<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\ProcessingStage;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\WebhookRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    #[Computed]
    public function recentWebhookRequests(): Collection
    {
        return WebhookRequest::with('tenant:id,name')
            ->select([
                'id', 'tenant_id', 'correlation_id', 'processing_stage',
                'retrieved_client_id', 'retrieved_matter_id', 'created_at',
            ])
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function recentFailures(): Collection
    {
        return WebhookRequest::with('tenant:id,name')
            ->select(['id', 'tenant_id', 'correlation_id', 'processing_stage', 'error_message', 'created_at'])
            ->where('processing_stage', ProcessingStage::Failed)
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function tenantsNeedingAttention(): Collection
    {
        return Tenant::active()
            ->where('password_authentication', false)
            ->whereDoesntHave('clioOAuthAccessTokens', fn ($q) =>
                $q->where('revoked', false)->where('access_expires_at', '>', now())
            )
            ->select(['id', 'name', 'slug'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function expiringSubscriptions(): Collection
    {
        return TenantSubscription::with('tenant:id,name')
            ->expiringWithin(30)
            ->orderBy('end_date')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function loadStats(): void
    {
        Cache::forget('admin.dashboard.stats');

        $this->stats = Cache::remember('admin.dashboard.stats', 60, function () {
            // Single query for tenant counts
            $tenantRow = DB::table('tenants')
                ->whereNull('deleted_at')
                ->selectRaw("
                    SUM(CASE WHEN status = 'active'  THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                ")
                ->first();

            // Single query for today's webhook counts
            $webhookToday = DB::table('webhook_requests')
                ->whereDate('created_at', today())
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN processing_stage = ? THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN processing_stage = ? THEN 1 ELSE 0 END) as failed
                ", [ProcessingStage::Completed->value, ProcessingStage::Failed->value])
                ->first();

            return [
                'active_tenants'  => (int) ($tenantRow->active  ?? 0),
                'pending_tenants' => (int) ($tenantRow->pending ?? 0),

                'tenants_with_active_subscriptions' => TenantSubscription::active()
                    ->distinct('tenant_id')
                    ->count('tenant_id'),

                'webhook_requests_today'           => (int) ($webhookToday->total     ?? 0),
                'webhook_requests_completed_today' => (int) ($webhookToday->completed ?? 0),
                'webhook_requests_failed_today'    => (int) ($webhookToday->failed    ?? 0),

                'webhook_requests_pending' => WebhookRequest::whereNotIn('processing_stage', [
                    ProcessingStage::Completed->value,
                    ProcessingStage::Failed->value,
                    ProcessingStage::Skipped->value,
                ])->count(),
            ];
        });

        unset($this->recentWebhookRequests, $this->recentFailures, $this->tenantsNeedingAttention, $this->expiringSubscriptions);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
