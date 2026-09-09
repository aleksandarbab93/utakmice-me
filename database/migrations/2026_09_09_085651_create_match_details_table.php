<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SStats' per-match detail — the events, statistics, venue and referee
 * behind "tok meča" and behind a report that names its scorers — used to
 * live only in the cache, refetched whenever it expired.
 *
 * It can't anymore: from a datacenter IP, SStats stops sending any response
 * past ~14.6 KB and never finishes it (confirmed from three unrelated
 * networks — the production host, a Cloudflare Worker and a third-party
 * proxy — while the same request from a home connection returns all 35 KB
 * in half a second). A match detail is exactly the kind of response that
 * crosses that line.
 *
 * So it's stored instead of cached: whatever machine manages to fetch it
 * once writes it down, and every page load after that is served from here,
 * on any network. A finished match's detail never changes, so there is
 * nothing to refresh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->unique()->constrained('matches')->cascadeOnDelete();
            $table->json('payload');
            $table->timestamp('fetched_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_details');
    }
};
