<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\League;
use App\Services\Euroleague\EuroleagueClient;
use App\Support\BasketballReportGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Refreshes only the fixtures that are live or about to start — skipping
 * the call entirely otherwise — so it's safe to run every minute even
 * though (unlike football:sync-live) there's no lighter live-only endpoint:
 * the Euroleague API always returns a competition's full season, so this
 * only pays that cost while a game is actually near kickoff.
 */
class SyncLiveBasketball extends Command
{
    protected $signature = 'basketball:sync-live';

    protected $description = 'Refreshes near-kickoff EuroLeague/EuroCup fixtures — safe to run every minute';

    private const COMPETITIONS = [
        'E' => 'Evroliga',
        'U' => 'Evrokup',
    ];

    public function handle(EuroleagueClient $client): int
    {
        $closed = $this->closeMatchesThatCannotStillBeRunning();
        if ($closed > 0) {
            $this->info("closed on the clock: {$closed}");
        }

        foreach (self::COMPETITIONS as $code => $name) {
            $league = League::where('external_source', 'euroleague')->where('external_id', $code)->first();

            if (! $league || ! $this->anythingSoon($league)) {
                $this->info("{$name}: nothing in progress — call skipped.");

                continue;
            }

            $season = $code.$this->currentSeasonYear();

            try {
                $games = $client->games($code, $season);
            } catch (\Throwable $e) {
                $this->error("{$name}: {$e->getMessage()}");

                continue;
            }

            $updated = $this->apply($league, $games);
            $this->info("{$name}: fixtures updated: {$updated}");
        }

        return self::SUCCESS;
    }

    private function currentSeasonYear(): int
    {
        $now = Carbon::now();

        return $now->month >= 7 ? $now->year : $now->year - 1;
    }

    /** Only worth a call while something in this competition is live or about to tip off. */
    private function anythingSoon(League $league): bool
    {
        return Fixture::where('league_id', $league->id)->where('status', 'live')->exists()
            || Fixture::where('league_id', $league->id)
                ->where('status', 'scheduled')
                ->whereBetween('kickoff_at', [now()->subMinutes(15), now()->addMinutes(15)])
                ->exists();
    }

    private function apply(League $league, array $games): int
    {
        $relevant = Fixture::where('league_id', $league->id)
            ->where(fn ($q) => $q->where('status', 'live')
                ->orWhereBetween('kickoff_at', [now()->subHours(4), now()->addMinutes(15)]))
            ->get()
            ->keyBy('external_id');

        if ($relevant->isEmpty()) {
            return 0;
        }

        $byId = collect($games)->keyBy('id');
        $updated = 0;

        foreach ($relevant as $externalId => $fixture) {
            $game = $byId->get($externalId);

            if (! $game) {
                continue;
            }

            $wasFinished = $fixture->status === 'finished';
            $fixture->status = $this->matchStatus($game);
            $fixture->home_score = $game['local']['score'] ?: null;
            $fixture->away_score = $game['road']['score'] ?: null;

            if (! $fixture->isDirty()) {
                continue;
            }

            $fixture->save();
            $updated++;

            if (! $wasFinished && $fixture->status === 'finished') {
                BasketballReportGenerator::generate($fixture, $game);
            }
        }

        return $updated;
    }

    private function matchStatus(array $game): string
    {
        if ($game['played']) {
            return 'finished';
        }

        $kickoff = isset($game['utcDate']) ? Carbon::parse($game['utcDate'])->utc() : Carbon::parse($game['date'], 'Europe/Belgrade')->utc();
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($kickoff) && $now->lessThan($kickoff->copy()->addHours(3))) {
            return 'live';
        }

        return 'scheduled';
    }

    /**
     * The clock-based backstop: a basketball game cannot genuinely still be
     * running four hours after tip-off (its status here was already only
     * ever inferred from the clock, never a real live signal), whatever the
     * last sync left it as.
     */
    private function closeMatchesThatCannotStillBeRunning(): int
    {
        $overdue = Fixture::whereHas('league', fn ($q) => $q->where('sport', 'kosarka'))
            ->where('status', 'live')
            ->where('kickoff_at', '<', now()->subHours(4))
            ->get();

        foreach ($overdue as $fixture) {
            Log::info('basketball:sync-live - closing a match stuck live past its clock window', [
                'external_id' => $fixture->external_id,
                'kickoff' => $fixture->kickoff_at->toDateTimeString(),
            ]);

            $fixture->update(['status' => 'finished']);
        }

        return $overdue->count();
    }
}
