<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('portal.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6">
        <flux:heading size="xl">Firm Settings</flux:heading>
        <flux:text class="text-neutral-500">Your firm's configuration and integration overview.</flux:text>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Firm Details --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Firm Details</p>
            </div>
            <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="text-sm text-neutral-500">Firm Name</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $tenant?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="text-sm text-neutral-500">Reference</dt>
                    <dd class="font-mono text-xs text-neutral-400">{{ $tenant?->reference ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="text-sm text-neutral-500">Status</dt>
                    <dd>
                        @if ($tenant)
                            @php
                                $statusColor = match($tenant->status->value) {
                                    'active'    => 'green',
                                    'suspended' => 'red',
                                    'archived'  => 'zinc',
                                    default     => 'yellow',
                                };
                            @endphp
                            <flux:badge :color="$statusColor" size="sm">{{ ucfirst($tenant->status->value) }}</flux:badge>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="text-sm text-neutral-500">Onboarded</dt>
                    <dd class="text-sm text-neutral-400">
                        {{ $tenant?->onboarded_at?->format('d M Y') ?? 'Not yet onboarded' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Integration Details --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Clio Integration</p>
            </div>
            <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="text-sm text-neutral-500">Region</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white">
                        {{ $tenant?->clioLocation?->name ?? '—' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="text-sm text-neutral-500">Connection Status</dt>
                    <dd>
                        @php
                            $connected = $tenant?->clioOAuthAccessTokens()->exists() ?? false;
                        @endphp
                        @if ($connected)
                            <flux:badge color="green" size="sm">Connected</flux:badge>
                        @else
                            <flux:badge color="red" size="sm">Not connected</flux:badge>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="text-sm text-neutral-500">iManage Connection</dt>
                    <dd>
                        @php
                            $imanageConnected = $tenant?->imanageOAuthAccessTokens()->exists() ?? false;
                        @endphp
                        @if ($imanageConnected)
                            <flux:badge color="green" size="sm">Connected</flux:badge>
                        @else
                            <flux:badge color="red" size="sm">Not connected</flux:badge>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Active Subscription --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 lg:col-span-2">
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Subscription</p>
            </div>
            @if ($subscription)
                <dl class="grid grid-cols-2 divide-y divide-zinc-100 dark:divide-zinc-800 sm:grid-cols-4 sm:divide-y-0 sm:divide-x">
                    <div class="px-6 py-5">
                        <dt class="text-xs text-neutral-500">Plan</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $subscription->plan_type ?? 'Standard' }}</dd>
                    </div>
                    <div class="px-6 py-5">
                        <dt class="text-xs text-neutral-500">Reference</dt>
                        <dd class="mt-1 font-mono text-xs text-neutral-400">{{ $subscription->reference }}</dd>
                    </div>
                    <div class="px-6 py-5">
                        <dt class="text-xs text-neutral-500">Start Date</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $subscription->start_date->format('d M Y') }}</dd>
                    </div>
                    <div class="px-6 py-5">
                        <dt class="text-xs text-neutral-500">Expires</dt>
                        <dd class="mt-1 text-sm {{ $subscription->end_date->isPast() ? 'text-ml-error' : ($subscription->end_date->diffInDays() <= 30 ? 'text-ml-warning' : 'text-zinc-900 dark:text-white') }}">
                            {{ $subscription->end_date->format('d M Y') }}
                            @if ($subscription->end_date->isPast())
                                <span class="ml-1 text-xs">(expired)</span>
                            @elseif ($subscription->end_date->diffInDays() <= 30)
                                <span class="ml-1 text-xs">({{ $subscription->end_date->diffForHumans() }})</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            @else
                <div class="px-6 py-8 text-center">
                    <p class="text-sm text-neutral-500">No active subscription found. Please contact support.</p>
                </div>
            @endif
        </div>

    </div>
</div>
