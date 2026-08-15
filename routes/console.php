<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:expire')->dailyAt('00:00');
Schedule::command('tokens:refresh-clio')->everyThirtyMinutes();
Schedule::command('tokens:refresh-imanage')->everyThirtyMinutes();
Schedule::command('webhooks:renew-expiries')->dailyAt('02:00');
Schedule::command('webhooks:send-summary')->dailyAt('08:00');
Schedule::command('webhooks:prune-bodies')->monthly();
