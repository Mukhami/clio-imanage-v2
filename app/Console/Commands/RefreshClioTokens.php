<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ClioOAuthAccessToken;
use App\Models\Tenant;
use Illuminate\Console\Command;

class RefreshClioTokens extends Command
{
    protected $signature = 'tokens:refresh-clio';
    protected $description = 'Refresh expiring Clio OAuth access tokens for all active tenants';

    public function handle(): int
    {
        $tenants = Tenant::active()->get();
        $needsRefresh = 0;

        foreach ($tenants as $tenant) {
            $expiring = ClioOAuthAccessToken::query()
                ->where('tenant_id', $tenant->id)
                ->where('revoked', false)
                ->where('access_expires_at', '<', now()->addMinutes(30))
                ->where('access_expires_at', '>', now())
                ->exists();

            if ($expiring) {
                $needsRefresh++;
                $this->warn("Tenant {$tenant->id} ({$tenant->name}): Clio access token expires within 30 minutes — refresh required.");
                // TODO: Trigger OAuth refresh flow via ClioApiService once refresh endpoint is integrated
            }
        }

        $this->info("Processed {$tenants->count()} tenant(s). {$needsRefresh} token(s) found needing refresh.");

        return Command::SUCCESS;
    }
}
