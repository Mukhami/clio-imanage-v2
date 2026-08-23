<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ?Tenant $tenant,
        public readonly string $resetUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        $mail = (new MailMessage)
            ->subject("You've been invited to {$appName}")
            ->line("You have been invited to join {$appName}.")
            ->line('An account has been created for you and you can set your password using the button below.');

        if ($this->tenant) {
            $mail->line('Tenant: ' . $this->tenant->name);
        }

        return $mail
            ->action('Activate Your Account', $this->resetUrl)
            ->line('This activation link will expire in 60 minutes.')
            ->line('If you did not expect to receive this invitation, no further action is required.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'user_invited',
            'reset_url'   => $this->resetUrl,
            'tenant_id'   => $this->tenant?->id,
            'tenant_name' => $this->tenant?->name,
        ];
    }
}
