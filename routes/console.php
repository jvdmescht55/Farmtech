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

// Keeps is_featured/featured_score current as real reviews, sales, and
// PDP click-throughs accumulate — see Product::computeFeaturedScore().
Schedule::command('products:rank-featured')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

// Weekly red meat prices from RPO (they publish once a week; twice a day keeps us current without hammering them).
Schedule::command('market:prices')->twiceDaily(7, 15)->withoutOverlapping();
