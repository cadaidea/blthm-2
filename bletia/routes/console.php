<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('digest:send')->everyTwoMinutes()->withoutOverlapping();
Schedule::command('cobro:recordar')->dailyAt('09:00')->withoutOverlapping();

Schedule::command('automatizaciones:tick')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('cheques:avisar')->dailyAt('08:30')->withoutOverlapping();
Schedule::command('materiaprima:avisar')->dailyAt('08:15')->withoutOverlapping();
Schedule::command('digest:housekeeping')->weeklyOn(1, '03:00')->withoutOverlapping();
