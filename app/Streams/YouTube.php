<?php

namespace App\Streams;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Everything the harvest needs from YouTube — ported from utakmice-rs-master.
 *
 * The feed is free in every sense: no key, no account, no quota. Whether a
 * video is live and whether it may be embedded are not in the feed, and with
 * no API key configured this falls back to oEmbed, which answers only the
 * embedding question — so a keyless install gets a correct page with a
 * duller badge, never a broken one.
 */
class YouTube
{
    private const FEED = 'https://www.youtube.com/feeds/videos.xml';

    private const API = 'https://www.googleapis.com/youtube/v3/videos';

    private const OEMBED = 'https://www.youtube.com/oembed';

    /** @return list<array{video_id: string, title: string, published_at: Carbon}> */
    public function feed(string $channelId): array
    {
        $response = Http::timeout(config('services.youtube.timeout'))
            ->retry(2, 300, throw: false)
            ->get(self::FEED, ['channel_id' => $channelId]);

        if (! $response->successful()) {
            Log::warning('stream feed unavailable', ['channel' => $channelId, 'status' => $response->status()]);

            return [];
        }

        $xml = @simplexml_load_string($response->body());

        if ($xml === false) {
            return [];
        }

        $entries = [];

        foreach ($xml->entry ?? [] as $entry) {
            $id = (string) $entry->children('yt', true)->videoId;

            if ($id === '') {
                continue;
            }

            $entries[] = [
                'video_id' => $id,
                'title' => trim((string) $entry->title),
                'published_at' => Carbon::parse((string) $entry->published),
            ];
        }

        return $entries;
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, array{is_live: bool, embeddable: bool, starts_at: ?Carbon}>
     */
    public function details(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        return config('services.youtube.key')
            ? $this->detailsFromApi($ids)
            : $this->detailsFromOembed($ids);
    }

    /** @param  list<string>  $ids */
    private function detailsFromApi(array $ids): array
    {
        $found = [];

        foreach (array_chunk($ids, 50) as $chunk) {
            $response = Http::timeout(config('services.youtube.timeout'))
                ->retry(2, 300, throw: false)
                ->get(self::API, [
                    'part' => 'status,liveStreamingDetails,snippet',
                    'id' => implode(',', $chunk),
                    'key' => config('services.youtube.key'),
                ]);

            if (! $response->successful()) {
                Log::warning('stream details refused', ['status' => $response->status(), 'body' => $response->body()]);

                continue;
            }

            foreach ($response->json('items', []) as $item) {
                $broadcast = $item['snippet']['liveBroadcastContent'] ?? 'none';
                $scheduled = $item['liveStreamingDetails']['scheduledStartTime'] ?? null;

                $found[$item['id']] = [
                    'is_live' => in_array($broadcast, ['live', 'upcoming'], true),
                    'embeddable' => (bool) ($item['status']['embeddable'] ?? false),
                    'starts_at' => $scheduled ? Carbon::parse($scheduled) : null,
                ];
            }
        }

        return $found;
    }

    /**
     * oEmbed answers 200 for a video that may be embedded and 401 for one
     * that may not. It cannot say whether the thing is live, so those rows
     * keep is_live false — a smaller lie than claiming LIVE on a replay.
     *
     * @param  list<string>  $ids
     */
    private function detailsFromOembed(array $ids): array
    {
        $found = [];

        foreach (array_slice($ids, 0, config('services.youtube.oembed_cap')) as $id) {
            $response = Http::timeout(config('services.youtube.timeout'))
                ->get(self::OEMBED, [
                    'url' => 'https://www.youtube.com/watch?v='.$id,
                    'format' => 'json',
                ]);

            if ($response->status() === 0) {
                continue; // a network failure is not an answer — ask again next run
            }

            $found[$id] = [
                'is_live' => false,
                'embeddable' => $response->successful(),
                'starts_at' => null,
            ];
        }

        return $found;
    }
}
