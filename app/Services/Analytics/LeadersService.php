<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class LeadersService
{
   protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }
   public function getAverageStatsLeaders()
    {

        // Fetch total stats for each player across all games
        // Get top 10 players by average points per game, with season and team information
        $topPoints = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats_archives.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'teams.name as team_name',
                'player_season_stats_archives.avg_points_per_game',
                'player_season_stats_archives.season_id'
            )
            ->orderByDesc('avg_points_per_game')
            ->limit(10)
            ->get();

        // Get top 10 players by average rebounds per game, with season and team information
        $topRebounds = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats_archives.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'teams.name as team_name',
                'player_season_stats_archives.avg_rebounds_per_game',
                'player_season_stats_archives.season_id'
            )
            ->orderByDesc('avg_rebounds_per_game')
            ->limit(10)
            ->get();

        // Get top 10 players by average assists per game, with season and team information
        $topAssists = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats_archives.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'teams.name as team_name',
                'player_season_stats_archives.avg_assists_per_game',
                'player_season_stats_archives.season_id'
            )
            ->orderByDesc('avg_assists_per_game')
            ->limit(10)
            ->get();

        // Get top 10 players by average steals per game, with season and team information
        $topSteals = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats_archives.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'teams.name as team_name',
                'player_season_stats_archives.avg_steals_per_game',
                'player_season_stats_archives.season_id'
            )
            ->orderByDesc('avg_steals_per_game')
            ->limit(10)
            ->get();

        // Get top 10 players by average blocks per game, with season and team information
        $topBlocks = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats_archives.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'teams.name as team_name',
                'player_season_stats_archives.avg_blocks_per_game',
                'player_season_stats_archives.season_id'
            )
            ->orderByDesc('avg_blocks_per_game')
            ->limit(10)
            ->get();


        // Return data as a JSON response
        return response()->json([
            'topPoints' => $topPoints,
            'topRebounds' => $topRebounds,
            'topAssists' => $topAssists,
            'topSteals' => $topSteals,
            'topBlocks' => $topBlocks,
        ]);
    }
    public function getTotalStatsLeaders()
    {
        // Get top 10 players by total combined points across all seasons
        $topTotalPoints = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->leftJoin('teams', 'players.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'players.is_active as is_active',
                'teams.name as team_name',
                DB::raw('SUM(player_season_stats_archives.total_points) as total_points'), // Sum total points across all seasons
                'players.id as player_id'
            )
            ->groupBy('player_season_stats_archives.player_id', 'players.name', 'teams.name', 'players.id', 'players.draft_id')
            ->orderByDesc('total_points')
            ->limit(10)
            ->get();

        // Get top 10 players by total combined rebounds across all seasons
        $topTotalRebounds = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->leftJoin('teams', 'players.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'players.is_active as is_active',
                'teams.name as team_name',
                DB::raw('SUM(player_season_stats_archives.total_rebounds) as total_rebounds'), // Sum total rebounds across all seasons
                'players.id as player_id'
            )
            ->groupBy('player_season_stats_archives.player_id', 'players.name', 'teams.name', 'players.id', 'players.draft_id')
            ->orderByDesc('total_rebounds')
            ->limit(10)
            ->get();

        // Get top 10 players by total combined assists across all seasons
        $topTotalAssists = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->leftJoin('teams', 'players.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'players.is_active as is_active',
                'teams.name as team_name',
                DB::raw('SUM(player_season_stats_archives.total_assists) as total_assists'), // Sum total assists across all seasons
                'players.id as player_id'
            )
            ->groupBy('player_season_stats_archives.player_id', 'players.name', 'teams.name', 'players.id', 'players.draft_id')
            ->orderByDesc('total_assists')
            ->limit(10)
            ->get();

        // Get top 10 players by total combined steals across all seasons
        $topTotalSteals = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->leftJoin('teams', 'players.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'players.is_active as is_active',
                'teams.name as team_name',
                DB::raw('SUM(player_season_stats_archives.total_steals) as total_steals'), // Sum total steals across all seasons
                'players.id as player_id'
            )
            ->groupBy('player_season_stats_archives.player_id', 'players.name', 'teams.name', 'players.id', 'players.draft_id')
            ->orderByDesc('total_steals')
            ->limit(10)
            ->get();

        // Get top 10 players by total combined blocks across all seasons
        $topTotalBlocks = DB::table('player_season_stats_archives')
            ->join('players', 'player_season_stats_archives.player_id', '=', 'players.id')
            ->leftJoin('teams', 'players.team_id', '=', 'teams.id')
            ->select(
                'players.draft_id as draft_id',
                'players.name as player_name',
                'players.is_active as is_active',
                'teams.name as team_name',
                DB::raw('SUM(player_season_stats_archives.total_blocks) as total_blocks'), // Sum total blocks across all seasons
                'players.id as player_id'
            )
            ->groupBy('player_season_stats_archives.player_id', 'players.name', 'teams.name', 'players.id', 'players.draft_id')
            ->orderByDesc('total_blocks')
            ->limit(10)
            ->get();

        // Return data as a JSON response
        return response()->json([
            'topTotalPoints' => $topTotalPoints,
            'topTotalRebounds' => $topTotalRebounds,
            'topTotalAssists' => $topTotalAssists,
            'topTotalSteals' => $topTotalSteals,
            'topTotalBlocks' => $topTotalBlocks,
        ]);
    }

    public function getSingleStatsLeaders()
    {

        $topSinglePoints = DB::table('top_10_single_game_points')
            ->get();
        // Highest Rebounds in a Single Game
        $topSingleRebounds = DB::table('top_10_single_game_rebounds')
            ->get();

        // Highest Assists in a Single Game
        $topSingleAssists = DB::table('top_10_single_game_assists')
            ->get();

        // Highest Blocks in a Single Game
        $topSingleBlocks = DB::table('top_10_single_game_blocks')
            ->get();

        // Highest Steals in a Single Game
        $topSingleSteals = DB::table('top_10_single_game_steals')
            ->get();

        // Return data as a JSON response
        return response()->json([
            'topSinglePoints' => $topSinglePoints,
            'topSingleRebounds' => $topSingleRebounds,
            'topSingleAssists' => $topSingleAssists,
            'topSingleBlocks' => $topSingleBlocks,
            'topSingleSteals' => $topSingleSteals
        ]);
    }
    
    public function updateAllTimeTopStats()
    {
        // Get the current season id (you can get this from your business logic or the latest game)
        $currentSeasonId = get_current_season_id();
        // Define the stat categories and corresponding columns in the player_game_stats table
        $statCategories = [
            'points' => 'pgs.points',
            'rebounds' => 'pgs.rebounds',
            'assists' => 'pgs.assists',
            'steals' => 'pgs.steals',
            'blocks' => 'pgs.blocks',
        ];

        // Iterate through each stat category
        foreach ($statCategories as $category => $column) {
            // Fetch the top 10 players for the current stat category
            $topStats = DB::table('player_game_stats as pgs')
                ->select(
                    DB::raw("'$category' AS stat_category"),
                    'pgs.player_id',
                    'players.name as player_name',
                    'pgs.game_id',
                    'pgs.team_id',
                    DB::raw("CASE WHEN pgs.team_id = schedule_view.home_id THEN schedule_view.away_id ELSE schedule_view.home_id END AS opponent_id"),
                    'pgs.season_id',
                    DB::raw("$column as stat_value")
                )
                ->join('players', 'pgs.player_id', '=', 'players.id')
                ->join('schedule_view', 'pgs.game_id', '=', 'schedule_view.game_id')
                ->where('pgs.season_id', $currentSeasonId) // Filter by current season
                ->orderByDesc($column) // Sort the stats by highest value
                ->limit(10) // Only top 10 players
                ->get();

            // Fetch the existing all-time top 10 stats for the given stat category
            $existingTopStats = DB::table('all_time_top_stats')
                ->where('stat_category', $category)
                ->orderByDesc('stat_value') // Order by stat_value descending
                ->limit(10) // Get top 10 all-time stats
                ->get();

            // Compare the current top stats with the all-time stats
            foreach ($topStats as $stat) {
                // Check if this stat qualifies to enter the all-time top 10
                $lowestStat = $existingTopStats->last(); // Get the lowest stat in the top 10

                // If there's room in the top 10 or this stat is greater than the lowest stat
                if ($existingTopStats->count() < 10 || $stat->stat_value > $lowestStat->stat_value) {
                    // If the table already has 10 stats, remove the lowest
                    if ($existingTopStats->count() == 10) {
                        DB::table('all_time_top_stats')
                            ->where('stat_category', $category)
                            ->where('stat_value', $lowestStat->stat_value)
                            ->delete();
                    }

                    // Insert the current top stat into the all-time top stats table
                    DB::table('all_time_top_stats')->insert([
                        'stat_category' => $stat->stat_category,
                        'player_id' => $stat->player_id,
                        'player_name' => $stat->player_name,
                        'game_id' => $stat->game_id,
                        'team_id' => $stat->team_id,
                        'opponent_id' => $stat->opponent_id,
                        'season_id' => $stat->season_id,
                        'stat_value' => $stat->stat_value,
                    ]);
                }
            }
        }
    }

    public function updateAllTimeTopStatsPerSeason(Request $request)
    {
        // Get the current season id (you can get this from your business logic or the latest game)
        $currentSeasonId = $request->season_id;
        // Define the stat categories and corresponding columns in the player_game_stats table
        $statCategories = [
            'points' => 'pgs.points',
            'rebounds' => 'pgs.rebounds',
            'assists' => 'pgs.assists',
            'steals' => 'pgs.steals',
            'blocks' => 'pgs.blocks',
        ];

        // Iterate through each stat category
        foreach ($statCategories as $category => $column) {
            // Fetch the top 10 players for the current stat category
            $topStats = DB::table('player_game_stats as pgs')
                ->select(
                    DB::raw("'$category' AS stat_category"),
                    'pgs.player_id',
                    'players.name as player_name',
                    'pgs.game_id',
                    'pgs.team_id',
                    DB::raw("CASE WHEN pgs.team_id = schedule_view.home_id THEN schedule_view.away_id ELSE schedule_view.home_id END AS opponent_id"),
                    'pgs.season_id',
                    DB::raw("$column as stat_value")
                )
                ->join('players', 'pgs.player_id', '=', 'players.id')
                ->join('schedule_view', 'pgs.game_id', '=', 'schedule_view.game_id')
                ->where('pgs.season_id', $currentSeasonId) // Filter by current season
                ->orderByDesc($column) // Sort the stats by highest value
                ->limit(10) // Only top 10 players
                ->get();

            // Fetch the existing all-time top 10 stats for the given stat category
            $existingTopStats = DB::table('all_time_top_stats')
                ->where('stat_category', $category)
                ->orderByDesc('stat_value') // Order by stat_value descending
                ->limit(10) // Get top 10 all-time stats
                ->get();

            // Compare the current top stats with the all-time stats
            foreach ($topStats as $stat) {
                // Check if this stat qualifies to enter the all-time top 10
                $lowestStat = $existingTopStats->last(); // Get the lowest stat in the top 10

                // If there's room in the top 10 or this stat is greater than the lowest stat
                if ($existingTopStats->count() < 10 || $stat->stat_value > $lowestStat->stat_value) {
                    // If the table already has 10 stats, remove the lowest
                    if ($existingTopStats->count() == 10) {
                        DB::table('all_time_top_stats')
                            ->where('stat_category', $category)
                            ->where('stat_value', $lowestStat->stat_value)
                            ->delete();
                    }

                    // Insert the current top stat into the all-time top stats table
                    DB::table('all_time_top_stats')->insert([
                        'stat_category' => $stat->stat_category,
                        'player_id' => $stat->player_id,
                        'player_name' => $stat->player_name,
                        'game_id' => $stat->game_id,
                        'team_id' => $stat->team_id,
                        'opponent_id' => $stat->opponent_id,
                        'season_id' => $stat->season_id,
                        'stat_value' => $stat->stat_value,
                    ]);
                }
            }
        }
    }

    public function getTop15MVPLeaders()
    {
        $topPlayers = DB::table('mvp_leaders as m')
            ->select([
                'm.player_id',
                'm.player_name',
                'm.is_rookie',
                'm.draft_status',
                'm.team_id',
                'm.team_name',
                't.primary_color',
                't.secondary_color',
                'm.games_played',
                'm.points_per_game',
                'm.rebounds_per_game',
                'm.assists_per_game',
                'm.steals_per_game',
                'm.blocks_per_game',
                'm.turnovers_per_game',
                'm.fouls_per_game',
                'm.total_points',
                'm.total_rebounds',
                'm.total_assists',
                'm.total_steals',
                'm.total_blocks',
                'm.total_turnovers',
                'm.total_fouls',
                'm.performance_score',
            ])
            ->join('teams as t', 'm.team_id', '=', 't.id')
            ->orderByDesc('m.performance_score')
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topPlayers,
        ]);
    }

    public function getTop20PlayersAllTime()
    {
        // Combine stats for players by player_id
        $top20PlayersAllTime = DB::table('player_season_stats_archives as player_season_stats')
            ->join('players', 'player_season_stats.player_id', '=', 'players.id')
            ->leftJoin('teams as current_team', 'players.team_id', '=', 'current_team.id') // Get the current team name
            ->select(
                'player_season_stats.player_id',
                'players.id as player_id',
                'players.name as player_name',
                'players.is_active as is_active',
                'current_team.id as current_team_id', // Current team of the player
                'current_team.name as current_team_name', // Current team of the player
                DB::raw('SUM(player_season_stats.total_points) as total_points'),
                DB::raw('SUM(player_season_stats.total_rebounds) as total_rebounds'),
                DB::raw('SUM(player_season_stats.total_assists) as total_assists'),
                DB::raw('SUM(player_season_stats.total_steals) as total_steals'),
                DB::raw('SUM(player_season_stats.total_blocks) as total_blocks'),
                DB::raw('SUM(player_season_stats.total_turnovers) as total_turnovers'),
                DB::raw('SUM(player_season_stats.total_fouls) as total_fouls'),
                DB::raw('COUNT(player_season_stats.season_id) as seasons_played'),
                DB::raw('(
                    SUM(player_season_stats.total_points) * 0.2 +
                    SUM(player_season_stats.total_rebounds) * 0.2 +
                    SUM(player_season_stats.total_assists) * 0.2 +
                    SUM(player_season_stats.total_steals) * 0.2 +
                    SUM(player_season_stats.total_blocks) * 0.2 - 
                    SUM(player_season_stats.total_turnovers) * 0.2 - 
                    SUM(player_season_stats.total_fouls) * 0.2
                ) as base_statistical_points')
            )
            ->groupBy(
                'player_season_stats.player_id',
                'players.id',
                'players.name',
                'players.is_active',
                'current_team.name',
                'current_team.id'
            )
            ->get();

        foreach ($top20PlayersAllTime as $player) {
            // Fetch individual awards for this player
            $awards = DB::table('season_awards')
                ->where('player_id', $player->player_id)
                ->select(
                    DB::raw('GROUP_CONCAT(DISTINCT CONCAT(award_name, " (Season ", season_id, ")") SEPARATOR ", ") as all_awards'),
                    DB::raw('COUNT(CASE WHEN award_name = "Best Overall Player" THEN 1 END) * 7 as best_overall_player_points'),
                    DB::raw('COUNT(CASE WHEN award_name = "Best Defensive Player" THEN 1 END) * 5 as best_defensive_player_points'),
                    DB::raw('COUNT(CASE WHEN award_name = "Best Overall Player" THEN 1 END) as best_overall_player_count'),
                    DB::raw('COUNT(CASE WHEN award_name = "Best Defensive Player" THEN 1 END) as best_defensive_player_count')
                )
                ->first();

            // Fetch championships won and finals MVP count for this player
            $finalsMVPCount = DB::table('seasons')
                ->where('finals_mvp_id', $player->player_id)
                ->count();

            $finalsMVPPoints = $finalsMVPCount * 6; // Each Finals MVP is worth 6 points

            // Add the additional stats to the player object
            $player->all_awards = $awards->all_awards ?? null;
            $player->best_overall_player_points = $awards->best_overall_player_points ?? 0;
            $player->best_defensive_player_points = $awards->best_defensive_player_points ?? 0;
            $player->best_overall_player_count = $awards->best_overall_player_count ?? 0;
            $player->best_defensive_player_count = $awards->best_defensive_player_count ?? 0;
            $player->finals_mvp_count = $finalsMVPCount ?? 0;
            $player->finals_mvp_points = $finalsMVPPoints;

            // Update ranking points with awards
            $player->total_statistical_points =
                $player->base_statistical_points +
                $player->best_overall_player_points +
                $player->best_defensive_player_points +
                $player->finals_mvp_points;
        }

        // Sort players by total_statistical_points in descending order
        $sortedPlayers = collect($top20PlayersAllTime)->sortByDesc('total_statistical_points')->take(20);

        return response()->json($sortedPlayers->values());
    }

    public function getTop10PlayersByTeam(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        $teamId = $request->team_id;

        // Fetch total stats for players for the given team across all seasons and also current team name
        $playerStatsForTeam = DB::table('player_season_stats_archives as player_season_stats')
            ->join('players', 'player_season_stats.player_id', '=', 'players.id')
            ->leftJoin('teams as current_team', 'players.team_id', '=', 'current_team.id') // Get the current team name
            ->select(
                'player_season_stats.player_id',
                'players.id as player_id',
                'players.name as player_name',
                'players.is_active as is_active',
                'current_team.id as current_team_id', // Current team of the player
                'current_team.name as current_team_name', // Current team of the player
                DB::raw('SUM(player_season_stats.total_points) as total_points'),
                DB::raw('SUM(player_season_stats.total_rebounds) as total_rebounds'),
                DB::raw('SUM(player_season_stats.total_assists) as total_assists'),
                DB::raw('SUM(player_season_stats.total_steals) as total_steals'),
                DB::raw('SUM(player_season_stats.total_blocks) as total_blocks'),
                DB::raw('SUM(player_season_stats.total_turnovers) as total_turnovers'),
                DB::raw('SUM(player_season_stats.total_fouls) as total_fouls'),
                DB::raw('COUNT(player_season_stats.season_id) as seasons_played'),
                DB::raw('(
                    SUM(player_season_stats.total_points) * 0.2 +
                    SUM(player_season_stats.total_rebounds) * 0.2 +
                    SUM(player_season_stats.total_assists) * 0.2 +
                    SUM(player_season_stats.total_steals) * 0.2 +
                    SUM(player_season_stats.total_blocks) * 0.2 - 
                    SUM(player_season_stats.total_turnovers) * 0.2 - 
                    SUM(player_season_stats.total_fouls) * 0.2
                ) as base_statistical_points')
            )
            ->where('player_season_stats.team_id', $teamId)
            ->groupBy(
                'player_season_stats.player_id',
                'players.id',
                'players.name',
                'players.is_active',
                'current_team.name',
                'current_team.id'
            )
            ->get();

        foreach ($playerStatsForTeam as $player) {
            // Fetch individual awards for this player
            $awards = DB::table('season_awards')
                ->where('player_id', $player->player_id)
                ->select(
                    DB::raw('GROUP_CONCAT(DISTINCT CONCAT(award_name, " (Season ", season_id, ")") SEPARATOR ", ") as all_awards'),
                    DB::raw('COUNT(CASE WHEN award_name = "Best Overall Player" THEN 1 END) * 7 as best_overall_player_points'),
                    DB::raw('COUNT(CASE WHEN award_name = "Best Defensive Player" THEN 1 END) * 5 as best_defensive_player_points'),
                    DB::raw('COUNT(CASE WHEN award_name = "Best Overall Player" THEN 1 END) as best_overall_player_count'),
                    DB::raw('COUNT(CASE WHEN award_name = "Best Defensive Player" THEN 1 END) as best_defensive_player_count')
                )
                ->first();

            // Fetch championships won and finals MVP count for this player
            $finalsMVPCount = DB::table('seasons')
                ->where('finals_mvp_id', $player->player_id)
                ->count();

            $finalsMVPPoints = $finalsMVPCount * 6; // Each Finals MVP is worth 6 points

            // Add the additional stats to the player object
            $player->all_awards = $awards->all_awards ?? null;
            $player->best_overall_player_points = $awards->best_overall_player_points ?? 0;
            $player->best_defensive_player_points = $awards->best_defensive_player_points ?? 0;
            $player->best_overall_player_count = $awards->best_overall_player_count ?? 0;
            $player->best_defensive_player_count = $awards->best_defensive_player_count ?? 0;
            $player->finals_mvp_count = $finalsMVPCount ?? 0;
            $player->finals_mvp_points = $finalsMVPPoints;

            // Update ranking points with awards
            $player->total_statistical_points =
                $player->base_statistical_points +
                $player->best_overall_player_points +
                $player->best_defensive_player_points +
                $player->finals_mvp_points;
        }

        // Sort players by total_statistical_points in descending order
        $sortedPlayers = collect($playerStatsForTeam)->sortByDesc('total_statistical_points')->take(15);

        return response()->json($sortedPlayers->values());
    }
}

