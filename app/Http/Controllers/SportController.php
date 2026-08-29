<?php

namespace App\Http\Controllers;

use App\Support\Accent;
use App\Support\FootballFeed;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SportController extends Controller
{
    public function home(Request $request, string $sport)
    {
        $this->validateSport($sport);

        $tab = $request->query('tab', 'uzivo');
        if (! in_array($tab, ['uzivo', 'danas', 'sutra'], true)) {
            $tab = 'uzivo';
        }

        $liveTabs = $sport === 'fudbal' ? FootballFeed::homeLive() : SampleData::homeLive($sport);
        $posts = SampleData::posts($sport);
        $hero = array_shift($posts);
        $secondary = array_splice($posts, 0, 2);
        $latest = collect($posts)->take(4);
        $mostRead = collect(array_merge([$hero], $secondary))->take(3)->values();
        $standings = $sport === 'fudbal' ? FootballFeed::standings() : SampleData::standings($sport);

        return view('home', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'active' => 'home',
            'tab' => $tab,
            'liveTabs' => $liveTabs,
            'activeMatches' => $liveTabs[$tab],
            'hero' => $hero,
            'secondary' => $secondary,
            'latest' => $latest,
            'mostRead' => $mostRead,
            'miniStandings' => array_slice($standings['rows'], 0, 3),
            'standingsCompetition' => $standings['competition'],
        ]);
    }

    public function scores(Request $request, string $sport)
    {
        $this->validateSport($sport);

        if ($sport === 'fudbal') {
            $grouped = FootballFeed::todaysMatchesGrouped();
        } else {
            $grouped = collect(SampleData::matches($sport))->groupBy('league');
        }

        return view('scores', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'active' => 'scores',
            'grouped' => $grouped,
        ]);
    }

    public function standings(Request $request, string $sport)
    {
        $this->validateSport($sport);

        if ($sport === 'fudbal') {
            $leagueSlug = $request->query('liga');
            $standings = FootballFeed::standings($leagueSlug ? Str::slug($leagueSlug) : null);
        } else {
            $standings = SampleData::standings($sport);
        }

        return view('standings', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'active' => 'standings',
            'standings' => $standings,
        ]);
    }

    private function validateSport(string $sport): void
    {
        abort_unless(in_array($sport, ['fudbal', 'kosarka'], true), 404);
    }
}
