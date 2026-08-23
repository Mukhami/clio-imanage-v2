<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantLockTimedOut extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $lockedAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tenant Job Lock Timed Out — ' . $this->tenant->name)
            ->line('A stale job lock was detected and automatically cleared.')
            ->line('Tenant: ' . $this->tenant->name)
            ->line('Lock created at: ' . $this->lockedAt)
            ->line('The lock was older than 1 hour, which indicates a job crashed without releasing it. The next job for this tenant has been allowed to proceed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'tenant_lock_timed_out',
            'tenant_id'   => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'locked_at'   => $this->lockedAt,
        ];
    }
}
