<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('admin.enquiries.index') }}" wire:navigate>Enquiries</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $enquiry->firm_name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ $enquiry->firm_name }}</flux:heading>
            <flux:text class="text-neutral-500 mt-1">Received {{ $enquiry->created_at->format('d M Y \a\t H:i') }}</flux:text>
        </div>

        {{-- Status actions --}}
        @php
            $statusColor = match($enquiry->status) {
                'contacted' => 'blue',
                'onboarded' => 'green',
                'declined'  => 'red',
                default     => 'yellow',
            };
        @endphp
        <div class="flex items-center gap-3">
            <flux:badge :color="$statusColor">{{ ucfirst($enquiry->status) }}</flux:badge>
            <flux:dropdown>
                <flux:button size="sm" variant="ghost" icon:trailing="chevron-down">Update Status</flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="updateStatus('pending')"   icon="clock">Pending</flux:menu.item>
                    <flux:menu.item wire:click="updateStatus('contacted')" icon="chat-bubble-left">Contacted</flux:menu.item>
                    <flux:menu.item wire:click="updateStatus('onboarded')" icon="check-circle">Onboarded</flux:menu.item>
                    <flux:menu.item wire:click="updateStatus('declined')"  icon="x-circle" variant="danger">Declined</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Firm & Contact --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Contact Details</p>
            </div>
            <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="flex items-center justify-between px-6 py-3">
                    <dt class="text-sm text-neutral-500">Firm Name</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $enquiry->firm_name }}</dd>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <dt class="text-sm text-neutral-500">Contact Name</dt>
                    <dd class="text-sm text-zinc-900 dark:text-white">{{ $enquiry->contact_name }}</dd>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <dt class="text-sm text-neutral-500">Email</dt>
                    <dd>
                        <a href="mailto:{{ $enquiry->email }}" class="text-sm text-core-600 dark:text-core-400 hover:underline">
                            {{ $enquiry->email }}
                        </a>
                    </dd>
                </div>
                @if ($enquiry->phone)
                    <div class="flex items-center justify-between px-6 py-3">
                        <dt class="text-sm text-neutral-500">Phone</dt>
                        <dd>
                            <a href="tel:{{ $enquiry->phone }}" class="text-sm text-zinc-900 dark:text-white">
                                {{ $enquiry->phone }}
                            </a>
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Firm Profile --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Firm Profile</p>
            </div>
            <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="flex items-center justify-between px-6 py-3">
                    <dt class="text-sm text-neutral-500">Firm Size</dt>
                    <dd class="text-sm text-zinc-900 dark:text-white">{{ $enquiry->firmSizeLabel() }}</dd>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <dt class="text-sm text-neutral-500">Clio Region</dt>
                    <dd class="text-sm text-zinc-900 dark:text-white">{{ $enquiry->clioRegionLabel() }}</dd>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <dt class="text-sm text-neutral-500">Status</dt>
                    <dd><flux:badge :color="$statusColor" size="sm">{{ ucfirst($enquiry->status) }}</flux:badge></dd>
                </div>
                <div class="flex items-center justify-between px-6 py-3">
                    <dt class="text-sm text-neutral-500">Submitted</dt>
                    <dd class="text-sm text-neutral-400">{{ $enquiry->created_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </div>

        {{-- Notes --}}
        @if ($enquiry->notes)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 lg:col-span-2">
                <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Notes from Enquirer</p>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $enquiry->notes }}</p>
                </div>
            </div>
        @endif

    </div>
</div>
