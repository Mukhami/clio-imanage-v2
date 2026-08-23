<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Enquiries</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Enquiries</flux:heading>
    </div>

    <div class="mb-4 flex items-center gap-2">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search firm, contact, email..."
            size="sm"
            class="w-64"
            clearable
        />
        <flux:select wire:model.live="statusFilter" size="sm" class="w-40" placeholder="All Statuses">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="contacted">Contacted</flux:select.option>
            <flux:select.option value="onboarded">Onboarded</flux:select.option>
            <flux:select.option value="declined">Declined</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="px-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Firm</flux:table.column>
                <flux:table.column>Contact</flux:table.column>
                <flux:table.column>Size</flux:table.column>
                <flux:table.column>Region</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Received</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->enquiries as $enquiry)
                    @php
                        $statusColor = match($enquiry->status) {
                            'contacted' => 'blue',
                            'onboarded' => 'green',
                            'declined'  => 'red',
                            default     => 'yellow',
                        };
                    @endphp
                    <flux:table.row :key="$enquiry->id">
                        <flux:table.cell>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $enquiry->firm_name }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <p class="text-sm text-zinc-900 dark:text-white">{{ $enquiry->contact_name }}</p>
                                <p class="text-xs text-neutral-400">{{ $enquiry->email }}</p>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-neutral-500">{{ $enquiry->firmSizeLabel() }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-neutral-500">{{ strtoupper($enquiry->clio_region) }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$statusColor" size="sm">{{ ucfirst($enquiry->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-neutral-400">{{ $enquiry->created_at->format('d M Y') }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button
                                size="xs"
                                variant="ghost"
                                href="{{ route('admin.enquiries.show', $enquiry->id) }}"
                                wire:navigate
                            >View</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-8 text-center text-neutral-500">
                            No enquiries yet.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        </div>

        @if ($this->enquiries->hasPages())
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                {{ $this->enquiries->links() }}
            </div>
        @endif
    </div>
</div>
