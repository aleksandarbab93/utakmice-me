<?php

namespace App\Http\Controllers;

use App\Support\Accent;
use App\Support\BasketballFeed;
use App\Support\FootballFeed;
use App\Support\PostFeed;
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
        $posts = PostFeed::posts($sport)->all();
        $hero = array_shift($posts);
        $secondary = array_splice($posts, 0, 2);
        $latest = collect($posts)->take(4);
        $mostRead = collect(array_merge([$hero], $secondary))->filter()->take(3)->values();

        // Basketball's new season doesn't start for a while — while uživo/danas/sutra
        // are all empty, feature the opening round's schedule instead of nothing.
        $openingRound = null;
        if ($sport === 'kosarka' && empty($liveTabs['uzivo']) && empty($liveTabs['danas']) && empty($liveTabs['sutra'])) {
            $openingRound = BasketballFeed::openingRound();
        }

        return view('home', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'active' => 'home',
            'tab' => $tab,
            'liveTabs' => $liveTabs,
            'activeMatches' => $liveTabs[$tab],
            'openingRound' => $openingRound,
            'hero' => $hero,
            'secondary' => $secondary,
            'latest' => $latest,
            'mostRead' => $mostRead,
        ]);
    }

    public function scores(Request $request, string $sport)
    {
        $this->validateSport($sport);

        $date = $request->query('date')
            ? Carbon::createFromFormat('Y-m-d', $request->query('date'))
            : Carbon::today();

        $groups = $sport === 'fudbal' ? FootballFeed::matchesForDate($date) : BasketballFeed::matchesForDate($date);

        // No explicit date and nothing today — jump straight to the season
        // opener's schedule instead of an empty "today" page.
        if ($sport === 'kosarka' && ! $request->query('date') && $groups->isEmpty()) {
            $nextDate = BasketballFeed::nextMatchDate();
            if ($nextDate) {
                $date = $nextDate;
                $groups = BasketballFeed::matchesForDate($date);
            }
        }

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
            'description' => $sport === 'kosarka'
                ? 'Rezultati košarkaških utakmica uživo — Evroliga i Evrokup. Tekući rezultati, raniji i budući mečevi.'
                : 'Rezultati fudbalskih utakmica uživo — Premijer liga, La Liga, Serie A, Bundesliga, Ligue 1, Liga prvaka i regionalne lige. Tekući rezultati, raniji i budući mečevi.',
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
