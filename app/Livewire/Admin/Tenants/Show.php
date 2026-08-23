<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Jobs\SyncClioData;
use App\Jobs\SyncImanageLibraries;
use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookType;
use App\Services\ClioApiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Tenant $tenant;

    public function mount(int $id): void
    {
        $this->tenant = Tenant::with([
            'clioLocation',
            'tenantSubscriptions'     => fn ($q) => $q->latest()->limit(1),
            'clioOAuthAccessTokens'   => fn ($q) => $q->where('revoked', false),
            'imanageOAuthAccessTokens'=> fn ($q) => $q->where('revoked', false),
            'webhooks.webhookType',
        ])->findOrFail($id);
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    #[Computed]
    public function clioConnected(): bool
    {
        return $this->tenant->clioOAuthAccessTokens
            ->where('access_expires_at', '>', now())
            ->isNotEmpty();
    }

    #[Computed]
    public function imanageConnected(): bool
    {
        return $this->tenant->imanageOAuthAccessTokens
            ->where('expires_at', '>', now())
            ->isNotEmpty();
    }

    #[Computed]
    public function webhookTypes(): Collection
    {
        return WebhookType::orderBy('id')->get();
    }

    #[Computed]
    public function tenantWebhooks(): Collection
    {
        return $this->tenant->webhooks->sortBy('webhook_type_id');
    }

    // -------------------------------------------------------------------------
    // Force Sync
    // -------------------------------------------------------------------------

    public function syncClioData(): void
    {
        SyncClioData::dispatch($this->tenant->id);
        session()->flash('success', 'Clio data sync queued.');
    }

    public function syncImanageData(): void
    {
        SyncImanageLibraries::dispatch($this->tenant->id, chainDataSync: true);
        session()->flash('success', 'iManage library + data sync queued.');
    }

    // -------------------------------------------------------------------------
    // Webhook Management
    // -------------------------------------------------------------------------

    public function registerWebhook(int $webhookTypeId): void
    {
        $webhookType = WebhookType::findOrFail($webhookTypeId);

        $existing = $this->tenant->webhooks()
            ->where('webhook_type_id', $webhookTypeId)
            ->where('status', 'active')
            ->exists();

        if ($existing) {
            session()->flash('error', "An active {$webhookType->name} webhook is already registered.");
            return;
        }

        try {
            $clio         = new ClioApiService($this->tenant);
            $sharedSecret = Str::random(64);
            $url          = route('webhook.receive', $this->tenant->reference);

            $response = $clio->createWebhook([
                'data' => [
                    'url'           => $url,
                    'model'         => $webhookType->model,
                    'events'        => [$webhookType->event],
                    'shared_secret' => $sharedSecret,
                ],
            ]);

            $data = $response['data'] ?? [];

            Webhook::create([
                'tenant_id'       => $this->tenant->id,
                'clio_id'         => $data['id'],
                'webhook_type_id' => $webhookTypeId,
                'url'             => $data['url'],
                'shared_secret'   => $sharedSecret,
                'status'          => $data['status'] ?? 'active',
                'expires_at'      => isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null,
                'etag'            => $data['etag'] ?? null,
            ]);

            session()->flash('success', "{$webhookType->name} webhook registered.");
        } catch (\Throwable $e) {
            Log::error("Failed to register webhook for tenant {$this->tenant->id}: {$e->getMessage()}");
            session()->flash('error', 'Failed to register webhook: ' . $e->getMessage());
        }

        unset($this->tenantWebhooks);
    }

    public function deleteWebhook(int $webhookId): void
    {
        $webhook = $this->tenant->webhooks()->findOrFail($webhookId);

        try {
            $clio = new ClioApiService($this->tenant);
            $clio->deleteWebhook($webhook->clio_id);
        } catch (\Throwable $e) {
            Log::warning("Failed to delete webhook {$webhook->clio_id} from Clio: {$e->getMessage()}");
        }

        $webhook->delete();
        session()->flash('success', 'Webhook deleted.');

        unset($this->tenantWebhooks);
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.show');
    }
}
