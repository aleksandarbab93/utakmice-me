<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['izvestaj', 'vest']);
            $table->string('sport');
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('lead')->nullable();
            $table->json('body')->nullable();
            $table->foreignId('league_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fixture_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->dateTime('published_at');
            $table->timestamps();

            $table->index(['sport', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
