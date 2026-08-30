<?php

namespace App\Support;

use App\Models\Fixture;
use App\Services\SStats\SStatsClient;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the "tok meča" (match flow) + statistics view for a single fixture
 * from SStats.net's per-match detail endpoint. Finished matches are cached
 * indefinitely (their data never changes); live matches briefly, so the
 * page stays close to real time without hammering the API on every request.
 */
class MatchDetail
{
    public static function build(Fixture $fixture, SStatsClient $client): array
    {
        $detail = self::fetch($fixture, $client);

        return [
            'league' => $fixture->league->name,
            'flag' => Accent::leagueFlag($fixture->league->name),
            'round' => $detail['game']['roundName'] ?? $fixture->matchday,
            'home' => ['name' => $fixture->homeTeam->name, 'initials' => TeamBadge::initials($fixture->homeTeam->name)],
            'away' => ['name' => $fixture->awayTeam->name, 'initials' => TeamBadge::initials($fixture->awayTeam->name)],
            'status' => $fixture->status,
            'statusLabel' => self::statusLabel($fixture),
            'kickoff' => $fixture->kickoff_at->format('d.m.Y. H:i'),
            'home_score' => $fixture->home_score,
            'away_score' => $fixture->away_score,
            'venue' => $detail['venue']['name'] ?? $fixture->venue,
            'referee' => $detail['refereeName'] ?? null,
            'halves' => self::halves($detail, $fixture),
            'stats' => self::stats($detail),
        ];
    }

    private static function fetch(Fixture $fixture, SStatsClient $client): ?array
    {
        if ($fixture->status === 'scheduled') {
            return null;
        }

        $ttl = $fixture->status === 'finished' ? now()->addWeek() : now()->addSeconds(20);

        try {
            return Cache::remember(
                "match-detail:{$fixture->external_id}",
                $ttl,
                fn () => $client->gameDetail((int) $fixture->external_id)
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private static function statusLabel(Fixture $fixture): string
    {
        if ($fixture->status === 'finished') {
            return 'KRAJ';
        }

        if ($fixture->status === 'scheduled') {
            return $fixture->kickoff_at->format('d.m.Y. H:i');
        }

        $minute = $fixture->minute ? (int) preg_replace('/\D/', '', $fixture->minute) : null;

        if (! $minute) {
            return 'UŽIVO';
        }

        $half = $minute > 45 ? '2. poluvrijeme' : '1. poluvrijeme';

        return "{$half} · {$minute}'";
    }

    /** Normalized, chronologically sorted event list — shared with MatchReportGenerator. */
    public static function events(?array $detail): \Illuminate\Support\Collection
    {
        if (! $detail) {
            return collect();
        }

        $homeId = $detail['game']['homeTeam']['id'] ?? null;

        return collect($detail['events'] ?? [])
            ->map(fn ($e) => self::mapEvent($e, $homeId))
            ->filter()
            ->sortBy('elapsed')
            ->values();
    }

    private static function halves(?array $detail, Fixture $fixture): array
    {
        $events = self::events($detail);

        $game = $detail['game'] ?? [];
        $halves = [];

        $first = $events->filter(fn ($e) => $e['elapsed'] <= 45)->values();
        if ($first->isNotEmpty() || isset($game['homeHTResult'])) {
            $halves[] = [
                'label' => '1. poluvrijeme',
                'score' => isset($game['homeHTResult']) ? $game['homeHTResult'].' : '.$game['awayHTResult'] : null,
                'events' => $first,
            ];
        }

        $second = $events->filter(fn ($e) => $e['elapsed'] > 45)->values();
        if ($second->isNotEmpty() || $fixture->status === 'finished') {
            $halves[] = [
                'label' => '2. poluvrijeme',
                'score' => $fixture->status === 'finished' ? $fixture->home_score.' : '.$fixture->away_score : null,
                'events' => $second,
            ];
        }

        return $halves;
    }

    private static function mapEvent(array $e, ?int $homeId): ?array
    {
        $name = $e['name'] ?? '';
        $player = $e['player']['name'] ?? null;
        $other = $e['assistPlayer']['name'] ?? null;

        if (! $player) {
            return null;
        }

        [$icon, $subtitle] = match (true) {
            str_contains($name, 'Own Goal') => ['og', 'Autogol'],
            str_contains($name, 'Missed Penalty') => ['miss', 'Promašen penal'],
            str_contains($name, 'Penalty') => ['goal', 'Penal'],
            str_contains($name, 'cancelled') => ['cancel', 'Gol poništen (VAR)'],
            str_contains($name, 'Goal') => ['goal', $other ? "Asistencija: {$other}" : null],
            $name === 'Red Card' => ['red', null],
            $name === 'Yellow Card' => ['yellow', null],
            str_contains($name, 'Substitution') => ['sub', $other ? "Umjesto: {$other}" : null],
            default => [null, null],
        };

        if (! $icon) {
            return null;
        }

        return [
            'elapsed' => $e['elapsed'] ?? 0,
            'extra' => $e['extra'] ?? null,
            'side' => ($e['teamId'] ?? null) === $homeId ? 'home' : 'away',
            'icon' => $icon,
            'player' => $player,
            'subtitle' => $subtitle,
        ];
    }

    private static function stats(?array $detail): array
    {
        $s = $detail['statistics'] ?? null;

        if (! $s) {
            return [];
        }

        $rows = [
            ['key' => 'ballPossession', 'label' => 'Posjed lopte', 'suffix' => '%'],
            ['key' => 'totalShots', 'label' => 'Udarci', 'suffix' => ''],
            ['key' => 'shotsOnGoal', 'label' => 'Udarci u okvir gola', 'suffix' => ''],
            ['key' => 'cornerKicks', 'label' => 'Korneri', 'suffix' => ''],
            ['key' => 'fouls', 'label' => 'Prekršaji', 'suffix' => ''],
            ['key' => 'offsides', 'label' => 'Ofsajdi', 'suffix' => ''],
            ['key' => 'yellowCards', 'label' => 'Žuti kartoni', 'suffix' => ''],
            ['key' => 'redCards', 'label' => 'Crveni kartoni', 'suffix' => ''],
        ];

        return collect($rows)
            ->map(function ($row) use ($s) {
                $home = $s[$row['key'].'Home'] ?? null;
                $away = $s[$row['key'].'Away'] ?? null;

                if ($home === null || $away === null) {
                    return null;
                }

                $total = $home + $away;

                return [
                    'label' => $row['label'],
                    'home' => $home,
                    'away' => $away,
                    'suffix' => $row['suffix'],
                    'pct_home' => $total > 0 ? round($home / $total * 100) : 50,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
