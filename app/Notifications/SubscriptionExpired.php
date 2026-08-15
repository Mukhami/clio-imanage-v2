<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TenantSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TenantSubscription $subscription,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Subscription Expired — {$this->subscription->tenant->name}")
            ->line("The subscription for {$this->subscription->tenant->name} has expired.")
            ->line('End date: ' . $this->subscription->end_date->toDateString());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'subscription_expired',
            'subscription_id' => $this->subscription->id,
            'tenant_id'       => $this->subscription->tenant_id,
        ];
    }
}
