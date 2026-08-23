<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'all';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        $query = auth()->user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        }

        return $query->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return Cache::remember(
            'user.' . auth()->id() . '.unread_notifications',
            60,
            fn () => auth()->user()->unreadNotifications()->count()
        );
    }

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        $this->bustNotificationCache();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->bustNotificationCache();
    }

    public function deleteNotification(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();
        $this->bustNotificationCache();
    }

    public function clearAll(): void
    {
        auth()->user()->notifications()->whereNotNull('read_at')->delete();
        $this->bustNotificationCache();
    }

    private function bustNotificationCache(): void
    {
        Cache::forget('user.' . auth()->id() . '.unread_notifications');
        unset($this->notifications, $this->unreadCount);
    }

    public function render(): View
    {
        return view('livewire.notifications.index');
    }
}
