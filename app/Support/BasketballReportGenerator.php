<?php

namespace App\Support;

use App\Models\Fixture;
use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Builds a short, factual "izvještaj" post from a finished EuroLeague/EuroCup
 * game — using the quarter-by-quarter score (already part of the same
 * payload SyncBasketballData/SyncLiveBasketball fetch, so this costs no
 * extra API call) to narrate how the game actually developed, not just the
 * final score. Mirrors App\Support\MatchReportGenerator's football version.
 * Never invents anything the API didn't actually give us — there's no
 * per-player data here, so no top-scorer line either.
 */
class BasketballReportGenerator
{
    /**
     * @param  array  $game  one entry from EuroleagueClient::games(), matching $fixture by external_id
     */
    public static function generate(Fixture $fixture, array $game): ?Post
    {
        if ($fixture->status !== 'finished' || $fixture->home_score === null || $fixture->away_score === null) {
            return null;
        }

        if (Post::where('fixture_id', $fixture->id)->where('type', 'izvestaj')->exists()) {
            return null;
        }

        $fixture->loadMissing(['homeTeam', 'awayTeam', 'league']);
        $home = $fixture->homeTeam->name;
        $away = $fixture->awayTeam->name;
        $hs = $fixture->home_score;
        $as = $fixture->away_score;
        $league = $fixture->league->name;

        $homePeriods = self::periods($game['local'] ?? []);
        $awayPeriods = self::periods($game['road'] ?? []);
        $variant = $fixture->id % 3;

        $title = self::headline($variant, $home, $away, $hs, $as, $league, $homePeriods, $awayPeriods);
        $slug = Str::slug($title.'-'.$fixture->id);

        $intro = self::opener($variant, $home, $away, $hs, $as, $league);
        $quarters = self::quarterSentence($home, $away, $homePeriods, $awayPeriods);
        $halftime = self::halftimeSentence($homePeriods, $awayPeriods);
        $venue = self::venueSentence($game);

        $paragraph1 = trim($intro.($quarters ? ' '.$quarters : '').($halftime ? ' '.$halftime : ''));
        $paragraph2 = $venue;

        $body = array_values(array_filter([$paragraph1, $paragraph2 ?: null]));

        $lead = $hs > $as
            ? "{$home} je bio bolji od {$away} i slavio rezultatom {$hs}:{$as} u meču {$league}."
            : "{$away} je slavio na gostovanju kod {$home}, meč u okviru {$league} završen je rezultatom {$hs}:{$as}.";

        return Post::create([
            'type' => 'izvestaj',
            'sport' => 'kosarka',
            'slug' => $slug,
            'title' => $title,
            'lead' => $lead,
            'body' => $body,
            'league_id' => $fixture->league_id,
            'fixture_id' => $fixture->id,
            'published_at' => self::estimatedFullTime($fixture),
        ]);
    }

    /** Quarter scores plus any overtime periods, in order. */
    private static function periods(array $side): array
    {
        $p = $side['partials'] ?? null;

        if (! $p) {
            return [];
        }

        $periods = array_values(array_filter([
            $p['partials1'] ?? null,
            $p['partials2'] ?? null,
            $p['partials3'] ?? null,
            $p['partials4'] ?? null,
        ], fn ($v) => $v !== null));

        foreach ($p['extraPeriods'] ?? [] as $extra) {
            if ($extra !== null) {
                $periods[] = $extra;
            }
        }

        return $periods;
    }

