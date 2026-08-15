<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_if(! $request->user()->hasRole(['super_admin', 'admin']), 403);

        $query = Tenant::query();

        if ($request->filled('status')) {
            $status = TenantStatus::from($request->input('status'));
            $query->byStatus($status);
        }

        $tenants = $query->paginate(15);

        return response()->json([
            'data' => $tenants->items(),
            'meta' => [
                'total'        => $tenants->total(),
                'per_page'     => 15,
                'current_page' => $tenants->currentPage(),
            ],
        ]);
    }

    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        abort_if(! $request->user()->hasRole(['super_admin', 'admin']), 403);

        $tenant->load([
            'clioLocation',
            'tenantSetting',
            'tenantSubscriptions' => fn ($q) => $q->latest()->limit(1),
        ]);

        return response()->json(['data' => $tenant]);
    }

    public function subscriptions(Request $request, Tenant $tenant): JsonResponse
    {
        abort_if(! $request->user()->hasRole(['super_admin', 'admin']), 403);

        $subscriptions = $tenant->tenantSubscriptions()->paginate(15);

        return response()->json([
            'data' => $subscriptions->items(),
            'meta' => [
                'total'        => $subscriptions->total(),
                'per_page'     => 15,
                'current_page' => $subscriptions->currentPage(),
            ],
        ]);
    }

    public function practiceAreaMappings(Request $request, Tenant $tenant): JsonResponse
    {
        abort_if(! $request->user()->hasRole(['super_admin', 'admin']), 403);

        $mappings = $tenant->practiceAreaMappings()
            ->with(['clioPracticeArea', 'imanagePracticeArea'])
            ->paginate(15);

        return response()->json([
            'data' => $mappings->items(),
            'meta' => [
                'total'        => $mappings->total(),
                'per_page'     => 15,
                'current_page' => $mappings->currentPage(),
            ],
        ]);
    }
}
