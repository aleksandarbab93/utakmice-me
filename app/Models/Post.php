<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = [
        'type', 'sport', 'slug', 'title', 'lead', 'body', 'image_url',
        'league_id', 'fixture_id', 'source_name', 'source_url', 'published_at',
    ];

    protected $casts = [
        'body' => 'array',
        'published_at' => 'datetime',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }
}
