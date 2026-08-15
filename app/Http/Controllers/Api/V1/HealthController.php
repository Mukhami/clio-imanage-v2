<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
        }

        try {
            Redis::ping();
            $checks['redis'] = 'ok';
        } catch (\Throwable $e) {
            $checks['redis'] = 'error: ' . $e->getMessage();
        }

        $allOk  = collect($checks)->every(fn ($v) => $v === 'ok');
        $status = $allOk ? 'ok' : 'degraded';

        return response()->json([
            'status'    => $status,
            'timestamp' => now()->toIso8601ZuluString(),
            'checks'    => $checks,
        ], $allOk ? 200 : 503);
    }
}
