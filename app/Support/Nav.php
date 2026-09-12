<?php

namespace App\Support;

/**
 * Builds sport-aware URLs. Fudbal lives at the site root (no /fudbal
 * prefix); košarka keeps its /kosarka prefix. Centralized here so the
 * URL scheme can change without touching every view.
 */
class Nav
{
    public static function home(string $sport): string
    {
        return $sport === 'kosarka' ? route('home.kosarka') : route('home.fudbal');
    }

    /** The day's matches are the front page, so this is home with a date on it. */
    public static function scores(string $sport, ?string $date = null): string
    {
        $url = self::home($sport);

        return $date ? $url.'?date='.$date : $url;
    }

    public static function standings(string $sport, ?string $liga = null): string
    {
        $url = $sport === 'kosarka' ? route('standings.kosarka') : route('standings.fudbal');

        return $liga ? $url.'?liga='.$liga : $url;
    }

    public static function match(string $slug): string
    {
        return route('match.show', $slug);
    }

    public static function news(?string $liga = null): string
    {
        $url = route('post.index');

        return $liga ? $url.'?liga='.$liga : $url;
    }

    public static function leagues(): string
    {
        return route('leagues');
    }

    public static function league(string $slug): string
    {
        return route('league.show', $slug);
    }

    public static function leagueResults(string $slug): string
    {
        return route('league.results', $slug);
    }

    public static function leagueFixtures(string $slug): string
    {
        return route('league.fixtures', $slug);
    }

    public static function about(): string
    {
        return route('page.about');
    }

    public static function contact(): string
    {
        return route('page.contact');
    }

    public static function privacy(): string
    {
        return route('page.privacy');
    }

    public static function advertising(): string
    {
        return route('page.advertising');
    }
}
