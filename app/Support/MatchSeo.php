<?php

namespace App\Support;

/**
 * The search-facing wording of a match page, shared by both sports. The same
 * two clubs meet several times a season, so the date is always part of it —
 * without one those pages carry identical titles.
 */
class MatchSeo
{
    /** @param array<string, mixed> $match As built by MatchDetail or BasketballMatchDetail. */
    public static function title(array $match): string
    {
        $clubs = self::clubs($match);

        if ($match['status'] !== 'finished') {
            return "{$clubs} uživo — prenos i rezultat, {$match['kickoff_date']} | Utakmice.me";
        }

        return $clubs.self::score($match).", rezultat {$match['kickoff_date']} | Utakmice.me";
    }

    /** @param array<string, mixed> $match */
    public static function description(array $match): string
    {
        $clubs = self::clubs($match);

        if ($match['status'] === 'finished') {
            return $clubs.self::score($match)." ({$match['kickoff_date']}) — {$match['league']}. Rezultat, tok meča i tabela.";
        }

        if ($match['status'] === 'live') {
            return $clubs.self::score($match)." — {$match['league']}, rezultat uživo i tabela.";
        }

        $tv = $match['tv'] ? " TV prenos: {$match['tv']}." : '';

        return "{$clubs}, {$match['league']} — {$match['kickoff_date']} u {$match['kickoff_time']}.{$tv} Rezultat uživo, međusobni dueli, forma i tabela.";
    }

    /** @param array<string, mixed> $match */
    public static function heading(array $match): string
    {
        return self::clubs($match).', '.$match['kickoff_date'];
    }

    /** @param array<string, mixed> $match */
    private static function clubs(array $match): string
    {
        return $match['home']['name'].' — '.$match['away']['name'];
    }

    /**
     * Empty for a match closed on the clock before any score arrived.
     *
     * @param  array<string, mixed>  $match
     */
    private static function score(array $match): string
    {
        return $match['home_score'] === null || $match['away_score'] === null
            ? ''
            : " {$match['home_score']}:{$match['away_score']}";
    }
}
