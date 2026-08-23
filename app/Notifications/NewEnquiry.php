<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEnquiry extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Enquiry $enquiry,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage)
            ->subject("New Enquiry — {$this->enquiry->firm_name}")
            ->greeting("New enquiry received")
            ->line("A law firm has submitted an enquiry through the {$appName} website.")
            ->line('**Firm:** ' . $this->enquiry->firm_name)
            ->line('**Contact:** ' . $this->enquiry->contact_name)
            ->line('**Email:** ' . $this->enquiry->email)
            ->when($this->enquiry->phone, fn ($m) => $m->line('**Phone:** ' . $this->enquiry->phone))
            ->line('**Firm size:** ' . $this->enquiry->firmSizeLabel())
            ->line('**Clio region:** ' . $this->enquiry->clioRegionLabel())
            ->when($this->enquiry->notes, fn ($m) => $m->line('**Notes:** ' . $this->enquiry->notes))
            ->line('Reply directly to ' . $this->enquiry->email . ' to follow up.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'new_enquiry',
            'enquiry_id'   => $this->enquiry->id,
            'firm_name'    => $this->enquiry->firm_name,
            'contact_name' => $this->enquiry->contact_name,
            'email'        => $this->enquiry->email,
        ];
    }
}
