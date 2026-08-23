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
use Throwable;

class SyncImanageLibraries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly int $tenantId,
        public readonly bool $chainDataSync = false,
    ) {
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        if (! $tenant->imanage_customer_id) {
            Log::error("SyncImanageLibraries: tenant [{$tenant->name}] has no imanage_customer_id — skipping.");
            $this->fail(new \RuntimeException("Tenant [{$tenant->name}] has no imanage_customer_id set."));

            return;
        }

        $imanage = new ImanageApiService($tenant);

        try {
            $response  = $imanage->getLibraries($tenant->imanage_customer_id);
            $libraries = $response['data']['list'] ?? $response['data'] ?? [];

            foreach ($libraries as $lib) {
                Library::updateOrCreate(
                    [
                        'tenant_id'          => $tenant->id,
                        'imanage_library_id' => $lib['id'],
                    ],
                    [
                        'name'        => $lib['name'] ?? $lib['id'],
                        'description' => $lib['description'] ?? null,
                    ]
                );
            }

            Log::info("SyncImanageLibraries: synced " . count($libraries) . " libraries for tenant [{$tenant->name}].");

            if ($this->chainDataSync) {
                $tenant->libraries()->each(function (Library $library) {
                    SyncImanageData::dispatch($this->tenantId, $library->id);
                });
            }
        } catch (Throwable $e) {
            Log::error("SyncImanageLibraries: failed for tenant [{$tenant->name}] — {$e->getMessage()}", [
                'tenant_id' => $tenant->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
