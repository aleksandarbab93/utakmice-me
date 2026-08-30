<?php

namespace App\Support;

use App\Models\Post;
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
            'league' => $post->league?->name ?? 'Fudbal',
            'title' => $post->title,
            'lead' => $post->lead,
            'meta' => RelativeTime::sr($post->published_at),
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
