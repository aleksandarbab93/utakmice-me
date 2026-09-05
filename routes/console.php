<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Full sync vs live sync
|--------------------------------------------------------------------------
|
| football:sync and basketball:sync pull everything — every fixture, every
| team, every table — for all tracked leagues. That's the right cost for a
| schedule that barely changes, but far too slow and heavy to run every
| minute just to catch a score changing.
|
| football:sync-live and basketball:sync-live exist for that instead: they
| touch only fixtures that are live or about to kick off, skip the call
| entirely when nothing is happening, and close out any match the source
| still calls "live" long after it can possibly still be running — the
| exact bug that once left dozens of matches stuck showing a red "120'"
| clock on this site.
|
| An explicit lock timeout on every entry below, not the 24h default: a run
| that dies mid-flight should free its lock in minutes, not hold it until
| the next day's run finds it still there.
*/

// Daily, not every 15 minutes: a full run now correctly paces every SStats
// call at the ~1-per-2s rate its keyless tier actually sustains (see
// SStatsClient::getPaged()), which makes a 14-league run take 20-40+
// minutes — running that every 15 minutes meant it was effectively always
// in flight, permanently starving football:sync-live and the match-detail
// page of the same shared rate-limit budget. Fixture schedules and league
// tables barely change hour to hour; sync-live already covers anything
// actually live or about to kick off.
Schedule::command('football:sync')->dailyAt('04:00')->withoutOverlapping(180);
Schedule::command('basketball:sync')->dailyAt('04:30')->withoutOverlapping(180);

Schedule::command('football:sync-live')->everyMinute()->withoutOverlapping(5);
Schedule::command('basketball:sync-live')->everyMinute()->withoutOverlapping(5);

// Free broadcasts (Prenosi uživo): a feed read per channel, no key, no
// quota — cheap enough to check often, and a channel that goes live twenty
// minutes before kickoff is no use to anybody if it's noticed six hours
// later.
Schedule::command('streams:sync')->everyTenMinutes()->withoutOverlapping(15);
