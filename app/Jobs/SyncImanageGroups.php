<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImanageGroup;
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

class SyncImanageGroups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $libraryId,
    ) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $tenant  = Tenant::findOrFail($this->tenantId);
        $library = Library::findOrFail($this->libraryId);
        $imanage = new ImanageApiService($tenant);

        try {
            $response = $imanage->getGroups(
                (string) $tenant->imanage_customer_id,
                $library->imanage_library_id,
            );

            $rows = $response['data']['list'] ?? $response['data'] ?? [];

            foreach ($rows as $group) {
                ImanageGroup::updateOrCreate(
                    [
                        'tenant_id'        => $tenant->id,
                        'library_id'       => $library->id,
                        'imanage_group_id' => $group['id'],
                    ],
                    [
                        'name' => $group['full_name'] ?? null,
                    ]
                );
            }

            Log::info("SyncImanageGroups: synced " . count($rows) . " groups for tenant [{$tenant->name}], library [{$library->imanage_library_id}].");
        } catch (Throwable $e) {
            Log::error("SyncImanageGroups: failed for tenant [{$tenant->name}], library [{$library->imanage_library_id}] — {$e->getMessage()}", [
                'tenant_id'  => $tenant->id,
                'library_id' => $library->id,
                'exception'  => $e,
            ]);

            throw $e;
        }
    }
}
