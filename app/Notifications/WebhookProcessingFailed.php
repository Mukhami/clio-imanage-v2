<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WebhookRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WebhookProcessingFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly WebhookRequest $webhookRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Webhook Processing Failed')
            ->line('A webhook request failed processing.')
            ->line('Tenant: ' . $this->webhookRequest->tenant->name)
            ->line('Correlation ID: ' . $this->webhookRequest->correlation_id)
            ->line('Error: ' . $this->webhookRequest->error_message);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'               => 'webhook_processing_failed',
            'webhook_request_id' => $this->webhookRequest->id,
            'tenant_id'          => $this->webhookRequest->tenant_id,
            'correlation_id'     => $this->webhookRequest->correlation_id,
            'error'              => $this->webhookRequest->error_message,
        ];
    }
}
