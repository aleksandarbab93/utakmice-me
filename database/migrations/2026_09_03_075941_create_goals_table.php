<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per goal, credited to the scoring team (an own goal credits
     * the opponent) — built from the same event data MatchReportGenerator
     * already parses for the match report, at no extra API cost. Powers
     * the "Najbolji strijelci" list on the league page.
     */
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('player_name');
            $table->unsignedSmallInteger('minute')->nullable();
            $table->boolean('is_penalty')->default(false);
            $table->boolean('is_own_goal')->default(false);
            $table->timestamps();

            $table->index(['league_id', 'player_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
