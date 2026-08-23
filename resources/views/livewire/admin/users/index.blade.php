<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Users</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Users</flux:heading>
        <flux:button href="{{ route('admin.users.create') }}" size="sm" wire:navigate>Invite User</flux:button>
    </div>

    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or email..."
            size="sm"
            class="w-72"
        />
        <flux:select wire:model.live="roleFilter" size="sm" class="w-44" placeholder="All Roles">
            <flux:select.option value="">All Roles</flux:select.option>
            <flux:select.option value="Super Admin">Super Admin</flux:select.option>
            <flux:select.option value="Admin">Admin</flux:select.option>
            <flux:select.option value="Support">Support</flux:select.option>
            <flux:select.option value="Tenant Admin">Tenant Admin</flux:select.option>
            <flux:select.option value="Tenant Viewer">Tenant Viewer</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column>Tenant</flux:table.column>
                    <flux:table.column>Last Login</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->users as $user)
                        @php
                            $isLocked = $user->locked_until && $user->locked_until->isFuture();
                        @endphp
                        <flux:table.row :key="$user->id">
                            <flux:table.cell variant="strong">
                                {{ $user->name }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->roles as $role)
                                        <flux:badge color="blue" size="sm">{{ $role->name }}</flux:badge>
                                    @endforeach
                                    @if ($user->roles->isEmpty())
                                        <span class="text-zinc-400 text-xs">No role</span>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($user->tenant)
                                    <flux:badge color="purple" size="sm">{{ $user->tenant->name }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Backoffice</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($isLocked)
                                    <flux:badge color="red" size="sm">Locked</flux:badge>
                                @else
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        href="{{ route('admin.users.show', $user->id) }}"
                                        wire:navigate
                                    >
                                        View
                                    </flux:button>
                                    @if ($isLocked)
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="unlockUser({{ $user->id }})"
                                            wire:confirm="Unlock this user?"
                                        >
                                            Unlock
                                        </flux:button>
                                    @else
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="lockUser({{ $user->id }})"
                                            wire:confirm="Lock this user? They will not be able to log in."
                                        >
                                            Lock
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="py-8 text-center text-zinc-400">
                                No users found.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        @if ($this->users->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->users->links() }}
            </div>
        @endif
    </div>
</div>
