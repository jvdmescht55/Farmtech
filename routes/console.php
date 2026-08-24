<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fired by `php artisan schedule:work` (see
// /etc/supervisor/conf.d/farmtech-scheduler.conf) rather than a system cron
// entry — matches how farmtech-worker.conf already runs `queue:work` as a
// standing supervisor program in this environment.
Schedule::command('products:auto-publish')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
