<?php

namespace Tests\Feature;

use App\Support\PageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The page cache sits in front of every page on the site, so what it does
 * and — more importantly — what it refuses to do is worth pinning down.
 */
class PageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml turns it off for every other test, where a cached
        // response would hide the very thing being asserted.
        config(['pagecache.enabled' => true]);
    }

    public function test_a_repeat_visit_is_served_from_cache_without_touching_the_database(): void
    {
        $this->get('/')->assertOk()->assertHeader('X-Page-Cache', 'miss');

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get('/')->assertOk()->assertHeader('X-Page-Cache', 'hit');

        // Only the cache store's own read — no page data is fetched again.
        $this->assertLessThanOrEqual(1, $queries);
    }

    public function test_an_unknown_query_parameter_is_never_cached(): void
    {
        // Otherwise every shared link with a tracking parameter mints its
        // own copy of the page.
        $this->get('/utakmice?fbclid=abc')->assertOk()->assertHeaderMissing('X-Page-Cache');
    }

    public function test_a_known_query_parameter_is_cached(): void
    {
        $this->get('/utakmice?date=2026-09-06')->assertOk()->assertHeader('X-Page-Cache', 'miss');
        $this->get('/utakmice?date=2026-09-06')->assertOk()->assertHeader('X-Page-Cache', 'hit');
    }

    public function test_bumping_the_generation_retires_every_stored_page(): void
    {
        $this->get('/')->assertHeader('X-Page-Cache', 'miss');
        $this->get('/')->assertHeader('X-Page-Cache', 'hit');

        PageCache::bump();

        $this->get('/')->assertHeader('X-Page-Cache', 'miss');
    }

    public function test_the_session_cookie_is_stripped_so_a_shared_cache_will_store_the_page(): void
    {
        $response = $this->get('/');

        $this->assertEmpty($response->headers->getCookies());
        $this->assertStringContainsString('s-maxage', $response->headers->get('Cache-Control'));
    }
}
