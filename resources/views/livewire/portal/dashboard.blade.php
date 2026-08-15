<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">
            Welcome, {{ auth()->user()->name }}
        </h1>
        <p class="text-sm text-gray-500">{{ auth()->user()->tenant?->name }}</p>
    </div>

    <!-- Stats -->
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg bg-white p-5 shadow">
            <p class="text-sm font-medium text-gray-500">Received Today</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['received_today'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow">
            <p class="text-sm font-medium text-gray-500">Completed Today</p>
            <p class="mt-1 text-3xl font-bold text-green-600">{{ $stats['completed_today'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow">
            <p class="text-sm font-medium text-gray-500">Failed Today</p>
            <p class="mt-1 text-3xl font-bold text-red-600">{{ $stats['failed_today'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow">
            <p class="text-sm font-medium text-gray-500">Pending Today</p>
            <p class="mt-1 text-3xl font-bold text-yellow-600">{{ $stats['pending_today'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Recent Webhook Requests -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Recent Activity</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Correlation ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Stage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Client ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Matter ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($recentRequests as $request)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-700">
                            {{ Str::limit($request['correlation_id'], 20) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if ($request['processing_stage'] === 'completed') bg-green-100 text-green-800
                                @elseif ($request['processing_stage'] === 'failed') bg-red-100 text-red-800
                                @elseif ($request['processing_stage'] === 'skipped') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ $request['processing_stage'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $request['retrieved_client_id'] ?? '—' }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $request['retrieved_matter_id'] ?? '—' }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($request['created_at'])->format('d M Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">No activity today.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
