<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Advance trimesters, activate progressions, generate charges
Schedule::command('finance:process-trimester-transitions')
    ->dailyAt('00:10')
    ->withoutOverlapping()
    ->onOneServer();

// Resume students from approved deferrals when their trimester arrives
Schedule::command('enrollment:process-deferrals')
    ->dailyAt('00:20')
    ->withoutOverlapping()
    ->onOneServer();

// Flag students with high outstanding balances (log-only, no destructive action)
Schedule::command('finance:flag-outstanding-balances --threshold=5000')
    ->monthlyOn(1, '06:00')
    ->onOneServer();
