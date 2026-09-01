<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedule_view_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('game_id');
            $table->integer('game_number');
            $table->string('round');
            $table->integer('season_id');
            $table->integer('conference_id');
            $table->string('series_id');
            $table->integer('series_number');
            $table->integer('home_id');
            $table->integer('home_score');
            $table->integer('away_id');
            $table->integer('away_score');
            $table->integer('winner_id');
            $table->integer('status'); 
            $table->string('created_at'); 
            $table->string('updated_at'); 
            $table->string('game_number_formatted'); 
            $table->string('series_id_number'); 
            $table->string('home_team_name'); 
            $table->string('away_team_name'); 
            $table->string('home_primary_color'); 
            $table->string('away_primary_color'); 
            $table->string('home_secondary_color'); 
            $table->string('away_secondary_color'); 
            $table->string('home_team_city'); 
            $table->string('away_team_city'); 
            $table->string('season_name'); 
            $table->string('league_name'); 
            $table->integer('league_type'); 
            $table->string('winning_name'); 
            $table->string('winning_city'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_view_snapshots');
    }
};
