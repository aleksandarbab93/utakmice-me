<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\Goal;
use App\Services\SStats\SStatsClient;
use App\Support\MatchDetail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-off (and re-runnable) backfill for the `goals` table — for finished
 * fixtures that predate MatchReportGenerator persisting goals as a side
 * effect, or for a fixture that lost its rows some other way. Skips any
 * fixture that already has goal rows, so it's safe to run repeatedly.
 */
#[Signature('app:backfill-goals')]
#[Description('Backfill the goals table from SStats match detail for finished fixtures missing goal rows')]
class BackfillGoals extends Command
{
    public function handle(SStatsClient $client): int
    {
        $fixtures = Fixture::where('status', 'finished')
            ->whereDoesntHave('goals')
            ->with('league')
            ->get();

        $this->info("{$fixtures->count()} fixtures to check.");

        $inserted = 0;

        foreach ($fixtures as $fixture) {
            try {
                $detail = $client->gameDetail((int) $fixture->external_id);
            } catch (\Throwable $e) {
                $this->error("  fixture {$fixture->id}: {$e->getMessage()}");

                continue;
            }

            $goals = MatchDetail::events($detail)->filter(fn ($e) => in_array($e['icon'], ['goal', 'og'], true));

            foreach ($goals as $g) {
                $isOwnGoal = $g['icon'] === 'og';
                $creditSide = $isOwnGoal ? ($g['side'] === 'home' ? 'away' : 'home') : $g['side'];

                Goal::create([
                    'fixture_id' => $fixture->id,
                    'league_id' => $fixture->league_id,
                    'team_id' => $creditSide === 'home' ? $fixture->home_team_id : $fixture->away_team_id,
                    'player_name' => $g['player'],
                    'minute' => $g['elapsed'],
                    'is_penalty' => $g['subtitle'] === 'Penal',
                    'is_own_goal' => $isOwnGoal,
                ]);
                $inserted++;
            }

            usleep(400000);
        }

        $this->info("Done. {$inserted} goals inserted.");

        return self::SUCCESS;
    }
}
