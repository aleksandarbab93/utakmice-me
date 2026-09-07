<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Team;
use App\Services\Euroleague\EuroleagueClient;
use App\Services\SStats\SStatsClient;
use App\Support\Accent;
use App\Support\BasketballMatchDetail;
use App\Support\LocalDay;
use App\Support\MatchDetail;
use Illuminate\Support\Carbon;

class MatchController extends Controller
{
    /**
     * Slug: crvena-zvezda-partizan-2026-08-09 — the date is always the
     * last 10 characters, the rest splits into two existing team slugs.
     * A bare numeric id (an old link, or one built before a rename)
     * 301s to the current canonical slug rather than 404ing.
     */
    public function show(string $slug, SStatsClient $sStatsClient, EuroleagueClient $euroleagueClient)
    {
        if (ctype_digit($slug)) {
            $fixture = Fixture::with(['homeTeam', 'awayTeam'])->find((int) $slug);
            abort_unless($fixture, 404);

            return redirect()->route('match.show', $fixture->slug, 301);
        }

        $fixture = $this->findBySlug($slug);
        abort_unless($fixture, 404);

        $fixture->loadMissing(['homeTeam', 'awayTeam', 'league']);

        if ($fixture->slug !== $slug) {
            return redirect()->route('match.show', $fixture->slug, 301);
        }

        $sport = $fixture->league->sport;
        abort_unless(in_array($sport, ['fudbal', 'kosarka'], true), 404);

        if ($sport === 'kosarka') {
            return view('basketball-match-detail', [
                'sport' => 'kosarka',
                'accent' => Accent::classes('kosarka'),
                'active' => 'scores',
                'match' => BasketballMatchDetail::build($fixture, $euroleagueClient),
            ]);
        }

        return view('match-detail', [
            'sport' => 'fudbal',
            'accent' => Accent::classes('fudbal'),
            'active' => 'scores',
            'match' => MatchDetail::build($fixture, $sStatsClient),
        ]);
    }

    private function findBySlug(string $slug): ?Fixture
    {
        $date = substr($slug, -10);
        $teams = substr($slug, 0, -11);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $teams === '') {
            return null;
        }

        try {
            $day = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Throwable) {
            return null;
        }

        $prefixes = [];
        foreach (str_split($teams) as $i => $char) {
            if ($char === '-') {
                $prefixes[] = substr($teams, 0, $i);
            }
        }

        $homeCandidates = Team::whereIn('slug', $prefixes)->get();

        foreach ($homeCandidates as $home) {
            $awaySlug = substr($teams, strlen($home->slug) + 1);
            $away = Team::where('slug', $awaySlug)->first();

            if (! $away) {
                continue;
            }

            $fixture = Fixture::whereBetween('kickoff_at', LocalDay::bounds($day))
                ->where('home_team_id', $home->id)
                ->where('away_team_id', $away->id)
                ->first();

            if ($fixture) {
                return $fixture;
            }
        }

        return null;
    }
}
