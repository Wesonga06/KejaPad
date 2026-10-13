<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('rent:generate')->monthlyOn(1, '00:30');
Schedule::command('rent:mark-overdue')->dailyAt('01:00');
Schedule::command('rent:send-reminders')->dailyAt('08:00');
