<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Services\SStats\SStatsClient;
use App\Support\GoalPush;
use App\Support\MatchReportGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Refreshes live football fixtures — one global API call covers every
 * league at once (SStats has no per-league live filter), so this is cheap
 * enough to run every minute. Modeled after utakmice.rs's sync:live: skip
 * the call entirely when nothing is happening, never trust a "live" status
 * forever (a match cannot still be running hours after kickoff no matter
 * what the feed says), and settle matches that silently drop out of the
 * live feed instead of leaving them stuck.
 */
class SyncLiveFootball extends Command
{
    protected $signature = 'football:sync-live';

    protected $description = 'Refreshes live football fixtures with one global call — safe to run every minute';

    private array $finishedLeagueIds = [];

    public function handle(SStatsClient $client): int
    {
        $closed = $this->closeMatchesThatCannotStillBeRunning();
        if ($closed > 0) {
            $this->info("closed on the clock: {$closed}");
        }

        if (! $this->anythingSoon()) {
            $this->info('nothing in progress — call skipped.');
            $this->refreshStandingsForFinishedLeagues();

            return self::SUCCESS;
        }

        try {
            $live = $client->liveGames();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->refreshStandingsForFinishedLeagues();

            return self::SUCCESS;
        }

        $updated = $this->apply($live, $client);
        $this->info("fixtures updated: {$updated}");

        $settled = $this->settleStragglers($live, $client);
        if ($settled > 0) {
            $this->info("settled stragglers: {$settled}");
        }

        $this->refreshStandingsForFinishedLeagues();

        return self::SUCCESS;
    }

    /** Only worth a call while something is actually live or about to kick off. */
    private function anythingSoon(): bool
    {
        return Fixture::where('status', 'live')->exists()
            || Fixture::where('status', 'scheduled')
                ->whereBetween('kickoff_at', [now()->subMinutes(15), now()->addMinutes(15)])
                ->exists();
    }

    /**
     * Writes a batch of live payloads onto the fixtures we already track.
     * Rows for matches from leagues we don't follow are simply not found
     * and skipped.
     */
    private function apply(array $rows, SStatsClient $client): int
    {
        if (! $rows) {
            return 0;
        }

        $externalIds = array_map(fn ($g) => (string) $g['id'], $rows);

        $fixtures = Fixture::where('external_source', 'sstats')
            ->whereIn('external_id', $externalIds)
            ->get()
            ->keyBy('external_id');

        $updated = 0;

        foreach ($rows as $game) {
            $fixture = $fixtures->get((string) $game['id']);

            if (! $fixture) {
                continue;
            }

            $wasFinished = $fixture->status === 'finished';
            $status = SStatsClient::mapStatus($game['status']);
            $before = [$fixture->home_score, $fixture->away_score];

            $fixture->status = $status;
            $fixture->home_score = $game['homeResult'];
            $fixture->away_score = $game['awayResult'];
            $fixture->minute = $game['elapsed'] ?? null;

            if (! $fixture->isDirty()) {
                continue;
            }

            $fixture->save();
            $updated++;

            GoalPush::maybe($fixture, $before);

            if (! $wasFinished && $status === 'finished') {
                $this->onMatchFinished($fixture, $client);
            }
        }

        return $updated;
    }

    /**
     * A match still marked live in our DB, but missing from this pass's
     * live payload and old enough that it cannot genuinely still be
     * running, is asked for by id — the source has usually already settled
     * it and simply stopped listing it as live.
     */
    private function settleStragglers(array $rows, SStatsClient $client): int
    {
        $seenIds = array_map(fn ($g) => (string) $g['id'], $rows) ?: ['0'];

        $stale = Fixture::where('external_source', 'sstats')
            ->where('status', 'live')
            ->whereNotIn('external_id', $seenIds)
            ->where('kickoff_at', '<', now()->subMinutes(95))
            ->get();

        $settled = 0;

        foreach ($stale as $fixture) {
            try {
                $detail = $client->gameDetail((int) $fixture->external_id);
            } catch (\Throwable) {
                continue;
            }

            $game = $detail['game'] ?? null;

            if (! $game) {
                continue;
            }

            $status = SStatsClient::mapStatus($game['status']);
            $before = [$fixture->home_score, $fixture->away_score];
            $fixture->status = $status;
            $fixture->home_score = $game['homeResult'];
            $fixture->away_score = $game['awayResult'];
            $fixture->minute = $game['elapsed'] ?? null;

            if (! $fixture->isDirty()) {
                continue;
            }

            $fixture->save();
            $settled++;

            GoalPush::maybe($fixture, $before);

            if ($status === 'finished') {
                $this->onMatchFinished($fixture, $client);
            }
        }

        return $settled;
    }

    /**
     * The clock-based backstop: a match cannot genuinely still be running
     * two and a half hours after kickoff (three and a half if it went to
     * extra time/penalties), whatever the feed still says. Catches the
     * source calling a match live for far longer than it possibly could
     * be — the exact bug that left dozens of Conference League qualifiers
     * stuck showing a red "120'" clock in this app.
     */
    private function closeMatchesThatCannotStillBeRunning(): int
    {
        $overdue = Fixture::where('status', 'live')
            ->where('kickoff_at', '<', now()->subMinutes(150))
            ->get()
            ->filter(function (Fixture $fixture) {
                $minute = $fixture->minute ? (int) preg_replace('/\D/', '', $fixture->minute) : null;
                $limit = ($minute !== null && $minute > 105) ? 210 : 150;

                return $fixture->kickoff_at->lt(now()->subMinutes($limit));
            });

        foreach ($overdue as $fixture) {
            Log::info('football:sync-live - closing a match the source still calls live', [
                'external_id' => $fixture->external_id,
                'kickoff' => $fixture->kickoff_at->toDateTimeString(),
                'minute' => $fixture->minute,
            ]);

            $fixture->update(['status' => 'finished', 'minute' => null]);
            $this->finishedLeagueIds[] = $fixture->league_id;
        }

        return $overdue->count();
    }

    /** A finished match should show up in a report and a fresh table without waiting for the next full sync. */
    private function onMatchFinished(Fixture $fixture, SStatsClient $client): void
    {
        $this->finishedLeagueIds[] = $fixture->league_id;

        try {
            MatchReportGenerator::generate($fixture, $client);
        } catch (\Throwable $e) {
            $this->error("report generation failed for fixture {$fixture->id}: {$e->getMessage()}");
        }
    }

    /** Only the leagues actually touched this run — not the full 8-league sweep. */
    private function refreshStandingsForFinishedLeagues(): void
    {
        $leagueIds = array_unique($this->finishedLeagueIds);

        if (! $leagueIds) {
            return;
        }

        foreach ($leagueIds as $leagueId) {
            $this->call('football:sync-standings', ['--league-id' => $leagueId]);
        }
    }
}
