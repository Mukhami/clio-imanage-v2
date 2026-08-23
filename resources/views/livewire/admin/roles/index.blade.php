<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Roles &amp; Permissions</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6">
        <flux:heading size="xl">Roles &amp; Permissions</flux:heading>
        <flux:text class="text-zinc-500 mt-1">Read-only overview of all roles and their assigned permissions.</flux:text>
    </div>

    @php
        $roleOrder = ['Super Admin', 'Admin', 'Support', 'Tenant Admin', 'Tenant Viewer'];
        $sortedRoles = $this->roles->sortBy(fn ($r) => array_search($r->name, $roleOrder));

        // Group permissions by prefix
        $grouped = $this->permissions->groupBy(function ($p) {
            return explode('.', $p->name)[0];
        })->sortKeys();
    @endphp

    <div class="overflow-x-auto overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800">
            <thead>
                <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider w-52">
                        Permission
                    </th>
                    @foreach ($sortedRoles as $role)
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 uppercase tracking-wider">
                            {{ $role->name }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($grouped as $prefix => $perms)
                    {{-- Group header row --}}
                    <tr class="bg-zinc-50/50 dark:bg-zinc-800/20">
                        <td
                            colspan="{{ $sortedRoles->count() + 1 }}"
                            class="px-6 py-2 text-xs font-semibold text-zinc-400 uppercase tracking-widest"
                        >
                            {{ $prefix }}
                        </td>
                    </tr>
                    @foreach ($perms->sortBy('name') as $permission)
                        <tr>
                            <td class="px-6 py-2.5 text-sm font-mono text-zinc-700 dark:text-zinc-300">
                                {{ $permission->name }}
                            </td>
                            @foreach ($sortedRoles as $role)
                                <td class="px-4 py-2.5 text-center">
                                    @if ($role->permissions->contains('name', $permission->name))
                                        <span class="text-green-500 font-bold text-base">✓</span>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
