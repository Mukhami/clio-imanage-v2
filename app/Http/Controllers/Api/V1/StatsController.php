<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProcessingStage;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\WebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_if(! $request->user()->hasRole(['super_admin', 'admin']), 403);

        $today     = now()->startOfDay();
        $weekStart = now()->startOfWeek();

        $webhooksToday = WebhookRequest::where('created_at', '>=', $today)->count();
        $webhooksWeek  = WebhookRequest::where('created_at', '>=', $weekStart)->count();

        $byStageToday = WebhookRequest::where('created_at', '>=', $today)
            ->select('processing_stage', DB::raw('count(*) as count'))
            ->groupBy('processing_stage')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->processing_stage->value => $row->count]);

        $activeTenants       = Tenant::active()->count();
        $activeSubscriptions = TenantSubscription::where('status', 'active')->count();

        return response()->json([
            'data' => [
                'webhooks' => [
                    'today'          => $webhooksToday,
                    'this_week'      => $webhooksWeek,
                    'by_stage_today' => $byStageToday,
                ],
                'tenants'       => ['active' => $activeTenants],
                'subscriptions' => ['active' => $activeSubscriptions],
            ],
        ]);
    }
}
