<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ReminderType;
use App\Models\TenantSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TenantSubscription $subscription,
        public readonly ReminderType $reminderType,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysRemaining = (int) now()->diffInDays($this->subscription->end_date, false);

        return (new MailMessage)
            ->subject("Subscription Expiring — {$this->subscription->tenant->name}")
            ->line("The subscription for {$this->subscription->tenant->name} is expiring soon.")
            ->line("Days remaining: {$daysRemaining}")
            ->line('End date: ' . $this->subscription->end_date->toDateString());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'tenant_id'       => $this->subscription->tenant_id,
            'end_date'        => $this->subscription->end_date->toDateString(),
            'reminder_type'   => $this->reminderType->value,
        ];
    }
}
