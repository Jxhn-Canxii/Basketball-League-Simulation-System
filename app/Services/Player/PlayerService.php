<?php

namespace App\Services\Player;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Player;
use App\Services\Contract\ContractService;
use App\Services\Helper\HelperService;
use App\Services\Team\ScoutingService;
use App\Services\League\RoundService;
use App\Services\Schedule\ScheduleService;
use App\Services\Player\PlayerGeneratorService;

class PlayerService
{
    protected $helper;
    protected $scout;
    protected $roundService;
    protected $scheduleService;
    protected $playerGeneratorService;
    protected $contract;

    public function __construct()
    {
        $this->helper = new HelperService();
        $this->scout = new ScoutingService();
        $this->roundService = new RoundService();
        $this->scheduleService = new ScheduleService();
        $this->contract = new ContractService();
        $this->playerGeneratorService = new PlayerGeneratorService();
    }

    public function getAllPlayers($request)
    {
        // Get pagination parameters from the request
        $perPage = $request->input('itemsperpage', 10); // Number of items per page
        $currentPage = $request->input('page_num', 1); // Current page number
        $search = $request->input('search', ''); // Search term
        $position = $request->input('position', ''); // Search term
        $injuryStatus = (int) $request->input('injury_status', 2); // 2 means all, 1 means injured, 0 means healthy
        $isActive = (int) $request->input('is_active', 1); // 2 means all, 1 means injured, 0 means healthy
        $withATeam = (int) $request->input('with_a_team', 1); // 1 means with team, 0 means free agent
        
        // Calculate the offset for the query
        $offset = ($currentPage - 1) * $perPage;

        // Start building the query with optional search filter and join with teams
        $query = DB::table('players')
            ->select(
                'players.id as player_id',
                'players.country',
                'players.name',
                'players.position',
                'players.age',
                'players.role',
                'players.is_active',
                'players.retirement_age',
                'players.contract_years',
                'players.draft_status',
                'players.is_injured',
                'players.overall_rating',
                'players.team_id',
                DB::raw("IF(players.team_id = 0, 'none', teams.name) as team_name"),

                // Awards
                DB::raw("
                    (SELECT GROUP_CONCAT(
                            CONCAT(award_name, ' (Season ', season_awards.season_id, ')')
                            SEPARATOR ', ')
                    FROM season_awards
                    WHERE season_awards.player_id = players.id
                    ) as awards
                "),

                // Finals MVP (text + flag)
                DB::raw("
                    COALESCE(
                        (SELECT
                            CONCAT('Finals MVP (Season ', seasons.id, ')')
                        FROM seasons
                        WHERE seasons.finals_mvp_id = players.id
                        LIMIT 1
                        ), '') as finals_mvp
                "),
                DB::raw("
                    CASE WHEN EXISTS (
                        SELECT 1 FROM seasons WHERE seasons.finals_mvp_id = players.id
                    ) THEN 1 ELSE 0 END as is_finals_mvp
                "),

                // Drafted team name (or acronym)
                DB::raw("COALESCE(drafted_team.acronym, drafted_team.name, 'N/A') as drafted_team_name"),

                // Formatted draft status
                DB::raw("
                    CASE 
                        WHEN players.draft_status = 'Undrafted' OR players.draft_status = 'Special Draft' THEN 
                            CONCAT('S', players.draft_id,' ',players.draft_status)
                        ELSE 
                            CONCAT(
                                players.draft_status, 
                                '(', COALESCE(drafted_team.acronym, drafted_team.name, 'N/A'), ')'
                            )
                    END as formatted_draft_status
                ")
            )
            ->leftJoin('teams', 'players.team_id', '=', 'teams.id')
            ->leftJoin('teams as drafted_team', 'players.drafted_team_id', '=', 'drafted_team.id');


        // Apply search filter if provided
        if ($search) {
            $query->where('players.name', 'like', "%{$search}%");
        }
        if ($position) {
            $query->where('players.position', 'like', "%{$position}%");
        }
        if ($injuryStatus !== 2) {
            $query->where('players.is_injured', $injuryStatus);
        }
        if ($isActive !== 2) {
            $query->where('players.is_active', $isActive);
        }

        if ($withATeam == 0) {
            $query->where('players.team_id',0);
        }

        // Add sorting by is_active status, then by role priority
        $query->orderBy('players.is_active', 'desc') // Active players first
            ->orderByRaw("FIELD(players.role, 'star player','all star', 'starter', 'role player', 'bench')");

        // Get total number of records
        $total = $query->count();

        // Fetch the paginated data
        $allPlayers = $query->offset($offset)
            ->limit($perPage)
            ->get();

        // Calculate total pages
        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'players' => $allPlayers,
        ]);
    }

    public function getPlayerSeasonPerformance($request)
    {

        $playerId = $request->player_id;

        // Fetch player season stats for the given player
        $playerStatsLatest = DB::table('player_season_stats as player_season_stats')
            ->join('players', 'player_season_stats.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats.team_id', '=', 'teams.id')
            ->join('seasons', 'player_season_stats.season_id', '=', 'seasons.id') // Join with seasons table
            ->leftJoin('player_ratings', function ($join) {
                $join->on('player_season_stats.player_id', '=', 'player_ratings.player_id')
                    ->on('player_season_stats.season_id', '=', 'player_ratings.season_id');
            }) // Left join with player_ratings table
            ->select(
                'players.id as player_id',
                'players.name as player_name',
                'player_season_stats.season_id',
                'player_ratings.overall_rating',
                'player_season_stats.player_valuation',
                'seasons.name as season_name', // Select season name
                DB::raw('GROUP_CONCAT(DISTINCT teams.name ORDER BY player_season_stats.id ASC) as team_names'),
                DB::raw('GROUP_CONCAT(DISTINCT teams.primary_color ORDER BY player_season_stats.id ASC) as team_primary_colors'),
                DB::raw('GROUP_CONCAT(DISTINCT teams.secondary_color ORDER BY player_season_stats.id ASC) as team_secondary_colors'),
                DB::raw('COALESCE(player_ratings.role, players.role) as player_role'), // Use COALESCE to handle NULL roles
                DB::raw('AVG(player_season_stats.avg_points_per_game) as avg_points_per_game'),
                DB::raw('AVG(player_season_stats.avg_rebounds_per_game) as avg_rebounds_per_game'),
                DB::raw('AVG(player_season_stats.avg_assists_per_game) as avg_assists_per_game'),
                DB::raw('AVG(player_season_stats.avg_steals_per_game) as avg_steals_per_game'),
                DB::raw('AVG(player_season_stats.avg_blocks_per_game) as avg_blocks_per_game'),
                DB::raw('AVG(player_season_stats.avg_turnovers_per_game) as avg_turnovers_per_game'),
                DB::raw('AVG(player_season_stats.avg_fouls_per_game) as avg_fouls_per_game'),
                DB::raw('SUM(player_season_stats.total_points) as total_points'),
                DB::raw('SUM(player_season_stats.total_rebounds) as total_rebounds'),
                DB::raw('SUM(player_season_stats.total_assists) as total_assists'),
                DB::raw('SUM(player_season_stats.total_steals) as total_steals'),
                DB::raw('SUM(player_season_stats.total_blocks) as total_blocks'),
                DB::raw('SUM(player_season_stats.total_turnovers) as total_turnovers'),
                DB::raw('SUM(player_season_stats.total_fouls) as total_fouls'),
                DB::raw('SUM(player_season_stats.total_minutes_played) as total_minutes_played'),
                DB::raw('SUM(player_season_stats.total_games_played) as total_games_played'),
                DB::raw('SUM(player_season_stats.total_games_started) as total_games_started'),
                DB::raw('AVG(player_season_stats.per) as per'),
                DB::raw('AVG(player_season_stats.ts_percent) as ts_percent'),
                DB::raw('AVG(player_season_stats.eff) as eff'), // Efficiency
                DB::raw('SUM(player_season_stats.total_field_goals_made) as total_field_goals_made'),
                DB::raw('SUM(player_season_stats.total_field_goal_attempts) as total_field_goal_attempts'),
                DB::raw('SUM(player_season_stats.total_two_pointers_made) as total_two_pointers_made'),
                DB::raw('SUM(player_season_stats.total_two_point_attempts) as total_two_point_attempts'),
                DB::raw('SUM(player_season_stats.total_three_pointers_made) as total_three_pointers_made'),
                DB::raw('SUM(player_season_stats.total_three_point_attempts) as total_three_point_attempts'),
                DB::raw('SUM(player_season_stats.total_free_throws_made) as total_free_throws_made'),
                DB::raw('SUM(player_season_stats.total_free_throw_attempts) as total_free_throw_attempts')
            )
            ->where('teams.conference_id','>',0)
            ->where('player_season_stats.player_id', $playerId)
            ->groupBy(
                'players.id',
                'players.name',
                'player_season_stats.season_id',
                'player_ratings.overall_rating',
                'seasons.name',
                'player_ratings.role',
                'players.role'
            )
            ->orderBy('player_season_stats.season_id', 'desc') // Sort by season_id in descending order
            ->get();


        $playerStats = DB::table('player_season_stats_archives as player_season_stats')
            ->join('players', 'player_season_stats.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats.team_id', '=', 'teams.id')
            ->join('seasons', 'player_season_stats.season_id', '=', 'seasons.id') // Join with seasons table
            ->leftJoin('player_ratings', function ($join) {
                $join->on('player_season_stats.player_id', '=', 'player_ratings.player_id')
                    ->on('player_season_stats.season_id', '=', 'player_ratings.season_id');
            }) // Left join with player_ratings table
            ->select(
                'players.id as player_id',
                'players.name as player_name',
                'player_season_stats.season_id',
                'player_ratings.overall_rating',
                'player_season_stats.player_valuation',
                'seasons.name as season_name', // Select season name
                DB::raw('GROUP_CONCAT(DISTINCT teams.name ORDER BY player_season_stats.id ASC) as team_names'),
                DB::raw('GROUP_CONCAT(DISTINCT teams.primary_color ORDER BY player_season_stats.id ASC) as team_primary_colors'),
                DB::raw('GROUP_CONCAT(DISTINCT teams.secondary_color ORDER BY player_season_stats.id ASC) as team_secondary_colors'),
                DB::raw('COALESCE(player_ratings.role, players.role) as player_role'), // Use COALESCE to handle NULL roles
                DB::raw('AVG(player_season_stats.avg_points_per_game) as avg_points_per_game'),
                DB::raw('AVG(player_season_stats.avg_rebounds_per_game) as avg_rebounds_per_game'),
                DB::raw('AVG(player_season_stats.avg_assists_per_game) as avg_assists_per_game'),
                DB::raw('AVG(player_season_stats.avg_steals_per_game) as avg_steals_per_game'),
                DB::raw('AVG(player_season_stats.avg_blocks_per_game) as avg_blocks_per_game'),
                DB::raw('AVG(player_season_stats.avg_turnovers_per_game) as avg_turnovers_per_game'),
                DB::raw('AVG(player_season_stats.avg_fouls_per_game) as avg_fouls_per_game'),
                DB::raw('SUM(player_season_stats.total_points) as total_points'),
                DB::raw('SUM(player_season_stats.total_rebounds) as total_rebounds'),
                DB::raw('SUM(player_season_stats.total_assists) as total_assists'),
                DB::raw('SUM(player_season_stats.total_steals) as total_steals'),
                DB::raw('SUM(player_season_stats.total_blocks) as total_blocks'),
                DB::raw('SUM(player_season_stats.total_turnovers) as total_turnovers'),
                DB::raw('SUM(player_season_stats.total_fouls) as total_fouls'),
                DB::raw('SUM(player_season_stats.total_minutes_played) as total_minutes_played'),
                DB::raw('SUM(player_season_stats.total_games_played) as total_games_played'),
                DB::raw('SUM(player_season_stats.total_games_started) as total_games_started'),
                DB::raw('AVG(player_season_stats.per) as per'),
                DB::raw('AVG(player_season_stats.ts_percent) as ts_percent'),
                DB::raw('AVG(player_season_stats.eff) as eff'), // Efficiency
                DB::raw('SUM(player_season_stats.total_field_goals_made) as total_field_goals_made'),
                DB::raw('SUM(player_season_stats.total_field_goal_attempts) as total_field_goal_attempts'),
                DB::raw('SUM(player_season_stats.total_two_pointers_made) as total_two_pointers_made'),
                DB::raw('SUM(player_season_stats.total_two_point_attempts) as total_two_point_attempts'),
                DB::raw('SUM(player_season_stats.total_three_pointers_made) as total_three_pointers_made'),
                DB::raw('SUM(player_season_stats.total_three_point_attempts) as total_three_point_attempts'),
                DB::raw('SUM(player_season_stats.total_free_throws_made) as total_free_throws_made'),
                DB::raw('SUM(player_season_stats.total_free_throw_attempts) as total_free_throw_attempts')
            )
            ->where('teams.conference_id','>',0)
            ->where('player_season_stats.player_id', $playerId)
            ->groupBy(
                'players.id',
                'players.name',
                'player_season_stats.season_id',
                'player_ratings.overall_rating',
                'seasons.name',
                'player_ratings.role',
                'players.role'
            )
            ->orderBy('player_season_stats.season_id', 'desc') // Sort by season_id in descending order
            ->get();


        if ($playerStats->isEmpty() && $playerStatsLatest->isEmpty()) {
            return response()->json([
                'error' => 'No stats found for the given player.',
                'player_stats' => [],
            ], 404);
        }

        // Initialize an array to hold formatted player stats
        $formattedPlayerStats = [];

        foreach ($playerStatsLatest as $stats) {
            // Calculate shooting percentages (avoid division by zero)
            $fieldGoalPercentage = $stats->total_field_goal_attempts > 0 ? ($stats->total_field_goals_made / $stats->total_field_goal_attempts) * 100 : 0;
            $twoPointPercentage = $stats->total_two_point_attempts > 0 ? ($stats->total_two_pointers_made / $stats->total_two_point_attempts) * 100 : 0;
            $threePointPercentage = $stats->total_three_point_attempts > 0 ? ($stats->total_three_pointers_made / $stats->total_three_point_attempts) * 100 : 0;
            $freeThrowPercentage = $stats->total_free_throw_attempts > 0 ? ($stats->total_free_throws_made / $stats->total_free_throw_attempts) * 100 : 0;

            // Append player season stats with averages, shooting percentages, and other stats
            $formattedPlayerStats[] = [
                'player_id' => $stats->player_id,
                'player_name' => $stats->player_name,
                'player_role' => $stats->player_role,
                'team_names' => $stats->team_names, // Concatenated team names
                'team_primary_colors' => $stats->team_primary_colors,
                'team_secondary_colors' => $stats->team_secondary_colors,
                'season_id' => $stats->season_id,
                'overall_rating' => $stats->overall_rating,
                'season_name' => $stats->season_name, // Season name
                'efficiency' => $stats->eff, // Efficiency
                'total_points' => $stats->total_points,
                'total_rebounds' => $stats->total_rebounds,
                'total_assists' => $stats->total_assists,
                'total_steals' => $stats->total_steals,
                'total_blocks' => $stats->total_blocks,
                'total_turnovers' => $stats->total_turnovers,
                'total_fouls' => $stats->total_fouls,
                'total_minutes_played' => $stats->total_minutes_played,
                'total_games_played' => $stats->total_games_played,
                'total_games_started' => $stats->total_games_started,
                'per' => $stats->per,
                'ts_percent' => $stats->ts_percent,
                'average_points_per_game' => round($stats->avg_points_per_game, 2),
                'average_rebounds_per_game' => round($stats->avg_rebounds_per_game, 2),
                'average_assists_per_game' => round($stats->avg_assists_per_game, 2),
                'average_steals_per_game' => round($stats->avg_steals_per_game, 2),
                'average_blocks_per_game' => round($stats->avg_blocks_per_game, 2),
                'average_turnovers_per_game' => round($stats->avg_turnovers_per_game, 2),
                'average_fouls_per_game' => round($stats->avg_fouls_per_game, 2),
                // Include shooting percentages
                'field_goal_percentage' => round($fieldGoalPercentage, 2),
                'two_point_percentage' => round($twoPointPercentage, 2),
                'three_point_percentage' => round($threePointPercentage, 2),
                'free_throw_percentage' => round($freeThrowPercentage, 2),
                // Include attempts and made
                'total_field_goals_made' => $stats->total_field_goals_made,
                'total_field_goal_attempts' => $stats->total_field_goal_attempts,
                'total_two_pointers_made' => $stats->total_two_pointers_made,
                'total_two_point_attempts' => $stats->total_two_point_attempts,
                'total_three_pointers_made' => $stats->total_three_pointers_made,
                'total_three_point_attempts' => $stats->total_three_point_attempts,
                'total_free_throws_made' => $stats->total_free_throws_made,
                'total_free_throw_attempts' => $stats->total_free_throw_attempts,

                'player_valuation'  => $stats->player_valuation,
                'latest' => true,
            ];
        }

        foreach ($playerStats as $stats) {
            // Calculate shooting percentages (avoid division by zero)
            $fieldGoalPercentage = $stats->total_field_goal_attempts > 0 ? ($stats->total_field_goals_made / $stats->total_field_goal_attempts) * 100 : 0;
            $twoPointPercentage = $stats->total_two_point_attempts > 0 ? ($stats->total_two_pointers_made / $stats->total_two_point_attempts) * 100 : 0;
            $threePointPercentage = $stats->total_three_point_attempts > 0 ? ($stats->total_three_pointers_made / $stats->total_three_point_attempts) * 100 : 0;
            $freeThrowPercentage = $stats->total_free_throw_attempts > 0 ? ($stats->total_free_throws_made / $stats->total_free_throw_attempts) * 100 : 0;

            // Append player season stats with averages, shooting percentages, and other stats
            $formattedPlayerStats[] = [
                'player_id' => $stats->player_id,
                'player_name' => $stats->player_name,
                'player_role' => $stats->player_role,
                'team_names' => $stats->team_names, // Concatenated team names
                'team_primary_colors' => $stats->team_primary_colors,
                'team_secondary_colors' => $stats->team_secondary_colors,
                'season_id' => $stats->season_id,
                'overall_rating' => $stats->overall_rating,
                'season_name' => $stats->season_name, // Season name
                'efficiency' => $stats->eff, // Efficiency
                'total_points' => $stats->total_points,
                'total_rebounds' => $stats->total_rebounds,
                'total_assists' => $stats->total_assists,
                'total_steals' => $stats->total_steals,
                'total_blocks' => $stats->total_blocks,
                'total_turnovers' => $stats->total_turnovers,
                'total_fouls' => $stats->total_fouls,
                'total_minutes_played' => $stats->total_minutes_played,
                'total_games_played' => $stats->total_games_played,
                'total_games_started' => $stats->total_games_started,
                'per' => $stats->per,
                'ts_percent' => $stats->ts_percent,
                'average_points_per_game' => round($stats->avg_points_per_game, 2),
                'average_rebounds_per_game' => round($stats->avg_rebounds_per_game, 2),
                'average_assists_per_game' => round($stats->avg_assists_per_game, 2),
                'average_steals_per_game' => round($stats->avg_steals_per_game, 2),
                'average_blocks_per_game' => round($stats->avg_blocks_per_game, 2),
                'average_turnovers_per_game' => round($stats->avg_turnovers_per_game, 2),
                'average_fouls_per_game' => round($stats->avg_fouls_per_game, 2),
                // Include shooting percentages
                'field_goal_percentage' => round($fieldGoalPercentage, 2),
                'two_point_percentage' => round($twoPointPercentage, 2),
                'three_point_percentage' => round($threePointPercentage, 2),
                'free_throw_percentage' => round($freeThrowPercentage, 2),
                // Include attempts and made
                'total_field_goals_made' => $stats->total_field_goals_made,
                'total_field_goal_attempts' => $stats->total_field_goal_attempts,
                'total_two_pointers_made' => $stats->total_two_pointers_made,
                'total_two_point_attempts' => $stats->total_two_point_attempts,
                'total_three_pointers_made' => $stats->total_three_pointers_made,
                'total_three_point_attempts' => $stats->total_three_point_attempts,
                'total_free_throws_made' => $stats->total_free_throws_made,
                'total_free_throw_attempts' => $stats->total_free_throw_attempts,

                'player_valuation'  => $stats->player_valuation,
                'latest' => false,
            ];
        }

        return response()->json([
            'player_stats' => $formattedPlayerStats,
            'checked' => true,
        ]);
    }

    public function getPlayerPlayoffPerformance($request)
    {

        $playerId = $request->player_id;

        // Fetch player season stats for the given player
        $playerStats = DB::table('player_season_playoff_stats_archives as player_season_playoff_stats')
            ->join('players', 'player_season_playoff_stats.player_id', '=', 'players.id')
            ->join('teams', 'player_season_playoff_stats.team_id', '=', 'teams.id')
            ->join('seasons', 'player_season_playoff_stats.season_id', '=', 'seasons.id') // Join with seasons table
            ->leftJoin('player_ratings', function ($join) {
                $join->on('player_season_playoff_stats.player_id', '=', 'player_ratings.player_id')
                    ->on('player_season_playoff_stats.season_id', '=', 'player_ratings.season_id');
            }) // Left join with player_ratings table
            ->select(
                'players.id as player_id',
                'players.name as player_name',
                'player_season_playoff_stats.season_id',
                'player_ratings.overall_rating',
                'seasons.name as season_name', // Select season name
                DB::raw('GROUP_CONCAT(DISTINCT teams.name ORDER BY player_season_playoff_stats.id ASC) as team_names'),
                DB::raw('GROUP_CONCAT(DISTINCT teams.primary_color ORDER BY player_season_playoff_stats.id ASC) as team_primary_colors'),
                DB::raw('GROUP_CONCAT(DISTINCT teams.secondary_color ORDER BY player_season_playoff_stats.id ASC) as team_secondary_colors'),
                DB::raw('COALESCE(player_ratings.role, players.role) as player_role'), // Use COALESCE to handle NULL roles
                DB::raw('AVG(player_season_playoff_stats.avg_points_per_game) as avg_points_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_rebounds_per_game) as avg_rebounds_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_assists_per_game) as avg_assists_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_steals_per_game) as avg_steals_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_blocks_per_game) as avg_blocks_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_turnovers_per_game) as avg_turnovers_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_fouls_per_game) as avg_fouls_per_game'),
                DB::raw('SUM(player_season_playoff_stats.total_points) as total_points'),
                DB::raw('SUM(player_season_playoff_stats.total_rebounds) as total_rebounds'),
                DB::raw('SUM(player_season_playoff_stats.total_assists) as total_assists'),
                DB::raw('SUM(player_season_playoff_stats.total_steals) as total_steals'),
                DB::raw('SUM(player_season_playoff_stats.total_blocks) as total_blocks'),
                DB::raw('SUM(player_season_playoff_stats.total_turnovers) as total_turnovers'),
                DB::raw('SUM(player_season_playoff_stats.total_fouls) as total_fouls'),
                DB::raw('SUM(player_season_playoff_stats.total_minutes_played) as total_minutes_played'),
                DB::raw('SUM(player_season_playoff_stats.total_games_played) as total_games_played'),
                DB::raw('SUM(player_season_playoff_stats.total_games_started) as total_games_started'),
                DB::raw('AVG(player_season_playoff_stats.per) as per'),
                DB::raw('AVG(player_season_playoff_stats.ts_percent) as ts_percent'),
                DB::raw('AVG(player_season_playoff_stats.eff) as eff'), // Efficiency
                DB::raw('SUM(player_season_playoff_stats.total_field_goals_made) as total_field_goals_made'),
                DB::raw('SUM(player_season_playoff_stats.total_field_goal_attempts) as total_field_goal_attempts'),
                DB::raw('SUM(player_season_playoff_stats.total_two_pointers_made) as total_two_pointers_made'),
                DB::raw('SUM(player_season_playoff_stats.total_two_point_attempts) as total_two_point_attempts'),
                DB::raw('SUM(player_season_playoff_stats.total_three_pointers_made) as total_three_pointers_made'),
                DB::raw('SUM(player_season_playoff_stats.total_three_point_attempts) as total_three_point_attempts'),
                DB::raw('SUM(player_season_playoff_stats.total_free_throws_made) as total_free_throws_made'),
                DB::raw('SUM(player_season_playoff_stats.total_free_throw_attempts) as total_free_throw_attempts')
            )
            ->where('player_season_playoff_stats.player_id', $playerId)
            ->groupBy(
                'players.id',
                'players.name',
                'player_season_playoff_stats.season_id',
                'player_ratings.overall_rating',
                'seasons.name',
                'player_ratings.role',
                'players.role'
            )
            ->orderBy('player_season_playoff_stats.season_id', 'desc') // Sort by season_id in descending order
            ->get();


        // Fetch player season stats for the given player


        $playerStatsLatest = DB::table('player_season_playoff_stats')
            ->join('players', 'player_season_playoff_stats.player_id', '=', 'players.id')
            ->join('teams', 'player_season_playoff_stats.team_id', '=', 'teams.id')
            ->join('seasons', 'player_season_playoff_stats.season_id', '=', 'seasons.id') // Join with seasons table
            ->leftJoin('player_ratings', function ($join) {
                $join->on('player_season_playoff_stats.player_id', '=', 'player_ratings.player_id')
                    ->on('player_season_playoff_stats.season_id', '=', 'player_ratings.season_id');
            }) // Left join with player_ratings table
            ->select(
                'players.id as player_id',
                'players.name as player_name',
                'player_season_playoff_stats.season_id',
                'player_ratings.overall_rating',
                'seasons.name as season_name', // Select season name
                DB::raw('GROUP_CONCAT(DISTINCT teams.name ORDER BY player_season_playoff_stats.id ASC) as team_names'),
                DB::raw('GROUP_CONCAT(DISTINCT teams.primary_color ORDER BY player_season_playoff_stats.id ASC) as team_primary_colors'),
                DB::raw('GROUP_CONCAT(DISTINCT teams.secondary_color ORDER BY player_season_playoff_stats.id ASC) as team_secondary_colors'),
                DB::raw('COALESCE(player_ratings.role, players.role) as player_role'), // Use COALESCE to handle NULL roles
                DB::raw('AVG(player_season_playoff_stats.avg_points_per_game) as avg_points_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_rebounds_per_game) as avg_rebounds_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_assists_per_game) as avg_assists_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_steals_per_game) as avg_steals_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_blocks_per_game) as avg_blocks_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_turnovers_per_game) as avg_turnovers_per_game'),
                DB::raw('AVG(player_season_playoff_stats.avg_fouls_per_game) as avg_fouls_per_game'),
                DB::raw('SUM(player_season_playoff_stats.total_points) as total_points'),
                DB::raw('SUM(player_season_playoff_stats.total_rebounds) as total_rebounds'),
                DB::raw('SUM(player_season_playoff_stats.total_assists) as total_assists'),
                DB::raw('SUM(player_season_playoff_stats.total_steals) as total_steals'),
                DB::raw('SUM(player_season_playoff_stats.total_blocks) as total_blocks'),
                DB::raw('SUM(player_season_playoff_stats.total_turnovers) as total_turnovers'),
                DB::raw('SUM(player_season_playoff_stats.total_fouls) as total_fouls'),
                DB::raw('SUM(player_season_playoff_stats.total_minutes_played) as total_minutes_played'),
                DB::raw('SUM(player_season_playoff_stats.total_games_played) as total_games_played'),
                DB::raw('SUM(player_season_playoff_stats.total_games_started) as total_games_started'),
                DB::raw('AVG(player_season_playoff_stats.per) as per'),
                DB::raw('AVG(player_season_playoff_stats.ts_percent) as ts_percent'),
                DB::raw('AVG(player_season_playoff_stats.eff) as eff'), // Efficiency
                DB::raw('SUM(player_season_playoff_stats.total_field_goals_made) as total_field_goals_made'),
                DB::raw('SUM(player_season_playoff_stats.total_field_goal_attempts) as total_field_goal_attempts'),
                DB::raw('SUM(player_season_playoff_stats.total_two_pointers_made) as total_two_pointers_made'),
                DB::raw('SUM(player_season_playoff_stats.total_two_point_attempts) as total_two_point_attempts'),
                DB::raw('SUM(player_season_playoff_stats.total_three_pointers_made) as total_three_pointers_made'),
                DB::raw('SUM(player_season_playoff_stats.total_three_point_attempts) as total_three_point_attempts'),
                DB::raw('SUM(player_season_playoff_stats.total_free_throws_made) as total_free_throws_made'),
                DB::raw('SUM(player_season_playoff_stats.total_free_throw_attempts) as total_free_throw_attempts')
            )
            ->where('player_season_playoff_stats.player_id', $playerId)
            ->groupBy(
                'players.id',
                'players.name',
                'player_season_playoff_stats.season_id',
                'player_ratings.overall_rating',
                'seasons.name',
                'player_ratings.role',
                'players.role'
            )
            ->orderBy('player_season_playoff_stats.season_id', 'desc') // Sort by season_id in descending order
            ->get();


        if ($playerStats->isEmpty() && $playerStatsLatest->isEmpty()) {
            return response()->json([
                'error' => 'No stats found for the given player.',
                'player_stats' => [],
            ], 404);
        }

        // Initialize an array to hold formatted player stats
        $formattedPlayerStats = [];

        foreach ($playerStatsLatest as $stats) {
            // Calculate shooting percentages (avoid division by zero)
            $fieldGoalPercentage = $stats->total_field_goal_attempts > 0 ? ($stats->total_field_goals_made / $stats->total_field_goal_attempts) * 100 : 0;
            $twoPointPercentage = $stats->total_two_point_attempts > 0 ? ($stats->total_two_pointers_made / $stats->total_two_point_attempts) * 100 : 0;
            $threePointPercentage = $stats->total_three_point_attempts > 0 ? ($stats->total_three_pointers_made / $stats->total_three_point_attempts) * 100 : 0;
            $freeThrowPercentage = $stats->total_free_throw_attempts > 0 ? ($stats->total_free_throws_made / $stats->total_free_throw_attempts) * 100 : 0;

            // Append player season stats with averages, shooting percentages, and other stats
            $formattedPlayerStats[] = [
                'player_id' => $stats->player_id,
                'player_name' => $stats->player_name,
                'player_role' => $stats->player_role,
                'team_names' => $stats->team_names, // Concatenated team names
                'team_primary_colors' => $stats->team_primary_colors,
                'team_secondary_colors' => $stats->team_secondary_colors,
                'season_id' => $stats->season_id,
                'overall_rating' => $stats->overall_rating,
                'season_name' => $stats->season_name, // Season name
                'efficiency' => $stats->eff, // Efficiency
                'total_points' => $stats->total_points,
                'total_rebounds' => $stats->total_rebounds,
                'total_assists' => $stats->total_assists,
                'total_steals' => $stats->total_steals,
                'total_blocks' => $stats->total_blocks,
                'total_turnovers' => $stats->total_turnovers,
                'total_fouls' => $stats->total_fouls,
                'total_minutes_played' => $stats->total_minutes_played,
                'total_games_played' => $stats->total_games_played,
                'total_games_started' => $stats->total_games_started,
                'per' => $stats->per,
                'ts_percent' => $stats->ts_percent,
                'average_points_per_game' => round($stats->avg_points_per_game, 2),
                'average_rebounds_per_game' => round($stats->avg_rebounds_per_game, 2),
                'average_assists_per_game' => round($stats->avg_assists_per_game, 2),
                'average_steals_per_game' => round($stats->avg_steals_per_game, 2),
                'average_blocks_per_game' => round($stats->avg_blocks_per_game, 2),
                'average_turnovers_per_game' => round($stats->avg_turnovers_per_game, 2),
                'average_fouls_per_game' => round($stats->avg_fouls_per_game, 2),
                // Include shooting percentages
                'field_goal_percentage' => round($fieldGoalPercentage, 2),
                'two_point_percentage' => round($twoPointPercentage, 2),
                'three_point_percentage' => round($threePointPercentage, 2),
                'free_throw_percentage' => round($freeThrowPercentage, 2),
                // Include attempts and made
                'total_field_goals_made' => $stats->total_field_goals_made,
                'total_field_goal_attempts' => $stats->total_field_goal_attempts,
                'total_two_pointers_made' => $stats->total_two_pointers_made,
                'total_two_point_attempts' => $stats->total_two_point_attempts,
                'total_three_pointers_made' => $stats->total_three_pointers_made,
                'total_three_point_attempts' => $stats->total_three_point_attempts,
                'total_free_throws_made' => $stats->total_free_throws_made,
                'total_free_throw_attempts' => $stats->total_free_throw_attempts,
            ];
        }

              foreach ($playerStats as $stats) {
            // Calculate shooting percentages (avoid division by zero)
            $fieldGoalPercentage = $stats->total_field_goal_attempts > 0 ? ($stats->total_field_goals_made / $stats->total_field_goal_attempts) * 100 : 0;
            $twoPointPercentage = $stats->total_two_point_attempts > 0 ? ($stats->total_two_pointers_made / $stats->total_two_point_attempts) * 100 : 0;
            $threePointPercentage = $stats->total_three_point_attempts > 0 ? ($stats->total_three_pointers_made / $stats->total_three_point_attempts) * 100 : 0;
            $freeThrowPercentage = $stats->total_free_throw_attempts > 0 ? ($stats->total_free_throws_made / $stats->total_free_throw_attempts) * 100 : 0;

            // Append player season stats with averages, shooting percentages, and other stats
            $formattedPlayerStats[] = [
                'player_id' => $stats->player_id,
                'player_name' => $stats->player_name,
                'player_role' => $stats->player_role,
                'team_names' => $stats->team_names, // Concatenated team names
                'team_primary_colors' => $stats->team_primary_colors,
                'team_secondary_colors' => $stats->team_secondary_colors,
                'season_id' => $stats->season_id,
                'overall_rating' => $stats->overall_rating,
                'season_name' => $stats->season_name, // Season name
                'efficiency' => $stats->eff, // Efficiency
                'total_points' => $stats->total_points,
                'total_rebounds' => $stats->total_rebounds,
                'total_assists' => $stats->total_assists,
                'total_steals' => $stats->total_steals,
                'total_blocks' => $stats->total_blocks,
                'total_turnovers' => $stats->total_turnovers,
                'total_fouls' => $stats->total_fouls,
                'total_minutes_played' => $stats->total_minutes_played,
                'total_games_played' => $stats->total_games_played,
                'total_games_started' => $stats->total_games_started,
                'per' => $stats->per,
                'ts_percent' => $stats->ts_percent,
                'average_points_per_game' => round($stats->avg_points_per_game, 2),
                'average_rebounds_per_game' => round($stats->avg_rebounds_per_game, 2),
                'average_assists_per_game' => round($stats->avg_assists_per_game, 2),
                'average_steals_per_game' => round($stats->avg_steals_per_game, 2),
                'average_blocks_per_game' => round($stats->avg_blocks_per_game, 2),
                'average_turnovers_per_game' => round($stats->avg_turnovers_per_game, 2),
                'average_fouls_per_game' => round($stats->avg_fouls_per_game, 2),
                // Include shooting percentages
                'field_goal_percentage' => round($fieldGoalPercentage, 2),
                'two_point_percentage' => round($twoPointPercentage, 2),
                'three_point_percentage' => round($threePointPercentage, 2),
                'free_throw_percentage' => round($freeThrowPercentage, 2),
                // Include attempts and made
                'total_field_goals_made' => $stats->total_field_goals_made,
                'total_field_goal_attempts' => $stats->total_field_goal_attempts,
                'total_two_pointers_made' => $stats->total_two_pointers_made,
                'total_two_point_attempts' => $stats->total_two_point_attempts,
                'total_three_pointers_made' => $stats->total_three_pointers_made,
                'total_three_point_attempts' => $stats->total_three_point_attempts,
                'total_free_throws_made' => $stats->total_free_throws_made,
                'total_free_throw_attempts' => $stats->total_free_throw_attempts,
            ];
        }
        return response()->json([
            'player_stats' => $formattedPlayerStats,
        ]);
    }

    public function getPlayerMainPerformance($request)
    {

        $playerId = $request->player_id;

        $latestSeasonId = get_current_season_id();

        // Fetch player and team details
        $playerDetails = $this->getPlayerDetails($playerId);

        if (!$playerDetails) {
            return response()->json([
                'error' => 'Player not found.',
            ], 404);
        }

        // Fetch playoff performance
        $playoffPerformance = $this->getPlayerPlayoffData($playerId); 

        // Set default values if no performance data found
        $playoffPerformance = $playoffPerformance ?: (object)[
            'round_of_16_appearances' => 0,
            'quarter_finals_appearances' => 0,
            'semi_finals_appearances' => 0,
            'interconference_semi_finals_appearances' => 0,
            'interconference_finals_appearances' => 0,
            'finals_appearances' => 0,
            'play_ins_finals_appearances' => 0,
            'play_ins_elims_round_1_appearances' => 0,
            'play_ins_elims_round_2_appearances' => 0
        ];

        // Fetch MVP count and seasons
        $awardsData = $this->getPlayerAwards($playerId);

        // Fetch MVP count and seasons
        $mvpData = $this->getFinalsMvp($playerId);

        $mvpCount = $mvpData->count();

        // Fetch championship count and season names
        $championships = $this->getPlayerChampionships($playerId,'finals');

        // Fetch conference championships (using playoff_series table)
        $conference_championships = $this->getPlayerChampionships($playerId,'semi_finals');

        // Fetch career high stats
        $careerHighs = $this->getPlayerGameHighs($playerId);

        // Calculate season count
        $seasonCount = $this->getPlayerStatsCounter($playerId,'player_season_stats_archives');

        // Calculate playoff count
        $playoffCount = $this->getPlayerStatsCounter($playerId,'player_season_playoff_stats_archives');

        $overallRankSeasons = $this->rankOneInASeason($playerId,'overall_rank');

        $conferenceRankSeasons = $this->rankOneInASeason($playerId,'conference_rank');

        $latestStats = $this->getLatestStats($playerId);

        $scoutingReportData = [
            'potential_rating' => $playerDetails->potential_rating,
            'overall_rating' => $playerDetails->overall_rating,
            'basketball_iq_rating' => $playerDetails->basketball_iq_rating,
            'defense_rating' => $playerDetails->defense_rating,
            'free_throw_rating' => $playerDetails->free_throw_rating,
            'injury_prone_rate' => $playerDetails->injury_prone_percentage,
            'leadership_rating' => $playerDetails->leadership_rating,
            'shooting_rating' => $playerDetails->shooting_rating,
            'passing_rating' => $playerDetails->passing_rating,
            'work_ethic_rating' => $playerDetails->work_ethic_rating,
            'player_name' => $playerDetails->player_name,
            'position' => $playerDetails->position,
            'season_count' => $seasonCount,
            'awards' => count($awardsData),
            'playoff_count' => $playoffCount,
            'national_championships' => count($championships),
        ];

        $scoutingReport = $this->scout->generateScoutingReport($scoutingReportData);

        return response()->json([
            'latest_stats' => $latestStats,
            'player_details' => $playerDetails,
            'playoff_performance' => $playoffPerformance,
            'mvp_count' => $mvpCount,
            'mvp_seasons' => $mvpData->pluck('season_name'),
            'national_championships' => $championships,
            'conference_championships' => $conference_championships,
            'national_overall_champions' => $overallRankSeasons,
            'conference_overall_champions' => $conferenceRankSeasons,
            'career_highs' => $careerHighs,
            'season_count' => $seasonCount,
            'awards' => $awardsData,
            'playoff_count' => $playoffCount,
            'current_season_id' => $latestSeasonId,
            'scouting_report' => $scoutingReport,
        ]);
    }

    public function getPlayerAwardsList($request){

        $playerId = $request->player_id;

        $awards = $this->getPlayerAwards($request->player_id);

        $overallRankSeasons = $this->rankOneInASeason($playerId,'overall_rank');

        $conferenceRankSeasons = $this->rankOneInASeason($playerId,'conference_rank');

        // Fetch MVP count and seasons
        $mvpData = $this->getFinalsMvp($playerId);

        // Fetch championship count and season names
        $championships = $this->getPlayerChampionships($playerId,'finals');

        // Fetch conference championships (using playoff_series table)
        $conference_championships = $this->getPlayerChampionships($playerId,'semi_finals');

        $formattedData = [];

        foreach ($awards as $stats) {
            # code...
            $formattedData[] = [
                'details' => $stats->details,
                'description' => $stats->description,
                'award_name' => $stats->award_name,
                'season' => $stats->season,
                'season_name' => $stats->season_name,
                'team_name' => $stats->team_name,
                'primary_color' => $stats->primary_color,
                'secondary_color' => $stats->secondary_color,
                'acronym' => $stats->acronym,
            ];
        }

        foreach ($overallRankSeasons as $stats) {
            # code...
            $formattedData[] = [
                'details' => 'Overall Top #1 Team',
                'description' => 'Teams Rank #1 in a Regular Season',
                'award_name' => 'Overall Top #1 Team',
                'season' => $stats->season_id,
                'season_name' => $stats->season_name,
                'team_name' => $stats->team_name,
                'primary_color' => $stats->primary_color,
                'secondary_color' => $stats->secondary_color,
                'acronym' => $stats->acronym,
            ];
        }

        foreach ($conferenceRankSeasons as $stats) {
            # code...
            $formattedData[] = [
                'details' => 'Conference Top #1 Team',
                'description' => 'Teams Conference Rank #1 in a Regular Season',
                'award_name' => 'Conference Top #1 Team',
                'season' => $stats->season_id,
                'season_name' => $stats->season_name,
                'team_name' => $stats->team_name,
                'primary_color' => $stats->primary_color,
                'secondary_color' => $stats->secondary_color,
                'acronym' => $stats->acronym,
            ];
        }

        foreach ($championships as $stats) {
            # code...
            $formattedData[] = [
                'details' => null,
                'description' => $stats->season_name.' National Finals Championships.',
                'award_name' => ' National Finals Champions',
                'season' => $stats->season_id,
                'season_name' => $stats->season_name,
                'team_name' => $stats->team_name,
                'primary_color' => $stats->primary_color,
                'secondary_color' => $stats->secondary_color,
                'acronym' => $stats->acronym,
            ];
        }

        foreach ($conference_championships as $stats) {
            # code...
            $formattedData[] = [
                'details' => null,
                'description' => $stats->season_name.' '.$stats->conference_name.' Conference Finals Championships.',
                'award_name' => $stats->conference_name.' Conference Champions',
                'season' => $stats->season_id,
                'season_name' => $stats->season_name,
                'team_name' => $stats->team_name,
                'primary_color' => $stats->primary_color,
                'secondary_color' => $stats->secondary_color,
                'acronym' => $stats->acronym,
            ];
        }

        $sortedAwards = collect($formattedData)->sort(function ($a, $b) {
            return $b['season'] <=> $a['season'];
        })->values();
    
        return response()->json($sortedAwards);
    }

    public function getPlayerGameLogs($request)
    {

        $playerId = $request->player_id;
        $seasonId = $request->season_id;
        $page = $request->page_num;
        $perPage = $request->itemsperpage;

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        $playerName = DB::table('players')
            ->join('teams', 'players.team_id', '=', 'teams.id', 'left')
            ->where('players.id', $playerId)
            ->select('players.name as player_name', 'teams.name as team_name') // Select player name and team name
            ->first();


        $playerDatabase = $this->helper->getPlayerStatsDatabaseName($seasonId);
        $scheduleTable = $this->helper->getScheduleDBName($seasonId);

        // Fetch player game logs for the given player and season with pagination
        $playerGameLogs = DB::table($playerDatabase . ' as player_game_stats')
            ->join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->join('teams as player_team', 'player_game_stats.team_id', '=', 'player_team.id') // Join with player's team to get team name
            ->join($scheduleTable.' as schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
            ->join('seasons', 'schedules.season_id', '=', 'seasons.id') // Join with seasons table
            ->leftJoin('teams as home_team', 'schedules.home_id', '=', 'home_team.id') // Join with home team
            ->leftJoin('teams as away_team', 'schedules.away_id', '=', 'away_team.id') // Join with away team
            ->select(
                'player_game_stats.id as stat_id', // Include player_game_stats.id in the select
                'player_game_stats.game_id',
                'player_team.name as team_name', // Player's team name
                DB::raw('CASE
                WHEN player_game_stats.team_id = schedules.home_id THEN away_team.name
                ELSE home_team.name
            END as opponent_team_name'), // Determine opponent team name
                'schedules.round as round', // Add round info
                'seasons.name as season_name', // Include season name
                'player_game_stats.*',
                DB::raw('(CASE
                WHEN player_game_stats.team_id = schedules.home_id THEN
                    (CASE WHEN schedules.home_score > schedules.away_score THEN "Win" ELSE "Loss" END)
                ELSE
                    (CASE WHEN schedules.away_score > schedules.home_score THEN "Win" ELSE "Loss" END)
            END) as game_result'), // Determine win/loss
            )
            ->where('player_game_stats.player_id', $playerId)
            ->where('player_game_stats.season_id', $seasonId)
            ->orderBy('player_game_stats.id', 'desc') // Order by player_game_stats.id in descending order
            ->offset($offset)
            ->limit($perPage)
            ->get();

        // Fetch total count of records for pagination info
        $totalRecords = DB::table($playerDatabase . ' as player_game_stats')
            ->join($scheduleTable.' as schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
            ->where('player_game_stats.player_id', $playerId)
            ->where('player_game_stats.season_id', $seasonId)
            ->count();

        // Prepare pagination metadata
        $totalPages = ceil($totalRecords / $perPage);

        // Calculate shooting percentages for each game and format the response
        $formattedGameLogs = $playerGameLogs->map(function ($log) {
            return $log;
        });

        // Prepare response
        return response()->json([
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'game_logs' => $formattedGameLogs,
            'player_name' => $playerName,
            'source' => $playerDatabase,
        ]);
    }
    
    public function getPlayerLatestGameLogs($request)
    {

        $playerId = $request->player_id;
        $seasonId = $request->season_id;
        $page = $request->page_num;
        $perPage = $request->itemsperpage;

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        $playerName = DB::table('players')
            ->join('teams', 'players.team_id', '=', 'teams.id', 'left')
            ->where('players.id', $playerId)
            ->select('players.name as player_name', 'teams.name as team_name') // Select player name and team name
            ->first();

        $scheduleTable = $this->helper->getScheduleDBName($seasonId);

        // Fetch player game logs for the given player and season with pagination
        $playerGameLogs = DB::table('player_game_stats')
            ->join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->join('teams as player_team', 'player_game_stats.team_id', '=', 'player_team.id') // Join with player's team to get team name
            ->join($scheduleTable.' as schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
            ->join('seasons', 'schedules.season_id', '=', 'seasons.id') // Join with seasons table
            ->leftJoin('teams as home_team', 'schedules.home_id', '=', 'home_team.id') // Join with home team
            ->leftJoin('teams as away_team', 'schedules.away_id', '=', 'away_team.id') // Join with away team
            ->select(
                'player_game_stats.id as stat_id', // Include player_game_stats.id in the select
                'player_game_stats.game_id',
                'player_team.name as team_name', // Player's team name
                DB::raw('CASE
                WHEN player_game_stats.team_id = schedules.home_id THEN away_team.name
                ELSE home_team.name
            END as opponent_team_name'), // Determine opponent team name
                'schedules.round as round', // Add round info
                'seasons.name as season_name', // Include season name
                'player_game_stats.*',
                DB::raw('(CASE
                WHEN player_game_stats.team_id = schedules.home_id THEN
                    (CASE WHEN schedules.home_score > schedules.away_score THEN "Win" ELSE "Loss" END)
                ELSE
                    (CASE WHEN schedules.away_score > schedules.home_score THEN "Win" ELSE "Loss" END)
            END) as game_result'), // Determine win/loss
            )
            ->where('player_game_stats.player_id', $playerId)
            ->orderBy('player_game_stats.id', 'desc') // Order by player_game_stats.id in descending order
            ->offset($offset)
            ->limit($perPage)
            ->get();

        // Fetch total count of records for pagination info
        $totalRecords = DB::table('player_game_stats')
            ->join($scheduleTable.' as schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
            ->where('player_game_stats.player_id', $playerId)
            ->count();

        // Prepare pagination metadata
        $totalPages = ceil($totalRecords / $perPage);

        // Calculate shooting percentages for each game and format the response
        $formattedGameLogs = $playerGameLogs->map(function ($log) {
            return $log;
        });

        // Prepare response
        return response()->json([
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'game_logs' => $formattedGameLogs,
            'player_name' => $playerName,
        ]);
    }

    public function getPlayersWithFilters($request)
    {
        $sortColumn = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'desc');
        $perPage = $request->input('itemsperpage', 10);
        $page = $request->input('page_num', 1);
        $offset = ($page - 1) * $perPage;

        // Base query to get filtered players from the player_playoff_appearances table
        $query = DB::table('player_playoff_appearances as ppa')
            ->join('players as p', 'ppa.player_id', '=', 'p.id')  // Join with players table to get player names
            ->leftJoin('teams as t', 'p.team_id', '=', 't.id')    // Join with teams table to get current team names
            ->select(
                'p.is_active AS active_status',
                'p.name as player_name',
                't.name as current_team_name',
                'ppa.*'
            );

        // Apply sorting
        switch ($sortColumn) {
            case 'playoff_appearances':
                $query->orderBy('ppa.total_playoff_series_appearances', $sortOrder);
                break;
            case 'national_eight':
                $query->orderBy('ppa.interconference_semi_finals_appearances', $sortOrder);
                break;
            case 'big_four':
                $query->orderBy('ppa.interconference_finals_appearances', $sortOrder);
                break;
            case 'finals_appearances':
                $query->orderBy('ppa.finals_appearances', $sortOrder);
                break;
            case 'seasons_played':
                $query->orderBy('ppa.total_seasons_played', $sortOrder); // NOTE: column must exist or be handled differently
                break;
            case 'conference_championships_won':
                $query->orderBy('ppa.conference_championships_won', $sortOrder);
                break;
            case 'championships_won':
                $query->orderBy('ppa.championships_won', $sortOrder);
                break;
            default:
                $query->orderBy('p.name', 'asc');
        }

        // Fetch total number of records
        $total = DB::table('player_playoff_appearances as ppa')
            ->join('players as p', 'ppa.player_id', '=', 'p.id')
            ->count();

        // Fetch paginated results
        $players = $query->skip($offset)->take($perPage)->get();

        // Add total_seasons_played to each player using player_season_stats table
        foreach ($players as $player) {
            $player->experience = DB::table('player_season_stats')
                ->where('player_id', $player->player_id)
                ->distinct('season_id')
                ->count('season_id');
        }

        // Return paginated response
        return response()->json([
            'data' => $players,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    public function getStarPlayersByTeam($request)
    {
        $teamId = $request->team_id;

        // Subquery to get the highest-efficiency star player per season
        $sub = DB::table('player_season_stats_archives')
            ->selectRaw('MAX(player_valuation) as valuable_player, season_id')
            ->where('team_id', $teamId)
            ->groupBy('season_id');

        // Join with player and season data
        $starPlayers = DB::table('player_season_stats_archives AS pss')
            ->joinSub($sub, 'valuable_player_stats', function ($join) {
                $join->on('pss.season_id', '=', 'valuable_player_stats.season_id')
                    ->on('pss.player_valuation', '=', 'valuable_player_stats.valuable_player');
            })
            ->join('players AS p', 'pss.player_id', '=', 'p.id')
            ->join('seasons AS s', 'pss.season_id', '=', 's.id')
            ->leftJoin('teams AS ct', 'p.team_id', '=', 'ct.id') // current team
            ->leftJoin('teams AS t', 'pss.team_id', '=', 't.id') // season team
            ->where('pss.team_id', $teamId)
            ->orderByDesc('pss.season_id')
            ->select([
                's.id AS season_id',
                's.name AS season_name',
                'p.id AS player_id',
                'p.draft_status AS draft_status',
                'p.draft_id AS draft_id',
                'p.name AS player_name',
                'p.role AS current_role',
                'pss.role AS season_role',
                'ct.name AS current_team',
                't.name AS season_team',
                'pss.avg_minutes_per_game',
                'pss.avg_points_per_game',
                'pss.avg_rebounds_per_game',
                'pss.avg_assists_per_game',
                'pss.avg_steals_per_game',
                'pss.avg_blocks_per_game',
                'pss.avg_turnovers_per_game',
                'pss.avg_fouls_per_game',
                'pss.total_field_goals_made',
                'pss.total_field_goal_attempts',
                'pss.total_two_pointers_made',
                'pss.total_two_point_attempts',
                'pss.total_three_pointers_made',
                'pss.total_three_point_attempts',
                'pss.total_free_throws_made',
                'pss.total_free_throw_attempts',
                'pss.total_points',
                'pss.total_rebounds',
                'pss.total_assists',
                'pss.total_steals',
                'pss.total_blocks',
                'pss.total_turnovers',
                'pss.total_fouls',
                'pss.total_minutes_played',
                'pss.total_games_played',
                'pss.total_games',
                'pss.bpg_game_leader',
                'pss.points_game_leader',
                'pss.rebounds_game_leader',
                'pss.assists_game_leader',
                'pss.steals_game_leader',
                'pss.blocks_game_leader',
                'pss.per',
                'pss.ts_percent',
                'pss.eff',
                'pss.field_goal_percentage',
                'pss.two_point_percentage',
                'pss.three_point_percentage',
                'pss.free_throw_percentage',
                'pss.created_at',
                'pss.updated_at',
            ])
            ->get();

        return response()->json($starPlayers);
    }

    public function getPlayerTransactions($request)
    {
        $player_id = $request->input('player_id');

        if (!$player_id) {
            return response()->json(['error' => 'Player ID is required'], 400);
        }

        $transactions = DB::table('transactions')
            ->join('players', 'transactions.player_id', '=', 'players.id')
            ->leftJoin('teams as from_team', 'transactions.from_team_id', '=', 'from_team.id')
            ->leftJoin('teams as to_team', 'transactions.to_team_id', '=', 'to_team.id')
            ->leftJoinSub(
                DB::table('player_season_stats as pss')
                    ->select('pss.player_id', 'pss.season_id', 'pss.role')
                    ->whereRaw('pss.id = (SELECT id FROM player_season_stats WHERE player_id = pss.player_id AND season_id = pss.season_id ORDER BY id DESC LIMIT 1)'), // Get latest role
                'latest_stats',
                function ($join) {
                    $join->on('transactions.player_id', '=', 'latest_stats.player_id')
                        ->on('transactions.season_id', '=', 'latest_stats.season_id');
                }
            )
            ->where('transactions.player_id', $player_id)
            ->whereNotIn('transactions.status', ['transfer', 'star player change', 'role change'])
            ->select(
                'transactions.id',
                'transactions.season_id',
                'transactions.from_team_id',
                'from_team.name as from_team_name',
                'transactions.to_team_id',
                'to_team.name as to_team_name',
                'transactions.status',
                'players.name as player_name',
                DB::raw('COALESCE(latest_stats.role, "Unknown") as latest_role'), // Get latest role per season
                DB::raw('GROUP_CONCAT(DISTINCT transactions.details ORDER BY transactions.details SEPARATOR ", ") as merged_details') // Merge duplicate transactions
            )
            ->groupBy(
                'transactions.id',
                'transactions.season_id',
                'transactions.from_team_id',
                'from_team.name',
                'transactions.to_team_id',
                'to_team.name',
                'transactions.status',
                'players.name',
                'latest_stats.role'
            )
            ->orderByDesc('transactions.season_id')
            ->orderByDesc('transactions.id')
            ->get();

        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'No transactions found for this player.'], 404);
        }

        return response()->json($transactions);
    }

    public function getPlayerContracts($request)
    {
        $player_id = $request->input('player_id');

        if (!$player_id) {
            return response()->json(['error' => 'Player ID is required'], 400);
        }

        $transactions = DB::table('player_contracts as contracts')
            ->join('players', 'contracts.player_id', '=', 'players.id')
            ->leftJoin('teams', 'contracts.team_id', '=', 'teams.id')
            ->leftJoin('seasons', 'contracts.id', '=', 'seasons.id')
            ->where('contracts.player_id', $player_id)
            ->select(
                'contracts.*',
                'players.name as player_name',
                'teams.name as team_name',
                'players.contract_years as years_remaining',
                'teams.primary_color as team_primary_colors',
                'teams.secondary_color as team_secondary_colors',
                'teams.acronym as team_acronym',
            )
            ->orderByDesc('contracts.id')
            ->get();

        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'No contract found for this player.'], 404);
        }

        return response()->json($transactions);
    }
    
    public function getCareerHighs($request)
    {
        $player_id = $request->input('player_id');

        if (!$player_id) {
            return response()->json(['error' => 'Player ID is required'], 400);
        }

        $transactions = DB::table('career_highlights as ch')
            ->join('players', 'ch.player_id', '=', 'players.id')
            ->leftJoin('teams', 'ch.team_id', '=', 'teams.id')
            ->leftJoin('teams as vs', 'ch.vs_team_id', '=', 'vs.id')
            ->leftJoin('seasons', 'ch.season_id', '=', 'seasons.id')
            ->where('ch.player_id', $player_id)
            ->where('ch.status','career-high')
            ->select(
                'ch.id',
                'ch.season_id',
                'ch.team_id',
                'ch.status',
                'ch.details',
                'ch.value as stats_value',
                'ch.type as stats_type',
                'teams.name as team_name',
                'vs.name as opponent_team_name',
                'seasons.name as season_name',
                'players.name as player_name',
            )
            ->orderByDesc('ch.id')
            ->get();

        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'No contract found for this player.'], 404);
        }

        return response()->json($transactions);
    }

    ///private functions
    private function getPlayerDetails($playerId){
        $data = DB::table('players')
            ->join('teams', 'players.team_id', '=', 'teams.id', 'left') // Join teams table to get team details
            ->join('teams as drafted_teams', 'players.drafted_team_id', '=', 'drafted_teams.id', 'left') // Join drafted teams table
            ->join('seasons', 'players.draft_id', '=', 'seasons.id', 'left') // Join draft seasons
            ->where('players.id', $playerId)
            ->select(
                'players.id as player_id',
                'teams.primary_color',
                'teams.secondary_color',
                'players.contract_type',
                'players.salary',
                'players.type as archetype',
                'players.hardship_contract',
                'players.position as position',
                'players.name as player_name',
                'players.country as country',
                'players.address as address',
                'players.age as age',
                'players.retirement_age as retirement_age',
                'teams.name as team_name',
                'players.role',
                'players.contract_years',
                'players.is_rookie',
                'players.is_active',
                'players.overall_rating',
                'players.potential_rating',
                'players.shooting_rating',
                'players.two_point_rating',
                'players.three_point_rating',
                'players.free_throw_rating',
                'players.defense_rating',
                'players.passing_rating',
                'players.rebounding_rating',
                'players.athleticism_rating',
                'players.basketball_iq_rating',
                'players.strength_rating',
                'players.stamina_rating',
                'players.clutch_rating',
                'players.leadership_rating',
                'players.work_ethic_rating',
                'players.injury_prone_percentage',
                'players.type',
                'players.draft_status as draft_status',
                'seasons.name as draft_class',
                'drafted_teams.acronym as drafted_team',
                'players.injury_recovery_games as injury_recovery_game_count'
            )
            ->first();

        return $data;
    }

    private function getPlayerStatsCounter($playerId,$tableName){
         // Calculate playoff count
        $statsCount = DB::table($tableName)
            ->where('player_id', $playerId)
            ->distinct('season_id')
            ->count('season_id');

        return $statsCount;
    }

    private function getPlayerGameHighs($playerId){

        return DB::table('player_game_highs')
            ->where('player_id', $playerId)
            ->first();
    }

    private function getPlayerPlayoffData($playerId){

        return DB::table('player_playoff_appearances')
            ->select(
                'round_of_16_appearances',
                'quarter_finals_appearances',
                'semi_finals_appearances',
                'interconference_semi_finals_appearances',
                'interconference_finals_appearances',
                'finals_appearances',
                'play_ins_finals_appearances',
                'play_ins_elims_round_1_appearances',
                'play_ins_elims_round_2_appearances'
            )
            ->where('player_id', $playerId)
            ->first();

    }

    private function getFinalsMvp($playerId){

        return DB::table('seasons')
                ->where('seasons.finals_mvp_id', $playerId)
                ->select('seasons.name as season_name')
                ->get();

    }

    private function getPlayerAwards($playerId){

        return DB::table('season_awards')
                ->join('players', 'season_awards.player_id', '=', 'players.id')
                ->join('teams', 'season_awards.team_id', '=', 'teams.id')
                ->leftJoin('seasons', 'season_awards.season_id', '=', 'seasons.id') // Join the seasons table
                ->where('season_awards.player_id', $playerId)
                ->select(
                    'season_awards.created_at as details',
                    'season_awards.award_description as description',
                    'season_awards.award_name as award_name',
                    'season_awards.season_id as season',
                    'seasons.name as season_name', // Select the season name
                    'teams.name as team_name',
                    'teams.primary_color',
                    'teams.secondary_color',
                    'teams.acronym',
                )
                ->distinct()
                ->orderBy('season_awards.season_id','desc')
                ->orderBy('season_awards.id','desc')
                ->get();
    }

    private function getPlayerChampionships($playerId, $round){
        
            return DB::table('seasons')
                    ->join('player_season_stats_archives as player_season_stats', 'seasons.id', '=', 'player_season_stats.season_id')
                    ->join('playoff_series_archives as playoff_series', 'seasons.id', '=', 'playoff_series.season_id')
                    ->join('teams as team', 'player_season_stats.team_id', '=', 'team.id')
                    ->join('teams as winner_team', 'playoff_series.winner_team_id', '=', 'winner_team.id')
                    ->select(
                        'seasons.id as season_id',
                        'seasons.name as season_name',
                        'winner_team.name as championship_team'
                    )
                    ->where('player_season_stats.player_id', $playerId)
                    ->where('playoff_series.round', $round)
                    ->where('playoff_series.status', 2) // Series is finished
                    ->whereColumn('playoff_series.winner_team_id', 'player_season_stats.team_id') // Match columns correctly
                    ->groupBy('seasons.id', 'seasons.name', 'winner_team.name')
                    ->distinct()
                    ->get();
    }

    private function rankOneInASeason($playerId, $type, $rank = 1){
        $overallRankSeasons = DB::table('player_season_stats_archives as pssa')
            ->join('standings_snapshots', function ($join) {
                $join->on('pssa.team_id', '=', 'standings_snapshots.team_id')
                    ->on('pssa.season_id', '=', 'standings_snapshots.season_id');
            })
            ->join('seasons', 'standings_snapshots.season_id', '=', 'seasons.id')
            ->join('teams', 'pssa.team_id', '=', 'teams.id')
            ->where('pssa.player_id', $playerId)
            ->where('standings_snapshots.'.$type, $rank)
            ->distinct()
            ->get([
                'standings_snapshots.season_id',
                'seasons.name as season_name',
                'standings_snapshots.overall_rank',
                'teams.name as team_name'
            ]);
        
        return $overallRankSeasons;
    }

    private function getLatestStats($playerId)
    {

        $seasonId = get_current_season_id();

        $seasonStats = DB::table('player_season_stats as pss')
            ->join('players', 'pss.player_id', '=', 'players.id')
            ->leftJoin('teams', 'pss.team_id', '=', 'teams.id')
            ->leftJoin('seasons', 'pss.season_id', '=', 'seasons.id')
            ->where('pss.player_id', $playerId)
            ->where('pss.season_id', $seasonId)
            ->select(
                'pss.*',
                'players.name as player_name',
                'seasons.name as season_name',
            )
            ->orderByDesc('pss.id')
            ->first();

        return $seasonStats;
    }
}

