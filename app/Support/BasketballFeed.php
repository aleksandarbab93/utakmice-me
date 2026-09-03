<?php

namespace App\Support;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Standing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reads the real, synced (via `php artisan basketball:sync`) EuroLeague /
 * EuroCup data out of the DB and reshapes it into the same array shapes
 * the Blade views expect — mirroring App\Support\FootballFeed.
 */
class BasketballFeed
{
    public static function leagues(): Collection
    {
        $bySlug = League::where('sport', 'kosarka')->get()->keyBy('name');

        return collect(Accent::leagues('kosarka'))
            ->map(fn ($name) => $bySlug->get($name))
            ->filter();
    }

    /** Fixtures for one calendar day across both competitions, grouped by league — for the Utakmice page. */
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
                    'minute' => null,
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
            'status' => $f->status === 'live' ? 'UŽIVO' : $f->kickoff_at->format('H:i'),
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

    /** The next calendar day (today or later) that has any fixture — used to jump straight to the season opener while nothing is on today/tomorrow. */
    public static function nextMatchDate(): ?Carbon
    {
        $leagueIds = self::leagues()->pluck('id');

        $fixture = Fixture::whereIn('league_id', $leagueIds)
            ->whereDate('kickoff_at', '>=', Carbon::today())
            ->orderBy('kickoff_at')
            ->first();

        return $fixture?->kickoff_at->copy()->startOfDay();
    }

    /** The opening round's fixtures — a preseason stand-in for the home page's empty uživo/danas/sutra widget. */
    public static function openingRound(): ?array
    {
        $date = self::nextMatchDate();

        if (! $date) {
            return null;
        }

        $leagueIds = self::leagues()->pluck('id');

        $matches = Fixture::whereIn('league_id', $leagueIds)
            ->whereDate('kickoff_at', $date)
            ->with(['homeTeam', 'awayTeam', 'league'])
            ->orderBy('kickoff_at')
            ->get()
            ->map(fn (Fixture $f) => [
                'league' => strtoupper($f->league->name),
                'status' => $f->kickoff_at->format('H:i'),
                'live' => false,
                'home' => $f->homeTeam->name,
                'away' => $f->awayTeam->name,
                'hs' => '–',
                'as' => '–',
            ])
            ->all();

        return [
            'date' => $date,
            'label' => FootballFeed::DAY_ABBR[$date->isoWeekday()].' '.$date->format('d.m.'),
            'matches' => $matches,
        ];
    }

    /** Standings table for one league (by slug) — defaults to the first tracked league. */
    public static function standings(?string $leagueSlug = null): array
    {
        $leagues = self::leagues();
        $league = ($leagueSlug ? $leagues->firstWhere('slug', $leagueSlug) : null) ?? $leagues->first();
        $emptyZones = ['cl' => [], 'el' => [], 'relegationCount' => 0];

        if (! $league) {
            return ['competition' => '', 'competitions' => [], 'rows' => [], 'zones' => $emptyZones, 'next' => null];
        }

        $rows = Standing::where('league_id', $league->id)
            ->with('team')
            ->orderBy('position')
            ->get()
            ->map(fn (Standing $s) => [
                'pos' => $s->position,
                'team' => $s->team->name,
                'played' => $s->played,
                'won' => $s->won,
                'draw' => null,
                'lost' => $s->lost,
                'goals_for' => $s->goals_for,
                'goals_against' => $s->goals_against,
                'points' => $s->points,
                'diff' => ($s->goal_diff >= 0 ? '+' : '').$s->goal_diff,
                'zone' => null,
                'form' => self::teamForm($s->team_id, $league->id),
                'next' => self::nextMatchTooltip($s->team_id, $league->id),
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
            'zones' => $emptyZones,
            'next' => $next ? [
                'label' => $next->homeTeam->name.' — '.$next->awayTeam->name,
                'when' => FootballFeed::DAY_ABBR[$next->kickoff_at->isoWeekday()].' '.$next->kickoff_at->format('H:i'),
            ] : null,
        ];
    }

    /** A team's last 5 finished games (oldest first) as W/L results — basketball has no draws — plus a tooltip naming the match. */
    private static function teamForm(int $teamId, int $leagueId): array
    {
        return Fixture::where('league_id', $leagueId)
            ->where('status', 'finished')
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->with(['homeTeam', 'awayTeam'])
            ->orderByDesc('kickoff_at')
            ->limit(5)
            ->get()
            ->reverse()
            ->values()
            ->map(function (Fixture $f) use ($teamId) {
                $isHome = $f->home_team_id === $teamId;
                $for = $isHome ? $f->home_score : $f->away_score;
                $against = $isHome ? $f->away_score : $f->home_score;

                return [
                    'result' => $for > $against ? 'W' : 'L',
                    'tooltip' => "{$f->home_score}:{$f->away_score} ({$f->homeTeam->name} - {$f->awayTeam->name})\n".$f->kickoff_at->format('d.m.Y.'),
                ];
            })
            ->all();
    }

    /** A team's next scheduled league fixture, as a tooltip for the FORMA column's leading "?" badge. */
    private static function nextMatchTooltip(int $teamId, int $leagueId): ?string
    {
        $fixture = Fixture::where('league_id', $leagueId)
            ->where('status', 'scheduled')
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('kickoff_at')
            ->first();

        if (! $fixture) {
            return null;
        }

        return "Sljedeća utakmica:\n{$fixture->homeTeam->name} - {$fixture->awayTeam->name}\n".$fixture->kickoff_at->format('d.m.Y.');
    }
}
