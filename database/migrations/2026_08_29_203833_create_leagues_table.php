<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('sport', ['fudbal', 'kosarka']);
            $table->string('external_source');
            $table->string('external_id');
            $table->timestamps();

            $table->unique(['external_source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
