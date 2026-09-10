<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which channel carries the match — "gdje da gledam", which is the second
 * thing anybody asks after the kickoff time.
 *
 * Free text rather than a channels table: the guide names them and we
 * repeat the name. One channel per match, because when a match is on two
 * the sync picks the one more people can watch (see SyncTvChannels).
 *
 * Deliberately absent from what the fixture sync writes, so a value entered
 * by hand — or read from the guide — survives the next sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('tv_channel', 64)->nullable()->after('venue');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('tv_channel');
        });
    }
};
