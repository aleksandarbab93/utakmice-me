<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Support\LocalDay;
use App\Support\SearchNormalizer;
use App\Support\TvGuide;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Fills in which channel carries which match.
 *
 * Ported from utakmice-rs-master, along with the two things that make it
 * harder than it sounds.
 *
 * The guide names clubs the way a viewer says them — "Crvena zvezda" — and
 * we hold them the way a data source does — "FK Crvena Zvezda". So the test
 * is containment rather than equality: every word the guide used must appear
 * in the club we hold. Strict in one direction and forgiving in the other,
 * because a sponsor in our name must not break a match and a missing word in
 * theirs must not invent one.
 *
 * And a schedule is mostly repeats — the same fixture is listed three times
 * a day, twice as a replay of a match that has already finished. Only a
 * broadcast beginning around the kickoff is the live one, so the time window
 * is not a refinement: without it the site tells people to watch a match on
 * a channel showing yesterday's tape.
 */
class SyncTvChannels extends Command
{
    protected $signature = 'tv:sync
        {--days=2   : how many days ahead to read, counting today}
        {--dry-run  : show what would be written and change nothing}
        {--force    : overwrite a channel that is already set}
        {--explain  : for every broadcast that found no match, say why}';

    protected $description = 'Reads a TV guide and records which channel carries each match';

    /**
     * How far a broadcast may begin from the kickoff and still be it.
     *
     * Ahead, because a match is handed to a studio first — the guide starts
     * the block at the top of the hour for a 20:15 kickoff. Behind, only a
     * little: a programme that begins after the whistle is a repeat.
     */
    private const STARTS_BEFORE = 75;

    private const STARTS_AFTER = 20;

    public function handle(): int
    {
        // A page of the guide is fifty channels with every programme each of
        // them shows that day — about 1.8 MB of JSON, and several times that
        // once decoded. One page is held at a time (see TvGuide::pages()),
        // but that one page is still large enough to matter.
        ini_set('memory_limit', '256M');

        if (! config('services.tv_guide.url')) {
            $this->info('Nema podešenog TV vodiča (TV_GUIDE_URL) — ništa se ne čita.');

            return self::SUCCESS;
        }

        $written = 0;
        $skipped = 0;

        for ($offset = 0; $offset < max(1, (int) $this->option('days')); $offset++) {
            $date = today()->addDays($offset);
            $programmes = TvGuide::forDate($date);

            if (! $programmes) {
                $this->warn($date->format('Y-m-d').': vodič nije vratio ništa.');

                continue;
            }

            $fixtures = $this->fixtures($date);
            $this->line(sprintf('%s: %d prenosa, %d mečeva', $date->format('Y-m-d'), count($programmes), $fixtures->count()));

            // Collected first, written after. A match is often on two
            // channels at once, and taking whichever the guide happened to
            // page first made the answer depend on the order of an HTTP
            // response.
            $chosen = [];

            foreach ($programmes as $programme) {
                $fixture = $this->match($programme, $fixtures);

                if (! $fixture) {
                    $this->explain($programme, $fixtures);

                    continue;
                }

                $chosen[$fixture->id] = $this->better($chosen[$fixture->id] ?? null, [$fixture, $programme]);
            }

            foreach ($chosen as [$fixture, $programme]) {
                if ($fixture->tv_channel && ! $this->option('force')) {
                    $skipped++;

                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("  upisao bi: {$fixture->homeTeam->name} - {$fixture->awayTeam->name} -> {$programme['channel']}");
                    $written++;

                    continue;
                }

                $fixture->forceFill(['tv_channel' => $programme['channel']])->save();
                $this->line("  <fg=green>✓</> {$fixture->homeTeam->name} - {$fixture->awayTeam->name} -> {$programme['channel']}");
                $written++;
            }
        }

        $this->newLine();
        $this->info("upisano kanala: {$written}, preskočeno (već imaju): {$skipped}");

        return self::SUCCESS;
    }

    /**
     * The matches of one day that could be on television. Every league we
     * carry is one somebody here follows, so unlike the source there is
     * nothing to filter out — the whole table is a few thousand rows.
     */
    private function fixtures(Carbon $date): Collection
    {
        return Fixture::with(['homeTeam', 'awayTeam'])
            ->whereBetween('kickoff_at', LocalDay::bounds($date))
            ->when(! $this->option('force'), fn ($q) => $q->whereNull('tv_channel'))
            ->get();
    }

