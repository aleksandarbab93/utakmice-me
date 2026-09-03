<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who to tell when a goal goes in.
 *
 * There are no accounts on this site, so a subscriber is a BROWSER: the
 * endpoint the push service handed it is the whole identity. Which matches
 * a browser wants is a list, not a flag — the browser owns it (same
 * localStorage favorites list this site already keeps) and posts the whole
 * of it on every change; the server just replaces what it holds, so the two
 * can never drift apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();

            // Long: push endpoints run past 200 characters with no spec
            // ceiling. Hashed for the unique index since MySQL/SQLite can't
            // usefully index the whole thing, and the endpoint is what a
            // subscriber is looked up by.
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();

            // The two halves of the browser's own key, used to encrypt the
            // payload so the push service only ever carries an opaque blob.
            $table->string('p256dh');
            $table->string('auth');

            $table->string('user_agent', 255)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('push_subscription_fixture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained('matches')->cascadeOnDelete();

            $table->unique(['push_subscription_id', 'fixture_id'], 'push_sub_fixture_unique');
            $table->index('fixture_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscription_fixture');
        Schema::dropIfExists('push_subscriptions');
    }
};
