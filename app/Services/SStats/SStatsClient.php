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

    private function get(string $path, array $query = []): array
    {
        if ($this->apiKey) {
            $query['apikey'] = $this->apiKey;
        }

        $response = Http::baseUrl($this->baseUrl)->get($path, $query);
        $response->throw();

        return $response->json() ?? [];
    }
}
