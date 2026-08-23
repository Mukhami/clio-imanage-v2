<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyWebhookSummary extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<array{stage: string, count: int}>  $rows
     */
    public function __construct(
        public readonly int $total,
        public readonly array $rows,
        public readonly int $failed,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Daily Webhook Processing Summary')
            ->line("Total webhook requests processed in the last 24 hours: **{$this->total}**")
            ->line('');

        foreach ($this->rows as $row) {
            $message->line("- **{$row['stage']}**: {$row['count']}");
        }

        if ($this->failed > 0) {
            $message
                ->line('')
                ->line("⚠️ {$this->failed} request(s) failed — please review the admin dashboard.");
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'   => 'daily_webhook_summary',
            'total'  => $this->total,
            'rows'   => $this->rows,
            'failed' => $this->failed,
        ];
    }
}
