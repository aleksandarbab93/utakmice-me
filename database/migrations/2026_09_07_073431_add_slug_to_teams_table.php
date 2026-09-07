<?php

use App\Models\Team;
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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('short_name');
        });

        // Backfill every existing team, oldest first so a name collision
        // between two clubs consistently favors whichever was synced first.
        Team::with('league')->orderBy('id')->each(function (Team $team) {
            $team->update(['slug' => Team::uniqueSlug($team->name, $team->league?->name, $team->external_id)]);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
