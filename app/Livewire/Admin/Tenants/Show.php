<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenants;

use App\Jobs\SyncClioData;
use App\Jobs\SyncImanageLibraries;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookType;
use App\Notifications\UserInvited;
use App\Services\ClioApiService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Show extends Component
{
    public Tenant $tenant;

    // -------------------------------------------------------------------------
    // Invite modal state
    // -------------------------------------------------------------------------

    public bool $showInviteModal = false;

    #[Validate('required|string|max:255')]
    public string $inviteName = '';

    #[Validate('required|email')]
    public string $inviteEmail = '';

    #[Validate('required|in:Tenant Admin,Tenant Viewer')]
    public string $inviteRole = 'Tenant Admin';

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
        Flux::toast(text: 'Clio data sync queued.', variant: 'success');
    }

    public function syncImanageData(): void
    {
        SyncImanageLibraries::dispatch($this->tenant->id, chainDataSync: true);
        Flux::toast(text: 'iManage library + data sync queued.', variant: 'success');
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
            Flux::toast(text: "An active {$webhookType->name} webhook is already registered.", variant: 'danger');
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

            Flux::toast(text: "{$webhookType->name} webhook registered.", variant: 'success');
        } catch (\Throwable $e) {
            Log::error("Failed to register webhook for tenant {$this->tenant->id}: {$e->getMessage()}");
            Flux::toast(text: 'Failed to register webhook: ' . $e->getMessage(), variant: 'danger');
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
        Flux::toast(text: 'Webhook deleted.', variant: 'success');

        unset($this->tenantWebhooks);
    }

    // -------------------------------------------------------------------------
    // Portal Users
    // -------------------------------------------------------------------------

    #[Computed]
    public function tenantUsers(): Collection
    {
        return User::where('tenant_id', $this->tenant->id)
            ->with('roles')
            ->orderBy('name')
            ->get();
    }

    public function openInviteModal(): void
    {
        $this->reset(['inviteName', 'inviteEmail']);
        $this->inviteRole = 'Tenant Admin';
        $this->showInviteModal = true;
    }

    public function inviteUser(): void
    {
        $this->validateOnly('inviteName');
        $this->validateOnly('inviteRole');
        $this->validate([
            'inviteEmail' => 'required|email|unique:users,email',
        ]);

        $user = User::create([
            'name'              => $this->inviteName,
            'email'             => $this->inviteEmail,
            'password'          => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
        ]);

        $user->assignRole($this->inviteRole);

        $token    = Password::createToken($user);
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));

        $user->notify(new UserInvited($this->tenant, $resetUrl));

        $this->showInviteModal = false;
        $this->reset(['inviteName', 'inviteEmail', 'inviteRole']);

        unset($this->tenantUsers);

        Flux::toast(text: "{$user->name} has been invited to the portal.", variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.tenants.show', [
            'tenantUsers' => $this->tenantUsers,
        ]);
    }
}
