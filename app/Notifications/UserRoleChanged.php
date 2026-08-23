<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRoleChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $newRole,
        public readonly User $changedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage)
            ->subject("Your role has been updated on {$appName}")
            ->line("Your role on {$appName} has been updated.")
            ->line('New role: ' . $this->newRole)
            ->line('Changed by: ' . $this->changedBy->name)
            ->line('If you believe this change was made in error, please contact your administrator.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'user_role_changed',
            'new_role'        => $this->newRole,
            'changed_by_id'   => $this->changedBy->id,
            'changed_by_name' => $this->changedBy->name,
        ];
    }
}
