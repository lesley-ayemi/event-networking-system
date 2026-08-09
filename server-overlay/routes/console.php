<?php

use App\Console\Commands\SendEventNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hourly is frequent enough to reliably catch every registration inside the
// 23-25 hour lookahead window the command itself uses, without needing a
// tighter/more expensive schedule.
Schedule::command(SendEventNotifications::class)->hourly();
