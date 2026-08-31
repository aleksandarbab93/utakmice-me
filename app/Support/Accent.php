<?php

namespace App\Support;

class Accent
{
    /**
     * Tailwind class names for the sport-driven accent color.
     * Written as complete literal strings per sport so Tailwind's
     * content scanner can find them (dynamic class interpolation
     * like "bg-accent-{$sport}" would otherwise get purged).
     */
    public static function classes(string $sport): array
    {
        return $sport === 'kosarka'
            ? [
                'bg' => 'bg-accent-basketball',
                'text' => 'text-accent-basketball',
                'border' => 'border-accent-basketball',
                'tint' => 'bg-accent-basketball/14',
                'tintBorder' => 'border-accent-basketball/45',
                'tagBg' => 'bg-accent-basketball/16',
                'row' => 'bg-accent-basketball/6',
                'hex' => '#FF6A2B',
                'label' => 'Košarka',
            ]
            : [
                'bg' => 'bg-accent-football',
                'text' => 'text-accent-football',
                'border' => 'border-accent-football',
                'tint' => 'bg-accent-football/14',
                'tintBorder' => 'border-accent-football/45',
                'tagBg' => 'bg-accent-football/16',
                'row' => 'bg-accent-football/6',
                'hex' => '#C6E64B',
                'label' => 'Fudbal',
            ];
    }

    public static function leagues(string $sport): array
    {
        return $sport === 'kosarka'
            ? ['Evroliga', 'Evrokup']
            : ['Premijer liga', 'La Liga', 'Serie A', 'Bundesliga', 'Ligue 1'];
    }

    /** Flag emoji per league — used in the Rezultati league group headers. */
    public static function leagueFlag(string $leagueName): string
    {
        return match ($leagueName) {
            'Premijer liga' => '🇬🇧',
            'La Liga' => '🇪🇸',
            'Serie A' => '🇮🇹',
            'Bundesliga' => '🇩🇪',
            'Ligue 1' => '🇫🇷',
            'Evroliga', 'Evrokup' => '🇪🇺',
            default => '🏳️',
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
