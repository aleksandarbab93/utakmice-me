<?php

namespace App\Support;

use App\Models\Fixture;
use App\Models\MatchDetailRecord;
use App\Services\SStats\SStatsClient;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the "tok meča" (match flow) + statistics view for a single fixture
 * from SStats.net's per-match detail payload — see payload() for where that
 * comes from, which is a longer story than it should be.
 */
class MatchDetail
{
    public static function build(Fixture $fixture, SStatsClient $client): array
    {
        $detail = self::fetch($fixture, $client);

        return [
            'league' => $fixture->league->name,
            'flag' => Accent::leagueIcon($fixture->league->name),
            'round' => $detail['game']['roundName'] ?? $fixture->matchday,
            'home' => ['name' => $fixture->homeTeam->name, 'initials' => TeamBadge::initials($fixture->homeTeam->name), 'crest' => $fixture->homeTeam->crest_url],
            'away' => ['name' => $fixture->awayTeam->name, 'initials' => TeamBadge::initials($fixture->awayTeam->name), 'crest' => $fixture->awayTeam->crest_url],
            'status' => $fixture->status,
            'statusLabel' => self::statusLabel($fixture),
            'kickoff' => $fixture->kickoff_at->local()->format('d.m.Y. H:i'),
            'kickoff_date' => $fixture->kickoff_at->local()->format('d.m.Y.'),
            'kickoff_time' => $fixture->kickoff_at->local()->format('H:i'),
            'home_score' => $fixture->home_score,
            'away_score' => $fixture->away_score,
            'venue' => $detail['venue']['name'] ?? $fixture->venue,
            'referee' => $detail['refereeName'] ?? null,
            'halves' => self::halves($detail, $fixture),
            'stats' => self::stats($detail),
            'preview' => self::preview($fixture, $client),
            'standings' => self::standingsFor($fixture),
        ];
    }

    private static function standingsFor(Fixture $fixture): array
    {
        $standings = FootballFeed::standings($fixture->league->slug);

        return [
            'rows' => $standings['rows'] ?? [],
            'zones' => $standings['zones'] ?? null,
        ];
    }

    private static function fetch(Fixture $fixture, SStatsClient $client): ?array
    {
        if ($fixture->status === 'scheduled') {
            return null;
        }

        return self::payload($fixture, $client);
    }

    /**
     * The match's detail payload, from wherever it can be had.
     *
     * Stored first, network second — SStats refuses to finish any response
     * past ~14.6 KB for datacenter IPs (see the match_details migration),
     * which is most of the matches worth writing about. Whatever machine
     * does manage a fetch writes the payload down, and from then on every
     * other machine reads it from the database instead. A finished match's
     * detail is final, so a stored one is never worth re-fetching.
     *
     * Shared with MatchReportGenerator: a report that names its scorers and
     * a "tok meča" panel are the same data seen twice.
     */
    public static function payload(Fixture $fixture, SStatsClient $client): ?array
    {
        $stored = MatchDetailRecord::where('fixture_id', $fixture->id)->first();

        if ($stored && $fixture->status === 'finished') {
            return $stored->payload;
        }

        $ttl = $fixture->status === 'finished' ? now()->addWeek() : now()->addSeconds(20);
        $detail = null;

        try {
            $detail = Cache::remember(
                "match-detail:{$fixture->external_id}",
                $ttl,
                fn () => $client->gameDetail((int) $fixture->external_id)
            );
        } catch (\Throwable) {
            // A stalled or refused fetch is not an error here: it just means
            // this machine can't reach that payload, and another one already
            // has or eventually will.
        }

        if ($detail) {
            self::remember($fixture, $detail);

            return $detail;
        }

        return $stored?->payload;
    }

    /** Keeps a payload we did manage to fetch, so no machine has to fetch it again. */
    private static function remember(Fixture $fixture, array $detail): void
    {
        MatchDetailRecord::updateOrCreate(
            ['fixture_id' => $fixture->id],
            ['payload' => $detail, 'fetched_at' => now()],
        );
    }

    private static function statusLabel(Fixture $fixture): string
    {
        if ($fixture->status === 'finished') {
            return 'KRAJ';
        }

        if ($fixture->status === 'scheduled') {
            return $fixture->kickoff_at->local()->format('d.m.Y. H:i');
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

    /** Head-to-head history + both teams' recent form — for matches that haven't started yet. */
    private static function preview(Fixture $fixture, SStatsClient $client): ?array
    {
        if ($fixture->status !== 'scheduled') {
            return null;
        }

        try {
            return Cache::remember(
                "match-preview:{$fixture->id}",
                now()->addHours(6),
                function () use ($client, $fixture) {
                    $homeId = (int) $fixture->homeTeam->external_id;
                    $awayId = (int) $fixture->awayTeam->external_id;

                    return [
                        'h2h' => self::formatGames($client->headToHead($homeId, $awayId, 5)),
                        'home_form' => self::formatGames($client->teamForm($homeId, 5), $fixture->homeTeam->name),
                        'away_form' => self::formatGames($client->teamForm($awayId, 5), $fixture->awayTeam->name),
                    ];
                }
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private static function formatGames(array $games, ?string $perspectiveTeam = null): array
    {
        return collect($games)
            ->map(function ($g) use ($perspectiveTeam) {
                $home = $g['homeTeam']['name'] ?? '?';
                $away = $g['awayTeam']['name'] ?? '?';
                $hs = $g['homeResult'] ?? null;
                $as = $g['awayResult'] ?? null;

                $result = null;
                if ($perspectiveTeam && $hs !== null && $as !== null) {
                    $isHome = $home === $perspectiveTeam;
                    $for = $isHome ? $hs : $as;
                    $against = $isHome ? $as : $hs;
                    $result = $for > $against ? 'W' : ($for < $against ? 'L' : 'D');
                }

                return [
                    'date' => isset($g['date']) ? \Illuminate\Support\Carbon::parse($g['date'])->format('d.m.y') : null,
                    'competition' => $g['season']['league']['name'] ?? null,
                    'home' => $home,
                    'away' => $away,
                    'home_score' => $hs,
                    'away_score' => $as,
                    'result' => $result,
                    'home_crest' => self::crestFor($home),
                    'away_crest' => self::crestFor($away),
                ];
            })
            ->all();
    }

    /** Look up a crest for a team appearing in H2H/form history by name — cheap local lookup, no extra API calls. */
    private static function crestFor(string $name): ?string
    {
        static $cache = [];

        if (! array_key_exists($name, $cache)) {
            $cache[$name] = \App\Models\Team::where('name', $name)->whereNotNull('crest_url')->value('crest_url');
        }

        return $cache[$name];
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
