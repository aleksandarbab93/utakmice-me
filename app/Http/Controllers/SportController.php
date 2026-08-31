<?php

namespace App\Http\Controllers;

use App\Support\Accent;
use App\Support\BasketballFeed;
use App\Support\FootballFeed;
use App\Support\PostFeed;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $liveTabs = $sport === 'fudbal' ? FootballFeed::homeLive() : BasketballFeed::homeLive();
        $posts = $sport === 'fudbal' ? PostFeed::posts($sport)->all() : SampleData::posts($sport);
        $hero = array_shift($posts);
        $secondary = array_splice($posts, 0, 2);
        $latest = collect($posts)->take(4);
        $mostRead = collect(array_merge([$hero], $secondary))->filter()->take(3)->values();
        $standings = $sport === 'fudbal' ? FootballFeed::standings() : BasketballFeed::standings();

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

        $date = $request->query('date')
            ? Carbon::createFromFormat('Y-m-d', $request->query('date'))
            : Carbon::today();

        $groups = $sport === 'fudbal' ? FootballFeed::matchesForDate($date) : BasketballFeed::matchesForDate($date);

        return view('scores', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'active' => 'scores',
            'groups' => $groups,
            'leagues' => collect(Accent::leagues($sport))->map(fn ($name) => ['name' => $name, 'slug' => Str::slug($name)]),
            'date' => $date,
            'dateLabel' => FootballFeed::dayLabel($date),
            'prevDate' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $date->copy()->addDay()->format('Y-m-d'),
        ]);
    }

    public function standings(Request $request, string $sport)
    {
        $this->validateSport($sport);

        $leagueSlug = $request->query('liga');
        $standings = $sport === 'fudbal'
            ? FootballFeed::standings($leagueSlug ? Str::slug($leagueSlug) : null)
            : BasketballFeed::standings($leagueSlug ? Str::slug($leagueSlug) : null);

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
