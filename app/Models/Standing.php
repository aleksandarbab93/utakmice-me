<?php

namespace App\Models;

use App\Observers\StandingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(StandingObserver::class)]
class Standing extends Model
{
    protected $fillable = [
        'league_id', 'team_id', 'position', 'played', 'won', 'draw', 'lost', 'goals_for', 'goals_against', 'points', 'goal_diff',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
