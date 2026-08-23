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

class SyncImanageLibraries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly int $tenantId,
        public readonly bool $chainDataSync = false,
    ) {
        $this->onQueue('maintenance');
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $tenant  = Tenant::findOrFail($this->tenantId);
        $imanage = new ImanageApiService($tenant);

        $response  = $imanage->getLibraries();
        $libraries = $response['data']['list'] ?? $response['data'] ?? [];

        foreach ($libraries as $lib) {
            Library::updateOrCreate(
                [
                    'tenant_id'          => $tenant->id,
                    'imanage_library_id' => $lib['id'],
                ],
                [
                    'name'        => $lib['name'] ?? null,
                    'description' => $lib['description'] ?? null,
                ]
            );
        }

        Log::info("Synced " . count($libraries) . " iManage libraries for tenant {$tenant->name}");

        // Optionally chain a full data sync for every library just upserted
        if ($this->chainDataSync) {
            $tenant->libraries()->each(function (Library $library) {
                SyncImanageData::dispatch($this->tenantId, $library->id);
            });
        }
    }
}
