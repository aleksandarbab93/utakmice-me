<?php

namespace App\Support;

class Accent
{
    /**
     * The site's one accent color — football and basketball differ only in
     * label/typography from here on, never in color. Kept as a sport-keyed
     * method (rather than a bare constant) because every call site already
     * asks for it this way and the 'label' value still varies.
     */
    public static function classes(string $sport): array
    {
        return [
            'bg' => 'bg-accent',
            'text' => 'text-accent',
            'border' => 'border-accent',
            'tint' => 'bg-accent/14',
            'tintBorder' => 'border-accent/45',
            'tagBg' => 'bg-accent/16',
            'row' => 'bg-accent/6',
            'hex' => '#FF6A2B',
            'label' => $sport === 'kosarka' ? 'Košarka' : 'Fudbal',
        ];
    }

    public static function leagues(string $sport): array
    {
        return $sport === 'kosarka'
            ? ['Evroliga', 'Evrokup']
            : [
                'Premijer liga', 'La Liga', 'Serie A', 'Bundesliga', 'Ligue 1',
                'Liga prvaka', 'Evropska liga', 'Liga konferencija',
                'Superliga Srbije', 'Prva crnogorska liga', 'Premijer liga BiH', 'HNL', '1. SNL', 'Prva liga Makedonije',
            ];
    }

    /**
     * A real flag/badge image per league — country flags and UEFA club-
     * competition badges, off api-sports.io's free keyless media CDN (its
     * league/team ids are the same ones SStats.net uses, and country flags
     * are just an ISO code away). 'badge' images are dark artwork on a
     * transparent background and need a light tile behind them; 'flag'
     * images are opaque and don't.
     */
    public static function leagueIcon(string $leagueName): array
    {
        return match ($leagueName) {
            'Premijer liga' => ['url' => 'https://media.api-sports.io/flags/gb-eng.svg', 'type' => 'flag'],
            'La Liga' => ['url' => 'https://media.api-sports.io/flags/es.svg', 'type' => 'flag'],
            'Serie A' => ['url' => 'https://media.api-sports.io/flags/it.svg', 'type' => 'flag'],
            'Bundesliga' => ['url' => 'https://media.api-sports.io/flags/de.svg', 'type' => 'flag'],
            'Ligue 1' => ['url' => 'https://media.api-sports.io/flags/fr.svg', 'type' => 'flag'],
            'Superliga Srbije' => ['url' => 'https://media.api-sports.io/flags/rs.svg', 'type' => 'flag'],
            'Prva crnogorska liga' => ['url' => 'https://media.api-sports.io/flags/me.svg', 'type' => 'flag'],
            'Premijer liga BiH' => ['url' => 'https://media.api-sports.io/flags/ba.svg', 'type' => 'flag'],
            'HNL' => ['url' => 'https://media.api-sports.io/flags/hr.svg', 'type' => 'flag'],
            '1. SNL' => ['url' => 'https://media.api-sports.io/flags/si.svg', 'type' => 'flag'],
            'Prva liga Makedonije' => ['url' => 'https://media.api-sports.io/flags/mk.svg', 'type' => 'flag'],
            'Liga prvaka' => ['url' => 'https://media.api-sports.io/football/leagues/2.png', 'type' => 'badge'],
            'Evropska liga' => ['url' => 'https://media.api-sports.io/football/leagues/3.png', 'type' => 'badge'],
            'Liga konferencija' => ['url' => 'https://media.api-sports.io/football/leagues/848.png', 'type' => 'badge'],
            'Evroliga', 'Evrokup' => ['url' => 'https://media.api-sports.io/flags/eu.svg', 'type' => 'flag'],
            default => ['url' => null, 'type' => 'flag'],
        };
    }

    /**
     * Table positions that qualify for Europe (Liga prvaka / Evropska liga)
     * and that mean relegation, per league. Positions are 1-based table
     * ranks, counted from the bottom for relegation.
     */
    public static function leagueZones(string $leagueName): array
    {
        return match ($leagueName) {
            'Premijer liga', 'La Liga', 'Serie A' => [
                'cl' => [1, 2, 3, 4],
                'el' => [5],
                'relegationCount' => 3,
            ],
            'Bundesliga' => [
                'cl' => [1, 2, 3, 4],
                'el' => [5],
                'relegationCount' => 2,
            ],
            'Ligue 1' => [
                'cl' => [1, 2, 3],
                'el' => [4],
                'relegationCount' => 2,
            ],
            default => ['cl' => [], 'el' => [], 'relegationCount' => 0],
        };
    }
}
