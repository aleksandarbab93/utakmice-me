<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One broadcast of one match, found by streams:sync — the video itself is YouTube's, this row is only what it belongs to. */
class FixtureStream extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_live' => 'bool',
        'embeddable' => 'bool',
        'starts_at' => 'datetime',
        'published_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(StreamSource::class, 'stream_source_id');
    }

    /** embeddable is nullable ("not asked yet"); this tests for true, not merely not-false. */
    public function scopePlayable(Builder $query): Builder
    {
        return $query->where('embeddable', true);
    }

    public function getWatchUrlAttribute(): string
    {
        return 'https://www.youtube.com/watch?v='.$this->external_id;
    }

    /** nocookie: nothing is requested from YouTube until the reader presses play. */
    public function getEmbedUrlAttribute(): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$this->external_id.'?autoplay=1&rel=0';
    }

    public function getThumbnailUrlAttribute(): string
    {
        return 'https://i.ytimg.com/vi/'.$this->external_id.'/hqdefault.jpg';
    }
}
