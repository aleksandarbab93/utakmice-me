<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * kickoff_at is stored in UTC, but "today"/"tomorrow" and the date picker
 * on the Rezultati page mean a Crna Gora/Srbija calendar day — a plain
 * whereDate('kickoff_at', $date) compares UTC calendar days instead, which
 * silently mis-buckets any match kicking off between local midnight and
 * 2am (UTC hasn't rolled over to the local day yet). This converts a
 * calendar date into the UTC instant range that actually spans it.
 */
class LocalDay
{
    /** @return array{0: Carbon, 1: Carbon} [start, end) in UTC for the Europe/Belgrade day $date's Y-m-d falls on. */
    public static function bounds(Carbon $date): array
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d').' 00:00:00', 'Europe/Belgrade');

        return [$start->copy()->utc(), $start->copy()->addDay()->utc()];
    }
}
