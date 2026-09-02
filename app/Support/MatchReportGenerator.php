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
    /** Izveštaji se generišu samo za "ligu petice" i Ligu šampiona — ne i za regionalne lige, Ligue 1, Evropsku ligu ili Ligu konferencija. */
    private const ELIGIBLE_LEAGUES = ['Premijer liga', 'Serie A', 'Bundesliga', 'La Liga', 'Liga prvaka'];

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

        if (! in_array($league, self::ELIGIBLE_LEAGUES, true)) {
            return null;
        }

        $detail = null;
        try {
            $detail = $client->gameDetail((int) $fixture->external_id);
        } catch (\Throwable) {
            // detail endpoint failing (rate limit, transient error) shouldn't block a basic report
        }

        $variant = $fixture->id % 3;
        $goals = MatchDetail::events($detail)->filter(fn ($e) => in_array($e['icon'], ['goal', 'og'], true))->values();
        $redCardEvent = MatchDetail::events($detail)->first(fn ($e) => $e['icon'] === 'red');

        $title = self::headline($variant, $home, $away, $hs, $as, $league, $goals, $redCardEvent, $detail);
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

    /**
     * Picks the most compelling real detail from the match to lead the
     * headline with — a hat-trick, a comeback, a late decider, a rout, or a
     * dramatic equalizer — falling back to a plain scoreline when nothing in
     * the event data stands out. Never invents a detail the API didn't give us.
     */
    private static function headline(int $variant, string $home, string $away, int $hs, int $as, string $league, \Illuminate\Support\Collection $goals, ?array $redCard, ?array $detail): string
    {
        $generic = "{$home} - {$away} {$hs}:{$as}";

        if ($goals->isEmpty()) {
            return $generic;
        }

        $winnerSide = $hs > $as ? 'home' : ($as > $hs ? 'away' : null);
        $winner = $winnerSide === 'home' ? $home : ($winnerSide === 'away' ? $away : null);
        $loser = $winnerSide === 'home' ? $away : ($winnerSide === 'away' ? $home : null);

        if ($winner !== null) {
            $scorerCounts = [];
            foreach ($goals as $g) {
                if ($g['icon'] !== 'goal') {
                    continue;
                }
                $key = $g['side'].'|'.$g['player'];
                $scorerCounts[$key] = ($scorerCounts[$key] ?? 0) + 1;
            }

            foreach ($scorerCounts as $key => $count) {
                if ($count < 3) {
                    continue;
                }
                [$side, $player] = explode('|', $key, 2);
                if (($side === 'home' ? $home : $away) !== $winner) {
                    continue;
                }
                $feat = $count >= 4 ? "sa {$count} gola" : 'hat-trikom';

                return "{$player} {$feat} predvodio {$winner} do pobjede protiv {$loser} ({$hs}:{$as})";
            }
        }

        // Chronological score progression, to spot a comeback and the goal that decided the winner.
        $runningHome = 0;
        $runningAway = 0;
        $winnerTrailed = false;
        $decisiveGoal = null;
        $priorLeader = null;

        foreach ($goals as $g) {
            $isOwnGoal = $g['icon'] === 'og';
            $creditSide = $isOwnGoal ? ($g['side'] === 'home' ? 'away' : 'home') : $g['side'];

            if ($creditSide === 'home') {
                $runningHome++;
            } else {
                $runningAway++;
            }

            if ($winnerSide === 'home' && $runningAway > $runningHome) {
                $winnerTrailed = true;
            } elseif ($winnerSide === 'away' && $runningHome > $runningAway) {
                $winnerTrailed = true;
            }

            $currentLeader = $runningHome === $runningAway ? null : ($runningHome > $runningAway ? 'home' : 'away');
            if ($currentLeader !== null && $currentLeader === $winnerSide && $currentLeader !== $priorLeader) {
                $decisiveGoal = $g;
            }
            $priorLeader = $currentLeader;
        }

        if ($winner !== null && $winnerTrailed) {
            return "Preokret: {$winner} stigao do pobjede protiv {$loser} ({$hs}:{$as})";
        }

        if ($winner !== null && $decisiveGoal !== null) {
            $minute = (int) $decisiveGoal['elapsed'];
            if ($minute >= 80 || $decisiveGoal['extra']) {
                $scorer = $decisiveGoal['player'];
                $minuteLabel = $decisiveGoal['elapsed'].($decisiveGoal['extra'] ? '+'.$decisiveGoal['extra'] : '');

                return "{$scorer} u {$minuteLabel}. minutu donio pobjedu {$winner} protiv {$loser} ({$hs}:{$as})";
            }
        }

        if ($winner !== null && abs($hs - $as) >= 3) {
            return "{$winner} razbio {$loser} rezultatom {$hs}:{$as}";
        }

        if ($winnerSide === null) {
            $lastGoal = $goals->last();
            $minute = (int) $lastGoal['elapsed'];
            if ($minute >= 80 || $lastGoal['extra']) {
                $isOwnGoal = $lastGoal['icon'] === 'og';
                $creditSide = $isOwnGoal ? ($lastGoal['side'] === 'home' ? 'away' : 'home') : $lastGoal['side'];
                $team = $creditSide === 'home' ? $home : $away;
                $other = $creditSide === 'home' ? $away : $home;
                $minuteLabel = $lastGoal['elapsed'].($lastGoal['extra'] ? '+'.$lastGoal['extra'] : '');

                return "{$team} u {$minuteLabel}. minutu spasio bod protiv {$other} ({$hs}:{$as})";
            }
        }

        if ($redCard !== null && $winner !== null) {
            $downTeam = $redCard['side'] === 'home' ? $home : $away;
            if ($downTeam === $loser) {
                return "{$winner} slavio protiv {$loser}a koji je igrao sa igračem manje ({$hs}:{$as})";
            }
        }

        return $winnerSide === null
            ? "{$home} i {$away} remizirali {$hs}:{$as}"
            : "{$winner} slavio protiv {$loser} rezultatom {$hs}:{$as}";
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
