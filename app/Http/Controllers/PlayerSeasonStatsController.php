<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\HelperController;
use Inertia\Inertia;

class PlayerSeasonStatsController extends Controller
{
    protected $helper;

    public function __construct(){
        $this->helper = new HelperController();
    }
    /**
     * Store aggregated stats of a player's performance for a season in the player_season_stats table.
     * If 'is_last' is true, update the latest season's status to 9.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public static function storeAllPlayerSeasonStats()
    {
        // Get the latest season ID or set it to 12 if it doesn’t exist
        $latestSeasonId = get_current_season_id() ?? 1;

        // Get all players from the team
        $players = DB::table('players as p')
            ->join('player_game_stats as pgs', 'p.id', '=', 'pgs.player_id')
            ->where('p.team_id', '>', 0)
            ->where('p.is_active', true)
            ->select('p.*')
            ->distinct()
            ->get();


        foreach ($players as $player) {
            // Check if the player has stats in player_game_stats for the latest season
            $hasStats = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->exists();

            // Query to count the total games played for a team in a given season
            $gamesPlayedCount = self::totalRegularSeasonGames($latestSeasonId, $player->team_id);

            if ($hasStats) {
                // Calculate stats if stats are found for the player
                $playerStats = DB::table('player_game_stats')
                    ->where('player_id', $player->id)
                    ->where('season_id', $latestSeasonId)
                    ->select(
                        'player_id',
                        'team_id',
                        DB::raw('COUNT(CASE WHEN minutes > 0 THEN 1 END) as total_games_played'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN points ELSE 0 END) as total_points'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN rebounds ELSE 0 END) as total_rebounds'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN assists ELSE 0 END) as total_assists'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN steals ELSE 0 END) as total_steals'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN blocks ELSE 0 END) as total_blocks'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN turnovers ELSE 0 END) as total_turnovers'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN fouls ELSE 0 END) as total_fouls'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN minutes ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_minutes_per_game'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN points ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_points_per_game'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN rebounds ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_rebounds_per_game'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN assists ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_assists_per_game'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN steals ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_steals_per_game'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN blocks ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_blocks_per_game'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN turnovers ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_turnovers_per_game'),
                        DB::raw('SUM(CASE WHEN minutes > 0 THEN fouls ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_fouls_per_game')
                    )
                    ->groupBy('player_id', 'player_game_stats.team_id')
                    ->first();
            } else {
                // Set all stats to 0 if no stats are found
                $playerStats = (object) [
                    'player_id' => $player->id,
                    'total_games' => $gamesPlayedCount,
                    'total_games_played' => 0,
                    'total_points' => 0,
                    'total_rebounds' => 0,
                    'total_assists' => 0,
                    'total_steals' => 0,
                    'total_blocks' => 0,
                    'total_turnovers' => 0,
                    'total_fouls' => 0,
                    'avg_minutes_per_game' => 0,
                    'avg_points_per_game' => 0,
                    'avg_rebounds_per_game' => 0,
                    'avg_assists_per_game' => 0,
                    'avg_steals_per_game' => 0,
                    'avg_blocks_per_game' => 0,
                    'avg_turnovers_per_game' => 0,
                    'avg_fouls_per_game' => 0,
                ];
            }

            // Get the player's role for the specified season
            $playerRating = DB::table('player_ratings')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->first();

            // Get game stats (dummy variables used for the example)
            $twoPointAttempts = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->sum('two_point_attempts');

            $threePointAttempts = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->sum('three_point_attempts');

            $twoPointMade = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->sum('two_point_made');

            $threePointMade = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->sum('three_point_made');

            $freeThrowAttempts = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->sum('free_throw_attempts');

            $freeThrowsMade = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->sum('free_throws_made');

            // Insert or update the player's game stats into the player_game_stats table
            DB::table('player_game_stats')->updateOrInsert(
                [
                    'player_id' => $player->id,
                    'season_id' => $latestSeasonId,
                    'team_id' => $player->team_id,
                ],
                [
                    // Insert actual shooting stats
                    'points' => $playerStats->total_points,
                    'rebounds' => $playerStats->total_rebounds,
                    'assists' => $playerStats->total_assists,
                    'steals' => $playerStats->total_steals,
                    'blocks' => $playerStats->total_blocks,
                    'turnovers' => $playerStats->total_turnovers,
                    'fouls' => $playerStats->total_fouls,
                    'minutes' => $playerStats->avg_minutes_per_game, // Average minutes per game

                    // Insert actual shooting stats:
                    'field_goal_attempts' => $twoPointAttempts + $threePointAttempts,
                    'field_goals_made' => $twoPointMade + $threePointMade,
                    'two_point_attempts' => $threePointAttempts,
                    'two_pointers_made' => $threePointMade,
                    'three_point_attempts' => $threePointAttempts,
                    'three_pointers_made' => $threePointMade,
                    'free_throw_attempts' => $freeThrowAttempts,
                    'free_throws_made' => $freeThrowsMade,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Now store/update the player's season stats
            DB::table('player_season_stats')->updateOrInsert(
                [
                    'player_id' => $player->id,
                    'season_id' => $latestSeasonId,
                ],
                [
                    'team_id' => $player->team_id,
                    'role' => $playerRating->role ?? $player->role,  // Role from player_ratings or default
                    'avg_minutes_per_game' => $playerStats->avg_minutes_per_game,
                    'avg_points_per_game' => $playerStats->avg_points_per_game,
                    'avg_rebounds_per_game' => $playerStats->avg_rebounds_per_game,
                    'avg_assists_per_game' => $playerStats->avg_assists_per_game,
                    'avg_steals_per_game' => $playerStats->avg_steals_per_game,
                    'avg_blocks_per_game' => $playerStats->avg_blocks_per_game,
                    'avg_turnovers_per_game' => $playerStats->avg_turnovers_per_game,
                    'avg_fouls_per_game' => $playerStats->avg_fouls_per_game,
                    'total_games' => $gamesPlayedCount,
                    'total_games_played' => $playerStats->total_games_played,  // Add total_games_played here
                    'total_points' => $playerStats->total_points,
                    'total_rebounds' => $playerStats->total_rebounds,
                    'total_assists' => $playerStats->total_assists,
                    'total_steals' => $playerStats->total_steals,
                    'total_blocks' => $playerStats->total_blocks,
                    'total_turnovers' => $playerStats->total_turnovers,
                    'total_fouls' => $playerStats->total_fouls,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json(['message' => 'Player season stats stored successfully.']);
    }
    /**
     * Store aggregated stats of a player's performance for a season in the player_season_stats table.
     * If 'is_last' is true, update the latest season's status to 9.
     *
     * @param int $teamId
     * @param int $playerId
     * @return \Illuminate\Http\JsonResponse
     */
    public static function storePlayerSeasonStats($teamId, $playerId)
    {
        try {
            // Get the latest season ID or default to 1 if none exists
            $latestSeasonId = get_current_season_id() ?? 1;

            // Fetch the specified player from the team
            $player = DB::table('players')
                ->where('team_id', $teamId)
                ->where('id', $playerId)
                ->where('is_active', true)
                ->first();

            if (!$player) {
                return response()->json(['error' => 'Player not found or inactive'], 404);
            }

            // Query to count the total games played for a team in a given season
            $gamesPlayedCount = self::totalRegularSeasonGames($latestSeasonId, $teamId);

            // Check if the player has stats in the latest season
            $hasStats = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('team_id', $teamId)
                ->where('is_playoff', 0)
                ->where('season_id', $latestSeasonId)
                ->exists();

            if ($hasStats) {
                // Calculate the player's aggregated stats for the latest season
                $playerStats = DB::table('player_game_stats')
                    ->where('player_id', $player->id)
                    ->where('team_id', $teamId)
                    ->where('minutes','>', 0)
                    ->where('is_playoff', 0)
                    ->where('season_id', $latestSeasonId)
                    ->select(
                        'player_id',
                        'team_id',
                        DB::raw('COUNT(id) as total_games_played'),
                        DB::raw('SUM(minutes) as total_minutes_played'),
                        DB::raw('SUM(points) as total_points'),
                        DB::raw('SUM(rebounds) as total_rebounds'),
                        DB::raw('SUM(assists) as total_assists'),
                        DB::raw('SUM(steals) as total_steals'),
                        DB::raw('SUM(blocks) as total_blocks'),
                        DB::raw('SUM(turnovers) as total_turnovers'),
                        DB::raw('SUM(fouls) as total_fouls'),
                        DB::raw('SUM(field_goals_made) as total_field_goals_made'),
                        DB::raw('SUM(field_goal_attempts) as total_field_goal_attempts'),
                        DB::raw('SUM(two_pointers_made) as total_two_pointers_made'),
                        DB::raw('SUM(two_point_attempts) as total_two_point_attempts'),
                        DB::raw('SUM(three_pointers_made) as total_three_pointers_made'),
                        DB::raw('SUM(three_point_attempts) as total_three_point_attempts'),
                        DB::raw('SUM(free_throws_made) as total_free_throws_made'),
                        DB::raw('SUM(free_throw_attempts) as total_free_throw_attempts'),
                        DB::raw('AVG(minutes) as avg_minutes_per_game'),
                        DB::raw('AVG(points) as avg_points_per_game'),
                        DB::raw('AVG(rebounds) as avg_rebounds_per_game'),
                        DB::raw('AVG(assists) as avg_assists_per_game'),
                        DB::raw('AVG(steals) as avg_steals_per_game'),
                        DB::raw('AVG(blocks) as avg_blocks_per_game'),
                        DB::raw('AVG(turnovers) as avg_turnovers_per_game'),
                        DB::raw('AVG(fouls) as avg_fouls_per_game')
                    )
                    ->groupBy('player_id', 'team_id')
                    ->first();
            } else {
                // Set default stats if no game stats exist
                $playerStats = (object) [
                    'player_id' => $player->id,
                    'total_games' => $gamesPlayedCount,
                    'total_games_played' => 0,
                    'total_minutes_played' => 0,
                    'total_points' => 0,
                    'total_rebounds' => 0,
                    'total_assists' => 0,
                    'total_steals' => 0,
                    'total_blocks' => 0,
                    'total_turnovers' => 0,
                    'total_fouls' => 0,
                    'total_field_goals_made' => 0,
                    'total_field_goal_attempts' => 0,
                    'total_two_pointers_made' => 0,
                    'total_two_point_attempts' => 0,
                    'total_three_pointers_made' => 0,
                    'total_three_point_attempts' => 0,
                    'total_free_throws_made' => 0,
                    'total_free_throw_attempts' => 0,
                    'avg_minutes_per_game' => 0,
                    'avg_points_per_game' => 0,
                    'avg_rebounds_per_game' => 0,
                    'avg_assists_per_game' => 0,
                    'avg_steals_per_game' => 0,
                    'avg_blocks_per_game' => 0,
                    'avg_turnovers_per_game' => 0,
                    'avg_fouls_per_game' => 0,
                ];
            }

            // Fetch the player's role and position for the specified season
            $playerRating = DB::table('player_ratings')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->first();

            // Insert or update the player's season stats in the player_season_stats table
            DB::table('player_season_stats')->updateOrInsert(
                [
                    'player_id' => $player->id,
                    'team_id' => $player->team_id,
                    'season_id' => $latestSeasonId,
                ],
                [
                    'team_id' => $player->team_id,
                    'role' => $playerRating->role ?? $player->role, // Role from player_ratings or default
                    'avg_minutes_per_game' => $playerStats->avg_minutes_per_game,
                    'avg_points_per_game' => $playerStats->avg_points_per_game,
                    'avg_rebounds_per_game' => $playerStats->avg_rebounds_per_game,
                    'avg_assists_per_game' => $playerStats->avg_assists_per_game,
                    'avg_steals_per_game' => $playerStats->avg_steals_per_game,
                    'avg_blocks_per_game' => $playerStats->avg_blocks_per_game,
                    'avg_turnovers_per_game' => $playerStats->avg_turnovers_per_game,
                    'avg_fouls_per_game' => $playerStats->avg_fouls_per_game,
                    'total_games' => $gamesPlayedCount,
                    'total_games_played' => $playerStats->total_games_played,
                    'total_minutes_played' => $playerStats->total_minutes_played,
                    'total_points' => $playerStats->total_points,
                    'total_rebounds' => $playerStats->total_rebounds,
                    'total_assists' => $playerStats->total_assists,
                    'total_steals' => $playerStats->total_steals,
                    'total_blocks' => $playerStats->total_blocks,
                    'total_turnovers' => $playerStats->total_turnovers,
                    'total_fouls' => $playerStats->total_fouls,
                    'total_field_goals_made' => $playerStats->total_field_goals_made,
                    'total_field_goal_attempts' => $playerStats->total_field_goal_attempts,
                    'total_two_pointers_made' => $playerStats->total_two_pointers_made,
                    'total_two_point_attempts' => $playerStats->total_two_point_attempts,
                    'total_three_pointers_made' => $playerStats->total_three_pointers_made,
                    'total_three_point_attempts' => $playerStats->total_three_point_attempts,
                    'total_free_throws_made' => $playerStats->total_free_throws_made,
                    'total_free_throw_attempts' => $playerStats->total_free_throw_attempts,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            return response()->json(['message' => 'Player season stats stored successfully.']);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    public static function storePlayerSeasonPlayoffStats($teamId, $playerId)
    {
        try {
            // Get the latest season ID or default to 1 if none exists
            $latestSeasonId = get_current_season_id() ?? 1;

            // Fetch the specified player from the team
            $player = DB::table('players')
                ->where('team_id', $teamId)
                ->where('id', $playerId)
                ->where('is_active', true)
                ->first();

            if (!$player) {
                return response()->json(['error' => 'Player not found or inactive'], 404);
            }

            // Query to count the total games played for a team in a given season
            // $gamesPlayedCount = self::totalRegularSeasonGames($latestSeasonId, $teamId);

            // Check if the player has stats in the latest season
            $hasStats = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('team_id', $teamId)
                ->where('is_playoff','>', 0)
                ->where('season_id', $latestSeasonId)
                ->exists();

            if ($hasStats) {
                // Calculate the player's aggregated stats for the latest season
                $playerStats = DB::table('player_game_stats')
                    ->where('player_id', $player->id)
                    ->where('team_id', $teamId)
                    ->where('minutes','>', 0)
                     ->where('is_playoff','>', 0)
                    ->where('season_id', $latestSeasonId)
                    ->select(
                        'player_id',
                        'team_id',
                        DB::raw('COUNT(id) as total_games_played'),
                        DB::raw('SUM(minutes) as total_minutes_played'),
                        DB::raw('SUM(points) as total_points'),
                        DB::raw('SUM(rebounds) as total_rebounds'),
                        DB::raw('SUM(assists) as total_assists'),
                        DB::raw('SUM(steals) as total_steals'),
                        DB::raw('SUM(blocks) as total_blocks'),
                        DB::raw('SUM(turnovers) as total_turnovers'),
                        DB::raw('SUM(fouls) as total_fouls'),
                        DB::raw('SUM(field_goals_made) as total_field_goals_made'),
                        DB::raw('SUM(field_goal_attempts) as total_field_goal_attempts'),
                        DB::raw('SUM(two_pointers_made) as total_two_pointers_made'),
                        DB::raw('SUM(two_point_attempts) as total_two_point_attempts'),
                        DB::raw('SUM(three_pointers_made) as total_three_pointers_made'),
                        DB::raw('SUM(three_point_attempts) as total_three_point_attempts'),
                        DB::raw('SUM(free_throws_made) as total_free_throws_made'),
                        DB::raw('SUM(free_throw_attempts) as total_free_throw_attempts'),
                        DB::raw('AVG(minutes) as avg_minutes_per_game'),
                        DB::raw('AVG(points) as avg_points_per_game'),
                        DB::raw('AVG(rebounds) as avg_rebounds_per_game'),
                        DB::raw('AVG(assists) as avg_assists_per_game'),
                        DB::raw('AVG(steals) as avg_steals_per_game'),
                        DB::raw('AVG(blocks) as avg_blocks_per_game'),
                        DB::raw('AVG(turnovers) as avg_turnovers_per_game'),
                        DB::raw('AVG(fouls) as avg_fouls_per_game')
                    )
                    ->groupBy('player_id', 'team_id')
                    ->first();
            } else {
                // Set default stats if no game stats exist
                $playerStats = (object) [
                    'player_id' => $player->id,
                    'total_games' => 0,
                    'total_games_played' => 0,
                    'total_minutes_played' => 0,
                    'total_points' => 0,
                    'total_rebounds' => 0,
                    'total_assists' => 0,
                    'total_steals' => 0,
                    'total_blocks' => 0,
                    'total_turnovers' => 0,
                    'total_fouls' => 0,
                    'total_field_goals_made' => 0,
                    'total_field_goal_attempts' => 0,
                    'total_two_pointers_made' => 0,
                    'total_two_point_attempts' => 0,
                    'total_three_pointers_made' => 0,
                    'total_three_point_attempts' => 0,
                    'total_free_throws_made' => 0,
                    'total_free_throw_attempts' => 0,
                    'avg_minutes_per_game' => 0,
                    'avg_points_per_game' => 0,
                    'avg_rebounds_per_game' => 0,
                    'avg_assists_per_game' => 0,
                    'avg_steals_per_game' => 0,
                    'avg_blocks_per_game' => 0,
                    'avg_turnovers_per_game' => 0,
                    'avg_fouls_per_game' => 0,
                ];
            }

            // Fetch the player's role and position for the specified season
            $playerRating = DB::table('player_ratings')
                ->where('player_id', $player->id)
                ->where('season_id', $latestSeasonId)
                ->first();

            // Insert or update the player's season stats in the player_season_stats table
            DB::table('player_season_playoff_stats')->updateOrInsert(
                [
                    'player_id' => $player->id,
                    'team_id' => $player->team_id,
                    'season_id' => $latestSeasonId,
                ],
                [
                    'team_id' => $player->team_id,
                    'role' => $playerRating->role ?? $player->role, // Role from player_ratings or default
                    'avg_minutes_per_game' => $playerStats->avg_minutes_per_game,
                    'avg_points_per_game' => $playerStats->avg_points_per_game,
                    'avg_rebounds_per_game' => $playerStats->avg_rebounds_per_game,
                    'avg_assists_per_game' => $playerStats->avg_assists_per_game,
                    'avg_steals_per_game' => $playerStats->avg_steals_per_game,
                    'avg_blocks_per_game' => $playerStats->avg_blocks_per_game,
                    'avg_turnovers_per_game' => $playerStats->avg_turnovers_per_game,
                    'avg_fouls_per_game' => $playerStats->avg_fouls_per_game,
                    'total_games' => $playerStats->total_games_played,
                    'total_games_played' => $playerStats->total_games_played,
                    'total_minutes_played' => $playerStats->total_minutes_played,
                    'total_points' => $playerStats->total_points,
                    'total_rebounds' => $playerStats->total_rebounds,
                    'total_assists' => $playerStats->total_assists,
                    'total_steals' => $playerStats->total_steals,
                    'total_blocks' => $playerStats->total_blocks,
                    'total_turnovers' => $playerStats->total_turnovers,
                    'total_fouls' => $playerStats->total_fouls,
                    'total_field_goals_made' => $playerStats->total_field_goals_made,
                    'total_field_goal_attempts' => $playerStats->total_field_goal_attempts,
                    'total_two_pointers_made' => $playerStats->total_two_pointers_made,
                    'total_two_point_attempts' => $playerStats->total_two_point_attempts,
                    'total_three_pointers_made' => $playerStats->total_three_pointers_made,
                    'total_three_point_attempts' => $playerStats->total_three_point_attempts,
                    'total_free_throws_made' => $playerStats->total_free_throws_made,
                    'total_free_throw_attempts' => $playerStats->total_free_throw_attempts,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            return response()->json(['message' => 'Player season stats stored successfully.']);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            //Log::error('Error in storeplayerseasonstats: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    public static function storePlayerNextSeasonStats($teamId, $playerId)
    {
        try {
            // Get the latest season ID or set it to 1 if none exists
            $latestSeasonId = get_current_season_id() ?? 0;
            $nextSeasonId = $latestSeasonId + 1;

            // Fetch the player
            $player = DB::table('players')
                ->where('team_id', $teamId)
                ->where('id', $playerId)
                ->where('is_active', true)
                ->first();

            if (!$player) {
                return response()->json(['error' => 'Player not found or inactive'], 404);
            }

            // Fetch player rating if available
            // $playerRating = DB::table('player_ratings')->where('player_id', $playerId)->first();
            $role = $player->role; // Default role if rating doesn't exist

            // Data to insert/update
            $data = [
                'player_id' => $player->id,
                'team_id' => $player->team_id,
                'season_id' => $nextSeasonId,
                'role' => $role,
                'avg_minutes_per_game' => 0,
                'avg_points_per_game' => 0,
                'avg_rebounds_per_game' => 0,
                'avg_assists_per_game' => 0,
                'avg_steals_per_game' => 0,
                'avg_blocks_per_game' => 0,
                'avg_turnovers_per_game' => 0,
                'avg_fouls_per_game' => 0,
                'total_games' => 0,
                'total_games_played' => 0,
                'total_minutes_played' => 0,
                'total_points' => 0,
                'total_rebounds' => 0,
                'total_assists' => 0,
                'total_steals' => 0,
                'total_blocks' => 0,
                'total_turnovers' => 0,
                'total_fouls' => 0,
                'total_field_goals_made' => 0,
                'total_field_goal_attempts' => 0,
                'total_two_pointers_made' => 0,
                'total_two_point_attempts' => 0,
                'total_three_pointers_made' => 0,
                'total_three_point_attempts' => 0,
                'total_free_throws_made' => 0,
                'total_free_throw_attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Return data before insertion for debugging
            // return response()->json([
            //     'message' => 'Attempting to insert/update player season stats',
            //     'player_id' => $player->id,
            //     'team_id' => $player->team_id,
            //     'season_id' => $nextSeasonId,
            //     'data' => $data
            // ]);

            // Save to database
            DB::table('player_season_stats')->updateOrInsert(
                [
                    'player_id' => $player->id,
                    'team_id' => $player->team_id,
                    'season_id' => $nextSeasonId,
                ],
                $data
            );

            DB::table('players')->where('id', $player->id)->update([
                'morale' => 75,
            ]);
            //return true;
            // if(!$insertRecords){
            //     return response()->json(['message' => "Failed storing stats for Player ID: {$playerId}",'data' => $data]);
            // }

            return response()->json(['message' => "Stored stats for Player ID: {$playerId}", 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public static function storePlayerCurrentSeasonStats($teamId, $playerId)
    {
        try {
            // Get the latest season ID or set it to 1 if none exists
            $latestSeasonId = get_current_season_id() ?? 0;

            // Fetch the player
            $player = DB::table('players')
                ->where('team_id', $teamId)
                ->where('id', $playerId)
                ->where('is_active', true)
                ->first();

            if (!$player) {
                return response()->json(['error' => 'Player not found or inactive'], 404);
            }

            // Fetch player rating if available
            //$playerRating = DB::table('player_ratings')->where('player_id', $playerId)->first();
            $role = $player->role; // Default role if rating doesn't exist

            // Data to insert/update
            $data = [
                'player_id' => $player->id,
                'team_id' => $player->team_id,
                'season_id' => $latestSeasonId,
                'role' => $role,
                'avg_minutes_per_game' => 0,
                'avg_points_per_game' => 0,
                'avg_rebounds_per_game' => 0,
                'avg_assists_per_game' => 0,
                'avg_steals_per_game' => 0,
                'avg_blocks_per_game' => 0,
                'avg_turnovers_per_game' => 0,
                'avg_fouls_per_game' => 0,
                'total_games' => 0,
                'total_games_played' => 0,
                'total_minutes_played' => 0,
                'total_points' => 0,
                'total_rebounds' => 0,
                'total_assists' => 0,
                'total_steals' => 0,
                'total_blocks' => 0,
                'total_turnovers' => 0,
                'total_fouls' => 0,
                'total_field_goals_made' => 0,
                'total_field_goal_attempts' => 0,
                'total_two_pointers_made' => 0,
                'total_two_point_attempts' => 0,
                'total_three_pointers_made' => 0,
                'total_three_point_attempts' => 0,
                'total_free_throws_made' => 0,
                'total_free_throw_attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Return data before insertion for debugging
            // return response()->json([
            //     'message' => 'Attempting to insert/update player season stats',
            //     'player_id' => $player->id,
            //     'team_id' => $player->team_id,
            //     'season_id' => $nextSeasonId,
            //     'data' => $data
            // ]);

            // Save to database
            DB::table('player_season_stats')->updateOrInsert(
                [
                    'player_id' => $player->id,
                    'team_id' => $player->team_id,
                    'season_id' => $latestSeasonId,
                ],
                $data
            );

            //return true;
            // if(!$insertRecords){
            //     return response()->json(['message' => "Failed storing stats for Player ID: {$playerId}",'data' => $data]);
            // }

            return response()->json(['message' => "Stored stats for Player ID: {$playerId}", 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    } 
    
    public static function totalRegularSeasonGames($seasonId, $teamId)
    {
        // $scheduleTable = $this->helper->getScheduleDBName($seasonId);

        // dd($scheduleTable);

        $gamesPlayedCount = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->where('game_number',0)
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->count();

        return $gamesPlayedCount;
    }
}
