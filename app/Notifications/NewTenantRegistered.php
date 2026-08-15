<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTenantRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Tenant Registered — {$this->tenant->name}")
            ->line("A new tenant has registered on the platform.")
            ->line('Name: ' . $this->tenant->name)
            ->line('Reference: ' . $this->tenant->reference);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'new_tenant_registered',
            'tenant_id'   => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
        ];
    }
}
