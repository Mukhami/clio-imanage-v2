<div x-on:close-modal.window="$flux.modal($event.detail.name).close()">


    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>{{ $tenant->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Subscriptions</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Subscriptions — {{ $tenant->name }}</flux:heading>
        <flux:modal.trigger name="add-subscription">
            <flux:button size="sm" variant="primary">Add Subscription</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Subscriptions table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        @if ($this->subscriptions->isEmpty())
            <div class="px-6 py-10 text-center">
                <flux:text class="text-zinc-400">No subscriptions on record for this tenant.</flux:text>
            </div>
        @else
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Start</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">End</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Clio Users</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Notes</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->subscriptions as $subscription)
                        @php
                            $statusColor = match($subscription->status) {
                                \App\Enums\SubscriptionStatus::Active  => 'green',
                                \App\Enums\SubscriptionStatus::Void    => 'red',
                                \App\Enums\SubscriptionStatus::Expired => 'yellow',
                            };
                        @endphp
                        <tr>
                            <td class="px-6 py-3 text-sm font-mono text-zinc-900 dark:text-white">{{ $subscription->reference }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $subscription->plan_type ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $subscription->start_date->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $subscription->end_date->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-sm">
                                <flux:badge :color="$statusColor" size="sm">{{ $subscription->status->value }}</flux:badge>
                                @if ($subscription->status === \App\Enums\SubscriptionStatus::Void && $subscription->voidedBy)
                                    <div class="mt-0.5 text-xs text-zinc-400">by {{ $subscription->voidedBy->name }} · {{ $subscription->voided_at->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $subscription->clio_users_at_start ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-400 max-w-xs truncate">{{ $subscription->notes ?? '—' }}</td>
                            <td class="px-6 py-3 text-right">
                                @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        wire:click="voidSubscription({{ $subscription->id }})"
                                        wire:confirm="Void subscription {{ $subscription->reference }}? This cannot be undone."
                                    >
                                        Void
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($this->subscriptions->hasPages())
                <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $this->subscriptions->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Add Subscription modal --}}
    <flux:modal name="add-subscription" class="w-full max-w-lg">
        <div class="p-6 space-y-5">
            <flux:heading size="lg">Add Subscription</flux:heading>

            <flux:field>
                <flux:label>Reference</flux:label>
                <flux:input wire:model="reference" placeholder="SUB-XXXXXXXX" />
                <flux:error name="reference" />
            </flux:field>

            <flux:field>
                <flux:label>Plan Type <span class="text-zinc-400 font-normal">(optional)</span></flux:label>
                <flux:input wire:model="planType" placeholder="e.g. Professional, Enterprise" />
                <flux:error name="planType" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Start Date</flux:label>
                    <flux:input type="date" wire:model="startDate" />
                    <flux:error name="startDate" />
                </flux:field>

                <flux:field>
                    <flux:label>End Date</flux:label>
                    <flux:input type="date" wire:model="endDate" />
                    <flux:error name="endDate" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Notes <span class="text-zinc-400 font-normal">(optional)</span></flux:label>
                <flux:textarea wire:model="notes" rows="3" placeholder="Internal notes about this subscription…" />
                <flux:error name="notes" />
            </flux:field>

            <flux:callout variant="warning" icon="information-circle" class="text-sm">
                Any existing active subscription for this tenant will be automatically voided.
            </flux:callout>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="addSubscription">
                    Create Subscription
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
