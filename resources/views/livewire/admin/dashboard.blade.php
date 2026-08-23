<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Admin Dashboard</flux:heading>
        <flux:button wire:click="loadStats" size="sm" variant="ghost" icon="arrow-path">Refresh</flux:button>
    </div>

    {{-- Tenant Stats --}}
    <div class="mb-6">
        <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500 mb-3">Tenants</flux:heading>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <flux:text class="text-zinc-500">Active</flux:text>
                <div class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ $stats['active_tenants'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <flux:text class="text-zinc-500">With Active Subscription</flux:text>
                <div class="mt-1 text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['tenants_with_active_subscriptions'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <flux:text class="text-zinc-500">Pending Setup</flux:text>
                <div class="mt-1 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_tenants'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- Webhook Stats --}}
    <div class="mb-6">
        <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500 mb-3">Webhooks — Today</flux:heading>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <flux:text class="text-zinc-500">Received</flux:text>
                <div class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ $stats['webhook_requests_today'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <flux:text class="text-zinc-500">Completed</flux:text>
                <div class="mt-1 text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['webhook_requests_completed_today'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <flux:text class="text-zinc-500">Failed</flux:text>
                <div class="mt-1 text-3xl font-bold text-red-600 dark:text-red-400">{{ $stats['webhook_requests_failed_today'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <flux:text class="text-zinc-500">Pending / In Progress</flux:text>
                <div class="mt-1 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['webhook_requests_pending'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- Attention + Expiring side by side --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Tenants Needing Attention --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center gap-2">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Needs Attention</flux:heading>
                @if ($this->tenantsNeedingAttention->isNotEmpty())
                    <flux:badge color="red" size="sm">{{ $this->tenantsNeedingAttention->count() }}</flux:badge>
                @endif
            </div>
            @if ($this->tenantsNeedingAttention->isEmpty())
                <div class="px-6 py-6 text-center">
                    <flux:text class="text-zinc-400">All active tenants have a valid Clio connection.</flux:text>
                </div>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->tenantsNeedingAttention as $tenant)
                        <li class="flex items-center justify-between px-6 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                <flux:text class="text-sm font-medium text-zinc-900 dark:text-white">{{ $tenant->name }}</flux:text>
                            </div>
                            <flux:button
                                size="xs"
                                variant="ghost"
                                href="{{ route('admin.tenants.show', $tenant->id) }}"
                                wire:navigate
                            >
                                View
                            </flux:button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Expiring Subscriptions --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center gap-2">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Subscriptions Expiring (30 days)</flux:heading>
                @if ($this->expiringSubscriptions->isNotEmpty())
                    <flux:badge color="yellow" size="sm">{{ $this->expiringSubscriptions->count() }}</flux:badge>
                @endif
            </div>
            @if ($this->expiringSubscriptions->isEmpty())
                <div class="px-6 py-6 text-center">
                    <flux:text class="text-zinc-400">No subscriptions expiring in the next 30 days.</flux:text>
                </div>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->expiringSubscriptions as $sub)
                        <li class="flex items-center justify-between px-6 py-3">
                            <div>
                                <flux:text class="text-sm font-medium text-zinc-900 dark:text-white">{{ $sub->tenant->name }}</flux:text>
                                <flux:text class="text-xs text-zinc-400 font-mono">{{ $sub->reference }}</flux:text>
                            </div>
                            <div class="flex items-center gap-3">
                                <flux:text class="text-sm text-yellow-600 dark:text-yellow-400">
                                    Expires {{ $sub->end_date->format('d M Y') }}
                                </flux:text>
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    href="{{ route('admin.tenants.subscriptions', $sub->tenant_id) }}"
                                    wire:navigate
                                >
                                    Manage
                                </flux:button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Recent Failures --}}
    @if ($this->recentFailures->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-red-200 dark:border-red-900/50 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-red-200 dark:border-red-900/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:heading size="sm" class="uppercase tracking-wider text-red-500">Recent Failures</flux:heading>
                    <flux:badge color="red" size="sm">{{ $this->recentFailures->count() }}</flux:badge>
                </div>
                <flux:button href="{{ route('admin.webhook-requests.index') }}" size="sm" variant="ghost" wire:navigate>View all</flux:button>
            </div>
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Correlation ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Error</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">When</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->recentFailures as $failure)
                        <tr>
                            <td class="px-6 py-3 text-sm text-zinc-900 dark:text-white">{{ $failure->tenant?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm font-mono text-zinc-500">{{ Str::limit($failure->correlation_id, 20) }}</td>
                            <td class="px-6 py-3 text-sm text-red-600 dark:text-red-400 max-w-xs truncate">{{ $failure->error_message ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-400">{{ $failure->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-3 text-right">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    href="{{ route('admin.webhook-requests.show', $failure->id) }}"
                                    wire:navigate
                                >
                                    View
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Recent Webhook Requests --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Recent Webhook Requests</flux:heading>
            <flux:button href="{{ route('admin.webhook-requests.index') }}" size="sm" variant="ghost" wire:navigate>View all</flux:button>
        </div>
        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Tenant</flux:table.column>
                    <flux:table.column>Correlation ID</flux:table.column>
                    <flux:table.column>Stage</flux:table.column>
                    <flux:table.column>Client ID</flux:table.column>
                    <flux:table.column>Matter ID</flux:table.column>
                    <flux:table.column>Received</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->recentWebhookRequests as $request)
                        @php
                            $stage = $request->processing_stage->value;
                            $stageColor = match($stage) {
                                'completed' => 'green',
                                'failed'    => 'red',
                                'skipped'   => 'yellow',
                                default     => 'blue',
                            };
                        @endphp
                        <flux:table.row :key="$request->id">
                            <flux:table.cell>{{ $request->tenant?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="font-mono">{{ Str::limit($request->correlation_id ?? '', 20) }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$stageColor" size="sm">{{ $stage }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $request->retrieved_client_id ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $request->retrieved_matter_id ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $request->created_at->format('d M H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    href="{{ route('admin.webhook-requests.show', $request->id) }}"
                                    wire:navigate
                                >
                                    View
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell class="text-center py-8 text-zinc-400" colspan="7">No webhook requests yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