    /**
     * Picks the most compelling real detail — overtime, a comeback, a
     * quarter that decided the game, a rout, or a nailbiter — falling back
     * to a plain scoreline when the quarter data doesn't stand out.
     */
    private static function headline(int $variant, string $home, string $away, int $hs, int $as, string $league, array $homeP, array $awayP): string
    {
        $generic = "{$home} - {$away} {$hs}:{$as}";
        $n = min(count($homeP), count($awayP));

        if ($n === 0) {
            return $generic;
        }

        $winnerSide = $hs > $as ? 'home' : 'away';
        $winner = $winnerSide === 'home' ? $home : $away;
        $loser = $winnerSide === 'home' ? $away : $home;
        $overtime = $n > 4;

        $runningHome = 0;
        $runningAway = 0;
        $leaders = [];
        $biggestPeriod = null;

        for ($i = 0; $i < $n; $i++) {
            $runningHome += $homeP[$i];
            $runningAway += $awayP[$i];
            $diff = $homeP[$i] - $awayP[$i];

            if ($biggestPeriod === null || abs($diff) > abs($biggestPeriod['diff'])) {
                $biggestPeriod = ['index' => $i, 'side' => $diff > 0 ? 'home' : 'away', 'diff' => abs($diff)];
            }

            $leaders[] = $runningHome === $runningAway ? null : ($runningHome > $runningAway ? 'home' : 'away');
        }

        $winnerTrailedEarlier = false;
        foreach (array_slice($leaders, 0, -1) as $leader) {
            if ($leader !== null && $leader !== $winnerSide) {
                $winnerTrailedEarlier = true;
                break;
            }
        }

        if ($overtime) {
            return "{$winner} slavio protiv {$loser} tek poslije produžetka ({$hs}:{$as})";
        }

        if ($winnerTrailedEarlier) {
            return "Preokret: {$winner} stigao do pobjede protiv {$loser} ({$hs}:{$as})";
        }

        if ($biggestPeriod['side'] === $winnerSide && $biggestPeriod['diff'] >= 15) {
            $label = self::periodLabel($biggestPeriod['index']);

            return "{$winner} razbio {$loser} u {$label} četvrtini i stigao do pobjede ({$hs}:{$as})";
        }

        $margin = abs($hs - $as);

        if ($margin >= 20) {
            return "{$winner} ubjedljivo slavio protiv {$loser} rezultatom {$hs}:{$as}";
        }

        if ($margin <= 3) {
            return "{$winner} do pobjede protiv {$loser} u tijesnoj završnici ({$hs}:{$as})";
        }

        return match ($variant) {
            0 => "{$winner} slavio protiv {$loser} rezultatom {$hs}:{$as} u meču {$league}",
            1 => "{$winner} bolji od {$loser} sa {$hs}:{$as} u okviru {$league}",
            default => "Pobjedom {$hs}:{$as} protiv {$loser}, {$winner} je upisao bod u {$league}",
        };
    }

    private static function periodLabel(int $index): string
    {
        return match ($index) {
            0 => 'prvoj',
            1 => 'drugoj',
            2 => 'trećoj',
            default => 'četvrtoj',
        };
    }

    private static function opener(int $variant, string $home, string $away, int $hs, int $as, string $league): string
    {
        $winner = $hs > $as ? $home : $away;
        $loser = $hs > $as ? $away : $home;
        $isHomeWin = $hs > $as;

        return match ($variant) {
            0 => $isHomeWin
                ? "{$home} je na svom terenu slavio protiv {$away} rezultatom {$hs}:{$as} u meču {$league}."
                : "{$away} je na gostovanju kod {$home} stigao do pobjede rezultatom {$hs}:{$as} u meču {$league}.",
            1 => "{$winner} je savladao {$loser} sa {$hs}:{$as} u okviru {$league}.",
            default => "Pobjedom {$hs}:{$as} protiv {$loser}, {$winner} je upisao bod u {$league}.",
        };
    }

    /** "Rezultati po četvrtinama: 19:26, 27:18, 15:21, 31:20." — real numbers only, straight from the source. */
    private static function quarterSentence(string $home, string $away, array $homeP, array $awayP): ?string
    {
        $n = min(count($homeP), count($awayP));

        if ($n === 0) {
            return null;
        }

        $scores = [];
        for ($i = 0; $i < $n; $i++) {
            $scores[] = "{$homeP[$i]}:{$awayP[$i]}";
        }

        return 'Rezultati po četvrtinama: '.implode(', ', $scores).'.';
    }

    private static function halftimeSentence(array $homeP, array $awayP): ?string
    {
        if (count($homeP) < 2 || count($awayP) < 2) {
            return null;
        }

        $homeHalf = $homeP[0] + $homeP[1];
        $awayHalf = $awayP[0] + $awayP[1];

        return "Na poluvremenu je rezultat bio {$homeHalf}:{$awayHalf}.";
    }

    private static function venueSentence(array $game): string
    {
        $venue = $game['venue']['name'] ?? null;

        return $venue ? "Meč je odigran u dvorani {$venue}." : '';
    }

    /** Tip-off + ~2h15m (a basketball game's usual duration with breaks), never later than now. */
    private static function estimatedFullTime(Fixture $fixture): Carbon
    {
        $fullTime = $fixture->kickoff_at->copy()->addMinutes(135);

        return $fullTime->isFuture() ? Carbon::now() : $fullTime;
    }
}
