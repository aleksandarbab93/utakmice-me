<?php

namespace App\Support;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Standing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reads the real, synced (via `php artisan football:sync`) football data
 * out of the DB and reshapes it into the same array shapes the Blade
 * views already expect from App\Support\SampleData — so the views
 * themselves needed no changes.
 */
class FootballFeed
{
    public const DAY_ABBR = [1 => 'PON', 2 => 'UTO', 3 => 'SRE', 4 => 'ČET', 5 => 'PET', 6 => 'SUB', 7 => 'NED'];

    public static function dayLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'DANAS';
        }

        if ($date->isTomorrow()) {
            return 'SUTRA';
        }

        if ($date->isYesterday()) {
            return 'JUČE';
        }

        return self::DAY_ABBR[$date->isoWeekday()].' '.$date->format('d.m.');
    }

    public static function leagues(): Collection
    {
        $bySlug = League::where('sport', 'fudbal')->get()->keyBy('name');

        return collect(Accent::leagues('fudbal'))
            ->map(fn ($name) => $bySlug->get($name))
            ->filter();
    }

    /**
     * Fixtures for one calendar day across all five leagues, grouped by
     * league with sidebar/header metadata attached — for the Rezultati page.
     */
    public static function matchesForDate(Carbon $date): Collection
    {
        $leagues = self::leagues();

        $fixtures = Fixture::whereIn('league_id', $leagues->pluck('id'))
            ->whereDate('kickoff_at', $date)
            ->with(['homeTeam', 'awayTeam', 'league'])
            ->orderBy('kickoff_at')
            ->get()
            ->groupBy('league_id');

        return $leagues->map(function (League $league) use ($fixtures) {
            $matches = ($fixtures->get($league->id) ?? collect())
                ->map(fn (Fixture $f) => [
                    'id' => $f->id,
                    'home' => $f->homeTeam->name,
                    'homeInitials' => TeamBadge::initials($f->homeTeam->name),
                    'homeCrest' => $f->homeTeam->crest_url,
                    'away' => $f->awayTeam->name,
                    'awayInitials' => TeamBadge::initials($f->awayTeam->name),
                    'awayCrest' => $f->awayTeam->crest_url,
                    'status' => $f->status,
                    'home_score' => $f->home_score,
                    'away_score' => $f->away_score,
                    'minute' => $f->status === 'live' && $f->minute ? $f->minute."'" : null,
                    'kickoff' => $f->kickoff_at->format('H:i'),
                ])
                ->values();

            return [
                'name' => $league->name,
                'slug' => $league->slug,
                'flag' => Accent::leagueIcon($league->name),
                'matches' => $matches,
            ];
        })->filter(fn ($group) => $group['matches']->isNotEmpty())->values();
    }

    /** Uživo / Danas / Sutra buckets for the home page widget. */
    public static function homeLive(): array
    {
        $leagueIds = self::leagues()->pluck('id');
        $base = Fixture::whereIn('league_id', $leagueIds)->with(['homeTeam', 'awayTeam', 'league']);

        $uzivo = (clone $base)->where('status', 'live')->get();
        $danas = (clone $base)->where('status', '!=', 'live')->whereDate('kickoff_at', Carbon::today())->orderBy('kickoff_at')->get();
        $sutra = (clone $base)->whereDate('kickoff_at', Carbon::tomorrow())->orderBy('kickoff_at')->get();

        $map = fn (Fixture $f) => [
            'id' => $f->id,
            'league' => strtoupper($f->league->name),
            'status' => $f->status === 'live' ? ($f->minute ? $f->minute."'" : 'UŽIVO') : $f->kickoff_at->format('H:i'),
            'live' => $f->status === 'live',
            'home' => $f->homeTeam->name,
            'away' => $f->awayTeam->name,
            'hs' => $f->status === 'scheduled' ? '–' : (string) $f->home_score,
            'as' => $f->status === 'scheduled' ? '–' : (string) $f->away_score,
        ];

        return [
            'uzivo' => $uzivo->map($map)->all(),
            'danas' => $danas->map($map)->all(),
            'sutra' => $sutra->map($map)->all(),
        ];
    }

    /** Standings table for one league (by slug) — defaults to the first tracked league. */
    public static function standings(?string $leagueSlug = null): array
    {
        $leagues = self::leagues();
        $league = ($leagueSlug ? $leagues->firstWhere('slug', $leagueSlug) : null) ?? $leagues->first();

        $standings = Standing::where('league_id', $league->id)->with('team')->orderBy('position')->get();
        $zones = Accent::leagueZones($league->name);
        $relegationStart = $standings->count() - $zones['relegationCount'] + 1;

        $rows = $standings
            ->map(fn (Standing $s) => [
                'pos' => $s->position,
                'team' => $s->team->name,
                'played' => $s->played,
                'won' => $s->won,
                'draw' => $s->draw,
                'lost' => $s->lost,
                'goals_for' => $s->goals_for,
                'goals_against' => $s->goals_against,
                'points' => $s->points,
                'diff' => ($s->goal_diff >= 0 ? '+' : '').$s->goal_diff,
                'zone' => match (true) {
                    in_array($s->position, $zones['cl'], true) => 'cl',
                    in_array($s->position, $zones['el'], true) => 'el',
                    $zones['relegationCount'] > 0 && $s->position >= $relegationStart => 'relegation',
                    default => null,
                },
                'form' => self::teamForm($s->team_id, $league->id),
            ])
            ->all();

        $next = Fixture::where('league_id', $league->id)
            ->where('status', 'scheduled')
            ->orderBy('kickoff_at')
            ->with(['homeTeam', 'awayTeam'])
            ->first();

        return [
            'competition' => $league->name,
            'competitions' => $leagues->pluck('name')->all(),
            'rows' => $rows,
            'zones' => $zones,
            'next' => $next ? [
                'label' => $next->homeTeam->name.' — '.$next->awayTeam->name,
                'when' => self::DAY_ABBR[$next->kickoff_at->isoWeekday()].' '.$next->kickoff_at->format('H:i'),
            ] : null,
        ];
    }

    /** A team's last 5 finished league matches (oldest first) as W/D/L letters. */
    private static function teamForm(int $teamId, int $leagueId): array
    {
        return Fixture::where('league_id', $leagueId)
            ->where('status', 'finished')
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->orderByDesc('kickoff_at')
            ->limit(5)
            ->get()
            ->reverse()
            ->values()
            ->map(function (Fixture $f) use ($teamId) {
                $isHome = $f->home_team_id === $teamId;
                $for = $isHome ? $f->home_score : $f->away_score;
                $against = $isHome ? $f->away_score : $f->home_score;

                return $for > $against ? 'W' : ($for < $against ? 'L' : 'D');
            })
            ->all();
    }
}
