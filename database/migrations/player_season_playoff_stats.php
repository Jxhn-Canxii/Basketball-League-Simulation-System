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
        Schema::create('player_season_playoff_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('season_id');
            $table->string('role');

            // Basic Per-Game Averages
            $table->decimal('avg_minutes_per_game', 5, 2)->default(0);
            $table->decimal('avg_points_per_game', 5, 2)->default(0);
            $table->decimal('avg_rebounds_per_game', 5, 2)->default(0);
            $table->decimal('avg_assists_per_game', 5, 2)->default(0);
            $table->decimal('avg_steals_per_game', 5, 2)->default(0);
            $table->decimal('avg_blocks_per_game', 5, 2)->default(0);
            $table->decimal('avg_turnovers_per_game', 5, 2)->default(0);
            $table->decimal('avg_fouls_per_game', 5, 2)->default(0);

            // Shooting Efficiency
            $table->integer('total_field_goals_made')->default(0);
            $table->integer('total_field_goal_attempts')->default(0);
            $table->integer('total_two_pointers_made')->default(0);
            $table->integer('total_two_point_attempts')->default(0);
            $table->integer('total_three_pointers_made')->default(0);
            $table->integer('total_three_point_attempts')->default(0);
            $table->integer('total_free_throws_made')->default(0);
            $table->integer('total_free_throw_attempts')->default(0);

            // Totals
            $table->integer('total_points')->default(0);
            $table->integer('total_rebounds')->default(0);
            $table->integer('total_assists')->default(0);
            $table->integer('total_steals')->default(0);
            $table->integer('total_blocks')->default(0);
            $table->integer('total_turnovers')->default(0);
            $table->integer('total_fouls')->default(0);
            $table->integer('total_minutes_played')->default(0);
            $table->integer('total_games_played')->default(0);
            $table->integer('total_games')->default(0);

            // Leader per game counter
            $table->integer('bpg_game_leader')->default(0);
            $table->integer('points_game_leader')->default(0);
            $table->integer('rebounds_game_leader')->default(0);
            $table->integer('assists_game_leader')->default(0);
            $table->integer('steals_game_leader')->default(0);
            $table->integer('blocks_game_leader')->default(0);

            // Advanced Metrics (Modified to DECIMAL(6,3) for 'eff')
            $table->decimal('per', 5, 3)->storedAs(DB::raw('CASE WHEN total_minutes_played = 0 THEN 0 ELSE (total_points + total_rebounds + total_assists + total_steals + total_blocks - (total_field_goal_attempts - total_field_goals_made) - total_turnovers) / total_minutes_played END'));
            $table->decimal('ts_percent', 5, 3)->storedAs(DB::raw('CASE WHEN (total_field_goal_attempts + (0.44 * total_free_throw_attempts)) = 0 THEN 0 ELSE total_points / (2 * (total_field_goal_attempts + (0.44 * total_free_throw_attempts))) END'));
            $table->decimal('eff', 6, 3)->storedAs(DB::raw('CASE WHEN (total_field_goal_attempts + total_free_throw_attempts + total_turnovers) = 0 THEN 0 ELSE (total_points + total_rebounds + total_assists + total_steals + total_blocks - (total_field_goal_attempts + total_free_throw_attempts + total_turnovers)) END'));

            // Added Stored Columns for Shooting Percentages
            $table->decimal('field_goal_percentage', 5, 2)->storedAs(DB::raw('CASE WHEN total_field_goal_attempts = 0 THEN 0 ELSE (total_field_goals_made / total_field_goal_attempts) * 100 END'));
            $table->decimal('two_point_percentage', 5, 2)->storedAs(DB::raw('CASE WHEN total_two_point_attempts = 0 THEN 0 ELSE (total_two_pointers_made / total_two_point_attempts) * 100 END'));
            $table->decimal('three_point_percentage', 5, 2)->storedAs(DB::raw('CASE WHEN total_three_point_attempts = 0 THEN 0 ELSE (total_three_pointers_made / total_three_point_attempts) * 100 END'));
            $table->decimal('free_throw_percentage', 5, 2)->storedAs(DB::raw('CASE WHEN total_free_throw_attempts = 0 THEN 0 ELSE (total_free_throws_made / total_free_throw_attempts) * 100 END'));

            $table->integer('performance_points')->default(100);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_season_playoff_stats');
    }
};
