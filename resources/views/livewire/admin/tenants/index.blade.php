<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Tenants</h1>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex gap-3">
        <input
            type="text"
            wire:model.live="search"
            placeholder="Search by name or slug..."
            class="w-64 rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <select
            wire:model.live="statusFilter"
            class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="suspended">Suspended</option>
            <option value="archived">Archived</option>
        </select>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Clio Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created At</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($tenants as $tenant)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $tenant->name }}</div>
                            <div class="text-xs text-gray-400">{{ $tenant->slug }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if ($tenant->status->value === 'active') bg-green-100 text-green-800
                                @elseif ($tenant->status->value === 'pending') bg-yellow-100 text-yellow-800
                                @elseif ($tenant->status->value === 'suspended') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $tenant->status->value }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $tenant->clioLocation?->name ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $tenant->created_at->format('d M Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">No tenants found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tenants->links() }}
    </div>
</div>
