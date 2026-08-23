<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ImanageOAuthAccessToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshImanageTokens extends Command
{
    protected $signature = 'tokens:refresh-imanage';
    protected $description = 'Refresh expiring iManage OAuth access tokens for all active tenants';

    public function handle(): int
    {
        // Find all non-revoked tokens expiring within 30 minutes that have a refresh token
        $tokens = ImanageOAuthAccessToken::query()
            ->with(['tenant'])
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<', now()->addMinutes(30))
            ->whereNotNull('refresh_token')
            ->get();

        if ($tokens->isEmpty()) {
            $this->info('No iManage tokens require refresh.');
            return Command::SUCCESS;
        }

        $refreshed = 0;
        $failed    = 0;

        foreach ($tokens as $token) {
            $tenant   = $token->tenant;
            $tokenUrl = rtrim((string) $tenant->imanage_cloud_url, '/') . '/auth/oauth2/token';

            try {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->asForm()
                    ->post($tokenUrl, [
                        'grant_type'    => 'refresh_token',
                        'client_id'     => (string) $tenant->imanage_app_id,
                        'client_secret' => (string) $tenant->imanage_app_secret,
                        'refresh_token' => (string) $token->refresh_token,
                    ]);

                $data = $response->json();

                if (! $response->successful() || empty($data['access_token'])) {
                    $this->error("Tenant {$tenant->id} ({$tenant->name}): iManage refresh failed (HTTP {$response->status()}).");
                    Log::error('iManage token refresh failed', [
                        'tenant_id' => $tenant->id,
                        'status'    => $response->status(),
                        'body'      => $response->body(),
                    ]);
                    $failed++;
                    continue;
                }

                // Revoke the old token and store the new one
                $token->update(['revoked' => true]);

                ImanageOAuthAccessToken::create([
                    'tenant_id'    => $tenant->id,
                    'access_token' => $data['access_token'],
                    'refresh_token'=> $data['refresh_token'] ?? null,
                    'expires_at'   => now()->addSeconds(max(0, ($data['expires_in'] ?? 3600) - 300)),
                    'revoked'      => false,
                ]);

                $this->line("Tenant {$tenant->id} ({$tenant->name}): iManage token refreshed.");
                $refreshed++;

            } catch (\Throwable $e) {
                $this->error("Tenant {$tenant->id} ({$tenant->name}): iManage refresh exception — {$e->getMessage()}");
                Log::error('iManage token refresh exception', [
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
