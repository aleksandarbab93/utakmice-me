<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class RelativeTime
{
    /** "Pre 12 minuta" / "Pre 3 sata" / "Pre 2 dana" — Serbian relative-time label. */
    public static function sr(Carbon $date): string
    {
        $minutes = max(0, (int) floor($date->diffInMinutes(Carbon::now())));

        if ($minutes < 1) {
            return 'Upravo sada';
        }

        if ($minutes < 60) {
            return 'Pre '.$minutes.' '.Plural::sr($minutes, 'minut', 'minuta', 'minuta');
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return 'Pre '.$hours.' '.Plural::sr($hours, 'sat', 'sata', 'sati');
        }

        $days = intdiv($hours, 24);

        return 'Pre '.$days.' '.Plural::sr($days, 'dan', 'dana', 'dana');
    }
}
