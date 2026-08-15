<div>
    @if (session('success'))
        <div class="mb-4 rounded bg-green-100 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Webhook Request</h1>
        <a href="{{ route('admin.webhook-requests.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Back to list</a>
    </div>

    <!-- Metadata -->
    <div class="mb-6 overflow-hidden rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Metadata</h2>
        </div>
        <dl class="divide-y divide-gray-100">
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-gray-500">Correlation ID</dt>
                <dd class="col-span-2 font-mono text-sm text-gray-900">{{ $webhookRequest->correlation_id }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-gray-500">Tenant</dt>
                <dd class="col-span-2 text-sm text-gray-900">{{ $webhookRequest->tenant?->name ?? '—' }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-gray-500">Stage</dt>
                <dd class="col-span-2">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                        @if ($webhookRequest->processing_stage->value === 'completed') bg-green-100 text-green-800
                        @elseif ($webhookRequest->processing_stage->value === 'failed') bg-red-100 text-red-800
                        @elseif ($webhookRequest->processing_stage->value === 'skipped') bg-yellow-100 text-yellow-800
                        @else bg-blue-100 text-blue-800 @endif">
                        {{ $webhookRequest->processing_stage->value }}
                    </span>
                </dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-gray-500">Created At</dt>
                <dd class="col-span-2 text-sm text-gray-900">{{ $webhookRequest->created_at->format('d M Y H:i:s') }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-gray-500">Completed At</dt>
                <dd class="col-span-2 text-sm text-gray-900">{{ $webhookRequest->completed_at?->format('d M Y H:i:s') ?? '—' }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-gray-500">Client ID</dt>
                <dd class="col-span-2 text-sm text-gray-900">{{ $webhookRequest->retrieved_client_id ?? '—' }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                <dt class="text-sm font-medium text-gray-500">Matter ID</dt>
                <dd class="col-span-2 text-sm text-gray-900">{{ $webhookRequest->retrieved_matter_id ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    <!-- Activity Flags -->
    <div class="mb-6 overflow-hidden rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Activity Flags</h2>
        </div>
        <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-3">
            @foreach ([
                'client_activity_complete'              => 'Client',
                'matter_activity_complete'              => 'Matter',
                'workspace_activity_complete'           => 'Workspace',
                'folder_activity_complete'              => 'Folder',
                'security_activity_complete'            => 'Security',
                'workspace_link_custom_field_populated' => 'Workspace Link CF',
            ] as $field => $label)
                <div class="flex items-center gap-2">
                    @if ($webhookRequest->$field)
                        <span class="h-3 w-3 rounded-full bg-green-500"></span>
                    @else
                        <span class="h-3 w-3 rounded-full bg-gray-300"></span>
                    @endif
                    <span class="text-sm text-gray-700">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Error Section -->
    @if ($webhookRequest->processing_stage->value === 'failed' && $webhookRequest->error_message)
        <div class="mb-6 overflow-hidden rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-red-600">Error</h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-red-700">{{ $webhookRequest->error_message }}</p>
                <p class="mt-1 text-xs text-gray-400">Error count: {{ $webhookRequest->error_count }}</p>
            </div>
        </div>
    @endif

    <!-- Reattempt -->
    @if (in_array($webhookRequest->processing_stage->value, ['failed', 'skipped']))
        <div class="mb-6">
            <button
                wire:click="reattempt"
                wire:confirm="Are you sure you want to reattempt this webhook request?"
                class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                Reattempt
            </button>
        </div>
    @endif

    <!-- Raw Headers & Body (collapsible) -->
    <div
        x-data="{ openHeaders: false, openBody: false }"
        class="space-y-4"
    >
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <button
                type="button"
                @click="openHeaders = !openHeaders"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
            >
                <span class="text-sm font-semibold uppercase tracking-wider text-gray-500">Raw Headers</span>
                <span class="text-gray-400" x-text="openHeaders ? '▲' : '▼'"></span>
            </button>
            <div x-show="openHeaders" x-cloak class="border-t border-gray-100 p-6">
                <pre class="overflow-x-auto rounded bg-gray-50 p-4 text-xs text-gray-700">{{ json_encode($webhookRequest->headers, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <button
                type="button"
                @click="openBody = !openBody"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
            >
                <span class="text-sm font-semibold uppercase tracking-wider text-gray-500">Raw Body</span>
                <span class="text-gray-400" x-text="openBody ? '▲' : '▼'"></span>
            </button>
            <div x-show="openBody" x-cloak class="border-t border-gray-100 p-6">
                <pre class="overflow-x-auto rounded bg-gray-50 p-4 text-xs text-gray-700">{{ json_encode($webhookRequest->body, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
</div>
