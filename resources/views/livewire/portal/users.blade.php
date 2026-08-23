<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('portal.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Users</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Firm Users</flux:heading>
        <flux:button wire:click="openCreateModal" icon="plus" size="sm">Invite User</flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="px-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Joined</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($users as $user)
                    @php
                        $status      = $user->status();
                        $statusColor = match($status) {
                            'active'    => 'green',
                            'invited'   => 'yellow',
                            'suspended' => 'red',
                        };
                        $statusLabel = ucfirst($status);
                    @endphp
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$user->name" size="sm" />
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</span>
                                @if ($user->id === auth()->id())
                                    <flux:badge size="sm" color="blue">You</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-neutral-500">{{ $user->email }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @foreach ($user->roles as $role)
                                <flux:badge size="sm" color="{{ $role->name === 'Tenant Admin' ? 'purple' : 'zinc' }}">
                                    {{ $role->name }}
                                </flux:badge>
                            @endforeach
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$statusColor">{{ $statusLabel }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-neutral-400">{{ $user->created_at->format('d M Y') }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($user->id !== auth()->id())
                                <flux:dropdown>
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        @if ($status === 'invited')
                                            <flux:menu.item icon="paper-airplane" wire:click="resendInvite({{ $user->id }})">
                                                Resend Invite
                                            </flux:menu.item>
                                        @endif
                                        @if ($status === 'suspended')
                                            <flux:menu.item icon="check-circle" wire:click="reactivateUser({{ $user->id }})">
                                                Reactivate
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item
                                                icon="no-symbol"
                                                variant="danger"
                                                wire:click="suspendUser({{ $user->id }})"
                                                wire:confirm="Suspend {{ $user->name }}? They will be immediately signed out and unable to log in."
                                            >
                                                Suspend
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-8 text-center text-neutral-500">No users found.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Invite User Modal --}}
    <flux:modal wire:model="showCreateModal" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Invite a User</flux:heading>
                <flux:text class="text-neutral-500">They'll receive an email to set their password.</flux:text>
            </div>

            <flux:field>
                <flux:label>Full Name</flux:label>
                <flux:input wire:model="name" placeholder="Jane Smith" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email Address</flux:label>
                <flux:input type="email" wire:model="email" placeholder="jane@lawfirm.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="role">
                    <flux:select.option value="Tenant Viewer">Tenant Viewer — can view activity only</flux:select.option>
                    <flux:select.option value="Tenant Admin">Tenant Admin — can view and manage users</flux:select.option>
                </flux:select>
                <flux:error name="role" />
            </flux:field>

            <div class="flex gap-3 justify-end">
                <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createUser" wire:loading.attr="disabled">
                    Send Invite
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
