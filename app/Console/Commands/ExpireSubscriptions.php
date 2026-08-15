<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\TenantSubscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Transition expired subscriptions to expired status';

    public function handle(): int
    {
        $count = TenantSubscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereDate('end_date', '<', now())
            ->update(['status' => SubscriptionStatus::Expired]);

        $this->info("Expired {$count} subscription(s).");

        return Command::SUCCESS;
    }
}
