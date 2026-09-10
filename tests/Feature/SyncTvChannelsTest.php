<?php

namespace Tests\Feature;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Matching a broadcast to a match turns on three weak signals at once — the
 * two club names and the kickoff — and every one of these tests is a way
 * that went wrong on the reference site before it went right.
 */
class SyncTvChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.tv_guide.url' => 'https://guide.test/search',
            'services.tv_guide.timezone' => 'Europe/Belgrade',
        ]);
    }

    /** A fixture kicking off at the given local wall clock. */
    private function fixture(string $home, string $away, string $localTime): Fixture
    {
        $league = League::firstOrCreate(
            ['external_source' => 'sstats', 'external_id' => '1'],
            ['name' => 'Superliga Srbije', 'slug' => 'superliga-srbije', 'sport' => 'fudbal'],
        );

        $team = function (string $name) use ($league) {
            return Team::firstOrCreate(
                ['external_source' => 'sstats', 'external_id' => md5($name)],
                ['league_id' => $league->id, 'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name)],
            );
        };

        return Fixture::create([
            'league_id' => $league->id,
            'home_team_id' => $team($home)->id,
            'away_team_id' => $team($away)->id,
            'external_source' => 'sstats',
            'external_id' => md5($home.$away.$localTime),
            'kickoff_at' => Carbon::parse(today()->format('Y-m-d').' '.$localTime, 'Europe/Belgrade')->utc(),
            'status' => 'scheduled',
        ]);
    }

    /**
     * What the guide would publish for a programme starting at the given
     * local wall clock: that clock with the offset added to it a second
     * time, and a "Z" on the end. See TvGuide::startsAt().
     */
    private function guideReturns(string $channel, string $title, string $localTime, string $category = 'Fudbal'): void
    {
        $real = Carbon::parse(today()->format('Y-m-d').' '.$localTime, 'Europe/Belgrade');
        $written = $real->copy()->addSeconds($real->getOffset())->format('Y-m-d\TH:i:s.000\Z');

        Http::fake([
            '*' => Http::response([
                'products' => [[
                    'name' => $channel,
                    'programs' => [['title' => $title, 'category' => $category, 'start' => $written]],
                ]],
                'pagination' => ['totalPages' => 1],
            ]),
        ]);
    }

    public function test_it_writes_the_channel_carrying_the_match(): void
    {
        $fixture = $this->fixture('FK Crvena Zvezda', 'FK Partizan', '20:00');
        $this->guideReturns('Arena Premium 1', 'Fudbal - Superliga: Crvena zvezda - Partizan', '19:45');

        $this->artisan('tv:sync --days=1')->assertSuccessful();

        $this->assertSame('Arena Premium 1', $fixture->fresh()->tv_channel);
    }

    /**
     * The guide names clubs the way a viewer says them and we hold them the
     * way a data source does, so the test is containment, not equality.
     */
    public function test_a_sponsor_in_our_name_does_not_break_the_match(): void
    {
        $fixture = $this->fixture('Crvena Zvezda Meridianbet Belgrade', 'FK Partizan', '20:00');
        $this->guideReturns('Arena Sport 1', 'Fudbal - Superliga: Crvena zvezda - Partizan', '20:00');

        $this->artisan('tv:sync --days=1');

        $this->assertSame('Arena Sport 1', $fixture->fresh()->tv_channel);
    }

    /**
     * A schedule is mostly repeats — the same fixture is listed again hours
     * later as a replay, and that broadcast is not where to watch it.
     */
    public function test_a_repeat_hours_later_is_not_the_live_broadcast(): void
    {
        $fixture = $this->fixture('FK Crvena Zvezda', 'FK Partizan', '20:00');
        $this->guideReturns('Arena Sport 2', 'Fudbal - Superliga: Crvena zvezda - Partizan', '23:30');

        $this->artisan('tv:sync --days=1');

        $this->assertNull($fixture->fresh()->tv_channel);
    }

    /** A viewer wants the channel they are likelier to have. */
    public function test_the_basic_channel_wins_over_the_premium_one(): void
    {
        $fixture = $this->fixture('FK Crvena Zvezda', 'FK Partizan', '20:00');

        $real = Carbon::parse(today()->format('Y-m-d').' 20:00', 'Europe/Belgrade');
        $written = $real->copy()->addSeconds($real->getOffset())->format('Y-m-d\TH:i:s.000\Z');
        $programme = ['title' => 'Fudbal - Superliga: Crvena zvezda - Partizan', 'category' => 'Fudbal', 'start' => $written];

        Http::fake(['*' => Http::response([
            'products' => [
                ['name' => 'Arena Premium 3', 'programs' => [$programme]],
                ['name' => 'Arena Sport 1', 'programs' => [$programme]],
            ],
            'pagination' => ['totalPages' => 1],
        ])]);

        $this->artisan('tv:sync --days=1');

        $this->assertSame('Arena Sport 1', $fixture->fresh()->tv_channel);
    }

    /**
     * "Fudbal - Mozzart Bet Superliga" is a studio show. Without requiring a
     * colon, the dash between the sport and the competition reads as a pair
     * of clubs and makes "Fudbal" the home side.
     */
    public function test_a_studio_show_is_not_a_match(): void
    {
        $fixture = $this->fixture('FK Crvena Zvezda', 'FK Partizan', '20:00');
        $this->guideReturns('Arena Sport 1', 'Fudbal - Mozzart Bet Superliga', '20:00');

        $this->artisan('tv:sync --days=1');

        $this->assertNull($fixture->fresh()->tv_channel);
    }

    /** The word is there and it is not the game. */
    public function test_american_football_is_not_ours(): void
    {
        $fixture = $this->fixture('Houston Texans', 'Las Vegas Raiders', '20:00');
        $this->guideReturns('Arena Sport 1', 'Americki fudbal - NFL: Houston Texans - Las Vegas Raiders', '20:00');

        $this->artisan('tv:sync --days=1');

        $this->assertNull($fixture->fresh()->tv_channel);
    }

    /** Somebody typed this in; a guide does not overrule a person unasked. */
    public function test_a_channel_already_set_is_left_alone_unless_forced(): void
    {
        $fixture = $this->fixture('FK Crvena Zvezda', 'FK Partizan', '20:00');
        $fixture->forceFill(['tv_channel' => 'RTS 1'])->save();

        $this->guideReturns('Arena Sport 1', 'Fudbal - Superliga: Crvena zvezda - Partizan', '20:00');

        $this->artisan('tv:sync --days=1');
        $this->assertSame('RTS 1', $fixture->fresh()->tv_channel);

        $this->artisan('tv:sync --days=1 --force');
        $this->assertSame('Arena Sport 1', $fixture->fresh()->tv_channel);
    }

    public function test_it_does_nothing_at_all_without_a_configured_guide(): void
    {
        config(['services.tv_guide.url' => null]);
        Http::fake();

        $fixture = $this->fixture('FK Crvena Zvezda', 'FK Partizan', '20:00');

        $this->artisan('tv:sync')->assertSuccessful();

        $this->assertNull($fixture->fresh()->tv_channel);
        Http::assertNothingSent();
    }

    public function test_a_guide_that_refuses_writes_nothing(): void
    {
        Http::fake(['*' => Http::response('', 503)]);

        $fixture = $this->fixture('FK Crvena Zvezda', 'FK Partizan', '20:00');

        $this->artisan('tv:sync --days=1')->assertSuccessful();

        $this->assertNull($fixture->fresh()->tv_channel);
    }
}
