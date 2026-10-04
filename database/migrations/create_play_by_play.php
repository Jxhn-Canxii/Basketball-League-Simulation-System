<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_by_play', function (Blueprint $table) {
            $table->id();
            $table->string('game_id',200);
            $table->integer('season_id')->default(0);
            $table->string('quarter',2)->default('Q1');
            $table->integer('possession_number')->default(0);
            $table->integer('team_id')->default(0);
            $table->integer('player_id')->default(0);
            $table->integer('secondary_player_id')->default(0);
            $table->integer('game_clock_seconds')->default(0);
            $table->string('event_type',50)->default('play');
            $table->integer('points')->default(0);
            $table->integer('shot_value')->default(0);
            $table->boolean('shot_made')->default(0);
            $table->string('description',255)->default(0);
            $table->string('score_update',10)->default('0-0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_by_play');
    }
};