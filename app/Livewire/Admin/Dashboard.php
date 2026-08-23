<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\ProcessingStage;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\WebhookRequest;
use Illuminate\Support\Collection;
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
        return WebhookRequest::with('tenant')
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
        return WebhookRequest::with('tenant')
            ->select(['id', 'tenant_id', 'correlation_id', 'processing_stage', 'error_message', 'created_at'])
            ->where('processing_stage', ProcessingStage::Failed)
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function tenantsNeedingAttention(): Collection
    {
        // Active tenants with no valid Clio OAuth token and not using password auth
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
        $this->stats = [
            // Tenants
            'active_tenants'  => Tenant::active()->count(),
            'pending_tenants' => Tenant::byStatus(TenantStatus::Pending)->count(),

            // Subscriptions
            'tenants_with_active_subscriptions' => TenantSubscription::active()
                ->distinct('tenant_id')
                ->count('tenant_id'),

            // Webhooks — today
            'webhook_requests_today'           => WebhookRequest::whereDate('created_at', today())->count(),
            'webhook_requests_completed_today' => WebhookRequest::whereDate('created_at', today())
                ->where('processing_stage', ProcessingStage::Completed->value)
                ->count(),
            'webhook_requests_failed_today'    => WebhookRequest::whereDate('created_at', today())
                ->where('processing_stage', ProcessingStage::Failed->value)
                ->count(),
            'webhook_requests_pending'         => WebhookRequest::whereNotIn('processing_stage', [
                ProcessingStage::Completed->value,
                ProcessingStage::Failed->value,
                ProcessingStage::Skipped->value,
            ])->count(),
        ];

        // Clear computed caches so they reload with fresh data
        unset($this->recentWebhookRequests, $this->recentFailures, $this->tenantsNeedingAttention, $this->expiringSubscriptions);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
