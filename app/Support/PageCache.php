<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The version stamp every cached page carries.
 *
 * Cached pages are keyed by URL, and there is no way to enumerate them
 * afterwards — a day's crawling alone mints thousands. So nothing ever
 * deletes them: bumping this number leaves every stored page unreachable
 * and lets them expire on their own, which retires the whole cache without
 * flushing a store that also holds standings, feeds and match details.
 */
class PageCache
{
    private const KEY = 'page:generation';

    public static function generation(): int
    {
        return (int) Cache::get(self::KEY, 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::KEY, self::generation() + 1);
    }
}
