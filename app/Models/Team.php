<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Team extends Model
{
    protected $fillable = ['league_id', 'name', 'short_name', 'aliases', 'crest_url', 'external_source', 'external_id'];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}
