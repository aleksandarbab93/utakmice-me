<?php

namespace App\Support;

use App\Models\Fixture;
use App\Models\PushSubscription;

/**
 * "GOL — Crvena zvezda 2:1 Partizan", to the phones following that match.
 *
 * Called from football:sync-live the moment a score is written — the only
 * place on this site that knows a goal has just happened. Deliberately
 * narrow about what counts as one: a false alarm is worse than a missed
 * goal, since a phone that buzzes for nothing is a phone whose owner turns
 * notifications off and never comes back.
 */
class GoalPush
{
    /**
     * @param  array{0: ?int, 1: ?int}  $before  the score as it was stored before this update
     */
    public static function maybe(Fixture $fixture, array $before): void
    {
        if (! self::isGoal($fixture, $before)) {
            return;
        }

        $subscriptions = PushSubscription::whereHas(
            'fixtures',
            fn ($query) => $query->where('matches.id', $fixture->id)
        )->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $fixture->loadMissing(['homeTeam', 'awayTeam', 'league']);

        WebPush::send($subscriptions, [
            'title' => "GOL! {$fixture->homeTeam->name} {$fixture->home_score}:{$fixture->away_score} {$fixture->awayTeam->name}",
            'body' => trim(($fixture->minute ? $fixture->minute."' · " : '').($fixture->league?->name ?? '')),
            'url' => Nav::match($fixture->id),
            // One notification per match, replaced rather than stacked: three
            // goals in ten minutes should leave one line on the lock screen
            // showing the current score, not three showing the history.
            'tag' => 'mec-'.$fixture->id,
        ]);
    }

    /** @param  array{0: ?int, 1: ?int}  $before */
    private static function isGoal(Fixture $fixture, array $before): bool
    {
        // Not before kickoff, and not after the whistle: a score written onto
        // a finished match is a correction, and nobody wants to be told at
        // midnight that a match they watched ended differently.
        if (! $fixture->isLive()) {
            return false;
        }

        [$wasHome, $wasAway] = $before;
        $home = $fixture->home_score;
        $away = $fixture->away_score;

        if ($home === null || $away === null) {
            return false;
        }

        // The first sight of a match already in progress is not a goal — we
        // learned it was 2:1, we did not watch it become 2:1.
        if ($wasHome === null || $wasAway === null) {
            return false;
        }

        // Up, not merely different. A correction downwards is not a goal.
        return $home > $wasHome || $away > $wasAway;
    }
}