    /** Which of our matches this broadcast is, if any. */
    private function match(array $programme, Collection $fixtures): ?Fixture
    {
        if (! $programme['home'] || ! $programme['away']) {
            return null;
        }

        $home = SearchNormalizer::normalize($programme['home']);
        $away = SearchNormalizer::normalize($programme['away']);

        foreach ($fixtures as $fixture) {
            if (! $this->aroundKickoff($programme['startsAt'], $fixture)) {
                continue;
            }

            if ($this->names($home, $fixture->homeTeam?->name) && $this->names($away, $fixture->awayTeam?->name)) {
                return $fixture;
            }
        }

        return null;
    }

    private function aroundKickoff(Carbon $startsAt, Fixture $fixture): bool
    {
        if (! $fixture->kickoff_at) {
            return false;
        }

        return $startsAt->between(
            $fixture->kickoff_at->copy()->subMinutes(self::STARTS_BEFORE),
            $fixture->kickoff_at->copy()->addMinutes(self::STARTS_AFTER),
        );
    }

    /**
     * Does the guide's name for a club fit the one we hold?
     *
     * Every word the guide used has to appear in ours. "Crvena zvezda" fits
     * "FK Crvena Zvezda"; "Real" alone would fit both Reals, which is why
     * the other side has to agree as well and why the kickoff has to be
     * right — three weak signals that are strong together.
     */
    private function names(string $guide, ?string $ours): bool
    {
        if ($guide === '' || ! $ours) {
            return false;
        }

        $mine = SearchNormalizer::normalize($ours);

        foreach (explode(' ', $guide) as $word) {
            // Single letters and numbers carry no weight and would match
            // half the table on their own.
            if (mb_strlen($word) < 2) {
                continue;
            }

            if (! str_contains(' '.$mine.' ', ' '.$word.' ')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Of two broadcasts of the same match, the one worth telling people
     * about. A viewer wants the channel they are most likely to have: Arena
     * Sport comes in the basic package and Arena Premium is bought on top of
     * it, so where a match is on both the basic one is the useful answer.
     */
    private function better(?array $held, array $offered): array
    {
        if (! $held) {
            return $offered;
        }

        $premium = fn (array $pair) => str_contains(mb_strtolower($pair[1]['channel']), 'premium');

        if ($premium($held) && ! $premium($offered)) {
            return $offered;
        }

        if (! $premium($held) && $premium($offered)) {
            return $held;
        }

        // Both the same kind, so the one that starts nearest the kickoff.
        $gap = fn (array $pair) => $pair[0]->kickoff_at
            ? abs($pair[1]['startsAt']->diffInMinutes($pair[0]->kickoff_at, false))
            : PHP_INT_MAX;

        return $gap($offered) < $gap($held) ? $offered : $held;
    }

    /**
     * Why a broadcast found nothing.
     *
     * Matching turns on two signals at once, and when a run comes back empty
     * the useful question is which of them is failing. A name that matches a
     * fixture two hours away says the clocks disagree; no name at all says
     * the guide and the source call the clubs different things.
     */
    private function explain(array $programme, Collection $fixtures): void
    {
        if (! $this->option('explain') || ! $programme['home'] || ! $programme['away']) {
            return;
        }

        $home = SearchNormalizer::normalize($programme['home']);
        $away = SearchNormalizer::normalize($programme['away']);

        foreach ($fixtures as $fixture) {
            if (! $this->names($home, $fixture->homeTeam?->name) || ! $this->names($away, $fixture->awayTeam?->name)) {
                continue;
            }

            $this->line(sprintf(
                '  <fg=yellow>vrijeme</>  %s | vodič %s, počinje %s (%+d min)',
                $programme['title'],
                $programme['startsAt']->format('H:i'),
                $fixture->kickoff_at?->format('H:i'),
                $fixture->kickoff_at ? $programme['startsAt']->diffInMinutes($fixture->kickoff_at, false) : 0,
            ));

            return;
        }

        $near = $fixtures
            ->filter(fn (Fixture $f) => $this->aroundKickoff($programme['startsAt'], $f))
            ->take(3)
            ->map(fn (Fixture $f) => trim(($f->homeTeam?->name ?? '?').' - '.($f->awayTeam?->name ?? '?')))
            ->implode('; ');

        $this->line(sprintf(
            '  <fg=red>ime</>  %s | tražio "%s" - "%s" | u to vrijeme imamo: %s',
            $programme['title'], $programme['home'], $programme['away'], $near ?: '(ništa)',
        ));
    }
}
