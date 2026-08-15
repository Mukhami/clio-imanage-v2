<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Library;
use App\Models\Tenant;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncImanageData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $libraryId,
    ) {
        $this->onQueue('maintenance');
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(ImanageApiService $imanage): void
    {
        $tenant  = Tenant::findOrFail($this->tenantId);
        $library = Library::findOrFail($this->libraryId);

        // TODO: Sync practice areas → upsert ImanagePracticeArea records
        // TODO: Sync templates → upsert ImanageTemplate records
        // TODO: Sync groups → upsert ImanageGroup records
        // TODO: Sync users → upsert ImanageUser records
        // TODO: Sync custom fields → upsert ImanageCustomField records

        Log::info("iManage data sync completed for tenant {$tenant->name}, library {$library->id}");
    }
}
