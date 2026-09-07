<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Team extends Model
{
    protected $fillable = ['league_id', 'name', 'short_name', 'slug', 'aliases', 'crest_url', 'external_source', 'external_id'];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Bare name first ("crvena-zvezda"), then name+league for the (rare)
     * clash between two clubs sharing a name in different countries, then
     * the external id as a last-resort tiebreaker — so a match page's URL
     * stays readable ("crvena-zvezda-partizan-2026-08-09") in the
     * overwhelming majority of cases instead of always carrying an id.
     */
    public static function uniqueSlug(string $name, ?string $league, string $externalId): string
    {
        $base = Str::slug($name) ?: 'tim';

        if (! static::where('slug', $base)->exists()) {
            return $base;
        }

        $withLeague = Str::slug($name.' '.$league);

        if ($withLeague !== $base && ! static::where('slug', $withLeague)->exists()) {
            return $withLeague;
        }

        return $base.'-'.$externalId;
    }
}
