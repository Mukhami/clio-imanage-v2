<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ImanageOAuthAccessToken;
use App\Models\Tenant;
use Illuminate\Console\Command;

class RefreshImanageTokens extends Command
{
    protected $signature = 'tokens:refresh-imanage';
    protected $description = 'Refresh expiring iManage OAuth access tokens for all active tenants';

    public function handle(): int
    {
        $tenants = Tenant::active()->get();
        $needsRefresh = 0;

        foreach ($tenants as $tenant) {
            $expiring = ImanageOAuthAccessToken::query()
                ->where('tenant_id', $tenant->id)
                ->where('revoked', false)
                ->where('expires_at', '<', now()->addMinutes(30))
                ->where('expires_at', '>', now())
                ->exists();

            if ($expiring) {
                $needsRefresh++;
                $this->warn("Tenant {$tenant->id} ({$tenant->name}): iManage access token expires within 30 minutes — refresh required.");
                // TODO: Trigger OAuth refresh flow via ImanageApiService once refresh endpoint is integrated
            }
        }

        $this->info("Processed {$tenants->count()} tenant(s). {$needsRefresh} token(s) found needing refresh.");

        return Command::SUCCESS;
    }
}
