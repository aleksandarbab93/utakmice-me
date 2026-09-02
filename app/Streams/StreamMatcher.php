<?php

namespace App\Streams;

use App\Models\Fixture;
use App\Models\Team;
use App\Support\SearchNormalizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Which match a broadcast is a broadcast OF — ported from utakmice-rs-master.
 *
 * A channel writes its title for a human: "Napredak - Metalac Mozzart Bet
 * Prva liga Srbije 2026/27 3. Kolo", or the same in Cyrillic, or round-first.
 * Nothing in it is an identifier, so the match is found the way a reader
 * would: both clubs named, around the date the video appeared.
 *
 * Wrong is worse than nothing — a bare word only counts when it belongs to
 * exactly one club among the candidates, and a tie that scoring can't break
 * is left unmatched rather than guessed.
 */
class StreamMatcher
{
    /** Words that name no club — without this, two clubs both carrying "FK" would half-match on it. */
    private const NOISE = [
        'fk', 'fc', 'ofk', 'gfk', 'sfk', 'nk', 'hk', 'ac', 'cf', 'sc', 'sd', 'ud',
        'club', 'klub', 'fudbalski', 'football', 'team', 'tim',
    ];

    /** Safe only because of the uniqueness test in score() — a word shared by two clubs decides nothing however long it is. */
    private const MIN_WORD = 3;

    /** Both directions: a live broadcast is created days before kickoff, a replay uploaded hours after. */
    private const DAYS_BEFORE = 21;

    private const DAYS_AFTER = 4;

    /** @param  Collection<int, Fixture>  $fixtures  candidates from the source's own league */
    public static function pick(Collection $fixtures, string $title, Carbon $publishedAt): ?Fixture
    {
        $needle = ' '.SearchNormalizer::normalize($title).' ';
        $vocabulary = self::vocabulary($fixtures);

        $scored = [];

        foreach ($fixtures as $fixture) {
            if ($fixture->kickoff_at->lt($publishedAt->copy()->subDays(self::DAYS_AFTER))
                || $fixture->kickoff_at->gt($publishedAt->copy()->addDays(self::DAYS_BEFORE))) {
                continue;
            }

            $home = self::score($needle, $fixture->homeTeam, $vocabulary);
            $away = self::score($needle, $fixture->awayTeam, $vocabulary);

            // Both, always — one club named and the other not is a highlights
            // reel or the other team's match, never this one.
            if ($home === 0 || $away === 0) {
                continue;
            }

            $scored[] = ['fixture' => $fixture, 'score' => $home + $away];
        }

        if ($scored === []) {
            return null;
        }

        usort($scored, function (array $a, array $b) use ($publishedAt) {
            if ($byScore = $b['score'] <=> $a['score']) {
                return $byScore;
            }

            return abs($a['fixture']->kickoff_at->diffInSeconds($publishedAt))
                <=> abs($b['fixture']->kickoff_at->diffInSeconds($publishedAt));
        });

        return $scored[0]['fixture'];
    }

    /**
     * How many DIFFERENT clubs in the running use each word — counted across
     * just the candidate set, since the only question is "which of THESE".
     *
     * @param  Collection<int, Fixture>  $fixtures
     * @return array<string, array<int, true>>
     */
    private static function vocabulary(Collection $fixtures): array
    {
        $words = [];

        foreach ($fixtures as $fixture) {
            foreach ([$fixture->homeTeam, $fixture->awayTeam] as $team) {
                if (! $team) {
                    continue;
                }

                foreach (self::words($team) as $word) {
                    $words[$word][$team->id] = true;
                }
            }
        }

        return $words;
    }

    /** @return list<string> */
    private static function words(Team $team): array
    {
        $source = implode(' ', array_filter([$team->name, $team->short_name, $team->aliases]));

        $words = array_filter(
            explode(' ', SearchNormalizer::normalize($source)),
            fn (string $word) => strlen($word) >= self::MIN_WORD
                && ! in_array($word, self::NOISE, true)
                && ! ctype_digit($word),
        );

        return array_values(array_unique($words));
    }

    /**
     * 2 for a whole name it goes by, 1 for a single word that can only be it, 0 for absent.
     *
     * @param  array<string, array<int, true>>  $vocabulary
     */
    private static function score(string $needle, ?Team $team, array $vocabulary): int
    {
        if (! $team) {
            return 0;
        }

        foreach (self::fullNames($team) as $candidate) {
            $normalized = SearchNormalizer::normalize($candidate);

            if ($normalized !== '' && str_contains($needle, ' '.$normalized.' ')) {
                return 2;
            }
        }

        foreach (self::words($team) as $word) {
            if (count($vocabulary[$word] ?? []) > 1) {
                continue; // shared with another candidate club — names neither
            }

            if (str_contains($needle, ' '.$word.' ')) {
                return 1;
            }
        }

        return 0;
    }

    /** @return list<string> */
    private static function fullNames(Team $team): array
    {
        $names = [$team->name, $team->short_name];

        foreach (explode(',', (string) $team->aliases) as $alias) {
            $names[] = trim($alias);
        }

        return array_values(array_filter($names));
    }
}
