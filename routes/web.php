<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Clio webhook receiver — no auth, rate-limited, CSRF exempt (handled by middleware exclusion)
Route::post('/webhook/{reference}', [WebhookController::class, 'receive'])
    ->middleware('throttle:120,1')
    ->name('webhook.receive');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
