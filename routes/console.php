<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// News Bot Scheduler — currently disabled (manual-only mode via admin panel)
// To re-enable: uncomment both lines and ensure cron runs: * * * * * php artisan schedule:run
// Schedule::command('news:fetch')->everyFifteenMinutes()->name('fetch-news')->withoutOverlapping();
// Schedule::command('news:generate --limit=20')->everyTenMinutes()->name('generate-articles')->withoutOverlapping();
