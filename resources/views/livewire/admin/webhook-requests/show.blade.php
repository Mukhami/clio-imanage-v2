<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.webhook-requests.index') }}" wire:navigate>Webhook Requests</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>
            <span class="font-mono">{{ Str::limit($webhookRequest->correlation_id, 20) }}</span>
        </flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Webhook Request</flux:heading>
        <flux:button variant="ghost" href="{{ route('admin.webhook-requests.index') }}" wire:navigate>&larr; Back</flux:button>
    </div>

    {{-- Metadata --}}
    @php
        $stage = $webhookRequest->processing_stage->value;
        $stageColor = match($stage) {
            'completed' => 'green',
            'failed'    => 'red',
            'skipped'   => 'yellow',
            default     => 'blue',
        };
    @endphp

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Metadata</flux:heading>
        </div>
        <div>
            <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Correlation ID</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">
                        <span class="font-mono">{{ $webhookRequest->correlation_id }}</span>
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Tenant</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->tenant?->name ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Stage</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">
                        <flux:badge :color="$stageColor" size="sm">{{ $stage }}</flux:badge>
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Created At</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->created_at->format('d M Y H:i:s') }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Completed At</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->completed_at?->format('d M Y H:i:s') ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Client ID</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->retrieved_client_id ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-3">
                    <dt class="text-sm font-medium text-zinc-500">Matter ID</dt>
                    <dd class="col-span-2 text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->retrieved_matter_id ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Activity Flags --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Activity Flags</flux:heading>
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
                    <span class="h-3 w-3 rounded-full {{ $webhookRequest->$field ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></span>
                    <flux:text>{{ $label }}</flux:text>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Error Section --}}
    @if ($stage === 'failed' && $webhookRequest->error_message)
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wider text-red-500">Error</flux:heading>
            </div>
            <div class="p-6">
                <flux:text class="text-red-600 dark:text-red-400">{{ $webhookRequest->error_message }}</flux:text>
                <flux:text class="mt-1 text-xs text-zinc-400">Error count: {{ $webhookRequest->error_count }}</flux:text>
            </div>
        </div>
    @endif

    {{-- Reattempt --}}
    @if (in_array($stage, ['failed', 'skipped']))
        <div class="mb-6">
            <flux:button variant="primary" wire:click="reattempt" wire:confirm="Are you sure you want to reattempt this webhook request?">
                Reattempt
            </flux:button>
        </div>
    @endif

    {{-- Raw Headers & Body (collapsible) --}}
    <div class="space-y-4">
        <div x-data="{ open: false }" class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-6 py-4 text-left">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Raw Headers</flux:heading>
                <flux:icon.chevron-down class="size-4 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" />
            </button>
            <div x-show="open" x-cloak class="border-t border-zinc-200 dark:border-zinc-700 p-6">
                <pre class="overflow-x-auto rounded-lg bg-zinc-50 dark:bg-zinc-800 p-4 text-xs text-zinc-700 dark:text-zinc-300">{{ json_encode($webhookRequest->headers, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>

        <div x-data="{ open: false }" class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-6 py-4 text-left">
                <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Raw Body</flux:heading>
                <flux:icon.chevron-down class="size-4 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" />
            </button>
            <div x-show="open" x-cloak class="border-t border-zinc-200 dark:border-zinc-700 p-6">
                <pre class="overflow-x-auto rounded-lg bg-zinc-50 dark:bg-zinc-800 p-4 text-xs text-zinc-700 dark:text-zinc-300">{{ json_encode($webhookRequest->body, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
</div>
