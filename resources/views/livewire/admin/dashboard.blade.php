<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>
        <button wire:click="loadStats" class="rounded bg-gray-100 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-200">
            Refresh
        </button>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <!-- Active Tenants -->
        <div class="rounded-lg bg-white p-5 shadow">
            <p class="text-sm font-medium text-gray-500">Active Tenants</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['active_tenants'] ?? 0 }}</p>
        </div>

        <!-- Webhooks Today -->
        <div class="rounded-lg bg-white p-5 shadow">
            <p class="text-sm font-medium text-gray-500">Webhooks Today</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['webhook_requests_today'] ?? 0 }}</p>
        </div>

        <!-- Completed Today -->
        <div class="rounded-lg bg-white p-5 shadow">
            <p class="text-sm font-medium text-gray-500">Completed Today</p>
            <p class="mt-1 text-3xl font-bold text-green-600">{{ $stats['webhook_requests_completed_today'] ?? 0 }}</p>
        </div>

        <!-- Failed Today -->
        <div class="rounded-lg bg-white p-5 shadow">
            <p class="text-sm font-medium text-gray-500">Failed Today</p>
            <p class="mt-1 text-3xl font-bold text-red-600">{{ $stats['webhook_requests_failed_today'] ?? 0 }}</p>
        </div>
    </div>
</div>
