<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImanageCustomField;
use App\Models\ImanageGroup;
use App\Models\ImanagePracticeArea;
use App\Models\ImanageSubPracticeArea;
use App\Models\ImanageTemplate;
use App\Models\ImanageUser;
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

    /** Page size for paginated iManage endpoints */
    private const PAGE_SIZE = 100;

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

    public function handle(): void
    {
        $tenant  = Tenant::findOrFail($this->tenantId);
        $library = Library::findOrFail($this->libraryId);
        $imanage = new ImanageApiService($tenant);

        $libId      = $library->imanage_library_id;
        $customerId = (string) $tenant->imanage_customer_id;

        $this->syncPracticeAreas($imanage, $tenant, $library, $customerId, $libId);
        $this->syncTemplates($imanage, $tenant, $library, $customerId, $libId);
        $this->syncGroups($imanage, $tenant, $library, $customerId, $libId);
        $this->syncUsers($imanage, $tenant, $library, $customerId, $libId);
        $this->syncCustomFields($imanage, $tenant, $library, $customerId, $libId);

        Log::info("iManage data sync completed for tenant {$tenant->name}, library {$library->imanage_library_id}");
    }

    // -------------------------------------------------------------------------
    // Sync methods
    // -------------------------------------------------------------------------

    private function syncPracticeAreas(ImanageApiService $imanage, Tenant $tenant, Library $library, string $customerId, string $libId): void
    {
        $rows = $this->paginate(fn (int $skip) => $imanage->getPracticeAreas($customerId, $libId, self::PAGE_SIZE, $skip));

        foreach ($rows as $pa) {
            $practiceArea = ImanagePracticeArea::updateOrCreate(
                [
                    'tenant_id'  => $tenant->id,
                    'library_id' => $library->id,
                    'key'        => $pa['key'],
                ],
                [
                    'description' => $pa['description'] ?? null,
                ]
            );

            // Sync nested sub-practice areas (iManage calls these "groups" within a practice-group)
            foreach ($pa['groups'] ?? [] as $sub) {
                ImanageSubPracticeArea::updateOrCreate(
                    [
                        'tenant_id'               => $tenant->id,
                        'library_id'              => $library->id,
                        'imanage_practice_area_id' => $practiceArea->id,
                        'key'                     => $sub['key'],
                    ],
                    [
                        'description' => $sub['description'] ?? null,
                    ]
                );
            }
        }

        Log::info("Synced " . count($rows) . " iManage practice areas for tenant {$tenant->name}, library {$libId}");
    }

    private function syncTemplates(ImanageApiService $imanage, Tenant $tenant, Library $library, string $customerId, string $libId): void
    {
        $rows = $this->paginate(fn (int $skip) => $imanage->getTemplates($customerId, $libId, self::PAGE_SIZE, $skip));

        foreach ($rows as $template) {
            ImanageTemplate::updateOrCreate(
                [
                    'tenant_id'           => $tenant->id,
                    'library_id'          => $library->id,
                    'imanage_template_id' => $template['id'],
                ],
                [
                    'description' => $template['description'] ?? null,
                ]
            );
        }

        Log::info("Synced " . count($rows) . " iManage templates for tenant {$tenant->name}, library {$libId}");
    }

    private function syncGroups(ImanageApiService $imanage, Tenant $tenant, Library $library, string $customerId, string $libId): void
    {
        $rows = $this->paginate(fn (int $skip) => $imanage->getGroups($customerId, $libId, self::PAGE_SIZE, $skip));

        foreach ($rows as $group) {
            ImanageGroup::updateOrCreate(
                [
                    'tenant_id'        => $tenant->id,
                    'library_id'       => $library->id,
                    'imanage_group_id' => $group['id'],
                ],
                [
                    'name' => $group['name'] ?? null,
                ]
            );
        }

        Log::info("Synced " . count($rows) . " iManage groups for tenant {$tenant->name}, library {$libId}");
    }

    private function syncUsers(ImanageApiService $imanage, Tenant $tenant, Library $library, string $customerId, string $libId): void
    {
        $rows = $this->paginate(fn (int $skip) => $imanage->getUsers($customerId, $libId, self::PAGE_SIZE, $skip));

        foreach ($rows as $user) {
            ImanageUser::updateOrCreate(
                [
                    'tenant_id'       => $tenant->id,
                    'library_id'      => $library->id,
                    'imanage_user_id' => $user['id'],
                ],
                [
                    'full_name' => $user['full_name'] ?? null,
                    'email'     => $user['email'] ?? null,
                ]
            );
        }

        Log::info("Synced " . count($rows) . " iManage users for tenant {$tenant->name}, library {$libId}");
    }

    private function syncCustomFields(ImanageApiService $imanage, Tenant $tenant, Library $library, string $customerId, string $libId): void
    {
        $response = $imanage->getCustomFields($customerId, $libId);
        $rows     = $response['data']['list'] ?? $response['data'] ?? [];

        foreach ($rows as $field) {
            ImanageCustomField::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'key'       => $field['key'],
                ],
                [
                    'description' => $field['description'] ?? null,
                    'wstype'      => $field['wstype'] ?? null,
                ]
            );
        }

        Log::info("Synced " . count($rows) . " iManage custom fields for tenant {$tenant->name}, library {$libId}");
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Fetch all pages from a paginated iManage endpoint.
     *
     * The callable receives the current $skip offset and must return the raw API
     * response array. iManage wraps list results under data.list; we collect all
     * pages until a page returns fewer rows than PAGE_SIZE.
     *
     * @param  callable(int): array  $fetcher
     * @return array<int, array<string, mixed>>
     */
    private function paginate(callable $fetcher): array
    {
        $all  = [];
        $skip = 0;

        do {
            $response = $fetcher($skip);
            $page     = $response['data']['list'] ?? $response['data'] ?? [];

            if (empty($page)) {
                break;
            }

            array_push($all, ...$page);
            $skip += count($page);
        } while (count($page) >= self::PAGE_SIZE);

        return $all;
    }
}
