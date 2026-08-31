<?php

namespace App\Support;

use App\Models\Fixture;
use App\Services\Euroleague\EuroleagueClient;
use Illuminate\Support\Carbon;

/**
 * Builds the match-preview view for a EuroLeague/EuroCup fixture: head to
 * head history and each team's recent form, pulled straight from the
 * Euroleague live API (spanning a couple of past seasons, since a brand
 * new season starts with nobody having played yet) — no play-by-play or
 * box-score data is available through the free API, so unlike football's
 * MatchDetail there is no "tok meča"/statistics tab here.
 */
class BasketballMatchDetail
{
    public static function build(Fixture $fixture, EuroleagueClient $client): array
    {
        return [
            'league' => $fixture->league->name,
            'flag' => Accent::leagueFlag($fixture->league->name),
            'round' => $fixture->matchday,
            'home' => ['name' => $fixture->homeTeam->name, 'initials' => TeamBadge::initials($fixture->homeTeam->name), 'crest' => $fixture->homeTeam->crest_url],
            'away' => ['name' => $fixture->awayTeam->name, 'initials' => TeamBadge::initials($fixture->awayTeam->name), 'crest' => $fixture->awayTeam->crest_url],
            'status' => $fixture->status,
            'statusLabel' => self::statusLabel($fixture),
            'kickoff' => $fixture->kickoff_at->format('d.m.Y. H:i'),
            'home_score' => $fixture->home_score,
            'away_score' => $fixture->away_score,
            'venue' => $fixture->venue,
            'preview' => self::preview($fixture, $client),
            'standings' => self::standingsFor($fixture),
        ];
    }

    private static function statusLabel(Fixture $fixture): string
    {
        if ($fixture->status === 'finished') {
            return 'KRAJ';
        }

        if ($fixture->status === 'scheduled') {
            return $fixture->kickoff_at->format('d.m.Y. H:i');
        }

        return 'UŽIVO';
    }

    private static function preview(Fixture $fixture, EuroleagueClient $client): ?array
    {
        try {
            $competitionCode = $fixture->league->external_id;
            $seasons = self::recentSeasonCodes($competitionCode);
            $homeCode = $fixture->homeTeam->external_id;
            $awayCode = $fixture->awayTeam->external_id;

            $home = $fixture->homeTeam->name;
            $away = $fixture->awayTeam->name;

            return [
                'h2h' => self::formatGames($client->headToHead($competitionCode, $seasons, $homeCode, $awayCode), [$home, $away]),
                'home_form' => self::formatGames($client->teamRecentGames($competitionCode, $seasons, $homeCode), [$home], $home),
                'away_form' => self::formatGames($client->teamRecentGames($competitionCode, $seasons, $awayCode), [$away], $away),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /** This season plus the two before it — a new season starts with nobody having played yet. */
    private static function recentSeasonCodes(string $competitionCode): array
    {
        $now = Carbon::now();
        $year = $now->month >= 7 ? $now->year : $now->year - 1;

        return [$competitionCode.$year, $competitionCode.($year - 1), $competitionCode.($year - 2)];
    }

    private static function formatGames(array $games, array $highlight = [], ?string $perspectiveTeam = null): array
    {
        return collect($games)
            ->map(function ($g) use ($highlight, $perspectiveTeam) {
                $home = $g['local']['club']['name'] ?? '?';
                $away = $g['road']['club']['name'] ?? '?';
                $hs = $g['local']['score'] ?? null;
                $as = $g['road']['score'] ?? null;

                $result = null;
                if ($perspectiveTeam && $hs !== null && $as !== null) {
                    $isHome = $home === $perspectiveTeam;
                    $for = $isHome ? $hs : $as;
                    $against = $isHome ? $as : $hs;
                    $result = $for > $against ? 'W' : 'L';
                }

                return [
                    'date' => isset($g['date']) ? Carbon::parse($g['date'])->format('d.m.y') : null,
                    'competition' => $g['season']['name'] ?? null,
                    'home' => $home,
                    'away' => $away,
                    'home_score' => $hs,
                    'away_score' => $as,
                    'result' => $result,
                    'home_crest' => $g['local']['club']['images']['crest'] ?? null,
                    'away_crest' => $g['road']['club']['images']['crest'] ?? null,
                    'home_highlight' => in_array($home, $highlight, true),
                    'away_highlight' => in_array($away, $highlight, true),
                ];
            })
            ->all();
    }

    private static function standingsFor(Fixture $fixture): array
    {
        $standings = BasketballFeed::standings($fixture->league->slug);

        return [
            'rows' => $standings['rows'] ?? [],
            'zones' => $standings['zones'] ?? null,
        ];
    }
}
