<?php

namespace App\Services\SStats;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the free SStats.net football data API
 * (https://api.sstats.net, docs at https://api.sstats.net/docs).
 *
 * Works anonymously with a shared rate limit; an optional free API key
 * (SSTATS_API_KEY) lifts that limit — see https://sstats.net/login.
 */
class SStatsClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
    ) {
    }

    /** Full season fixture list for a league (played + upcoming). */
    public function fixtures(int $leagueId, int $year): array
    {
        return $this->get('/games/list', ['LeagueId' => $leagueId, 'Year' => $year])['data'] ?? [];
    }

    /** Current standings table for a league season. */
    public function standings(int $leagueId, int $year): array
    {
        $data = $this->get('/seasons/standings', ['leagueId' => $leagueId, 'year' => $year])['data'] ?? null;

        return $data['tables'][0]['rows'] ?? [];
    }

    /** Full match detail — events (goals/cards/subs), statistics, venue, referee. */
    public function gameDetail(int $gameId): ?array
    {
        return $this->get("/games/{$gameId}")['data'] ?? null;
    }

    /**
     * Every live match worldwide, in one call — SStats has no per-league
     * live filter, so this is deliberately unscoped; the caller matches
     * rows against its own tracked fixtures by external_id.
     */
    public function liveGames(): array
    {
        return $this->get('/games/list', ['Live' => 'true'])['data'] ?? [];
    }

    /**
     * SStats' numeric match-status code, mapped to our three-value status.
     * 2 = Not Started. 8/9/10/17/18 = finished in some form (normal time,
     * extra time, penalties, or another settled/awarded variant — SStats'
     * own "Ended" filter groups exactly these). Everything else is live.
     */
    public static function mapStatus(int $apiStatus): string
    {
        return match ($apiStatus) {
            2 => 'scheduled',
            8, 9, 10, 17, 18 => 'finished',
            default => 'live',
        };
    }

    /** Last N finished meetings between two teams, across all competitions, most recent first. */
    public function headToHead(int $teamA, int $teamB, int $limit = 5): array
    {
        return $this->get('/games/list', [
            'BothTeams' => "{$teamA},{$teamB}",
            'Ended' => 'true',
            'Order' => -1,
            'Limit' => $limit,
        ])['data'] ?? [];
    }

    /** A team's last N finished matches across all competitions, most recent first. */
    public function teamForm(int $teamId, int $limit = 5): array
    {
        return $this->get('/games/list', [
            'Team' => $teamId,
            'Ended' => 'true',
            'Order' => -1,
            'Limit' => $limit,
        ])['data'] ?? [];
    }

    private function get(string $path, array $query = []): array
    {
        if ($this->apiKey) {
            $query['apikey'] = $this->apiKey;
        }

        // A full season's /games/list comes with a full odds tree per match,
        // which is slow enough over the shared/anonymous tier to blow past
        // Laravel's 30s default — 2 retries buys some resilience against a
        // one-off stall without hammering the shared rate limit.
        $response = Http::baseUrl($this->baseUrl)->timeout(120)->retry(2, 3000)->get($path, $query);
        $response->throw();

        return $response->json() ?? [];
    }
}
