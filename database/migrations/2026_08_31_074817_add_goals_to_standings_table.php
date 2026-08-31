<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('standings', function (Blueprint $table) {
            $table->unsignedSmallInteger('goals_for')->default(0)->after('lost');
            $table->unsignedSmallInteger('goals_against')->default(0)->after('goals_for');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('standings', function (Blueprint $table) {
            $table->dropColumn(['goals_for', 'goals_against']);
        });
    }
};
