<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WebhookExtensionFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly int $clioWebhookId,
        public readonly string $errorMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Webhook Extension Failed — {$this->tenant->name}")
            ->line("A webhook extension attempt failed for tenant: {$this->tenant->name}.")
            ->line('Clio Webhook ID: ' . $this->clioWebhookId)
            ->line('Error: ' . $this->errorMessage);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'webhook_extension_failed',
            'tenant_id'        => $this->tenant->id,
            'clio_webhook_id'  => $this->clioWebhookId,
            'error'            => $this->errorMessage,
        ];
    }
}
