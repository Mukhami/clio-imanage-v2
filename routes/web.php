<?php

use App\Http\Controllers\Admin\ClioOAuthController;
use App\Http\Controllers\Admin\ImanageOAuthController;
use App\Http\Controllers\WebhookController;
use App\Livewire\Admin;
use App\Livewire\Portal;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/enquiry', \App\Livewire\Enquiry::class)->name('enquiry');

// Clio webhook receiver — no auth, rate-limited, CSRF exempt (handled by middleware exclusion)
Route::post('/webhook/{reference}', [WebhookController::class, 'receive'])
    ->middleware('throttle:120,1')
    ->name('webhook.receive');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/notifications', \App\Livewire\Notifications\Index::class)->name('notifications.index');
});

// Admin panel — Super Admin, Admin, Support
Route::middleware(['auth', 'verified', 'role:Super Admin|Admin|Support'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', Admin\Dashboard::class)->name('dashboard');
        Route::get('/tenants', Admin\Tenants\Index::class)->name('tenants.index');
        Route::get('/tenants/create', Admin\Tenants\Create::class)->name('tenants.create');
        Route::get('/tenants/{id}', Admin\Tenants\Show::class)->name('tenants.show');
        Route::get('/tenants/{id}/edit', Admin\Tenants\Edit::class)->name('tenants.edit');
        Route::get('/tenants/{id}/config', Admin\Tenants\Config::class)->name('tenants.config');
        Route::get('/tenants/{id}/advanced-config', Admin\Tenants\AdvancedConfig::class)->name('tenants.advanced-config');
        Route::get('/tenants/{id}/sequence-config', Admin\Tenants\SequenceConfig::class)->name('tenants.sequence-config');
        Route::get('/tenants/{id}/subscriptions', Admin\Tenants\Subscriptions::class)->name('tenants.subscriptions');
        Route::get('/tenants/{id}/clio-users', Admin\Tenants\ClioUsers::class)->name('tenants.clio-users');
        Route::get('/tenants/{id}/imanage-data', Admin\Tenants\ImanageData::class)->name('tenants.imanage-data');
        Route::get('/webhook-requests', Admin\WebhookRequests\Index::class)->name('webhook-requests.index');
        Route::get('/webhook-requests/{id}', Admin\WebhookRequests\Show::class)->name('webhook-requests.show');

        Route::get('/users', Admin\Users\Index::class)->name('users.index');
        Route::get('/users/create', Admin\Users\Create::class)->name('users.create');
        Route::get('/users/{id}', Admin\Users\Show::class)->name('users.show');
        Route::get('/users/{id}/edit', Admin\Users\Edit::class)->name('users.edit');
        Route::get('/roles', Admin\Roles\Index::class)->name('roles.index');

        // OAuth — admin initiates the flow
        Route::get('/tenants/{id}/clio/authorize', [ClioOAuthController::class, 'redirect'])->name('tenants.clio.authorize');
        Route::get('/tenants/{id}/imanage/authorize', [ImanageOAuthController::class, 'redirect'])->name('tenants.imanage.authorize');
    });

// OAuth callbacks — public, no auth (Clio/iManage redirect here after user consents)
// Rate-limited to prevent abuse: 20 attempts per minute per IP
Route::middleware('throttle:20,1')->group(function () {
    Route::get('/oauth/clio/callback', [ClioOAuthController::class, 'callback'])->name('oauth.clio.callback');
    Route::get('/oauth/imanage/callback', [ImanageOAuthController::class, 'callback'])->name('oauth.imanage.callback');
});

// Tenant portal — Tenant Admin, Tenant Viewer
Route::middleware(['auth', 'verified', 'role:Tenant Admin|Tenant Viewer'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/dashboard', Portal\Dashboard::class)->name('dashboard');
        Route::get('/webhook-activity', Portal\WebhookActivity::class)->name('webhook-activity');
    });

require __DIR__.'/settings.php';
