<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Webhook Requests</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Webhook Requests</flux:heading>
    </div>

    <div class="mb-4 flex items-center gap-2 flex-nowrap overflow-x-auto pb-1">
        <flux:input wire:model.live="search" placeholder="Correlation ID, Client, Matter..." size="sm" class="min-w-52 w-52" />
        <flux:select wire:model.live="stageFilter" size="sm" class="w-44" placeholder="All Stages">
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
        </flux:select>
        <flux:select wire:model.live="tenantFilter" size="sm" class="w-44" placeholder="All Tenants">
            <option value="">All Tenants</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="dateFrom" size="sm" class="w-36" />
        <flux:input type="date" wire:model.live="dateTo" size="sm" class="w-36" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Tenant</flux:table.column>
                    <flux:table.column>Correlation ID</flux:table.column>
                    <flux:table.column>Stage</flux:table.column>
                    <flux:table.column>Client ID</flux:table.column>
                    <flux:table.column>Matter ID</flux:table.column>
                    <flux:table.column>Created At</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
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
                            <flux:table.cell>{{ $request->tenant?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="font-mono">{{ Str::limit($request->correlation_id, 20) }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$stageColor" size="sm">{{ $stage }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $request->retrieved_client_id ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $request->retrieved_matter_id ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $request->created_at->format('d M Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" size="sm" href="{{ route('admin.webhook-requests.show', $request) }}" wire:navigate>View</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="py-8 text-center text-zinc-400">No webhook requests found.</flux:table.cell>
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
