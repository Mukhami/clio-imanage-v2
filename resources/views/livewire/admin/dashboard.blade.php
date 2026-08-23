<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Admin Dashboard</flux:heading>
        <flux:button wire:click="loadStats" size="sm" variant="ghost" icon="arrow-path">Refresh</flux:button>
    </div>

    {{-- Tenant Stats --}}
    <div class="mb-6">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-400">Tenants</p>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <p class="text-xs font-medium text-neutral-400">Active</p>
                <div class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ $stats['active_tenants'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <p class="text-xs font-medium text-neutral-400">With Active Subscription</p>
                <div class="mt-1 text-3xl font-bold text-growth-400">{{ $stats['tenants_with_active_subscriptions'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <p class="text-xs font-medium text-neutral-400">Pending Setup</p>
                <div class="mt-1 text-3xl font-bold text-ml-warning">{{ $stats['pending_tenants'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- Webhook Stats --}}
    <div class="mb-6">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-400">Webhooks — Today</p>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <p class="text-xs font-medium text-neutral-400">Received</p>
                <div class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ $stats['webhook_requests_today'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <p class="text-xs font-medium text-neutral-400">Completed</p>
                <div class="mt-1 text-3xl font-bold text-growth-400">{{ $stats['webhook_requests_completed_today'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <p class="text-xs font-medium text-neutral-400">Failed</p>
                <div class="mt-1 text-3xl font-bold text-ml-error">{{ $stats['webhook_requests_failed_today'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <p class="text-xs font-medium text-neutral-400">Pending / In Progress</p>
                <div class="mt-1 text-3xl font-bold text-core-400">{{ $stats['webhook_requests_pending'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- Attention + Expiring side by side --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Tenants Needing Attention --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Needs Attention</p>
                @if ($this->tenantsNeedingAttention->isNotEmpty())
                    <flux:badge color="red" size="sm">{{ $this->tenantsNeedingAttention->count() }}</flux:badge>
                @endif
            </div>
            @if ($this->tenantsNeedingAttention->isEmpty())
                <div class="px-6 py-6 text-center">
                    <p class="text-sm text-neutral-500">All active tenants have a valid Clio connection.</p>
                </div>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->tenantsNeedingAttention as $tenant)
                        <li class="flex items-center justify-between px-6 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-ml-error"></span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $tenant->name }}</span>
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
            <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Subscriptions Expiring (30 days)</p>
                @if ($this->expiringSubscriptions->isNotEmpty())
                    <flux:badge color="yellow" size="sm">{{ $this->expiringSubscriptions->count() }}</flux:badge>
                @endif
            </div>
            @if ($this->expiringSubscriptions->isEmpty())
                <div class="px-6 py-6 text-center">
                    <p class="text-sm text-neutral-500">No subscriptions expiring in the next 30 days.</p>
                </div>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->expiringSubscriptions as $sub)
                        <li class="flex items-center justify-between px-6 py-3">
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $sub->tenant->name }}</p>
                                <p class="font-mono text-xs text-neutral-500">{{ $sub->reference }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-ml-warning">
                                    Expires {{ $sub->end_date->format('d M Y') }}
                                </span>
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
        <div class="mb-6 overflow-hidden rounded-xl border border-ml-error/30 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-ml-error/30 px-6 py-4">
                <div class="flex items-center gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-ml-error">Recent Failures</p>
                    <flux:badge color="red" size="sm">{{ $this->recentFailures->count() }}</flux:badge>
                </div>
                <flux:button href="{{ route('admin.webhook-requests.index') }}" size="sm" variant="ghost" wire:navigate>View all</flux:button>
            </div>
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-neutral-400">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-neutral-400">Correlation ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-neutral-400">Error</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-neutral-400">When</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->recentFailures as $failure)
                        <tr>
                            <td class="px-6 py-3 text-sm text-zinc-900 dark:text-white">{{ $failure->tenant?->name ?? '—' }}</td>
                            <td class="px-6 py-3 font-mono text-sm text-neutral-400">{{ Str::limit($failure->correlation_id, 20) }}</td>
                            <td class="max-w-xs truncate px-6 py-3 text-sm text-ml-error">{{ $failure->error_message ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-neutral-400">{{ $failure->created_at->diffForHumans() }}</td>
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
        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Recent Webhook Requests</p>
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
                            <flux:table.cell class="py-8 text-center text-neutral-500" colspan="7">No webhook requests yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
