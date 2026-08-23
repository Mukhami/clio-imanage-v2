<div>
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">{{ session('success') }}</flux:callout>
    @endif

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>{{ $tenant->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Clio Users</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Clio Users — {{ $tenant->name }}</flux:heading>
            <flux:text class="text-zinc-500 mt-1">
                {{ $this->counts['total'] }} total · {{ $this->counts['enabled'] }} enabled
            </flux:text>
        </div>
        <flux:button
            size="sm"
            variant="ghost"
            wire:click="syncUsers"
            wire:confirm="Queue a Clio data sync for this tenant?"
        >
            Sync Users
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search name, email or initials…"
            class="max-w-sm"
            clearable
        />
        <flux:select wire:model.live="filter" class="w-36">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="enabled">Enabled</flux:select.option>
            <flux:select.option value="disabled">Disabled</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        @if ($this->users->isEmpty())
            <div class="px-6 py-10 text-center">
                @if ($search || $filter !== 'all')
                    <flux:text class="text-zinc-400">No users match your filters.</flux:text>
                @else
                    <flux:text class="text-zinc-400">No Clio users synced yet. Click <strong>Sync Users</strong> to fetch from Clio.</flux:text>
                @endif
            </div>
        @else
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Initials</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Subscription</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Clio ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Last Synced</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->users as $user)
                        <tr>
                            <td class="px-6 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $user->name ?? '—' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $user->email ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm font-mono text-zinc-500">{{ $user->initials ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $user->subscription_type ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm">
                                @if ($user->enabled)
                                    <flux:badge color="green" size="sm">Enabled</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Disabled</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm font-mono text-zinc-400">{{ $user->clio_id }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-400">{{ $user->updated_at->format('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($this->users->hasPages())
                <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $this->users->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
