<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.users.index') }}" wire:navigate>Users</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.users.show', $user->id) }}" wire:navigate>{{ $user->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6">
        <flux:heading size="xl">Edit User</flux:heading>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 max-w-lg">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">User Details</flux:heading>
        </div>

        <div class="p-6 space-y-5">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="Full name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email Address</flux:label>
                <flux:input wire:model="email" type="email" placeholder="email@example.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="selectedRole">
                    @foreach ($this->availableRoles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedRole" />
            </flux:field>

            <div class="flex items-center gap-3 pt-2">
                <flux:button wire:click="save" variant="primary">Save Changes</flux:button>
                <flux:button href="{{ route('admin.users.show', $user->id) }}" wire:navigate variant="ghost">Cancel</flux:button>
            </div>
        </div>
    </div>
</div>
