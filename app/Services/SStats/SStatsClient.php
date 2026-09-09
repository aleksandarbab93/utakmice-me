<?php

namespace App\Services\SStats;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Thin wrapper around the free SStats.net football data API
 * (https://api.sstats.net, docs at https://api.sstats.net/docs).
 *
 * Works anonymously with a shared rate limit; an optional free API key
 * (SSTATS_API_KEY) lifts that limit — see https://sstats.net/login.
 *
 * On production, baseUrl points at the Cloudflare Worker relay in
 * deploy/sstats-relay-worker.js rather than at SStats directly — that box's
 * network path to SStats stalls on any response past ~14.6 KB. The relay
 * token below is what the worker checks so it isn't an open proxy.
 */
class SStatsClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly ?string $relayToken = null,
    ) {
    }

    /** Full season fixture list for a league (played + upcoming). */
    public function fixtures(int $leagueId, int $year): array
    {
        return $this->getPaged('/games/list', ['LeagueId' => $leagueId, 'Year' => $year]);
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
        return $this->getPaged('/games/list', ['Live' => 'true']);
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

    /**
     * The same listing, fetched a page at a time.
     *
     * Not an optimization — a requirement. A full season's /games/list
     * carries a full odds tree per match, and some hosts (ours included)
     * cannot finish reading a response past roughly 14 KB: the connection
     * delivers the first burst and then stalls until it times out, whatever
     * the timeout length. Ten rows keeps every page comfortably under that,
     * matching what utakmice-rs-master measured on the same kind of host.
     *
     * Only /games/list supports Limit/Offset.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPaged(string $path, array $query = []): array
    {
        $size = 10;
        $maxPages = 500;
        $rows = [];

        for ($page = 0; $page < $maxPages; $page++) {
            $batch = $this->get($path, $query + ['Limit' => $size, 'Offset' => $page * $size])['data'] ?? [];
            $rows = array_merge($rows, $batch);

            if (count($batch) < $size) {
                break;
            }

            // Measured against the live source, keyless: bursting eats a 429
            // within seconds, but one request every two seconds gets through
            // clean — the same pacing utakmice-rs-master settled on.
            usleep(2000000);
        }

        return $rows;
    }

    /**
     * Once anyone hits the shared per-minute limit, every other process —
     * the every-minute live sync, a visitor's match-detail page, the daily
     * full sync — shares the same budget and would just get refused too.
     * This cache flag makes that failure instant and free instead of every
     * process finding out the hard way with its own request and retries,
     * which was compounding the outage instead of letting it clear.
     */
    private const COOLDOWN_KEY = 'sstats:cooldown';

    private function get(string $path, array $query = []): array
    {
        if (Cache::get(self::COOLDOWN_KEY)) {
            return [];
        }

        if ($this->apiKey) {
            $query['apikey'] = $this->apiKey;
        }

        if ($this->relayToken) {
            $query['token'] = $this->relayToken;
        }

        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                [$status, $body] = $this->curlGet($url);
            } catch (RuntimeException $e) {
                if ($attempts <= 2) {
                    sleep(3);

                    continue;
                }

                throw $e;
            }

            if ($status === 429) {
                Cache::put(self::COOLDOWN_KEY, true, now()->addSeconds(45));

                return [];
            }

            if ($status < 200 || $status >= 300) {
                throw new RuntimeException("SStats {$url} returned status {$status}");
            }

            return json_decode($body, true) ?? [];
        }
    }

    /**
     * Raw curl, not Laravel's HTTP client — diagnosed on prod with
     * CURLOPT_VERBOSE: SStats negotiates HTTP/2 over TLS (ALPN), sends
     * every header and the full response body, then never sends the frame
     * that marks the stream finished — a server-side HTTP/2 bug. Every
     * client that also speaks h2 hangs forever waiting for a stream end
     * that's never coming; a plain `curl` from the same box only "worked"
     * by accident (its ALPN negotiation didn't always land on h2).
     * CURLOPT_SSL_ENABLE_ALPN keeps the connection on HTTP/1.1, which
     * isn't affected — but Laravel's HTTP client refuses that option (it's
     * outside its curl-option allow-list), so this bypasses it entirely.
     *
     * @return array{0: int, 1: string}
     */
    private function curlGet(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_ENABLE_ALPN => false,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($errno !== 0) {
            throw new RuntimeException("SStats {$url}: curl error {$errno}: {$error}");
        }

        return [$status, (string) $body];
    }
}
