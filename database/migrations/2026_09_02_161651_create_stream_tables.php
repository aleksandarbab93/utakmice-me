<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a match can actually be watched, free and in the open — ported from
 * utakmice-rs-master's stream_sources/fixture_streams design.
 *
 * `stream_sources` is hand-kept: one row per YouTube channel known to
 * broadcast a given league. Nothing here comes from an API — which channel
 * carries which competition is knowledge, not data.
 *
 * `fixture_streams` is what the harvest (streams:sync) found: one row per
 * (match, video), pruned once its match is old enough that nobody is
 * looking for the replay any more.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16)->default('youtube');
            $table->string('channel_id', 64);
            $table->string('channel_name');
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->timestamp('polled_at')->nullable();
            $table->timestamps();

            $table->unique(['league_id', 'provider', 'channel_id']);
            $table->index('is_active');
        });

        Schema::create('fixture_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('stream_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 16)->default('youtube');
            $table->string('external_id', 64);
            $table->string('title')->nullable();
            $table->boolean('is_live')->default(false);
            // null = not asked yet; false = the channel forbids embedding.
            $table->boolean('embeddable')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id', 'fixture_id']);
            $table->index(['fixture_id', 'is_live']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixture_streams');
        Schema::dropIfExists('stream_sources');
    }
};
