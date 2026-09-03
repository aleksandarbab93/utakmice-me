<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\League;
use App\Support\Accent;

class LeaguesController extends Controller
{
    public function index()
    {
        $leagues = League::withCount('teams')->get()->keyBy('name');

        $liveByLeague = Fixture::live()
            ->selectRaw('league_id, count(*) as total')
            ->groupBy('league_id')
            ->pluck('total', 'league_id');

        $bySport = collect(['fudbal' => 'Fudbal', 'kosarka' => 'Košarka'])
            ->map(function (string $label, string $sport) use ($leagues) {
                $ordered = collect(Accent::leagues($sport))->map(fn ($name) => $leagues->get($name))->filter();

                $grouped = $ordered->groupBy(fn (League $league) => Accent::leagueCountry($league->name));
                $byCountry = $grouped->except([null])->sortKeys();

                if ($continental = $grouped->get(null)) {
                    $byCountry->put('Evropska takmičenja', $continental);
                }

                return ['sport' => $sport, 'label' => $label, 'byCountry' => $byCountry];
            })
            ->filter(fn (array $block) => $block['byCountry']->isNotEmpty())
            ->values();

        return view('leagues.index', [
            'bySport' => $bySport,
            'liveByLeague' => $liveByLeague,
        ]);
    }
}
