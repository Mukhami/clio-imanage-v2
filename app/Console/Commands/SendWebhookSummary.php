<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ProcessingStage;
use App\Models\User;
use App\Models\WebhookRequest;
use App\Notifications\DailyWebhookSummary;
use Illuminate\Console\Command;

class SendWebhookSummary extends Command
{
    protected $signature = 'webhooks:send-summary';
    protected $description = 'Send daily webhook processing summary email to admins';

    public function handle(): int
    {
        $since = now()->subHours(24);

        $rows = WebhookRequest::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('processing_stage, COUNT(*) as count')
            ->groupBy('processing_stage')
            ->orderBy('processing_stage')
            ->get()
            ->map(fn ($row) => [
                'stage' => $row->processing_stage instanceof \BackedEnum
                    ? $row->processing_stage->value
                    : (string) $row->processing_stage,
                'count' => (int) $row->count,
            ])
            ->toArray();

        $total = WebhookRequest::query()
            ->where('created_at', '>=', $since)
            ->count();

        $failed = WebhookRequest::query()
            ->where('created_at', '>=', $since)
            ->where('processing_stage', ProcessingStage::Failed)
            ->count();

        $tableRows = array_map(fn ($r) => [$r['stage'], $r['count']], $rows);
        $this->table(['Processing Stage', 'Count'], $tableRows);
        $this->info("Summary generated for {$total} webhook request(s). Failed: {$failed}.");

        if ($total === 0) {
            $this->info('No webhook requests in last 24 hours — skipping notifications.');
            return Command::SUCCESS;
        }

        $admins = User::role(['Super Admin', 'Admin'])->get();

        foreach ($admins as $admin) {
            $admin->notify(new DailyWebhookSummary($total, $rows, $failed));
        }

        $this->info("Notified {$admins->count()} admin(s).");

        return Command::SUCCESS;
    }
}
