<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Reshapes real App\Models\Post rows into the array shape the Blade views
 * already expect (originally modeled on App\Support\SampleData), so the
 * views themselves needed no changes.
 */
class PostFeed
{
    public static function posts(string $sport, int $limit = 20): Collection
    {
        return Post::where('sport', $sport)
            ->with(['league', 'fixture.homeTeam', 'fixture.awayTeam'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Post $post) => self::format($post));
    }

    /**
     * Chronological izvještaj feed for the /vijesti listing page, optionally
     * narrowed to one league. Fudbal only — that page's chips, copy and SEO
     * description are all written for football; košarka reports show on
     * their own home page instead until this page is worth splitting by sport.
     */
    public static function reports(?string $leagueSlug = null, string $direction = 'desc', int $perPage = 12): LengthAwarePaginator
    {
        return Post::where('type', 'izvestaj')
            ->where('sport', 'fudbal')
            ->when($leagueSlug, fn ($q) => $q->whereHas('league', fn ($l) => $l->where('slug', $leagueSlug)))
            ->with(['league', 'fixture.homeTeam', 'fixture.awayTeam'])
            ->orderBy('published_at', $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Post $post) => self::format($post));
    }

    /** Distinct leagues with at least one izvještaj post — for the /vijesti filter chips. */
    public static function reportLeagues(): Collection
    {
        return Post::where('type', 'izvestaj')
            ->where('sport', 'fudbal')
            ->with('league')
            ->get()
            ->pluck('league')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn ($league) => ['name' => $league->name, 'slug' => $league->slug]);
    }

    public static function postBySlug(string $slug): ?array
    {
        $post = Post::where('slug', $slug)
            ->with(['league', 'fixture.homeTeam', 'fixture.awayTeam', 'fixture.league'])
            ->first();

        if (! $post) {
            return null;
        }

        $formatted = self::format($post);
        $formatted['match'] = self::matchPayload($post);

        return $formatted;
    }

    private static function format(Post $post): array
    {
        $words = str_word_count(implode(' ', array_merge([$post->lead], $post->body ?? [])));

        return [
            'slug' => $post->slug,
            'type' => $post->type,
            'sport' => $post->sport,
            'league' => $post->league?->name ?? 'Fudbal',
            'league_slug' => $post->league?->slug,
            'title' => $post->title,
            'lead' => $post->lead,
            'meta' => RelativeTime::sr($post->published_at),
            'published_at' => $post->published_at,
            'read_minutes' => max(1, (int) ceil($words / 200)),
            'author' => null,
            'body' => $post->body ?? [],
            'quote' => null,
            'tags' => self::tags($post),
            'image_url' => $post->image_url,
        ];
    }

    private static function tags(Post $post): array
    {
        if ($post->type === 'izvestaj' && $post->fixture) {
            return array_filter([
                $post->fixture->homeTeam?->name,
                $post->fixture->awayTeam?->name,
                $post->league?->name,
            ]);
        }

        return array_filter([$post->league?->name]);
    }

    private static function matchPayload(Post $post): ?array
    {
        $fixture = $post->fixture;

        if (! $fixture) {
            return null;
        }

        return [
            'id' => $fixture->id,
            'home' => $fixture->homeTeam->name,
            'away' => $fixture->awayTeam->name,
            'home_score' => $fixture->home_score,
            'away_score' => $fixture->away_score,
            'status' => $fixture->status,
            'minute' => $fixture->minute,
            'venue' => null,
            'round' => $fixture->matchday,
        ];
    }
}
