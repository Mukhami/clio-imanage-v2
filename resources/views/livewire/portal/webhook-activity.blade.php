<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('portal.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Webhook Activity</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Webhook Activity</flux:heading>
    </div>

    <div class="mb-4 flex items-center gap-2 flex-nowrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search correlation ID, client, matter..." size="sm" class="min-w-52 w-52" clearable />
        <flux:select wire:model.live="stageFilter" size="sm" class="w-44" placeholder="All Stages">
            <flux:select.option value="">All Stages</flux:select.option>
            <flux:select.option value="received">Received</flux:select.option>
            <flux:select.option value="validated">Validated</flux:select.option>
            <flux:select.option value="parsed">Parsed</flux:select.option>
            <flux:select.option value="filtered">Filtered</flux:select.option>
            <flux:select.option value="enqueued">Enqueued</flux:select.option>
            <flux:select.option value="processing">Processing</flux:select.option>
            <flux:select.option value="post_processing">Post Processing</flux:select.option>
            <flux:select.option value="completed">Completed</flux:select.option>
            <flux:select.option value="failed">Failed</flux:select.option>
            <flux:select.option value="skipped">Skipped</flux:select.option>
        </flux:select>
        <flux:input type="date" wire:model.live="dateFrom" size="sm" class="w-36" />
        <flux:input type="date" wire:model.live="dateTo" size="sm" class="w-36" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Correlation ID</flux:table.column>
                    <flux:table.column>Stage</flux:table.column>
                    <flux:table.column>Client ID</flux:table.column>
                    <flux:table.column>Matter ID</flux:table.column>
                    <flux:table.column>Created At</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($webhookRequests as $request)
                        @php
                            $stage = $request->processing_stage->value;
                            $stageColor = match($stage) {
                                'completed' => 'green',
                                'failed'    => 'red',
                                'skipped'   => 'yellow',
                                default     => 'blue',
                            };
                        @endphp
                        <flux:table.row :key="$request->id">
                            <flux:table.cell>
                                <span class="font-mono">{{ Str::limit($request->correlation_id, 24) }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$stageColor" size="sm">{{ $stage }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $request->retrieved_client_id ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $request->retrieved_matter_id ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $request->created_at->format('d M Y H:i') }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-8 text-center text-zinc-400">No webhook requests found.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        @if ($webhookRequests->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $webhookRequests->links() }}
            </div>
        @endif
    </div>
</div>
