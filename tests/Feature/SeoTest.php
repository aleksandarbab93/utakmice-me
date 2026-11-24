<?php

namespace Tests\Feature;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Post;
use App\Models\Team;
use App\Services\SStats\SStatsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function league(): League
    {
        return League::firstOrCreate(
            ['external_source' => 'sstats', 'external_id' => '13'],
            ['name' => 'Premijer liga BiH', 'slug' => 'premijer-liga-bih', 'sport' => 'fudbal'],
        );
    }

    /** @param array<string, mixed> $attributes */
    private function fixture(string $home, string $away, array $attributes = []): Fixture
    {
        $team = fn (string $name): Team => Team::firstOrCreate(
            ['external_source' => 'sstats', 'external_id' => md5($name)],
            ['league_id' => $this->league()->id, 'name' => $name, 'slug' => Str::slug($name)],
        );

        return Fixture::create($attributes + [
            'league_id' => $this->league()->id,
            'home_team_id' => $team($home)->id,
            'away_team_id' => $team($away)->id,
            'external_source' => 'sstats',
            'external_id' => (string) Str::uuid(),
            'kickoff_at' => '2026-10-31 16:00:00',
            'status' => 'scheduled',
        ]);
    }

    private function withoutSStats(): void
    {
        $this->mock(SStatsClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('gameDetail')->andReturn([]);
            $mock->shouldReceive('headToHead', 'teamForm')->andReturn([]);
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function schemas(TestResponse $response): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $found);

        return array_map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $found[1]);
    }

    private function canonical(TestResponse $response): string
    {
        preg_match('#<link rel="canonical" href="([^"]+)">#', $response->getContent(), $found);

        return html_entity_decode($found[1]);
    }

    public function test_front_pages_put_uzivo_next_to_the_searched_word(): void
    {
        $response = $this->get('/')->assertOk()
            ->assertSee('<title>Utakmice uživo danas i večeras — fudbal, rezultati i TV prenosi | Utakmice.me</title>', false);
        $this->assertSame('https://schema.org', $this->schemas($response)[0]['@context']);

        $this->get('/kosarka')->assertOk()
            ->assertSee('<title>Košarka uživo — utakmice danas i večeras, Evroliga i TV prenosi | Utakmice.me</title>', false)
            ->assertSeeInOrder(['<h1', 'Košarka uživo', '</h1>'], false);
    }

    public function test_a_picked_day_is_named_in_the_title(): void
    {
        $this->travelTo('2026-11-24 12:00:00');

        $this->get('/?date=2026-11-20')->assertOk()->assertSee('<title>Utakmice uživo 20.11.2026. —', false);
    }

    public function test_a_malformed_date_returns_404(): void
    {
        $this->get('/?date=nesto')->assertNotFound();
        $this->get('/?date[]=2026-11-20')->assertNotFound();
    }

    public function test_an_upcoming_match_has_a_dated_title_heading_and_breadcrumb(): void
    {
        $this->withoutSStats();
        $fixture = $this->fixture('Čelik', 'Borac Banja Luka', ['tv_channel' => 'Arena Sport 1']);

        $response = $this->get(route('match.show', $fixture->slug))->assertOk();

        $response->assertSee('<title>Čelik — Borac Banja Luka uživo — prenos i rezultat, 31.10.2026. | Utakmice.me</title>', false)
            ->assertSee('TV prenos: Arena Sport 1.')
            ->assertSee('<h1 class="sr-only">Čelik — Borac Banja Luka, 31.10.2026.</h1>', false);

        $breadcrumb = collect($this->schemas($response))->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame('https://schema.org', $breadcrumb['@context']);
        $this->assertSame(
            ['Fudbal', 'Premijer liga BiH', 'Čelik — Borac Banja Luka, 31.10.2026.'],
            array_column($breadcrumb['itemListElement'], 'name'),
        );
        $this->assertSame([1, 2, 3], array_column($breadcrumb['itemListElement'], 'position'));
    }

    public function test_a_finished_match_shows_its_score_in_the_title(): void
    {
        $this->withoutSStats();
        $fixture = $this->fixture('Čelik', 'Borac Banja Luka', ['status' => 'finished', 'home_score' => 1, 'away_score' => 2]);

        $this->get(route('match.show', $fixture->slug))->assertOk()
            ->assertSee('<title>Čelik — Borac Banja Luka 1:2, rezultat 31.10.2026. | Utakmice.me</title>', false);
    }

    public function test_a_finished_match_without_a_score_leaves_it_out_of_the_title(): void
    {
        $this->withoutSStats();
        $fixture = $this->fixture('Čelik', 'Borac Banja Luka', ['status' => 'finished']);

        $this->get(route('match.show', $fixture->slug))->assertOk()
            ->assertSee('<title>Čelik — Borac Banja Luka, rezultat 31.10.2026. | Utakmice.me</title>', false);
    }

    public function test_league_pages_name_the_season_and_what_they_list(): void
    {
        $this->travelTo('2026-11-24 12:00:00');
        $league = $this->league();

        $this->get(route('league.show', $league->slug))->assertOk()
            ->assertSee('<title>Premijer liga BiH 2026/27 — tabela, rezultati i raspored | Utakmice.me</title>', false);
        $this->get(route('league.fixtures', $league->slug))->assertOk()
            ->assertSee('<title>Premijer liga BiH 2026/27 — raspored i termini utakmica | Utakmice.me</title>', false);
    }

    public function test_a_later_schedule_page_is_its_own_canonical(): void
    {
        foreach (range(1, 21) as $number) {
            $this->fixture('Čelik', 'Klub '.$number);
        }
        $schedule = route('league.fixtures', 'premijer-liga-bih');

        $this->assertSame($schedule, $this->canonical($this->get($schedule.'?page=1&utm_source=x')));

        $second = $this->get($schedule.'?page=2&utm_source=x')->assertOk()->assertSee(', strana 2 |');
        $this->assertSame($schedule.'?page=2', $this->canonical($second));
    }

    public function test_the_news_canonical_drops_sort_order_and_tracking(): void
    {
        $response = $this->get('/vijesti?liga=hnl&sort=najstarije&utm_source=x')->assertOk();

        $this->assertSame(route('post.index', ['liga' => 'hnl']), $this->canonical($response));
    }

    public function test_a_report_is_marked_up_as_a_news_article(): void
    {
        $post = Post::create([
            'slug' => 'izvjestaj', 'type' => 'izvestaj', 'sport' => 'fudbal', 'title' => 'Čelik slavio', 'lead' => 'Uvod </script>',
            'body' => ['Tekst.'], 'published_at' => '2026-10-31 18:00:00', 'image_url' => '/slika.jpg',
        ]);

        $response = $this->get(route('post.show', $post->slug))->assertOk()
            ->assertSee('<meta property="og:image" content="'.url('/slika.jpg').'">', false);

        $article = collect($this->schemas($response))->firstWhere('@type', 'NewsArticle');
        $this->assertSame('Čelik slavio', $article['headline']);
        $this->assertSame('Uvod </script>', $article['description']);
        $this->assertSame('2026-10-31T18:00:00+00:00', $article['datePublished']);
    }

    public function test_the_sitemap_dates_a_match_by_its_last_change_not_its_kickoff(): void
    {
        $this->travelTo('2026-10-01 10:00:00');
        $this->fixture('Čelik', 'Borac Banja Luka');

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<lastmod>2026-10-01T10:00:00+00:00</lastmod>', false)
            ->assertDontSee('2026-10-31T16:00:00', false);
    }
}
