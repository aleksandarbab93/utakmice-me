<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The matches table had one index — the unique pair the sync upserts on —
 * and every page on the site reads it by date, by league, or by status.
 * Each of those was a full scan of four thousand rows, several times per
 * page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // The Rezultati page and the home page's Danas/Sutra buckets:
            // a day's window across a handful of leagues, in kickoff order.
            $table->index(['league_id', 'kickoff_at']);

            // The same window without a league filter, plus every
            // "finished, most recent first" lookup behind the reports.
            $table->index(['kickoff_at', 'status']);

            // The live sync and the uživo widget ask only this.
            $table->index('status');

            // A club's own fixtures — the form guide asks for both sides.
            $table->index('home_team_id');
            $table->index('away_team_id');
        });

        Schema::table('posts', function (Blueprint $table) {
            // The Vijesti feed and its per-league filter chips.
            $table->index(['type', 'published_at']);
            $table->index('league_id');
        });

        Schema::table('standings', function (Blueprint $table) {
            // Every table on the site is read exactly this way.
            $table->index(['league_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex(['league_id', 'kickoff_at']);
            $table->dropIndex(['kickoff_at', 'status']);
            $table->dropIndex(['status']);
            $table->dropIndex(['home_team_id']);
            $table->dropIndex(['away_team_id']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['type', 'published_at']);
            $table->dropIndex(['league_id']);
        });

        Schema::table('standings', function (Blueprint $table) {
            $table->dropIndex(['league_id', 'position']);
        });
    }
};
