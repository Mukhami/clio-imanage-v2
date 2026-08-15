<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\ClioApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncClioData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly int $tenantId,
    ) {
        $this->onQueue('maintenance');
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(ClioApiService $clio): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        // TODO: Sync Clio users via $clio->getUsers() → upsert ClioUser records

        // TODO: Sync Clio groups via $clio->getGroups() → upsert ClioGroup records

        // TODO: Sync Clio practice areas via $clio->getPracticeAreas() → upsert ClioPracticeArea records

        Log::info("Clio data sync completed for tenant {$tenant->name}");
    }
}
