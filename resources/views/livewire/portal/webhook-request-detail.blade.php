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
                        <dd class="mt-0.5"><flux:badge :color="$stageColor" size="sm">{{ $stage }}</flux:badge></dd>
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
                <div class="rounded-xl border border-ml-error/30 bg-ml-error-soft dark:bg-zinc-900 dark:border-ml-error/30">
                    <div class="border-b border-ml-error/30 px-6 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-ml-error">Error</p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-ml-error">{{ $webhookRequest->error_message }}</p>
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
