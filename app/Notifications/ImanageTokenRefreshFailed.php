<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImanageTokenRefreshFailed extends Notification implements ShouldQueue
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
            ->subject("iManage Token Refresh Failed — {$this->tenant->name}")
            ->line("The iManage OAuth token refresh failed for tenant: {$this->tenant->name}.")
            ->line('Error: ' . $this->errorMessage);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'imanage_token_refresh_failed',
            'tenant_id' => $this->tenant->id,
            'error'     => $this->errorMessage,
        ];
    }
}
