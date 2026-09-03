<?php

namespace App\Support;

class RoundLabel
{
    /** The source's shorthand ("Regular Season - 8") → the reader's wording ("8. kolo"). */
    public static function sr(?string $matchday): ?string
    {
        if ($matchday === null) {
            return null;
        }

        if (preg_match('/(\d+)\s*$/', $matchday, $m)) {
            return $m[1].'. kolo';
        }

        return $matchday;
    }
}
