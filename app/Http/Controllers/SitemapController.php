<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * A single sitemap file — at our current scale (a few thousand fixtures,
 * a few hundred posts) well under the 50,000-URL sitemap limit, so there's
 * no need for the sitemap-index-plus-sections split a much larger archive
 * would require.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [];

            foreach (['fudbal', 'kosarka'] as $sport) {
                $urls[] = ['loc' => \App\Support\Nav::home($sport), 'priority' => '1.0'];
                $urls[] = ['loc' => \App\Support\Nav::scores($sport), 'priority' => '0.9'];
                $urls[] = ['loc' => \App\Support\Nav::standings($sport), 'priority' => '0.6'];
            }

            $urls[] = ['loc' => route('post.index'), 'priority' => '0.8'];
            $urls[] = ['loc' => route('streams'), 'priority' => '0.5'];
            $urls[] = ['loc' => route('leagues'), 'priority' => '0.6'];

            foreach (['page.about', 'page.contact', 'page.privacy', 'page.advertising'] as $name) {
                $urls[] = ['loc' => route($name), 'priority' => '0.3'];
            }

            League::query()->select('id', 'slug')->get()->each(function (League $league) use (&$urls) {
                $urls[] = ['loc' => route('league.show', $league->slug), 'priority' => '0.6'];
                $urls[] = ['loc' => route('league.results', $league->slug), 'priority' => '0.5'];
                $urls[] = ['loc' => route('league.fixtures', $league->slug), 'priority' => '0.5'];
            });

            Post::query()->select('slug', 'published_at')->orderByDesc('published_at')->get()
                ->each(function (Post $post) use (&$urls) {
                    $urls[] = [
                        'loc' => route('post.show', $post->slug),
                        'lastmod' => $post->published_at->toAtomString(),
                        'priority' => '0.5',
                    ];
                });

            Fixture::query()->select('id', 'kickoff_at', 'status')->orderByDesc('kickoff_at')->get()
                ->each(function (Fixture $fixture) use (&$urls) {
                    $urls[] = [
                        'loc' => route('match.show', $fixture->id),
                        'lastmod' => $fixture->kickoff_at?->toAtomString(),
                        'priority' => $fixture->status === 'live' ? '0.9' : '0.4',
                    ];
                });

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
