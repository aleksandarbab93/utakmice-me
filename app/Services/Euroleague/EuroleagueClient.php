<?php

namespace App\Services\Euroleague;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Euroleague Basketball's own public live-data API
 * (api-live.euroleague.net) — the same one their official app and site
 * use. No API key required. Covers both EuroLeague ("E") and EuroCup
 * ("U") via a shared competition/season code scheme, e.g. "E2026".
 */
class EuroleagueClient
{
    private const BASE_URL = 'https://api-live.euroleague.net';

    /** Full season schedule + results. */
    public function games(string $competitionCode, string $seasonCode): array
    {
        return $this->getJson("/v2/competitions/{$competitionCode}/seasons/{$seasonCode}/games")['data'] ?? [];
    }

    /** Regular-season standings — only available as XML, no v2 JSON equivalent exists. */
    public function standings(string $seasonCode): array
    {
        $response = Http::baseUrl(self::BASE_URL)->get('/v1/standings', ['seasonCode' => $seasonCode]);
        $response->throw();

        $xml = simplexml_load_string($response->body());

        if ($xml === false || ! isset($xml->group->team)) {
            return [];
        }

        $rows = [];
        foreach ($xml->group->team as $team) {
            $rows[] = [
                'name' => (string) $team->name,
                'code' => (string) $team->code,
                'rank' => (int) $team->ranking,
                'played' => (int) $team->totalgames,
                'wins' => (int) $team->wins,
                'losses' => (int) $team->losses,
                'ptsFor' => (int) $team->ptsfavour,
                'ptsAgainst' => (int) $team->ptsagainst,
                'diff' => (int) $team->difference,
            ];
        }

        return $rows;
    }

    private function getJson(string $path): array
    {
        $response = Http::baseUrl(self::BASE_URL)->get($path);
        $response->throw();

        return $response->json() ?? [];
    }
}
