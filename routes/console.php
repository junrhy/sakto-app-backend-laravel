<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Clean up past F&B opened dates and blocked dates daily at midnight
Schedule::command('app:cleanup-fnb-past-dates')->dailyAt('00:00');
