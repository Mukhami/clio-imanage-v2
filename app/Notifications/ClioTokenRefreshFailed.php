<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClioTokenRefreshFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $errorMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Clio Token Refresh Failed — {$this->tenant->name}")
            ->line("The Clio OAuth token refresh failed for tenant: {$this->tenant->name}.")
            ->line('Error: ' . $this->errorMessage);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'clio_token_refresh_failed',
            'tenant_id' => $this->tenant->id,
            'error'     => $this->errorMessage,
        ];
    }
}
