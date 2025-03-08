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
        Schema::create('all_time_top_stats', function (Blueprint $table) {
            $table->id();
            $table->string('stat_category', 50);  // e.g., 'points', 'rebounds', 'assists', etc.
            $table->integer('player_id');
            $table->string('player_name', 100);   // Stores player name for easy reference
            $table->integer('game_id');
            $table->integer('team_id');
            $table->integer('opponent_id');  // ID of the opposing team
            $table->integer('season_id');
            $table->integer('stat_value');        // Value of the stat (e.g., points scored)
            $table->date('recorded_at');          // Date of the game
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('all_time_top_stats');
    }
};
