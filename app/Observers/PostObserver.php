<?php

namespace App\Observers;

use App\Models\Post;
use App\Support\PageCache;

/**
 * A new report is the one thing on this site somebody might reasonably be
 * waiting to see. The page cache would surface it within half a minute on
 * its own; this makes it the next request instead.
 *
 * Deliberately not done for fixtures: the live sync touches every match in
 * play once a minute, and bumping on each of those would leave the page
 * cache permanently empty on exactly the evenings it matters most. Their
 * staleness is bounded by the cache's own short TTL instead.
 */
class PostObserver
{
    public function saved(Post $post): void
    {
        PageCache::bump();
    }

    public function deleted(Post $post): void
    {
        PageCache::bump();
    }
}
