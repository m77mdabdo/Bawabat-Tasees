<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| REQUIRES A CRON ENTRY on the server, or none of this runs:
|
|     * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
|
| Worth stating loudly because the retention prune below is a privacy
| commitment, not a cleanup nicety — the privacy policy tells visitors their
| session data is deleted after the configured window, and without the cron
| entry that sentence is untrue.
|
*/

// 03:10 rather than 03:00: every scheduler in the world fires on the hour,
// and this shares a host with whatever else is on it.
Schedule::command('visitor-sessions:prune')
    ->dailyAt('03:10')
    ->onOneServer()
    // The job is idempotent — it deletes what is already past the window —
    // so a run that overlaps a slow previous one would only duplicate work.
    ->withoutOverlapping();
