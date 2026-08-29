<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('external_source');
            $table->string('external_id');
            $table->dateTime('kickoff_at');
            $table->enum('status', ['scheduled', 'live', 'finished'])->default('scheduled');
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->string('minute')->nullable();
            $table->string('matchday')->nullable();
            $table->string('venue')->nullable();
            $table->timestamps();

            $table->unique(['external_source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
