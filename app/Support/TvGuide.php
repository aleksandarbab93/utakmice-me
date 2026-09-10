<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads a published TV listing and hands back the sports broadcasts in it.
 *
 * Ported from utakmice-rs-master. The guide it was written against is a
 * Serbian provider's channel-programme search: JSON, no key, paged fifty at
 * a time. Which guide this site may read, and on what terms, is a question
 * about somebody's terms of use rather than about code — so the URL lives
 * in .env and is empty by default. With no URL, sync:tv does nothing.
 */
class TvGuide
{
    /** What the guide calls a match we might carry. */
    private const SPORTS = ['fudbal', 'kosarka', 'košarka'];

    /**
     * And what it calls one we do not. "Americki fudbal - NFL: Houston
     * Texans - Las Vegas Raiders" contains the word and is not the game.
     */
    private const NOT_OURS = ['americki fudbal', 'američki fudbal', 'futsal', 'mini fudbal'];

    /**
     * One day of the guide, as programmes.
     *
     * @return list<array{channel: string, title: string, home: ?string, away: ?string, startsAt: Carbon}>
     */
    public static function forDate(Carbon $date): array
    {
        $out = [];

        foreach (self::pages($date) as $channels) {
            foreach ($channels as $channel) {
                $name = self::channelName((string) ($channel['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                foreach ($channel['programs'] ?? [] as $programme) {
                    $title = trim((string) ($programme['title'] ?? ''));
                    $start = $programme['start'] ?? null;

                    if ($title === '' || ! $start || ! self::isMatch($title, $programme['category'] ?? null)) {
                        continue;
                    }

                    [$home, $away] = self::teams($title);

                    $out[] = [
                        'channel' => $name,
                        'title' => $title,
                        'home' => $home,
                        'away' => $away,
                        'startsAt' => self::startsAt((string) $start),
                    ];
                }
            }
        }

        return $out;
    }

    /**
     * The guide, one page at a time.
     *
     * Yielded rather than collected, and this matters more than it looks: a
     * page is fifty channels with every programme each of them shows that
     * day, which measures about 1.8 MB — eleven of those held at once, then
     * decoded into PHP arrays, is hundreds of megabytes to end up with the
     * handful of football broadcasts among them. Handed over a page at a
     * time, only one is ever in memory.
     *
     * @return \Generator<int, array<int, array<string, mixed>>>
     */
    private static function pages(Carbon $date): \Generator
    {
        $url = (string) config('services.tv_guide.url');

        if ($url === '') {
            return;
        }

        for ($page = 0; $page < 20; $page++) {
            try {
                $response = Http::timeout((int) config('services.tv_guide.timeout', 30))
                    ->retry(2, 1000)
                    ->get($url, [
                        'sort' => 'pozicija-rastuce',
                        'searchQueryContext' => 'CHANNEL_PROGRAM',
                        'query' => ':pozicija-rastuce:tip-kanala-radio:TV kanali:channelProgramDates:'.$date->format('Y-m-d'),
                        'pageSize' => 50,
                        'currentPage' => $page,
                    ]);
            } catch (\Throwable $e) {
                Log::warning('TV guide unreachable', ['page' => $page, 'reason' => $e->getMessage()]);
                break;
            }

            if (! $response->successful()) {
                Log::warning('TV guide refused', ['page' => $page, 'status' => $response->status()]);
                break;
            }

            $products = $response->json('products') ?? [];

            if (! $products) {
                break;
            }

            $totalPages = (int) ($response->json('pagination.totalPages') ?? 0);

            yield $products;

            // Released before the next page is asked for, so peak memory is
            // one page rather than all of them.
            unset($products, $response);

            if ($page + 1 >= $totalPages) {
                break;
            }
        }
    }

    /**
     * Is this programme a match, rather than a preview, a round-up or a film?
     *
     * The guide's own category is the first word and the title's prefix the
     * second — "Fudbal - Spanska liga: Betis - Real Sociedad" — and both are
     * checked, because the category is not always filled in.
     */
    private static function isMatch(string $title, ?string $category): bool
    {
        $haystack = mb_strtolower($category.' '.$title, 'UTF-8');

        foreach (self::NOT_OURS as $other) {
            if (str_contains($haystack, $other)) {
                return false;
            }
        }

        foreach (self::SPORTS as $sport) {
            if (str_starts_with($haystack, $sport) || str_contains($haystack, $sport.' ')) {
                // A match has two sides, and the guide only ever writes them
                // after a colon:
                //
                //   Fudbal - Spanska liga: Betis - Real Sociedad   a match
                //   Fudbal - Mozzart Bet Superliga                 a studio show
                //
                // Without the colon the dash is the one between the sport and
                // the competition, and reading it as a pair of clubs makes
                // "Fudbal" the home side of half a dozen programmes a day.
                return str_contains($title, ':') && str_contains(
                    substr($title, strrpos($title, ':') + 1),
                    ' - '
                );
            }
        }

        return false;
    }

    /**
     * The two clubs out of a programme title. The guide writes them last and
     * consistently:
     *
     *   Fudbal - Spanska liga: Betis - Real Sociedad
     *   Kosarka - Grcka liga: Finale: Olympiacos - Panathinaikos, G5
     *
     * So the pair is whatever follows the final colon, and anything after a
     * comma is the leg or the round rather than part of a name.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function teams(string $title): array
    {
        if (! str_contains($title, ':')) {
            return [null, null];
        }

        $tail = trim(substr($title, strrpos($title, ':') + 1));

        // Only the separator with spaces around it: "Bodo/Glimt" keeps its
        // slash and "Rosario-Central" its hyphen.
        $sides = preg_split('/\s+-\s+/u', $tail, 2);

        if (count($sides) !== 2) {
            return [null, null];
        }

        $clean = fn (string $side) => trim(preg_replace('/,.*$/u', '', $side));

        $home = $clean($sides[0]);
        $away = $clean($sides[1]);

        return [$home ?: null, $away ?: null];
    }

    /**
     * The channel as we will store it. The guide's own spelling, tidied:
     * "Arena PREMIUM 1" is the same channel as "Arena Premium 1" and must
     * not become two entries on the listing.
     */
    private static function channelName(string $raw): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $raw));

        return preg_replace_callback('/\bPREMIUM\b/u', fn () => 'Premium', $name);
    }

    /**
     * The moment a programme actually starts.
     *
     * The guide this was written for stamps every programme with a "Z", and
     * none of them are UTC: a match kicking off at 19:00 in Belgrade is
     * written "21:00:00.000Z" — the local wall clock with the offset added
     * to it a second time, and then declared UTC. Read literally that puts
     * every broadcast four hours after its own match, which is what a broken
     * clock looks like and what a schedule of repeats does not.
     *
     * So: read the wall clock as the local time it really is, then take the
     * offset back off. Set TV_GUIDE_TIMEZONE empty for a guide whose stamps
     * mean what they say.
     */
    private static function startsAt(string $stamp): Carbon
    {
        $timezone = (string) config('services.tv_guide.timezone', 'Europe/Belgrade');

        if ($timezone === '') {
            return Carbon::parse($stamp)->utc();
        }

        $local = Carbon::parse(substr($stamp, 0, 19), $timezone);

        return $local->subSeconds($local->getOffset())->utc();
    }
}
