<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\FixtureStream;
use App\Models\StreamSource;
use App\Streams\StreamMatcher;
use App\Streams\YouTube;
use Illuminate\Console\Command;
use Throwable;

/**
 * Finds the free broadcasts of matches we already hold, and attaches them —
 * ported from utakmice-rs-master's sync:streams.
 *
 * The cheapest sync on the site: one feed read per channel, no key, no
 * quota. Verification (is this embeddable, is it live) is a deliberately
 * separate pass, since discovery is free and can run every ten minutes
 * while asking YouTube those two questions is worth doing more sparingly.
 */
class SyncStreams extends Command
{
    protected $signature = 'streams:sync
        {--league=      : limit to one league slug}
        {--prune=30     : delete broadcasts of matches older than this many days}
        {--no-verify    : discovery only, leave the new rows unverified}';

    protected $description = 'Attaches free live broadcasts to the fixtures they belong to';

    public function handle(YouTube $youtube): int
    {
        $sources = StreamSource::with('league')->where('is_active', true)->get();

        if ($slug = $this->option('league')) {
            $sources = $sources->filter(fn (StreamSource $s) => $s->league?->slug === $slug);

            if ($sources->isEmpty()) {
                $this->error("No active stream source for league '{$slug}'.");

                return self::FAILURE;
            }
        }

        $found = 0;
        $attached = 0;

        foreach ($sources as $source) {
            if (! $source->league) {
                continue;
            }

            try {
                $entries = $youtube->feed($source->channel_id);
            } catch (Throwable $e) {
                $this->error("{$source->channel_name}: {$e->getMessage()}");

                continue;
            }

            $fixtures = Fixture::with(['homeTeam:id,name,short_name,aliases', 'awayTeam:id,name,short_name,aliases'])
                ->where('league_id', $source->league_id)
                ->whereBetween('kickoff_at', [now()->subDays(30), now()->addDays(30)])
                ->get(['id', 'league_id', 'home_team_id', 'away_team_id', 'kickoff_at']);

            $matched = 0;

            foreach ($entries as $entry) {
                $found++;

                $fixture = StreamMatcher::pick($fixtures, $entry['title'], $entry['published_at']);

                if (! $fixture) {
                    continue;
                }

                $stream = FixtureStream::updateOrCreate(
                    [
                        'provider' => StreamSource::YOUTUBE,
                        'external_id' => $entry['video_id'],
                        'fixture_id' => $fixture->id,
                    ],
                    [
                        'stream_source_id' => $source->id,
                        'title' => $entry['title'],
                        'published_at' => $entry['published_at'],
                    ],
                );

                $matched++;
                $attached += $stream->wasRecentlyCreated ? 1 : 0;
            }

            $source->forceFill(['polled_at' => now()])->saveQuietly();

            $this->line("  {$source->channel_name}: ".count($entries)." entries, {$matched} matched");
        }

        $verified = $this->option('no-verify') ? 0 : $this->verify($youtube);
        $pruned = $this->prune();

        $this->info("streams: {$found} entries read, {$attached} new, {$verified} verified, {$pruned} pruned");

        return self::SUCCESS;
    }

    /**
     * Fills in what the feed can't say. Two kinds of row qualify: anything
     * never asked about, and anything around its own match's kickoff, where
     * "upcoming" turns into "live" turns into "none" over a few hours.
     */
    private function verify(YouTube $youtube): int
    {
        $due = FixtureStream::with('fixture:id,kickoff_at')
            ->where(fn ($q) => $q
                ->whereNull('verified_at')
                ->orWhereHas('fixture', fn ($f) => $f->whereBetween('kickoff_at', [now()->subHours(4), now()->addHours(6)])))
            ->limit(200)
            ->get();

        if ($due->isEmpty()) {
            return 0;
        }

        $details = $youtube->details($due->pluck('external_id')->all());
        $count = 0;

        foreach ($due as $stream) {
            $detail = $details[$stream->external_id] ?? null;

            if (! $detail) {
                continue;
            }

            $stream->forceFill([
                'is_live' => $detail['is_live'],
                'embeddable' => $detail['embeddable'],
                'starts_at' => $detail['starts_at'] ?? $stream->starts_at,
                'verified_at' => now(),
            ])->save();

            $count++;
        }

        return $count;
    }

    /** The table is a working set, not an archive — rows behind old matches are simply found again if ever needed. */
    private function prune(): int
    {
        $days = (int) $this->option('prune');

        if ($days <= 0) {
            return 0;
        }

        return FixtureStream::whereHas(
            'fixture',
            fn ($q) => $q->where('kickoff_at', '<', now()->subDays($days)),
        )->delete();
    }
}
