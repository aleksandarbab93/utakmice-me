<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stored SStats match-detail payload. Named ...Record to leave the
 * shorter App\Support\MatchDetail — which shapes this into what the view
 * renders — as it was.
 */
class MatchDetailRecord extends Model
{
    protected $table = 'match_details';

    protected $fillable = ['fixture_id', 'payload', 'fetched_at'];

    protected $casts = [
        'payload' => 'array',
        'fetched_at' => 'datetime',
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }
}
