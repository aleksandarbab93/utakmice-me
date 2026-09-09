<?php

namespace App\Observers;

use App\Models\Standing;
use App\Support\PageCache;
use Illuminate\Support\Facades\Cache;

/**
 * A table is cached for five minutes because it is expensive to build, not
 * because it is allowed to be five minutes wrong. When the sync actually
 * moves a club, that cache goes immediately.
 *
 * Eloquent only fires this when a row was dirty, so a sync writing the same
 * numbers back — which is most syncs — costs nothing.
 */
class StandingObserver
{
    public function saved(Standing $standing): void
    {
        Cache::forget("standings:{$standing->league_id}");
        PageCache::bump();
    }

    public function deleted(Standing $standing): void
    {
        $this->saved($standing);
    }
}
