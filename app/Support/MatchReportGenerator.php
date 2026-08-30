<?php

namespace App\Support;

use App\Models\Fixture;
use App\Models\Post;
use App\Services\SStats\SStatsClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Builds a short, factual "izveštaj" post from a finished fixture — pulling
 * real goal scorers, half-time score and match statistics from SStats.net's
 * per-match detail endpoint, not just the final score. Phrasing is picked
 * from a small pool per outcome (keyed off the fixture id) so reports don't
 * all read identically. Never invents anything the API didn't actually give us.
 */
class MatchReportGenerator
{
    public static function generate(Fixture $fixture, SStatsClient $client): ?Post
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

        $detail = null;
        try {
            $detail = $client->gameDetail((int) $fixture->external_id);
        } catch (\Throwable) {
            // detail endpoint failing (rate limit, transient error) shouldn't block a basic report
        }

        $variant = $fixture->id % 3;
        $title = "{$home} - {$away} {$hs}:{$as}";
        $slug = Str::slug($title.'-'.$fixture->id);

        $intro = self::opener($variant, $home, $away, $hs, $as, $league);
        $goalsNarrative = self::goalsNarrative($detail, $home, $away);
        $halfTime = self::halfTimeSentence($detail);
        $redCard = self::redCardSentence($detail, $home, $away);
        $statsSentence = self::statsSentence($detail, $home, $away);
        $venueSentence = self::venueSentence($detail);

        $paragraph1 = trim($intro.($goalsNarrative ? ' '.$goalsNarrative : '').($halfTime ? ' '.$halfTime : ''));
        $paragraph2 = trim(($redCard ? $redCard.' ' : '').($statsSentence ? $statsSentence.' ' : '').$venueSentence);

        $body = array_values(array_filter([$paragraph1, $paragraph2 ?: null]));

        $lead = $hs > $as
            ? "{$home} je bio bolji od {$away} i slavio rezultatom {$hs}:{$as} u meču {$league}."
            : ($as > $hs
                ? "{$away} je slavio na gostovanju kod {$home}, meč u okviru {$league} završen je rezultatom {$hs}:{$as}."
                : "{$home} i {$away} podijelili su bodove, meč u okviru {$league} završen je rezultatom {$hs}:{$as}.");

