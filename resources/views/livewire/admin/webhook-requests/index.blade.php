<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Webhook Requests</h1>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap gap-3">
        <input
            type="text"
            wire:model.live="search"
            placeholder="Correlation ID, Client ID, Matter ID..."
            class="w-64 rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <select
            wire:model.live="stageFilter"
            class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            <option value="">All Stages</option>
            <option value="received">Received</option>
            <option value="validated">Validated</option>
            <option value="parsed">Parsed</option>
            <option value="filtered">Filtered</option>
            <option value="enqueued">Enqueued</option>
            <option value="processing">Processing</option>
            <option value="post_processing">Post Processing</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="skipped">Skipped</option>
        </select>
        <select
            wire:model.live="tenantFilter"
            class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            <option value="">All Tenants</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
            @endforeach
        </select>
        <input
            type="date"
            wire:model.live="dateFrom"
            class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <input
            type="date"
            wire:model.live="dateTo"
            class="rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tenant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Correlation ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Stage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Client ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Matter ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created At</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($webhookRequests as $request)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                            {{ $request->tenant?->name ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-700">
                            {{ Str::limit($request->correlation_id, 20) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if ($request->processing_stage->value === 'completed') bg-green-100 text-green-800
                                @elseif ($request->processing_stage->value === 'failed') bg-red-100 text-red-800
                                @elseif ($request->processing_stage->value === 'skipped') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ $request->processing_stage->value }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $request->retrieved_client_id ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $request->retrieved_matter_id ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $request->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <a href="{{ route('admin.webhook-requests.show', $request) }}" class="text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">No webhook requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $webhookRequests->links() }}
    </div>
</div>
