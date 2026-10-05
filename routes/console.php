<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('shop:expire-pending-orders')->everyFiveMinutes();
Schedule::command('payments:archive-logs')->daily();
Schedule::command('shop:database-backup')
    ->dailyAt((string) config('backup.database.schedule_time', '18:00'))
    ->timezone((string) config('backup.database.schedule_timezone', 'Asia/Tehran'));