        return Post::create([
            'type' => 'izvestaj',
            'sport' => 'fudbal',
            'slug' => $slug,
            'title' => $title,
            'lead' => $lead,
            'body' => $body,
            'league_id' => $fixture->league_id,
            'fixture_id' => $fixture->id,
            'published_at' => self::estimatedFullTime($fixture),
        ]);
    }

    private static function opener(int $variant, string $home, string $away, int $hs, int $as, string $league): string
    {
        if ($hs > $as) {
            return match ($variant) {
                0 => "{$home} je na svom terenu slavio protiv {$away} rezultatom {$hs}:{$as} u meču {$league}.",
                1 => "{$home} je savladao {$away} sa {$hs}:{$as} u okviru {$league}.",
                default => "Pobjedom {$hs}:{$as} protiv {$away}, {$home} je upisao tri boda u {$league}.",
            };
        }

        if ($as > $hs) {
            return match ($variant) {
                0 => "{$away} je na gostovanju kod {$home} stigao do pobjede rezultatom {$hs}:{$as} u meču {$league}.",
                1 => "{$away} je iznenadio {$home} na njegovom terenu i slavio {$hs}:{$as} u okviru {$league}.",
                default => "Gostujuća pobjeda {$away} od {$hs}:{$as} obilježila je duel sa {$home} u {$league}.",
            };
        }

        return match ($variant) {
            0 => "{$home} i {$away} podijelili su bodove nakon meča {$league} koji je završen rezultatom {$hs}:{$as}.",
            1 => "Nakon 90 minuta na semaforu je ostalo {$hs}:{$as} — {$home} i {$away} remizirali su u {$league}.",
            default => "{$home} i {$away} nijesu uspjeli da naprave razliku, meč u {$league} završen je {$hs}:{$as}.",
        };
    }

    /**
     * Walks the goals in chronological order and narrates how the score
     * actually developed (opener, equalizer, go-ahead, extended lead) —
     * instead of just listing scorers — using the shared event timeline
     * that also powers the /mec/{fixture} "tok meča" page.
     */
    private static function goalsNarrative(?array $detail, string $home, string $away): ?string
    {
        $goals = MatchDetail::events($detail)->filter(fn ($e) => in_array($e['icon'], ['goal', 'og'], true))->values();

        if ($goals->isEmpty()) {
            return null;
        }

        $runningHome = 0;
        $runningAway = 0;
        $sentences = [];

        foreach ($goals as $i => $g) {
            $isOwnGoal = $g['icon'] === 'og';
            $creditSide = $isOwnGoal ? ($g['side'] === 'home' ? 'away' : 'home') : $g['side'];

            // Margin from the scoring team's own perspective (positive = leading), before and after this goal.
            $marginBefore = $creditSide === 'home' ? $runningHome - $runningAway : $runningAway - $runningHome;

            if ($creditSide === 'home') {
                $runningHome++;
            } else {
                $runningAway++;
            }

            $marginAfter = $creditSide === 'home' ? $runningHome - $runningAway : $runningAway - $runningHome;

            $team = $creditSide === 'home' ? $home : $away;
            $minute = $g['elapsed'].($g['extra'] ? '+'.$g['extra'] : '');
            $isPenalty = $g['subtitle'] === 'Penal';

            $scorerPhrase = $isOwnGoal
                ? "autogolom {$g['player']}a"
                : ($isPenalty ? "golom iz jedanaesterca ({$g['player']})" : "golom {$g['player']}a");

            if ($i === 0) {
                $sentences[] = "{$team} je poveo {$scorerPhrase} u {$minute}. minutu.";
            } elseif ($marginAfter === 0) {
                $sentences[] = "{$team} je izjednačio preko {$g['player']}a u {$minute}. minutu.";
            } elseif ($marginAfter < 0) {
                $sentences[] = "{$team} je ublažio zaostatak {$scorerPhrase} u {$minute}. minutu.";
            } elseif ($marginBefore < 0) {
                $sentences[] = "{$team} je preokrenuo rezultat {$scorerPhrase} u {$minute}. minutu.";
            } elseif ($marginBefore === 0) {
                $sentences[] = "{$team} je preuzeo vodstvo {$scorerPhrase} u {$minute}. minutu.";
            } else {
                $sentences[] = "{$team} je povećao prednost {$scorerPhrase} u {$minute}. minutu.";
            }
        }

        return implode(' ', $sentences);
    }

    private static function redCardSentence(?array $detail, string $home, string $away): ?string
    {
        $red = MatchDetail::events($detail)->first(fn ($e) => $e['icon'] === 'red');

        if (! $red) {
            return null;
        }

        $team = $red['side'] === 'home' ? $home : $away;
        $minute = $red['elapsed'].($red['extra'] ? '+'.$red['extra'] : '');

        return "Meč je obilježilo i isključenje igrača {$red['player']} ({$team}) u {$minute}. minutu.";
    }

    private static function halfTimeSentence(?array $detail): ?string
    {
        $ht = $detail['game'] ?? null;
        $homeHt = $ht['homeHTResult'] ?? null;
        $awayHt = $ht['awayHTResult'] ?? null;

        if ($homeHt === null || $awayHt === null) {
            return null;
        }

        return "Na poluvremenu je rezultat bio {$homeHt}:{$awayHt}.";
    }

    private static function statsSentence(?array $detail, string $home, string $away): ?string
    {
        $stats = $detail['statistics'] ?? null;

        if (! $stats || $stats['ballPossessionHome'] === null || $stats['totalShotsHome'] === null) {
            return null;
        }

        $possHome = $stats['ballPossessionHome'];
        $possAway = $stats['ballPossessionAway'];
        $shotsHome = $stats['totalShotsHome'];
        $shotsAway = $stats['totalShotsAway'];
        $shotsHomeWord = Plural::sr($shotsHome, 'udarac', 'udarca', 'udaraca');
        $shotsAwayWord = Plural::sr($shotsAway, 'udarac', 'udarca', 'udaraca');

        return "{$home} je imao {$possHome}% posjeda lopte i uputio {$shotsHome} {$shotsHomeWord} na gol, dok je {$away} imao {$possAway}% posjeda i {$shotsAway} {$shotsAwayWord}.";
    }

    private static function venueSentence(?array $detail): string
    {
        $venue = $detail['venue']['name'] ?? null;

        return $venue ? "Meč je odigran na stadionu {$venue}." : '';
    }

    /** Kickoff + ~2h (a football match's usual full duration), never later than now. */
    private static function estimatedFullTime(Fixture $fixture): Carbon
    {
        $fullTime = $fixture->kickoff_at->copy()->addMinutes(115);

        return $fullTime->isFuture() ? Carbon::now() : $fullTime;
    }
}
