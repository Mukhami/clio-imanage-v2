<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\WebhookRequestController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\Api\V1\HealthController;

Route::get('/v1/health', [HealthController::class, 'index']);

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/stats', [StatsController::class, 'index']);

    Route::get('/tenants', [TenantController::class, 'index']);
    Route::get('/tenants/{tenant}', [TenantController::class, 'show']);

    Route::get('/tenants/{tenant}/webhook-requests', [WebhookRequestController::class, 'index']);
    Route::get('/tenants/{tenant}/webhook-requests/{webhookRequest}', [WebhookRequestController::class, 'show']);
    Route::post('/tenants/{tenant}/webhook-requests/{webhookRequest}/reattempt', [WebhookRequestController::class, 'reattempt']);

    Route::get('/tenants/{tenant}/subscriptions', [TenantController::class, 'subscriptions']);
    Route::get('/tenants/{tenant}/mappings/practice-areas', [TenantController::class, 'practiceAreaMappings']);
});
