<?php

namespace App\Services\TheSportsDb;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around TheSportsDB's free public API (shared "test" key,
 * no signup needed) — used only to look up a club's badge image, since
 * SStats.net (our football data source) doesn't expose crest URLs at all.
 */
class TheSportsDbClient
{
    private const BASE_URL = 'https://www.thesportsdb.com/api/v1/json/3';

    public function findTeamBadge(string $name): ?string
    {
        $response = Http::baseUrl(self::BASE_URL)->get('/searchteams.php', ['t' => $name]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json('teams.0.strBadge');
    }
}
