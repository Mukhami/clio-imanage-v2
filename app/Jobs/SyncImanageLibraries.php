<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncImanageLibraries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly int $tenantId,
    ) {
        $this->onQueue('maintenance');
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(ImanageApiService $imanage): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        // TODO: Sync libraries via $imanage->getLibraries() → upsert Library records

        Log::info("iManage libraries sync completed for tenant {$tenant->name}");
    }
}
