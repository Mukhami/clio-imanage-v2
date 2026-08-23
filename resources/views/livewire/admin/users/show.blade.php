<div>
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">{{ session('success') }}</flux:callout>
    @endif
    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle" class="mb-4">{{ session('error') }}</flux:callout>
    @endif

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.users.index') }}" wire:navigate>Users</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $user->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ $user->name }}</flux:heading>
        <flux:button href="{{ route('admin.users.edit', $user->id) }}" size="sm" wire:navigate>Edit</flux:button>
    </div>

    {{-- Profile Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Profile</flux:heading>
        </div>
        <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-zinc-500">Name</dt>
                <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $user->name }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-zinc-500">Email</dt>
                <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $user->email }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-zinc-500">Tenant</dt>
                <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">
                    @if ($user->tenant)
                        <flux:badge color="purple" size="sm">{{ $user->tenant->name }}</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">Backoffice</flux:badge>
                    @endif
                </dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-zinc-500">Last Login</dt>
                <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">
                    {{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}
                </dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-zinc-500">Created At</dt>
                <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">
                    {{ $user->created_at->format('d M Y H:i') }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Status Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Account Status</flux:heading>
        </div>
        <div class="flex items-center justify-between px-6 py-4">
            @php $isLocked = $user->locked_until && $user->locked_until->isFuture(); @endphp
            <div class="flex items-center gap-3">
                @if ($isLocked)
                    <flux:badge color="red" size="sm">Locked</flux:badge>
                    <flux:text class="text-sm text-zinc-500">Locked until {{ $user->locked_until->format('d M Y H:i') }}</flux:text>
                @else
                    <flux:badge color="green" size="sm">Active</flux:badge>
                @endif
            </div>
            <div>
                @if ($isLocked)
                    <flux:button
                        variant="ghost"
                        size="sm"
                        wire:click="unlockUser"
                        wire:confirm="Unlock this user?"
                    >
                        Unlock User
                    </flux:button>
                @else
                    <flux:button
                        variant="ghost"
                        size="sm"
                        wire:click="lockUser"
                        wire:confirm="Lock this user? They will not be able to log in."
                    >
                        Lock User
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- Roles Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Roles</flux:heading>
        </div>
        <div class="flex flex-wrap gap-2 px-6 py-4">
            @forelse ($this->userRoles as $role)
                <flux:badge color="blue" size="sm">{{ $role->name }}</flux:badge>
            @empty
                <flux:text class="text-zinc-400">No roles assigned.</flux:text>
            @endforelse
        </div>
    </div>

    {{-- Permissions Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Permissions (via roles)</flux:heading>
        </div>
        <div class="px-6 py-4">
            @if ($this->userPermissions->isEmpty())
                <flux:text class="text-zinc-400">No permissions assigned.</flux:text>
            @else
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->userPermissions as $permission)
                        <div class="flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="text-green-500">✓</span>
                            <span class="font-mono text-xs">{{ $permission->name }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
