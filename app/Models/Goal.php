<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    protected $fillable = [
        'fixture_id', 'league_id', 'team_id', 'player_name', 'minute', 'is_penalty', 'is_own_goal',
    ];

    protected $casts = [
        'is_penalty' => 'boolean',
        'is_own_goal' => 'boolean',
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
