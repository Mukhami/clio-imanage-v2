<div>
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">{{ session('success') }}</flux:callout>
    @endif

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.tenants.index') }}" wire:navigate>Tenants</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.tenants.show', $tenant->id) }}" wire:navigate>{{ $tenant->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>iManage Data</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">iManage Data — {{ $tenant->name }}</flux:heading>
            <flux:text class="text-zinc-500 mt-1">{{ $this->libraries->count() }} {{ Str::plural('library', $this->libraries->count()) }} synced</flux:text>
        </div>
        <flux:button
            size="sm"
            variant="ghost"
            wire:click="syncData"
            wire:confirm="Queue a full iManage libraries + data sync for this tenant?"
        >
            Sync Now
        </flux:button>
    </div>

    {{-- Libraries --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Libraries</flux:heading>
        </div>
        @if ($this->libraries->isEmpty())
            <div class="px-6 py-10 text-center">
                <flux:text class="text-zinc-400">No libraries synced yet. Click <strong>Sync Now</strong> to fetch from iManage.</flux:text>
            </div>
        @else
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">iManage ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Templates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Practice Areas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Users</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Groups</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->libraries as $library)
                        <tr class="{{ $selectedLibraryId === $library->id ? 'bg-blue-50 dark:bg-blue-950/20' : '' }}">
                            <td class="px-6 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $library->name }}</td>
                            <td class="px-6 py-3 text-sm font-mono text-zinc-500">{{ $library->imanage_library_id }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $library->imanage_templates_count }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $library->imanage_practice_areas_count }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $library->imanage_users_count }}</td>
                            <td class="px-6 py-3 text-sm text-zinc-500">{{ $library->imanage_groups_count }}</td>
                            <td class="px-6 py-3 text-right">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    wire:click="selectLibrary({{ $library->id }})"
                                >
                                    {{ $selectedLibraryId === $library->id ? 'Hide' : 'Templates' }}
                                </flux:button>
                            </td>
                        </tr>
                        @if ($selectedLibraryId === $library->id)
                            <tr>
                                <td colspan="7" class="bg-zinc-50 dark:bg-zinc-800/50 px-6 py-0">
                                    <div class="py-4">
                                        @if ($this->templates->isEmpty())
                                            <flux:text class="text-zinc-400 text-sm">No templates in this library.</flux:text>
                                        @else
                                            <table class="min-w-full">
                                                <thead>
                                                    <tr>
                                                        <th class="py-2 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider pr-8">Template Name</th>
                                                        <th class="py-2 text-left text-xs font-medium text-zinc-400 uppercase tracking-wider">iManage Template ID</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                                    @foreach ($this->templates as $template)
                                                        <tr>
                                                            <td class="py-2 text-sm text-zinc-700 dark:text-zinc-300 pr-8">{{ $template->description }}</td>
                                                            <td class="py-2 text-sm font-mono text-zinc-400">{{ $template->imanage_template_id }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
