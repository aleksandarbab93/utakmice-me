<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function streams(): HasMany
    {
        return $this->hasMany(FixtureStream::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'live');
    }
}
