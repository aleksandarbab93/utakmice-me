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

        $date = $this->requestedDate($request) ?? Carbon::today();

        // The bare address keeps one title all year round, including the days
        // it jumps ahead to the next round; only a picked day names its date.
        $day = $request->query('date') && ! $date->isToday() ? $date : null;

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
            'title' => $this->scoresTitle($sport, $day),
            'heading' => $this->scoresHeading($sport, $day),
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

    private function requestedDate(Request $request): ?Carbon
    {
        $value = $request->query('date');

        if (! $value) {
            return null;
        }

        abort_unless(is_string($value) && Carbon::hasFormat($value, 'Y-m-d'), 404);

        return Carbon::createFromFormat('Y-m-d', $value);
    }

    /**
     * Search Console's top queries are "uzivo utakmice", "utakmice uzivo
     * kosarka" and "kosarka uzivo", so "uživo" stands right next to the word
     * it is typed with. A picked day gets its date instead of "danas i
     * večeras", which would be a lie there.
     */
    private function scoresTitle(string $sport, ?Carbon $day): string
    {
        $when = $day ? $day->format('d.m.Y.') : 'danas i večeras';

        return $sport === 'kosarka'
            ? "Košarka uživo — utakmice {$when}, Evroliga i TV prenosi | Utakmice.me"
            : "Utakmice uživo {$when} — fudbal, rezultati i TV prenosi | Utakmice.me";
    }

    private function scoresHeading(string $sport, ?Carbon $day): string
    {
        $heading = $sport === 'kosarka' ? 'Košarka uživo' : 'Utakmice uživo';

        return $day ? $heading.' — '.$day->format('d.m.Y.') : $heading;
    }

    private function validateSport(string $sport): void
    {
        abort_unless(in_array($sport, ['fudbal', 'kosarka'], true), 404);
    }
}
