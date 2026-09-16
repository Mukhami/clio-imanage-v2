<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\WebhookStatus;
use App\Models\User;
use App\Models\Webhook;
use App\Notifications\WebhookExtensionFailed;
use App\Services\ClioApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RenewWebhookRegistration implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 300; // 5 minutes between retries

    public function __construct(private readonly int $webhookId) {}

    public function handle(): void
    {
        $webhook = Webhook::with('tenant.clioLocation')->find($this->webhookId);

        // Bail if the webhook was deleted or is no longer active
        if (! $webhook || $webhook->status !== WebhookStatus::Active) {
            return;
        }

        $tenant = $webhook->tenant;

        try {
            $clio     = new ClioApiService($tenant);
            $response = $clio->renewWebhook((int) $webhook->clio_id, [
                'data' => [
                    'expires_at' => now()->addDays(29)->format('Y-m-d\TH:i:sP'),
                ],
            ]);

            $newExpiresAt = data_get($response, 'data.expires_at');

            if ($newExpiresAt) {
                $webhook->expires_at = $newExpiresAt;
                $webhook->save();
            }

            Log::info('Webhook renewed via job', [
                'tenant_id'   => $tenant->id,
                'webhook_id'  => $webhook->id,
                'clio_id'     => $webhook->clio_id,
                'expires_at'  => $webhook->expires_at,
            ]);

            // Re-schedule this job 24 hours before the new expiry
            self::dispatchFor($webhook);

        } catch (Throwable $e) {
            Log::error('Webhook renewal job failed', [
                'tenant_id'  => $tenant->id,
                'webhook_id' => $webhook->id,
                'clio_id'    => $webhook->clio_id,
                'error'      => $e->getMessage(),
            ]);

            // Notify admins on final failure (after all retries exhausted)
            if ($this->attempts() >= $this->tries) {
                User::role(['Super Admin', 'Admin'])->each(
                    fn (User $admin) => $admin->notify(
                        new WebhookExtensionFailed($tenant, (int) $webhook->clio_id, $e->getMessage())
                    )
                );
            }

            throw $e;
        }
    }

    /**
     * Dispatch this job delayed to 24 hours before the webhook expires.
     * Falls back to 2 days from now if expires_at is not set.
     */
    public static function dispatchFor(Webhook $webhook): void
    {
        $expiresAt = $webhook->expires_at ?? now()->addDays(3);
        $runAt     = $expiresAt->copy()->subDay();

        // If already within 24 hours of expiry, run in 10 minutes
        if ($runAt->isPast() || $runAt->diffInMinutes(now()) < 10) {
            $runAt = now()->addMinutes(10);
        }

        static::dispatch($webhook->id)
            ->onQueue('maintenance')
            ->delay($runAt);
    }
}
