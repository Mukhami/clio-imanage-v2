<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookRequest;
use Illuminate\Console\Command;

class PruneWebhookRequestBodies extends Command
{
    protected $signature = 'webhooks:prune-bodies';
    protected $description = 'Remove raw body data from completed webhook requests older than 90 days';

    public function handle(): int
    {
        $count = WebhookRequest::query()
            ->whereIn('processing_stage', ['completed', 'failed', 'skipped'])
            ->where('created_at', '<', now()->subDays(90))
            ->update([
                'headers' => null,
                'body'    => null,
            ]);

        $this->info("Pruned body data from {$count} webhook request(s) older than 90 days.");

        return Command::SUCCESS;
    }
}
