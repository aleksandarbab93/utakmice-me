<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class RelativeTime
{
    /** "Prije 12 minuta" / "Prije 3 sata" / "Prije 2 dana" — ijekavian relative-time label. */
    public static function sr(Carbon $date): string
    {
        $minutes = max(0, (int) floor($date->diffInMinutes(Carbon::now())));

        if ($minutes < 1) {
            return 'Upravo sada';
        }

        if ($minutes < 60) {
            return 'Prije '.$minutes.' '.Plural::sr($minutes, 'minut', 'minuta', 'minuta');
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return 'Prije '.$hours.' '.Plural::sr($hours, 'sat', 'sata', 'sati');
        }

        $days = intdiv($hours, 24);

        return 'Prije '.$days.' '.Plural::sr($days, 'dan', 'dana', 'dana');
    }
}
