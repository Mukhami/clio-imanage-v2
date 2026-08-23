<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Library;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncImanageData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 60;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $libraryId,
    ) {}

    public function handle(): void
    {
        $tenant  = Tenant::findOrFail($this->tenantId);
        $library = Library::findOrFail($this->libraryId);

        Log::info("SyncImanageData: dispatching sync jobs for tenant [{$tenant->name}], library [{$library->imanage_library_id}].");

        SyncImanagePracticeAreas::dispatch($this->tenantId, $this->libraryId);
        SyncImanageTemplates::dispatch($this->tenantId, $this->libraryId);
        SyncImanageGroups::dispatch($this->tenantId, $this->libraryId);
        SyncImanageUsers::dispatch($this->tenantId, $this->libraryId);
    }
}
