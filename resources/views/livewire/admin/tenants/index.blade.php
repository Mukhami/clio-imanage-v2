<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Tenants</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Tenants</flux:heading>
        <flux:button href="{{ route('admin.tenants.create') }}" size="sm" wire:navigate>New Tenant</flux:button>
    </div>

    <div class="mb-4 flex items-center gap-3">
        <flux:input wire:model.live="search" placeholder="Search by name or slug..." size="sm" class="w-64" />
        <flux:select wire:model.live="statusFilter" size="sm" class="w-44" placeholder="All Statuses">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="suspended">Suspended</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Clio Location</flux:table.column>
                    <flux:table.column>Created At</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($tenants as $tenant)
                        @php
                            $statusColor = match($tenant->status->value) {
                                'active'    => 'green',
                                'pending'   => 'yellow',
                                'suspended' => 'red',
                                default     => 'zinc',
                            };
                        @endphp
                        <flux:table.row :key="$tenant->id">
                            <flux:table.cell variant="strong">
                                {{ $tenant->name }}
                                <div class="text-xs font-normal text-zinc-400">{{ $tenant->slug }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$statusColor" size="sm">{{ $tenant->status->value }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $tenant->clioLocation?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $tenant->created_at->format('d M Y') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" size="sm" href="{{ route('admin.tenants.show', $tenant) }}" wire:navigate>View</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-8 text-center text-zinc-400">No tenants found.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        @if ($tenants->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $tenants->links() }}
            </div>
        @endif
    </div>
</div>
