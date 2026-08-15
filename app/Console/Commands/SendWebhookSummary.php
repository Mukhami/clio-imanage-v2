<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookRequest;
use Illuminate\Console\Command;

class SendWebhookSummary extends Command
{
    protected $signature = 'webhooks:send-summary';
    protected $description = 'Send daily webhook processing summary email to admins';

    public function handle(): int
    {
        $rows = WebhookRequest::query()
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('processing_stage, COUNT(*) as count')
            ->groupBy('processing_stage')
            ->orderBy('processing_stage')
            ->get()
            ->map(fn ($row) => [$row->processing_stage instanceof \BackedEnum ? $row->processing_stage->value : $row->processing_stage, $row->count])
            ->toArray();

        $count = WebhookRequest::query()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $this->table(['Processing Stage', 'Count'], $rows);

        // TODO: dispatch a notification to admin users

        $this->info("Summary generated for {$count} webhook request(s).");

        return Command::SUCCESS;
    }
}
