<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ClioPracticeArea;
use App\Models\ClioGroup;
use App\Models\ClioUser;
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
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $tenant = Tenant::with('clioLocation')->findOrFail($this->tenantId);
        $clio   = new ClioApiService($tenant);

        $this->syncUsers($clio, $tenant);
        $this->syncGroups($clio, $tenant);
        $this->syncPracticeAreas($clio, $tenant);

        Log::info("Clio data sync completed for tenant {$tenant->name}");
    }

    private function syncUsers(ClioApiService $clio, Tenant $tenant): void
    {
        $response = $clio->getUsers();
        $users    = $response['data'] ?? [];

        foreach ($users as $user) {
            ClioUser::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'clio_id'   => $user['id'],
                ],
                [
                    'name'              => $user['name'] ?? null,
                    'email'             => $user['email'] ?? null,
                    'first_name'        => $user['first_name'] ?? null,
                    'last_name'         => $user['last_name'] ?? null,
                    'initials'          => $user['initials'] ?? null,
                    'enabled'           => $user['enabled'] ?? false,
                    'time_zone'         => $user['time_zone'] ?? null,
                    'subscription_type' => $user['subscription_type'] ?? null,
                ]
            );
        }

        Log::info("Synced " . count($users) . " Clio users for tenant {$tenant->name}");
    }

    private function syncGroups(ClioApiService $clio, Tenant $tenant): void
    {
        $response = $clio->getGroups();
        $groups   = $response['data'] ?? [];

        foreach ($groups as $group) {
            ClioGroup::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'clio_id'   => $group['id'],
                ],
                [
                    'name' => $group['name'] ?? null,
                    'type' => $group['type'] ?? null,
                    'etag' => $group['etag'] ?? null,
                ]
            );
        }

        Log::info("Synced " . count($groups) . " Clio groups for tenant {$tenant->name}");
    }

    private function syncPracticeAreas(ClioApiService $clio, Tenant $tenant): void
    {
        $response = $clio->getPracticeAreas();
        $areas    = $response['data'] ?? [];

        foreach ($areas as $area) {
            ClioPracticeArea::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'clio_id'   => $area['id'],
                ],
                [
                    'name'     => $area['name'] ?? null,
                    'code'     => $area['code'] ?? null,
                    'category' => $area['category'] ?? null,
                    'etag'     => $area['etag'] ?? null,
                ]
            );
        }

        Log::info("Synced " . count($areas) . " Clio practice areas for tenant {$tenant->name}");
    }
}
