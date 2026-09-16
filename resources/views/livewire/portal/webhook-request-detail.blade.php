<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('portal.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('portal.webhook-activity') }}">Webhook Activity</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ Str::limit($webhookRequest->correlation_id, 20) }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Webhook Request</flux:heading>
            <p class="font-mono text-sm text-neutral-400">{{ $webhookRequest->correlation_id }}</p>
        </div>
        @php
            $stage = $webhookRequest->processing_stage->value;
            $stageColor = match($stage) {
                'completed' => 'green',
                'failed'    => 'red',
                'skipped'   => 'yellow',
                default     => 'blue',
            };
        @endphp
        <flux:badge :color="$stageColor">{{ $stage }}</flux:badge>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Summary --}}
        <div class="lg:col-span-1 space-y-6">

            {{-- Processing Info --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Processing</p>
                </div>
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <div class="px-6 py-3">
                        <dt class="text-xs text-neutral-500">Stage</dt>
                        <dd class="mt-0.5">
                            <flux:badge :color="$stageColor" size="sm">{{ $stage }}</flux:badge>
                            @if ($webhookRequest->failed_at_stage)
                                <span class="ml-1 text-xs text-neutral-500">(during: {{ $webhookRequest->failed_at_stage }})</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-6 py-3">
                        <dt class="text-xs text-neutral-500">Received</dt>
                        <dd class="mt-0.5 text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->created_at->format('d M Y H:i:s') }}</dd>
                    </div>
                    @if ($webhookRequest->started_at)
                        <div class="px-6 py-3">
                            <dt class="text-xs text-neutral-500">Processing Started</dt>
                            <dd class="mt-0.5 text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->started_at->format('d M Y H:i:s') }}</dd>
                        </div>
                    @endif
                    @if ($webhookRequest->completed_at)
                        <div class="px-6 py-3">
                            <dt class="text-xs text-neutral-500">Completed</dt>
                            <dd class="mt-0.5 text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->completed_at->format('d M Y H:i:s') }}</dd>
                        </div>
                    @endif
                    @if ($webhookRequest->error_count > 0)
                        <div class="px-6 py-3">
                            <dt class="text-xs text-neutral-500">Error Count</dt>
                            <dd class="mt-0.5 text-sm text-ml-error">{{ $webhookRequest->error_count }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Extracted Data --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Extracted Data</p>
                </div>
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <div class="px-6 py-3">
                        <dt class="text-xs text-neutral-500">Client ID</dt>
                        <dd class="mt-0.5 font-mono text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->retrieved_client_id ?? '—' }}</dd>
                    </div>
                    <div class="px-6 py-3">
                        <dt class="text-xs text-neutral-500">Matter ID</dt>
                        <dd class="mt-0.5 font-mono text-sm text-zinc-900 dark:text-white">{{ $webhookRequest->retrieved_matter_id ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Activity Completion --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Activity Completion</p>
                </div>
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ([
                        'Client'            => $webhookRequest->client_activity_complete,
                        'Matter'            => $webhookRequest->matter_activity_complete,
                        'Workspace'         => $webhookRequest->workspace_activity_complete,
                        'Folders'           => $webhookRequest->folder_activity_complete,
                        'Security'          => $webhookRequest->security_activity_complete,
                        'Workspace Link CF' => $webhookRequest->workspace_link_custom_field_populated,
                    ] as $label => $done)
                        <li class="flex items-center justify-between px-6 py-2.5">
                            <span class="text-sm text-neutral-500">{{ $label }}</span>
                            @if ($done)
                                <flux:icon.check-circle class="size-4 text-ml-success" />
                            @else
                                <flux:icon.minus-circle class="size-4 text-neutral-300 dark:text-neutral-600" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>

        {{-- Right column: error + raw payload --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Error Message --}}
            @if ($webhookRequest->error_message)
                <div class="rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-950">
                    <div class="border-b border-red-200 dark:border-red-800 px-6 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">Error</p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-red-700 dark:text-red-300 whitespace-pre-wrap">{{ $webhookRequest->error_message }}</p>
                        @if ($webhookRequest->failed_at_stage)
                            <p class="mt-2 text-xs text-red-500">Failed during: <strong>{{ $webhookRequest->failed_at_stage }}</strong></p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Skip Reason --}}
            @if ($stage === 'skipped' && $webhookRequest->skip_reason)
                <div class="rounded-xl border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-950">
                    <div class="border-b border-yellow-200 dark:border-yellow-800 px-6 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-yellow-600 dark:text-yellow-400">Skipped</p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ $webhookRequest->skip_reason }}</p>
                    </div>
                </div>
            @endif

            {{-- API Call Log --}}
            @if (!empty($webhookRequest->api_call_log))
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">API Call Log ({{ count($webhookRequest->api_call_log) }} calls)</p>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($webhookRequest->api_call_log as $i => $call)
                            <div x-data="{ open: false }" class="px-6 py-3">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ ($call['status'] ?? 0) >= 400 ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' }}">
                                            {{ $call['status'] ?? '—' }}
                                        </span>
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $call['step'] ?? 'Unknown' }}</span>
                                        <span class="text-xs text-zinc-400 font-mono">{{ $call['method'] ?? '' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-zinc-400">{{ $call['at'] ?? '' }}</span>
                                        <flux:icon.chevron-down class="size-4 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" />
                                    </div>
                                </button>
                                <div x-show="open" x-cloak class="mt-3 space-y-3">
                                    <div>
                                        <p class="text-xs font-medium text-zinc-500 mb-1">URL</p>
                                        <p class="text-xs font-mono text-zinc-700 dark:text-zinc-300">{{ $call['url'] ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-zinc-500 mb-1">Request Payload</p>
                                        <pre class="overflow-x-auto rounded-lg bg-zinc-50 dark:bg-zinc-950 p-3 text-xs text-zinc-700 dark:text-zinc-300">{{ json_encode($call['request'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-zinc-500 mb-1">Response</p>
                                        <pre class="overflow-x-auto rounded-lg bg-zinc-50 dark:bg-zinc-950 p-3 text-xs text-zinc-700 dark:text-zinc-300">{{ json_encode($call['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Raw Payload --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Raw Payload</p>
                </div>
                <div class="p-4">
                    @if ($webhookRequest->body)
                        <pre class="overflow-x-auto rounded-lg bg-zinc-50 dark:bg-zinc-950 p-4 text-xs text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ json_encode($webhookRequest->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    @else
                        <p class="py-4 text-center text-sm text-neutral-400">No payload recorded.</p>
                    @endif
                </div>
            </div>

            {{-- Request Headers --}}
            @if ($webhookRequest->headers)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Request Headers</p>
                    </div>
                    <div class="p-4">
                        <pre class="overflow-x-auto rounded-lg bg-zinc-50 dark:bg-zinc-950 p-4 text-xs text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ json_encode($webhookRequest->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
