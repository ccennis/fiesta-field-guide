<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('fiesta:import-ffd')->weeklyOn(1, '6:00')->withoutOverlapping();
Schedule::command('fiesta:suggest-swatches')->weeklyOn(1, '6:30')->withoutOverlapping();
