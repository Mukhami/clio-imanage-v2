<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('dashboard') }}" icon="home" />
        <flux:breadcrumbs.item>Notifications</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">
            Notifications
            @if ($this->unreadCount > 0)
                <flux:badge color="red" size="sm" class="ml-2">{{ $this->unreadCount }}</flux:badge>
            @endif
        </flux:heading>
        <div class="flex items-center gap-2">
            @if ($this->unreadCount > 0)
                <flux:button variant="ghost" size="sm" wire:click="markAllAsRead">
                    Mark all as read
                </flux:button>
            @endif
            <flux:button
                variant="ghost"
                size="sm"
                wire:click="clearAll"
                wire:confirm="Delete all read notifications?"
            >
                Clear read
            </flux:button>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-4 flex items-center gap-1 border-b border-zinc-200 dark:border-zinc-700">
        <button
            wire:click="$set('filter', 'all')"
            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $filter === 'all' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            All
        </button>
        <button
            wire:click="$set('filter', 'unread')"
            class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $filter === 'unread' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Unread
            @if ($this->unreadCount > 0)
                <flux:badge color="red" size="sm">{{ $this->unreadCount }}</flux:badge>
            @endif
        </button>
    </div>

    {{-- Notifications List --}}
    <div class="space-y-2">
        @forelse ($this->notifications as $notification)
            @php
                $isUnread  = is_null($notification->read_at);
                $type      = $notification->data['type'] ?? 'notification';
                $message   = match ($type) {
                    'new_tenant_registered'  => 'New tenant registered: ' . ($notification->data['tenant_name'] ?? ''),
                    'webhook_processing_failed' => 'Webhook processing failed for tenant ID ' . ($notification->data['tenant_id'] ?? ''),
                    'user_invited'           => 'You have been invited to join ' . config('app.name'),
                    'user_role_changed'      => 'Your role has been updated to: ' . ($notification->data['new_role'] ?? ''),
                    default                  => ucfirst(str_replace('_', ' ', $type)),
                };
                $icon = match ($type) {
                    'new_tenant_registered'     => 'building-office-2',
                    'webhook_processing_failed' => 'exclamation-triangle',
                    'user_invited'              => 'user-plus',
                    'user_role_changed'         => 'shield-check',
                    default                     => 'bell',
                };
            @endphp
            <div class="overflow-hidden rounded-xl border bg-white dark:bg-zinc-900 transition-colors
                {{ $isUnread
                    ? 'border-blue-300 dark:border-blue-700 border-l-4 border-l-blue-500'
                    : 'border-zinc-200 dark:border-zinc-700' }}"
            >
                <div class="flex items-start justify-between gap-4 px-5 py-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                            {{ $isUnread ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' }}"
                        >
                            <flux:icon :name="$icon" class="h-4 w-4" />
                        </div>
                        <div>
                            <p class="text-sm {{ $isUnread ? 'font-semibold text-zinc-900 dark:text-white' : 'text-zinc-700 dark:text-zinc-300' }}">
                                {{ $message }}
                            </p>
                            <p class="mt-0.5 text-xs text-zinc-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($isUnread)
                            <flux:button
                                variant="ghost"
                                size="xs"
                                wire:click="markAsRead('{{ $notification->id }}')"
                            >
                                Mark read
                            </flux:button>
                        @endif
                        <flux:button
                            variant="ghost"
                            size="xs"
                            wire:click="deleteNotification('{{ $notification->id }}')"
                            wire:confirm="Delete this notification?"
                        >
                            Delete
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 py-16">
                <flux:icon name="bell-slash" class="h-12 w-12 text-zinc-300 dark:text-zinc-600 mb-4" />
                <flux:heading size="sm" class="text-zinc-400">No notifications</flux:heading>
                <flux:text class="text-zinc-400 text-sm mt-1">
                    {{ $filter === 'unread' ? 'You have no unread notifications.' : 'You have no notifications.' }}
                </flux:text>
            </div>
        @endforelse
    </div>

    @if ($this->notifications->hasPages())
        <div class="mt-4">
            {{ $this->notifications->links() }}
        </div>
    @endif
</div>
