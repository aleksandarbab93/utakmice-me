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
    private const DAY_ABBR = [1 => 'PON', 2 => 'UTO', 3 => 'SRE', 4 => 'ČET', 5 => 'PET', 6 => 'SUB', 7 => 'NED'];

    public static function leagues(): Collection
    {
        $bySlug = League::where('sport', 'fudbal')->get()->keyBy('name');

        return collect(Accent::leagues('fudbal'))
            ->map(fn ($name) => $bySlug->get($name))
            ->filter();
    }

    /** Today's fixtures across all five leagues, grouped by league name — for the Rezultati page. */
    public static function todaysMatchesGrouped(): Collection
    {
        $leagueIds = self::leagues()->pluck('id');

        $fixtures = Fixture::whereIn('league_id', $leagueIds)
            ->whereDate('kickoff_at', Carbon::today())
            ->with(['homeTeam', 'awayTeam', 'league'])
            ->orderBy('kickoff_at')
            ->get();

        return $fixtures
            ->map(fn (Fixture $f) => self::mapMatch($f))
            ->groupBy('league');
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

        $rows = Standing::where('league_id', $league->id)
            ->with('team')
            ->orderBy('position')
            ->get()
            ->map(fn (Standing $s) => [
                'pos' => $s->position,
                'team' => $s->team->name,
                'played' => $s->played,
                'points' => $s->points,
                'diff' => ($s->goal_diff >= 0 ? '+' : '').$s->goal_diff,
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
            'next' => $next ? [
                'label' => $next->homeTeam->name.' — '.$next->awayTeam->name,
                'when' => self::DAY_ABBR[$next->kickoff_at->isoWeekday()].' '.$next->kickoff_at->format('H:i'),
            ] : null,
        ];
    }

    private static function mapMatch(Fixture $f): array
    {
        return [
            'league' => $f->league->name,
            'home' => $f->homeTeam->name,
            'away' => $f->awayTeam->name,
            'status' => $f->status,
            'home_score' => $f->home_score,
            'away_score' => $f->away_score,
            'minute' => $f->status === 'live' && $f->minute ? $f->minute."'" : null,
            'kickoff' => $f->kickoff_at->format('H:i'),
        ];
    }
}
