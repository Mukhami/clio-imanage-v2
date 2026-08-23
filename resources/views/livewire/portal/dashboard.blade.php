<div>
    <div class="mb-6">
        <flux:heading size="xl">Welcome, {{ auth()->user()->name }}</flux:heading>
        <flux:text class="text-zinc-500">{{ auth()->user()->tenant?->name }}</flux:text>
    </div>

    {{-- Stat Cards --}}
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
            <flux:text class="text-zinc-500">Received Today</flux:text>
            <div class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ $stats['received_today'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
            <flux:text class="text-zinc-500">Completed Today</flux:text>
            <div class="mt-1 text-3xl font-bold text-green-600">{{ $stats['completed_today'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
            <flux:text class="text-zinc-500">Failed Today</flux:text>
            <div class="mt-1 text-3xl font-bold text-red-600">{{ $stats['failed_today'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
            <flux:text class="text-zinc-500">Pending Today</flux:text>
            <div class="mt-1 text-3xl font-bold text-yellow-600">{{ $stats['pending_today'] ?? 0 }}</div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 mb-6">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="uppercase tracking-wider text-zinc-500">Recent Activity</flux:heading>
        </div>
        <div class="px-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Correlation ID</flux:table.column>
                    <flux:table.column>Stage</flux:table.column>
                    <flux:table.column>Client ID</flux:table.column>
                    <flux:table.column>Matter ID</flux:table.column>
                    <flux:table.column>Created At</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($recentRequests as $request)
                        @php
                            $stage = $request['processing_stage'];
                            $stageColor = match($stage) {
                                'completed' => 'green',
                                'failed'    => 'red',
                                'skipped'   => 'yellow',
                                default     => 'blue',
                            };
                        @endphp
                        <flux:table.row>
                            <flux:table.cell>
                                <span class="font-mono">{{ Str::limit($request['correlation_id'], 20) }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$stageColor" size="sm">{{ $stage }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $request['retrieved_client_id'] ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $request['retrieved_matter_id'] ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ \Carbon\Carbon::parse($request['created_at'])->format('d M Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    href="{{ route('portal.webhook-requests.show', $request['id']) }}"
                                    wire:navigate
                                >View</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="py-8 text-center text-zinc-400">No activity today.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
