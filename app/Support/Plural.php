<?php

namespace App\Support;

/** Serbian/Montenegrin numeral-noun agreement (1 udarac, 2–4 udarca, 5+ udaraca). */
class Plural
{
    public static function sr(int $n, string $one, string $few, string $many): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $one;
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ! ($mod100 >= 12 && $mod100 <= 14)) {
            return $few;
        }

        return $many;
    }
}
