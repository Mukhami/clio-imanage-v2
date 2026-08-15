<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\WebhookStatus;
use App\Models\Webhook;
use Illuminate\Console\Command;

class RenewWebhookExpiries extends Command
{
    protected $signature = 'webhooks:renew-expiries';
    protected $description = 'Extend Clio webhook registrations expiring within 7 days';

    public function handle(): int
    {
        $webhooks = Webhook::where('status', WebhookStatus::Active)
            ->where('expires_at', '<', now()->addDays(7))
            ->get();

        foreach ($webhooks as $webhook) {
            $this->info("Tenant {$webhook->tenant_id}: webhook {$webhook->clio_id} expires at {$webhook->expires_at}");
            // TODO: call ClioApiService to extend the webhook registration
        }

        $this->info("Found {$webhooks->count()} webhook(s) expiring within 7 days.");

        return Command::SUCCESS;
    }
}
