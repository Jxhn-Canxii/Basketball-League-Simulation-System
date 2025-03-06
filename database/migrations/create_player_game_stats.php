<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_game_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->default(0);
            $table->string('game_id');
            $table->foreignId('player_id')->default(0);
            $table->foreignId('team_id')->default(0);
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

        // Add computed columns using raw SQL since Laravel doesn't support GENERATED columns directly
        DB::statement('
            ALTER TABLE player_game_stats ADD per FLOAT GENERATED ALWAYS AS (
                (points + rebounds + assists + steals + blocks - (field_goal_attempts - field_goals_made) - turnovers) 
                / NULLIF(minutes, 0)
            ) STORED
        ');

        DB::statement('
            ALTER TABLE player_game_stats ADD ts_percent FLOAT GENERATED ALWAYS AS (
                points / NULLIF(2 * (field_goal_attempts + (0.44 * free_throw_attempts)), 0)
            ) STORED
        ');

        DB::statement('
            ALTER TABLE player_game_stats ADD eff FLOAT GENERATED ALWAYS AS (
                (points + rebounds + assists + steals + blocks - (field_goal_attempts + free_throw_attempts + turnovers))
            ) STORED
        ');

        DB::statement('
            ALTER TABLE player_game_stats ADD field_goal_percentage FLOAT GENERATED ALWAYS AS (
                CASE
                    WHEN field_goal_attempts = 0 THEN 0
                    ELSE (field_goals_made / field_goal_attempts) * 100
                END
            ) STORED
        ');

        DB::statement('
            ALTER TABLE player_game_stats ADD three_point_percentage FLOAT GENERATED ALWAYS AS (
                CASE
                    WHEN three_point_attempts = 0 THEN 0
                    ELSE (three_pointers_made / three_point_attempts) * 100
                END
            ) STORED
        ');

        DB::statement('
            ALTER TABLE player_game_stats ADD free_throw_percentage FLOAT GENERATED ALWAYS AS (
                CASE
                    WHEN free_throw_attempts = 0 THEN 0
                    ELSE (free_throws_made / free_throw_attempts) * 100
                END
            ) STORED
        ');

        DB::statement('
            ALTER TABLE player_game_stats ADD two_point_percentage FLOAT GENERATED ALWAYS AS (
                CASE
                    WHEN two_point_attempts = 0 THEN 0
                    ELSE (two_pointers_made / two_point_attempts) * 100
                END
            ) STORED
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('player_game_stats');
    }
};
