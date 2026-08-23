<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Integrations\Clio\Requests\RefreshAccessToken;
use App\Models\ClioOAuthAccessToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshClioTokens extends Command
{
    protected $signature = 'tokens:refresh-clio';
    protected $description = 'Refresh expiring Clio OAuth access tokens for all active tenants';

    public function handle(): int
    {
        // Find all non-revoked tokens expiring within 30 minutes that have a usable refresh token
        $tokens = ClioOAuthAccessToken::query()
            ->with(['tenant.clioLocation'])
            ->where('revoked', false)
            ->where('access_expires_at', '>', now())
            ->where('access_expires_at', '<', now()->addMinutes(30))
            ->whereNotNull('refresh_token')
            ->where(function ($q) {
                $q->whereNull('refresh_expires_at')
                  ->orWhere('refresh_expires_at', '>', now());
            })
            ->get();

        if ($tokens->isEmpty()) {
            $this->info('No Clio tokens require refresh.');
            return Command::SUCCESS;
        }

        $refreshed = 0;
        $failed    = 0;

        foreach ($tokens as $token) {
            $tenant   = $token->tenant;
            $location = $tenant->clioLocation;

            $tokenUrl = rtrim((string) ($location?->app_url ?? 'https://app.clio.com/'), '/') . '/oauth/token';

            try {
                $response = (new RefreshAccessToken(
                    tokenUrl:     $tokenUrl,
                    clientId:     (string) $tenant->clio_app_id,
                    clientSecret: (string) $tenant->clio_app_secret,
                    refreshToken: (string) $token->refresh_token,
                ))->send();

                $data = $response->json();

                if (empty($data['access_token'])) {
                    $this->error("Tenant {$tenant->id} ({$tenant->name}): Clio refresh returned no access_token.");
                    Log::error('Clio token refresh returned no access_token', [
                        'tenant_id' => $tenant->id,
                        'response'  => $data,
                    ]);
                    $failed++;
                    continue;
                }

                // Revoke the old token and store the new one
                $token->update(['revoked' => true]);

                ClioOAuthAccessToken::create([
                    'tenant_id'          => $tenant->id,
                    'access_token'       => $data['access_token'],
                    'refresh_token'      => $data['refresh_token'] ?? null,
                    'access_expires_at'  => now()->addSeconds(max(0, ($data['expires_in'] ?? 3600) - 300)),
                    'refresh_expires_at' => isset($data['refresh_expires_in'])
                        ? now()->addSeconds($data['refresh_expires_in'])
                        : null,
                    'revoked' => false,
                ]);

                $this->line("Tenant {$tenant->id} ({$tenant->name}): Clio token refreshed.");
                $refreshed++;

            } catch (\Throwable $e) {
                $this->error("Tenant {$tenant->id} ({$tenant->name}): Clio refresh exception — {$e->getMessage()}");
                Log::error('Clio token refresh exception', [
                    'tenant_id' => $tenant->id,
                    'error'     => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->info("Done. Refreshed: {$refreshed}, Failed: {$failed}.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
