<div>
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">{{ session('success') }}</flux:callout>
    @endif
    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle" class="mb-4">{{ session('error') }}</flux:callout>
    @endif

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $tenant->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ $tenant->name }}</flux:heading>
        <flux:button href="{{ route('admin.tenants.edit', $tenant->id) }}" size="sm" wire:navigate>Edit</flux:button>
    </div>

    {{-- Overview --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Overview</flux:heading>
        </div>
        <div>
            @php
                $statusColor = match($tenant->status->value) {
                    'active'    => 'green',
                    'pending'   => 'yellow',
                    'suspended' => 'red',
                    default     => 'zinc',
                };
            @endphp
            <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Name</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $tenant->name }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Slug</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white font-mono">{{ $tenant->slug }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Reference</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white font-mono">{{ $tenant->reference ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Status</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">
                        <flux:badge :color="$statusColor" size="sm">{{ $tenant->status->value }}</flux:badge>
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Clio Location</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $tenant->clioLocation?->name ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Onboarded At</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $tenant->onboarded_at?->format('d M Y H:i') ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Created At</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $tenant->created_at->format('d M Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Settings --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Settings</flux:heading>
        </div>
        <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-3">
            <div class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-full {{ $tenant->password_authentication ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                <flux:text>Password Auth</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-full {{ $tenant->has_group_security_mapping ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                <flux:text>Group Security Mapping</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-full {{ $tenant->enable_workspace_link_custom_field ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                <flux:text>Workspace Link Custom Field</flux:text>
            </div>
        </div>
    </div>

    {{-- OAuth Connections --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">OAuth Connections</flux:heading>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            {{-- Clio --}}
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="h-2.5 w-2.5 rounded-full {{ $this->clioConnected ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                    <div>
                        <flux:text class="font-medium text-zinc-900 dark:text-white">Clio</flux:text>
                        <flux:text class="text-xs text-zinc-500">{{ $this->clioConnected ? 'Active token on record' : 'Not connected' }}</flux:text>
                    </div>
                </div>
                <flux:button
                    size="sm"
                    variant="{{ $this->clioConnected ? 'ghost' : 'primary' }}"
                    href="{{ route('admin.tenants.clio.authorize', $tenant->id) }}"
                >
                    {{ $this->clioConnected ? 'Re-authorise' : 'Authorise with Clio' }}
                </flux:button>
            </div>
            {{-- iManage --}}
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="h-2.5 w-2.5 rounded-full {{ $this->imanageConnected ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                    <div>
                        <flux:text class="font-medium text-zinc-900 dark:text-white">iManage</flux:text>
                        <flux:text class="text-xs text-zinc-500">
                            @if ($tenant->password_authentication)
                                Password authentication — OAuth not required
                            @elseif ($this->imanageConnected)
                                Active token on record
                            @else
                                Not connected
                            @endif
                        </flux:text>
                    </div>
                </div>
                @if (! $tenant->password_authentication)
                    <flux:button
                        size="sm"
                        variant="{{ $this->imanageConnected ? 'ghost' : 'primary' }}"
                        href="{{ route('admin.tenants.imanage.authorize', $tenant->id) }}"
                    >
                        {{ $this->imanageConnected ? 'Re-authorise' : 'Authorise with iManage' }}
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- Webhooks --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Webhooks</flux:heading>
            <flux:text class="text-xs text-zinc-400 font-mono">{{ route('webhook.receive', $tenant->reference) }}</flux:text>
        </div>
        @if ($this->webhookTypes->isEmpty())
            <div class="px-6 py-4">
                <flux:text class="text-zinc-400">No webhook types configured. Run the webhook types seeder.</flux:text>
            </div>
        @else
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Clio ID</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->webhookTypes as $type)
                        @php
                            $webhook = $this->tenantWebhooks->firstWhere('webhook_type_id', $type->id);
                            $statusColor = match($webhook?->status?->value ?? '') {
                                'active'  => 'green',
                                'expired' => 'yellow',
                                'failed'  => 'red',
                                default   => 'zinc',
                            };
                        @endphp
                        <tr>
                            <td class="px-6 py-3 text-sm text-zinc-900 dark:text-white">{{ $type->name }}</td>
                            <td class="px-6 py-3 text-sm">
                                @if ($webhook)
                                    <flux:badge :color="$statusColor" size="sm">{{ $webhook->status->value }}</flux:badge>
                                @else
                                    <span class="text-zinc-400 text-xs">Not registered</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-zinc-500">
                                {{ $webhook?->expires_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-3 text-sm font-mono text-zinc-500">
                                {{ $webhook?->clio_id ?? '—' }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if ($webhook)
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        wire:click="deleteWebhook({{ $webhook->id }})"
                                        wire:confirm="Delete this webhook from Clio and remove it from the system?"
                                    >
                                        Delete
                                    </flux:button>
                                @else
                                    <flux:button
                                        size="xs"
                                        wire:click="registerWebhook({{ $type->id }})"
                                    >
                                        Register
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Force Sync --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Force Sync</flux:heading>
        </div>
        <div class="flex items-center gap-3 p-4">
            <flux:button
                variant="ghost"
                size="sm"
                wire:click="syncClioData"
                wire:confirm="Dispatch a full Clio data sync (users, groups, practice areas) for this tenant?"
            >
                Sync Clio Data
            </flux:button>
            <flux:button
                variant="ghost"
                size="sm"
                wire:click="syncImanageData"
                wire:confirm="Dispatch a full iManage libraries + data sync for this tenant?"
            >
                Sync iManage Data
            </flux:button>
        </div>
    </div>

    {{-- Manage --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Manage</flux:heading>
        </div>
        <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-3 lg:grid-cols-4">
            <flux:button variant="ghost" href="{{ route('admin.tenants.config', $tenant->id) }}" wire:navigate size="sm" class="justify-start">
                Configuration
            </flux:button>
            <flux:button variant="ghost" href="{{ route('admin.tenants.advanced-config', $tenant->id) }}" wire:navigate size="sm" class="justify-start">
                Advanced Config
            </flux:button>
            <flux:button variant="ghost" href="{{ route('admin.tenants.sequence-config', $tenant->id) }}" wire:navigate size="sm" class="justify-start">
                Sequence Config
            </flux:button>
            <flux:button variant="ghost" href="{{ route('admin.tenants.subscriptions', $tenant->id) }}" wire:navigate size="sm" class="justify-start">
                Subscriptions
            </flux:button>
            <flux:button variant="ghost" href="{{ route('admin.tenants.clio-users', $tenant->id) }}" wire:navigate size="sm" class="justify-start">
                Clio Users
            </flux:button>
            <flux:button variant="ghost" href="{{ route('admin.tenants.imanage-data', $tenant->id) }}" wire:navigate size="sm" class="justify-start">
                iManage Data
            </flux:button>
        </div>
    </div>

    {{-- Subscription --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Subscription</flux:heading>
            <flux:button size="xs" variant="ghost" href="{{ route('admin.tenants.subscriptions', $tenant->id) }}" wire:navigate>Manage</flux:button>
        </div>
        @php $subscription = $tenant->tenantSubscriptions->first() @endphp
        @if ($subscription)
            <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Status</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $subscription->status->value }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Expires At</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $subscription->expires_at?->format('d M Y') ?? '—' }}</dd>
                </div>
            </dl>
        @else
            <div class="px-6 py-4">
                <flux:text class="text-zinc-400">No subscription on record.</flux:text>
            </div>
        @endif
    </div>
</div>
