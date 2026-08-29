<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fixture extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'league_id', 'home_team_id', 'away_team_id', 'external_source', 'external_id',
        'kickoff_at', 'status', 'home_score', 'away_score', 'minute', 'matchday', 'venue',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
