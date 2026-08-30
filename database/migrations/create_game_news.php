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
        Schema::create('game_news', function (Blueprint $table) {
            $table->id();
            $table->string('game_id',150);
            $table->unsignedBigInteger('season_id');
            $table->unsignedInteger('round');
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->string('title', 255);
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_news');
    }
};