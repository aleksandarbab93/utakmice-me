<?php

namespace App\Http\Controllers;

use App\Support\Accent;
use App\Support\BasketballFeed;
use App\Support\FootballFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SportController extends Controller
{
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
            'title' => $this->scoresTitle($sport, $date),
            'description' => $sport === 'kosarka'
                ? 'Košarkaške utakmice danas i večeras — Evroliga i Evrokup uživo. Rezultati, tabele i raspored iz Crne Gore, Srbije i regiona.'
                : 'Fudbalske utakmice danas i večeras — rezultati uživo, TV prenosi i tabele. Liga prvaka, liga petice, Prva crnogorska liga, Superliga Srbije i regionalne lige.',
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

    /**
     * "Utakmice danas i večeras — fudbal, rezultati uživo i TV prenosi", the
     * way the reference site titles its front page: the phrases people
     * actually type, in the order they type them. Another day gets that
     * day's date instead of "danas i večeras", which would be a lie there.
     */
    private function scoresTitle(string $sport, Carbon $date): string
    {
        $when = $date->isToday() ? 'danas i večeras' : $date->format('d.m.Y.');

        return $sport === 'kosarka'
            ? "Košarka {$when} — Evroliga, rezultati uživo i TV prenosi | Utakmice.me"
            : "Utakmice {$when} — fudbal, rezultati uživo i TV prenosi | Utakmice.me";
    }

    private function validateSport(string $sport): void
    {
        abort_unless(in_array($sport, ['fudbal', 'kosarka'], true), 404);
    }
}
