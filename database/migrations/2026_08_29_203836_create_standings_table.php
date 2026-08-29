<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('played');
            $table->unsignedSmallInteger('won');
            $table->unsignedSmallInteger('draw');
            $table->unsignedSmallInteger('lost');
            $table->unsignedSmallInteger('points');
            $table->integer('goal_diff')->default(0);
            $table->timestamps();

            $table->unique(['league_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
