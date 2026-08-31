<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Services\Euroleague\EuroleagueClient;
use App\Services\SStats\SStatsClient;
use App\Support\Accent;
use App\Support\BasketballMatchDetail;
use App\Support\MatchDetail;

class MatchController extends Controller
{
    public function show(Fixture $fixture, SStatsClient $sStatsClient, EuroleagueClient $euroleagueClient)
    {
        $fixture->loadMissing(['homeTeam', 'awayTeam', 'league']);

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
}
