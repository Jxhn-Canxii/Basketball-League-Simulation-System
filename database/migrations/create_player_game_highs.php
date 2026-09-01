<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_game_highs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->default(0);
            $table->float('minutes')->default(0);
            $table->integer('points')->default(0);
            $table->integer('rebounds')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('steals')->default(0);
            $table->integer('blocks')->default(0);
            $table->integer('turnovers')->default(0);
            $table->integer('fouls')->default(0);
            $table->integer('field_goal_attempts')->default(0);
            $table->integer('field_goals_made')->default(0);
            $table->integer('three_point_attempts')->default(0);
            $table->integer('three_pointers_made')->default(0);
            $table->integer('free_throw_attempts')->default(0);
            $table->integer('free_throws_made')->default(0);
            $table->integer('two_point_attempts')->default(0);
            $table->integer('two_pointers_made')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_game_highs');
    }
};