<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Standing;
use App\Models\Team;
use App\Services\Euroleague\EuroleagueClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SyncBasketballData extends Command
{
    protected $signature = 'basketball:sync';

    protected $description = 'Sync EuroLeague and EuroCup schedules/standings from api-live.euroleague.net';

    /** Euroleague competition code => display name, matching App\Support\Accent::leagues('kosarka') */
    private const COMPETITIONS = [
        'E' => 'Evroliga',
        'U' => 'Evrokup',
    ];

    public function handle(EuroleagueClient $client): int
    {
        foreach (self::COMPETITIONS as $code => $name) {
            $season = $code.$this->currentSeasonYear();
            $this->info("Sync {$name} ({$season})...");

            $league = League::updateOrCreate(
                ['external_source' => 'euroleague', 'external_id' => $code],
                ['name' => $name, 'slug' => Str::slug($name), 'sport' => 'kosarka'],
            );

            try {
                $this->syncGames($client, $league, $season);

                // EuroCup's regular season is split into groups and the free
                // standings endpoint has no reliable way to select/combine
                // them (it silently returns just one group of 8) — skip it
                // rather than show an incomplete, misleading table.
                if ($code === 'E') {
                    $this->syncStandings($client, $league, $season);
                }
            } catch (\Throwable $e) {
                $this->error("  {$name}: {$e->getMessage()}");
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    /** Euroleague's season "year" label: a season starting in autumn of year N is coded EN. */
    private function currentSeasonYear(): int
    {
        $now = Carbon::now();

        return $now->month >= 7 ? $now->year : $now->year - 1;
    }

    private function syncGames(EuroleagueClient $client, League $league, string $seasonCode): void
    {
        $games = $client->games($league->external_id, $seasonCode);

        foreach ($games as $game) {
            $home = $this->upsertTeam($league, $game['local']['club']);
            $away = $this->upsertTeam($league, $game['road']['club']);

            Fixture::updateOrCreate(
                ['external_source' => 'euroleague', 'external_id' => $game['id']],
                [
                    'league_id' => $league->id,
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'kickoff_at' => $game['date'],
                    'status' => $this->matchStatus($game),
                    'home_score' => $game['local']['score'] ?: null,
                    'away_score' => $game['road']['score'] ?: null,
                    'minute' => null,
                    'matchday' => $game['roundName'] ?? null,
                    'venue' => $game['venue']['name'] ?? null,
                ],
            );
        }

        $this->line('  games: '.count($games).' rows');
    }

    private function syncStandings(EuroleagueClient $client, League $league, string $seasonCode): void
    {
        $rows = $client->standings($seasonCode);

        foreach ($rows as $row) {
            $team = Team::where('external_source', 'euroleague')->where('external_id', $row['code'])->first();

            if (! $team) {
                continue; // team not seen in any synced game yet — skip rather than guess a name
            }

            Standing::updateOrCreate(
                ['league_id' => $league->id, 'team_id' => $team->id],
                [
                    'position' => $row['rank'],
                    'played' => $row['played'],
                    'won' => $row['wins'],
                    'draw' => 0,
                    'lost' => $row['losses'],
                    'goals_for' => $row['ptsFor'],
                    'goals_against' => $row['ptsAgainst'],
                    'points' => $row['wins'],
                    'goal_diff' => $row['diff'],
                ],
            );
        }

        $this->line('  standings: '.count($rows).' rows');
    }

    private function upsertTeam(League $league, array $club): Team
    {
        return Team::updateOrCreate(
            ['external_source' => 'euroleague', 'external_id' => $club['code']],
            [
                'league_id' => $league->id,
                'name' => $club['name'],
                'short_name' => $club['abbreviatedName'] ?? $club['name'],
                'crest_url' => $club['images']['crest'] ?? null,
            ],
        );
    }

    /**
     * The API only exposes a "played" flag (no live in-progress signal), so a
     * game past its tip-off time but not yet flagged played is treated as live
     * for up to a normal game's duration.
     */
    private function matchStatus(array $game): string
    {
        if ($game['played']) {
            return 'finished';
        }

        $kickoff = Carbon::parse($game['date']);
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($kickoff) && $now->lessThan($kickoff->copy()->addHours(3))) {
            return 'live';
        }

        return 'scheduled';
    }
}
