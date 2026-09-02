<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The two channels in our tracked leagues confirmed, by hand, to actually
 * broadcast for free on YouTube — verified the same way utakmice-rs-master's
 * seed does it: each channel's own feed read and checked against real
 * fixtures before being trusted.
 *
 * Every other league this site tracks is rights-locked (the top-5 European
 * leagues, the UEFA club competitions) or was checked and found to have no
 * free source (HNL, Premijer liga BiH, Prva crnogorska liga, 1. SNL — see
 * utakmice-rs-master's own seed for the per-league detail). None of those
 * are guessed at here; a wrong channel id would risk showing the wrong
 * match entirely.
 *
 * Runs a no-op if leagues haven't been synced yet (football:sync creates
 * them) — the same "skip rather than guess" the reference project's seed
 * follows.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sources = [
            // Mostly clips this season, occasionally a full match — the
            // channel's titles carry the round and both clubs in full.
            ['superliga-srbije', 'UCd8Axjg_lLpFxTSdX_kIGDQ', 'Mozzart Bet Super liga Srbije', 'Najčešće isečci, povremeno ceo meč'],

            // Full matches, a day or two after the round. Cyrillic titles —
            // teams.aliases carries the Latin spellings StreamMatcher needs.
            ['prva-liga-makedonije', 'UCp9pwO69gUHWs_y8WB_xMdQ', 'FFM Macedonia', 'Celi mečevi Prve MFL, ćirilični naslovi'],
        ];

        foreach ($sources as [$slug, $channelId, $name, $note]) {
            $leagueId = DB::table('leagues')->where('slug', $slug)->value('id');

            if (! $leagueId) {
                continue;
            }

            DB::table('stream_sources')->updateOrInsert(
                ['league_id' => $leagueId, 'provider' => 'youtube', 'channel_id' => $channelId],
                ['channel_name' => $name, 'note' => $note, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        $this->alias();
    }

    /**
     * The Macedonian federation writes Cyrillic titles: "Скендија" normalizes
     * to "skendija" while our club is stored as "Shkendija" — a different
     * word, not a different spelling, which teams.aliases exists to bridge.
     */
    private function alias(): void
    {
        $leagueId = DB::table('leagues')->where('slug', 'prva-liga-makedonije')->value('id');

        if (! $leagueId) {
            return;
        }

        $teamIds = DB::table('matches')->where('league_id', $leagueId)->pluck('home_team_id')
            ->merge(DB::table('matches')->where('league_id', $leagueId)->pluck('away_team_id'))
            ->unique();

        $aliases = [
            'Shkëndija Haraçinë' => 'Škendija Aračinovo',
            'Bashkimi Kumanovo' => 'Baškimi',
            'Aresimi' => 'Arsimi',
        ];

        foreach ($aliases as $name => $alias) {
            $team = DB::table('teams')->whereIn('id', $teamIds)->where('name', $name)->first();

            if (! $team) {
                continue;
            }

            $existing = array_values(array_filter(array_map('trim', explode(',', (string) $team->aliases))));

            if (in_array($alias, $existing, true)) {
                continue;
            }

            $existing[] = $alias;

            DB::table('teams')->where('id', $team->id)
                ->update(['aliases' => implode(', ', $existing), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('stream_sources')->whereIn('channel_id', [
            'UCd8Axjg_lLpFxTSdX_kIGDQ',
            'UCp9pwO69gUHWs_y8WB_xMdQ',
        ])->delete();
    }
};
