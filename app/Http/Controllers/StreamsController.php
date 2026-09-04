<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\StreamSource;
use App\Support\Accent;

/**
 * Every match that can actually be watched, free and legally, in one place.
 * Adapted from utakmice-rs-master's StreamsController — live first, then
 * today, then the days ahead, since that's also the order the value falls
 * off in for a reader who just arrived.
 */
class StreamsController extends Controller
{
    public function index()
    {
        $fixtures = Fixture::with(['league', 'homeTeam', 'awayTeam', 'streams' => fn ($q) => $q->playable()])
            ->whereHas('streams', fn ($q) => $q->playable())
            ->whereBetween('kickoff_at', [now()->subHours(4), now()->addDays(10)])
            ->orderBy('kickoff_at')
            ->limit(200)
            ->get();

        $groups = $fixtures
            ->sortBy(fn (Fixture $fixture) => [$fixture->isLive() ? 0 : 1, $fixture->kickoff_at->timestamp])
            ->groupBy(fn (Fixture $fixture) => $fixture->kickoff_at->local()->format('Y-m-d'));

        return view('streams', [
            'sport' => 'fudbal',
            'accent' => Accent::classes('fudbal'),
            'active' => 'streams',
            'groups' => $groups,
            'liveNow' => $groups->flatten()->filter->isLive()->count(),
            'sources' => StreamSource::with('league')->where('is_active', true)->orderBy('channel_name')->get(),
        ]);
    }
}
