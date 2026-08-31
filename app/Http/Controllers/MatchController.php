<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Services\SStats\SStatsClient;
use App\Support\Accent;
use App\Support\MatchDetail;

class MatchController extends Controller
{
    public function show(Fixture $fixture, SStatsClient $client)
    {
        $fixture->loadMissing(['homeTeam', 'awayTeam', 'league']);

        abort_unless($fixture->league->sport === 'fudbal', 404);

        return view('match-detail', [
            'sport' => 'fudbal',
            'accent' => Accent::classes('fudbal'),
            'active' => 'scores',
            'match' => MatchDetail::build($fixture, $client),
        ]);
    }
}
