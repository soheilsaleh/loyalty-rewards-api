<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('loyalty:expire-points')->dailyAt('03:00');
Schedule::command('loyalty:notify-expiring-points')->dailyAt('09:00');
