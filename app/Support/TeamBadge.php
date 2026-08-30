<?php

namespace App\Support;

/**
 * Team crest images aren't available from our data source (SStats.net),
 * so team rows show a computed initials badge instead of a real logo.
 */
class TeamBadge
{
    public static function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $words = array_values(array_filter(
            $words,
            fn ($w) => mb_strlen(preg_replace('/[^\p{L}]/u', '', $w)) >= 2
        ));

        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 3));
    }
}
