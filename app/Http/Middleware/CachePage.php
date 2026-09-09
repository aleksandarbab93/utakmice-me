<?php

namespace App\Http\Middleware;

use App\Support\PageCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves whole rendered pages back out of the cache.
 *
 * Every page here is anonymous and identical for everybody who asks, and
 * the underlying data only changes when a sync writes — once a minute at
 * the very most. Rendering a match list means the fixtures, the league
 * rows, the crests and a standings table with a form guide per club; that
 * is dozens of queries to produce bytes that were already produced a few
 * seconds ago for somebody else.
 *
 * A short TTL rather than careful invalidation, because live scores are the
 * point of the site: half a minute behind is close enough to live given the
 * sync itself only runs each minute, and it needs no observer to be right.
 */
class CachePage
{
    /** Long enough to absorb a burst, short enough that a live score stays live. */
    private const TTL = 30;

    /**
     * Query parameters the site actually uses. Anything else — fbclid, utm_*,
     * whatever a crawler invents — means the page isn't cached at all, so
     * tracking junk can't mint a cache entry per visitor.
     */
    private const KNOWN_QUERY = ['date', 'tab', 'liga', 'page'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->worthCaching($request)) {
            return $next($request);
        }

        $key = 'page:'.PageCache::generation().':'.sha1($request->fullUrl());

        if ($cached = Cache::get($key)) {
            return $this->publicly(
                response($cached['body'], 200, ['Content-Type' => $cached['type']])
            )->header('X-Page-Cache', 'hit');
        }

        $response = $next($request);

        if ($this->worthKeeping($response)) {
            Cache::put($key, [
                'body' => $response->getContent(),
                'type' => $response->headers->get('Content-Type', 'text/html; charset=utf-8'),
            ], self::TTL);

            $this->publicly($response)->headers->set('X-Page-Cache', 'miss');
        }

        return $response;
    }

    /**
     * Lets a CDN hold the page too, and strips the session cookie while doing
     * it — a response carrying Set-Cookie is one no shared cache will store,
     * and an anonymous reader has nothing in a session worth keeping anyway.
     */
    private function publicly(Response $response): Response
    {
        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
        }

        $response->headers->set(
            'Cache-Control',
            'public, max-age=0, s-maxage='.self::TTL.', stale-while-revalidate='.(self::TTL * 2)
        );

        return $response;
    }

    private function worthCaching(Request $request): bool
    {
        if (! config('pagecache.enabled')) {
            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        // The JSON endpoints the push toggle and the detail intake use.
        if ($request->is('api/*')) {
            return false;
        }

        return array_diff(array_keys($request->query()), self::KNOWN_QUERY) === [];
    }

    private function worthKeeping(Response $response): bool
    {
        return $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
