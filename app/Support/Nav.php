<?php

namespace App\Support;

/**
 * Builds sport-aware URLs. Fudbal lives at the site root (no /fudbal
 * prefix); košarka keeps its /kosarka prefix. Centralized here so the
 * URL scheme can change without touching every view.
 */
class Nav
{
    public static function home(string $sport, ?string $tab = null): string
    {
        $url = $sport === 'kosarka' ? route('home.kosarka') : route('home.fudbal');

        return $tab ? $url.'?tab='.$tab : $url;
    }

    public static function scores(string $sport, ?string $date = null): string
    {
        $url = $sport === 'kosarka' ? route('scores.kosarka') : route('scores.fudbal');

        return $date ? $url.'?date='.$date : $url;
    }

    public static function standings(string $sport, ?string $liga = null): string
    {
        $url = $sport === 'kosarka' ? route('standings.kosarka') : route('standings.fudbal');

        return $liga ? $url.'?liga='.$liga : $url;
    }

    public static function match(int $fixtureId): string
    {
        return route('match.show', $fixtureId);
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
