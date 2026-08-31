<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Standing;
use App\Models\Team;
use App\Services\SStats\SStatsClient;
use App\Services\TheSportsDb\TheSportsDbClient;
use App\Support\MatchReportGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SyncFootballData extends Command
{
    protected $signature = 'football:sync';

    protected $description = 'Sync leagues, teams, fixtures and standings from SStats.net for the five tracked leagues';

    /** SStats league id => display name, matching App\Support\Accent::leagues('fudbal') */
    private const LEAGUES = [
        39 => 'Premijer liga',
        140 => 'La Liga',
        135 => 'Serie A',
        78 => 'Bundesliga',
        61 => 'Ligue 1',
        2 => 'Liga prvaka',
        3 => 'Evropska liga',
        848 => 'Liga konferencija',
    ];

    public function handle(SStatsClient $client, TheSportsDbClient $crestClient): int
    {
        $year = $this->currentSeasonYear();

        foreach (self::LEAGUES as $externalId => $name) {
            $this->info("Sync {$name} (#{$externalId}, season {$year})...");

            $league = League::updateOrCreate(
                ['external_source' => 'sstats', 'external_id' => (string) $externalId],
                ['name' => $name, 'slug' => Str::slug($name), 'sport' => 'fudbal'],
            );

            try {
                $this->syncFixtures($client, $crestClient, $league, $externalId, $year);
                sleep(1);
                $this->syncStandings($client, $league, $externalId, $year);
            } catch (\Throwable $e) {
                $this->error("  {$name}: {$e->getMessage()}");
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    /** European season "year" label: Aug–Dec belongs to the season starting that year, Jan–Jul to the one before. */
    private function currentSeasonYear(): int
    {
        $now = Carbon::now();

        return $now->month >= 7 ? $now->year : $now->year - 1;
    }

    private function syncFixtures(SStatsClient $client, TheSportsDbClient $crestClient, League $league, int $externalLeagueId, int $year): void
    {
        $games = $client->fixtures($externalLeagueId, $year);
        $reportsGenerated = 0;

        foreach ($games as $game) {
            $home = $this->upsertTeam($league, $game['homeTeam'], $crestClient);
            $away = $this->upsertTeam($league, $game['awayTeam'], $crestClient);

            $fixture = Fixture::updateOrCreate(
                ['external_source' => 'sstats', 'external_id' => (string) $game['id']],
                [
                    'league_id' => $league->id,
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'kickoff_at' => $game['date'],
                    'status' => $this->mapStatus($game['status']),
                    'home_score' => $game['homeResult'],
                    'away_score' => $game['awayResult'],
                    'minute' => $game['elapsed'] ?? null,
                    'matchday' => $game['roundName'] ?? null,
                ],
            );

            if (MatchReportGenerator::generate($fixture, $client)) {
                $reportsGenerated++;
                usleep(400000); // the extra per-match detail call adds to the shared rate limit budget
            }
        }

        $this->line('  fixtures: '.count($games).' rows'.($reportsGenerated ? ", {$reportsGenerated} new reports" : ''));
    }

    private function syncStandings(SStatsClient $client, League $league, int $externalLeagueId, int $year): void
    {
        $rows = $client->standings($externalLeagueId, $year);

        foreach ($rows as $row) {
            $team = Team::where('external_source', 'sstats')->where('external_id', (string) $row['teamId'])->first();

            if (! $team) {
                continue; // team not seen in any fixture yet — skip rather than guess a name
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

        $this->line('  standings: '.count($rows).' rows');
    }

    private function upsertTeam(League $league, array $team, TheSportsDbClient $crestClient): Team
    {
        $existing = Team::where('external_source', 'sstats')->where('external_id', (string) $team['id'])->first();

        $crestUrl = $existing?->crest_url;
        if (! $crestUrl) {
            try {
                $crestUrl = $crestClient->findTeamBadge($team['name']);
                usleep(500000); // be considerate to the shared free-tier key (it rate-limits bursts)
            } catch (\Throwable) {
                $crestUrl = null;
            }
        }

        return Team::updateOrCreate(
            ['external_source' => 'sstats', 'external_id' => (string) $team['id']],
            [
                'league_id' => $league->id,
                'name' => $team['name'],
                'short_name' => $team['name'],
                'crest_url' => $crestUrl,
            ],
        );
    }

    private function mapStatus(int $apiStatus): string
    {
        return match ($apiStatus) {
            2 => 'scheduled',
            // 8 Finished, 9 Finished After Extra Time, 10 Finished After Penalty,
            // 17/18 other finished/awarded variants (per SStats' own "Ended" filter).
            8, 9, 10, 17, 18 => 'finished',
            default => 'live',
        };
    }
}
