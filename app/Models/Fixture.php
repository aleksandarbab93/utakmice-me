<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /** The stored SStats detail payload, when some machine has managed to fetch it. */
    public function detailRecord(): HasOne
    {
        return $this->hasOne(MatchDetailRecord::class);
    }

    public function pushSubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(PushSubscription::class, 'push_subscription_fixture');
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    /**
     * SEO slug for the match-detail URL: crvena-zvezda-partizan-2026-08-09.
     * Computed, not stored — team slugs are stable and the date is the
     * local calendar day the visitor would actually call "the match",
     * matching how MatchController parses it back apart.
     */
    public function getSlugAttribute(): string
    {
        return $this->homeTeam->slug.'-'.$this->awayTeam->slug.'-'.$this->kickoff_at->local()->format('Y-m-d');
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'live');
    }
}
