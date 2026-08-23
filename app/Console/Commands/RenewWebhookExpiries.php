<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\WebhookStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Webhook;
use App\Notifications\WebhookExtensionFailed;
use App\Services\ClioApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RenewWebhookExpiries extends Command
{
    protected $signature = 'webhooks:renew-expiries';
    protected $description = 'Extend Clio webhook registrations expiring within 7 days';

    public function handle(): int
    {
        $webhooks = Webhook::with('tenant')
            ->where('status', WebhookStatus::Active)
            ->where('expires_at', '<', now()->addDays(7))
            ->get();

        if ($webhooks->isEmpty()) {
            $this->info('No webhooks expiring within 7 days.');
            return Command::SUCCESS;
        }

        $renewed = 0;
        $failed  = 0;

        foreach ($webhooks as $webhook) {
            /** @var Webhook $webhook */
            $tenant = $webhook->tenant;

            try {
                $clio     = new ClioApiService($tenant);
                $response = $clio->renewWebhook((int) $webhook->clio_id);

                $newExpiresAt = data_get($response, 'data.expires_at');

                if ($newExpiresAt) {
                    $webhook->expires_at = $newExpiresAt;
                    $webhook->save();
                }

                $this->line("Tenant {$tenant->id} ({$tenant->name}): webhook {$webhook->clio_id} renewed — expires {$webhook->expires_at}");
                $renewed++;

            } catch (Throwable $e) {
                $this->error("Tenant {$tenant->id} ({$tenant->name}): failed to renew webhook {$webhook->clio_id} — {$e->getMessage()}");

                Log::error('Webhook renewal failed', [
                    'tenant_id'  => $tenant->id,
                    'webhook_id' => $webhook->id,
                    'clio_id'    => $webhook->clio_id,
                    'error'      => $e->getMessage(),
                ]);

                // Notify all Super Admin and Admin users
                $admins = User::role(['Super Admin', 'Admin'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new WebhookExtensionFailed($tenant, (int) $webhook->clio_id, $e->getMessage()));
                }

                $failed++;
            }
        }

        $this->info("Done. Renewed: {$renewed}, Failed: {$failed}.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
