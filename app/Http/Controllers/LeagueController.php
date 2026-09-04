<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Goal;
use App\Models\League;
use App\Support\Accent;
use App\Support\BasketballFeed;
use App\Support\FootballFeed;
use App\Support\RoundLabel;
use App\Support\TeamBadge;
use Illuminate\Support\Carbon;

class LeagueController extends Controller
{
    public function show(string $slug)
    {
        $league = $this->findLeague($slug);
        $sport = $league->sport;

        $standings = $sport === 'fudbal' ? FootballFeed::standings($league->slug) : BasketballFeed::standings($league->slug);

        $scorers = $sport === 'fudbal'
            ? Goal::where('league_id', $league->id)
                ->where('is_own_goal', false)
                ->with('team')
                ->selectRaw('player_name, team_id, COUNT(*) as goals')
                ->groupBy('player_name', 'team_id')
                ->orderByDesc('goals')
                ->limit(10)
                ->get()
            : collect();

        $round = $this->currentRound($league);

        $results = $round === null
            ? collect()
            : $this->list($league)->where('matchday', $round)->where('status', 'finished')->orderBy('kickoff_at')->get();

        return view('leagues.show', $this->shared($league) + [
            'standings' => $standings,
            'scorers' => $scorers,
            'round' => RoundLabel::sr($round),
            'results' => $results->map(fn (Fixture $f) => $this->matchPayload($f)),
        ]);
    }

    /** Played matches, newest first. */
    public function results(string $slug)
    {
        $league = $this->findLeague($slug);

        $fixtures = $this->list($league)
            ->whereIn('status', ['finished'])
            ->orderByDesc('kickoff_at')
            ->paginate(20);

        return view('leagues.fixtures', $this->shared($league) + [
            'tab' => 'results',
            'fixtures' => $fixtures,
        ]);
    }

    /** Everything still to be played, soonest first. */
    public function fixtures(string $slug)
    {
        $league = $this->findLeague($slug);

        $fixtures = $this->list($league)
            ->whereNotIn('status', ['finished'])
            ->orderBy('kickoff_at')
            ->paginate(20);

        return view('leagues.fixtures', $this->shared($league) + [
            'tab' => 'fixtures',
            'fixtures' => $fixtures,
        ]);
    }

    private function findLeague(string $slug): League
    {
        return League::where('slug', $slug)->firstOrFail();
    }

    private function list(League $league)
    {
        return Fixture::with(['homeTeam', 'awayTeam'])->where('league_id', $league->id);
    }

    /** What every tab of the league page needs. */
    private function shared(League $league): array
    {
        $sport = $league->sport;

        $now = Carbon::now();

        return [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'active' => 'league',
            'league' => $league,
            'clubCount' => $league->teams()->count(),
            'season' => $now->month >= 7 ? $now->year : $now->year - 1,
        ];
    }

    private function matchPayload(Fixture $f): array
    {
        return [
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
            'kickoff' => $f->kickoff_at->local()->format('H:i'),
        ];
    }

    /**
     * The round being played "now": the round of the fixture closest in
     * time to right now — without this, an archived first round would win
     * on a fresh sync where nothing has been marked finished yet.
     */
    private function currentRound(League $league): ?string
    {
        $base = Fixture::where('league_id', $league->id)->whereNotNull('matchday');

        $before = (clone $base)->where('kickoff_at', '<=', now())->orderByDesc('kickoff_at')->first();
        $after = (clone $base)->where('kickoff_at', '>', now())->orderBy('kickoff_at')->first();

        $closest = match (true) {
            ! $before => $after,
            ! $after => $before,
            default => now()->diffInSeconds($before->kickoff_at, true) <= now()->diffInSeconds($after->kickoff_at, true)
                ? $before
                : $after,
        };

        return $closest?->matchday;
    }
}
