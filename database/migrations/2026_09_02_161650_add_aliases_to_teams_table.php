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
        Schema::table('teams', function (Blueprint $table) {
            // Other names a club is known by — a Cyrillic feed's "Смедерево
            // 1924" or a rebrand our source hasn't caught up with. What
            // Streams\StreamMatcher needs to match a broadcast title that
            // doesn't spell a club exactly the way we do.
            $table->string('aliases')->nullable()->after('short_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('aliases');
        });
    }
};
