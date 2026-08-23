<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\ClioApiService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Subscriptions extends Component
{
    use WithPagination;

    public Tenant $tenant;

    // -------------------------------------------------------------------------
    // Add subscription form
    // -------------------------------------------------------------------------

    public string $reference   = '';
    public string $planType    = '';
    public string $startDate   = '';
    public string $endDate     = '';
    public string $notes       = '';

    public function mount(int $id): void
    {
        $this->tenant    = Tenant::with('clioLocation')->findOrFail($id);
        $this->resetForm();
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    #[Computed]
    public function subscriptions(): LengthAwarePaginator
    {
        return $this->tenant->tenantSubscriptions()
            ->with('voidedBy')
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function addSubscription(): void
    {
        $this->validate([
            'reference' => ['required', 'string', 'max:255', 'unique:tenant_subscriptions,reference'],
            'planType'  => ['nullable', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate'   => ['required', 'date', 'after:startDate'],
            'notes'     => ['nullable', 'string'],
        ]);

        // Void any currently active subscriptions for this tenant
        $this->tenant->tenantSubscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->each(function (TenantSubscription $sub) {
                $sub->update([
                    'status'    => SubscriptionStatus::Void,
                    'voided_at' => now(),
                    'voided_by' => Auth::id(),
                ]);
            });

        // Attempt to capture current Clio user count
        $clioUsersAtStart = $this->fetchClioUserCount();

        TenantSubscription::create([
            'tenant_id'          => $this->tenant->id,
            'reference'          => strtoupper(trim($this->reference)),
            'status'             => SubscriptionStatus::Active,
            'plan_type'          => $this->planType ?: null,
            'start_date'         => $this->startDate,
            'end_date'           => $this->endDate,
            'notes'              => $this->notes ?: null,
            'clio_users_at_start' => $clioUsersAtStart,
        ]);

        $this->resetForm();
        $this->dispatch('close-modal', name: 'add-subscription');
        Flux::toast(text: 'Subscription created.', variant: 'success');

        unset($this->subscriptions);
        $this->resetPage();
    }

    public function voidSubscription(int $subscriptionId): void
    {
        $subscription = $this->tenant->tenantSubscriptions()->findOrFail($subscriptionId);

        $subscription->update([
            'status'    => SubscriptionStatus::Void,
            'voided_at' => now(),
            'voided_by' => Auth::id(),
        ]);

        Flux::toast(text: "Subscription {$subscription->reference} voided.", variant: 'success');

        unset($this->subscriptions);
        $this->resetPage();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function fetchClioUserCount(): ?int
    {
        try {
            $hasToken = $this->tenant->clioOAuthAccessTokens()
                ->where('revoked', false)
                ->where('access_expires_at', '>', now())
                ->exists();

            if (! $hasToken) {
                return null;
            }

            $clio  = new ClioApiService($this->tenant);
            $data  = $clio->getUsers();
            $users = $data['data'] ?? [];

            return count($users);
        } catch (\Throwable $e) {
            Log::warning("Could not fetch Clio user count for tenant {$this->tenant->id}: {$e->getMessage()}");

            return null;
        }
    }

    private function resetForm(): void
    {
        $this->reference = 'SUB-' . strtoupper(Str::random(8));
        $this->planType  = '';
        $this->startDate = now()->toDateString();
        $this->endDate   = now()->addYear()->toDateString();
        $this->notes     = '';
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.subscriptions');
    }
}
