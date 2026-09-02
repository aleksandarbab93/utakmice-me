<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Models\Standing;
use App\Models\Team;
use App\Services\SStats\SStatsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Pulls the table for one league (or every tracked league without
 * --league-id). Split out of football:sync so a match going final can
 * trigger a targeted refresh — the league that just changed, not all
 * eight — via football:sync-live, without waiting for the next full sync.
 */
class SyncFootballStandings extends Command
{
    protected $signature = 'football:sync-standings {--league-id= : internal league id — every tracked league if omitted}';

    protected $description = 'Pulls the standings table for one or every tracked football league';

    public function handle(SStatsClient $client): int
    {
        $leagues = $this->option('league-id')
            ? League::where('id', (int) $this->option('league-id'))->where('sport', 'fudbal')->get()
            : League::where('sport', 'fudbal')->get();

        $year = $this->currentSeasonYear();

        foreach ($leagues as $league) {
            try {
                $this->syncStandings($client, $league, (int) $league->external_id, $year);
            } catch (\Throwable $e) {
                $this->error("  {$league->name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    private function currentSeasonYear(): int
    {
        $now = Carbon::now();

        return $now->month >= 7 ? $now->year : $now->year - 1;
    }

    private function syncStandings(SStatsClient $client, League $league, int $externalLeagueId, int $year): void
    {
        $rows = $client->standings($externalLeagueId, $year);

        foreach ($rows as $row) {
            $team = Team::where('external_source', 'sstats')->where('external_id', (string) $row['teamId'])->first();

            if (! $team) {
                continue;
            }

            Standing::updateOrCreate(
                ['league_id' => $league->id, 'team_id' => $team->id],
                [
                    'position' => $row['rank'],
                    'played' => $row['played'],
                    'won' => $row['wins'],
                    'draw' => $row['draws'],
                    'lost' => $row['loses'],
                    'goals_for' => $row['goalsFor'],
                    'goals_against' => $row['goalsAgainst'],
                    'points' => $row['points'],
                    'goal_diff' => $row['goalsFor'] - $row['goalsAgainst'],
                ],
            );
        }

        $this->line("{$league->name}: ".count($rows).' clubs in the table');
    }
}
