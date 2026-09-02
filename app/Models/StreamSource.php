<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One YouTube channel known to broadcast one league, for free — hand-kept, never written by the sync besides polled_at. */
class StreamSource extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'bool',
        'polled_at' => 'datetime',
    ];

    public const YOUTUBE = 'youtube';

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function streams(): HasMany
    {
        return $this->hasMany(FixtureStream::class);
    }

    public function getChannelUrlAttribute(): string
    {
        return 'https://www.youtube.com/channel/'.$this->channel_id;
    }

    /** No key, no quota: the channel's fifteen most recent uploads. */
    public function getFeedUrlAttribute(): string
    {
        return 'https://www.youtube.com/feeds/videos.xml?channel_id='.$this->channel_id;
    }
}
