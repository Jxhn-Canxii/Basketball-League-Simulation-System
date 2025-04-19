<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Models\Schedules;
use App\Models\Seasons;
use App\Models\Teams;
use App\Models\PlayerGameStats;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PlayersController extends Controller
{

    public function index()
    {
        return Inertia::render('Players/Index', [
            'status' => session('status'),
        ]);
    }
    public function freeagents()
    {
        return Inertia::render('FreeAgents/Index', [
            'status' => session('status'),
        ]);
    }
    public function experience()
    {
        return Inertia::render('Experience/Index', [
            'status' => session('status'),
        ]);
    }
    public function listTeamRoster(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'season_id' => 'nullable|integer',
        ]);

        $teamId = $request->team_id;
        $seasonId = $request->season_id;

        // Initialize an array to hold player stats
        $playerStats = [];
        $latestSeasonId = get_current_season_id();
        // $currentSeasonId = DB::table('seasons')->max('id');
        // if (is_null($seasonId) || $seasonId == 0) {
        //     $seasonId = $latestSeasonId;
        // }
        
        // Fetch the season status
        $seasonStatus = DB::table('seasons')->where('id', $seasonId)->value('status');

        // Fetch player stats for the given team_id and season_id
        $playerStatsData = DB::table('player_season_stats')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->get();
        if (count($playerStatsData) > 0) {
            foreach ($playerStatsData as $stats) {
                // Fetch the player
                $player = DB::table('players')
                    ->select('players.*', 'teams.acronym as drafted_team', 'seasons.name as draft_class')
                    ->leftJoin('seasons', 'players.draft_id', '=', 'seasons.id')
                    ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id')
                    ->where('players.id', $stats->player_id)->first();

                if ($player) {
                    // Count the number of games played for the player
                    $gamesPlayed = DB::table('player_game_stats')
                        ->where('player_id', $player->id)
                        ->where('team_id', $teamId)
                        ->where('season_id', $seasonId)
                        ->where('minutes', '>', 0) // Only count games where minutes > 0
                        ->count(); // Directly count the rows

                    // If season status is 11 and the player has 0 games played, skip this player
                    if ($seasonStatus == 11 && $gamesPlayed == 0) {
                        continue; // Skip the rest of the logic for this player
                    }

                    // Assuming 30 minutes is the average threshold

                   
                    $seasonsPlayedWithTeam = DB::table('player_season_stats')
                        ->where('player_id', $player->id)
                        ->where('team_id', $teamId)
                        ->where('season_id', '<=', $seasonId) // Include only seasons up to the provided season_id
                        ->count('team_id');

                    $totalSeasonsPlayed = DB::table('player_season_stats')
                        ->where('player_id', $player->id)
                        ->where('season_id', '<=', $seasonId) // Include only seasons up to the provided season_id
                        ->distinct('season_id') // Ensure distinct season IDs are counted
                        ->count('season_id');

                    
                    // Add player stats to the array
                    $playerStats[] = [
                        'player_id' => $player->id,
                        'name' => $player->name,
                        'position' => $player->position,
                        'age' => $player->age,
                        'role' => $stats->role,
                        'is_active' => $player->is_active,
                        'hardship_contract' => $player->hardship_contract,
                        'injury_recovery_games' => $player->injury_recovery_games,
                        'is_rookie' => $player->is_rookie,
                        'is_injured' => $player->is_injured,
                        'contract_years' => $player->contract_years,
                        'retirement_age' => $player->retirement_age,
                        'drafted_team' => $player->drafted_team,
                        'draft_id' => $player->draft_id,
                        'draft_class' => $player->draft_class,
                        'draft_status' => $player->draft_status,
                        'overall_rating' => $player->overall_rating,
                        'status' => $player->team_id == $teamId ? ($player->is_active ? 1 : 0) : 2,
                        'average_minutes_per_game' => (float)$stats->avg_minutes_per_game,
                        'average_points_per_game' => (float)$stats->avg_points_per_game,
                        'average_rebounds_per_game' => (float)$stats->avg_rebounds_per_game,
                        'average_assists_per_game' => (float)$stats->avg_assists_per_game,
                        'average_steals_per_game' => (float)$stats->avg_steals_per_game,
                        'average_blocks_per_game' => (float)$stats->avg_blocks_per_game,
                        'average_turnovers_per_game' => (float)$stats->avg_turnovers_per_game,
                        'average_fouls_per_game' => (float)$stats->avg_fouls_per_game,
                        'bpg_game_leader' => (float)$stats->bpg_game_leader,
                        'effeciency' => (float)$stats->eff,
                        'field_goal_percentage' => (float)$stats->field_goal_percentage,
                        'three_point_percentage' => (float)$stats->three_point_percentage,
                        'two_point_percentage' => (float)$stats->two_point_percentage,
                        'free_throw_percentage' => (float)$stats->free_throw_percentage,
                        'team_total_games' => (float)$stats->total_games,
                        'games_played' => (float)$stats->total_games_played,
                        'per_game_score' => (float)$stats->per,
                        'total_score' => 0,
                        'combined_score' => 0,
                        'seasons_played_with_team' => $seasonsPlayedWithTeam,
                        'total_seasons_played' => $totalSeasonsPlayed,
                        'latest_season' => $latestSeasonId,
                        // 'status' => 'season-ongoing'
                    ];
                }
            }
        } else {
            // Fetch players from the players table and set all stats to zero

            $players = DB::table('players')
                ->select('players.*', 'teams.acronym as drafted_team', 'seasons.name as draft_class')
                ->leftJoin('seasons', 'players.draft_id', '=', 'seasons.id')
                ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id')
                ->where('team_id', $teamId)
                ->get();

            // Fetch average statistics for players
            $playerGameStats = DB::table('player_game_stats')
                ->select(
                    'player_id',
                    DB::raw('COUNT(CASE WHEN minutes > 0 THEN 1 END) as games_played'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN minutes ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_minutes'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN points ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_points'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN rebounds ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_rebounds'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN assists ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_assists'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN steals ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_steals'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN blocks ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_blocks'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN turnovers ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_turnovers'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN fouls ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_fouls'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN minutes ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_minutes'),
                    DB::raw('SUM(CASE WHEN eff > 0 THEN eff ELSE 0 END) / NULLIF(COUNT(CASE WHEN eff > 0 THEN 1 END), 0) as avg_eff'),
                    DB::raw('SUM(CASE WHEN field_goal_percentage > 0 THEN field_goal_percentage ELSE 0 END) / NULLIF(COUNT(CASE WHEN field_goal_percentage > 0 THEN 1 END), 0) as field_goal_percentage'),
                    DB::raw('SUM(CASE WHEN per > 0 THEN per ELSE 0 END) / NULLIF(COUNT(CASE WHEN per > 0 THEN 1 END), 0) as per_game_score')
                )
                ->where('season_id', $seasonId) // Filter by the specific season
                ->groupBy('player_id')
                ->get()
                ->keyBy('player_id'); // Key the result by player_id for quick lookup

            foreach ($players as $player) {
                $playerId = $player->id;

                // Default values in case there are no stats
                $stats = [
                    'average_minutes_per_game' => (float)0,
                    'average_points_per_game' => (float)0,
                    'average_rebounds_per_game' => (float)0,
                    'average_assists_per_game' => (float)0,
                    'average_steals_per_game' => (float)0,
                    'average_blocks_per_game' => (float)0,
                    'average_turnovers_per_game' => (float)0,
                    'average_fouls_per_game' => (float)0,
                    'bpg_game_leader' => (float) 0,
                    'field_goal_percentage' => (float)0,
                    'per_game_score' => (float) 0,
                    'average_eff' => (float)0,
                    'games_played' => 0,
                ];

                // If there are stats for this player, update values
                if (isset($playerGameStats[$playerId])) {
                    $stats = [
                        'average_minutes_per_game' => (float) $playerGameStats[$playerId]->avg_minutes,
                        'average_points_per_game' => (float) $playerGameStats[$playerId]->avg_points,
                        'average_rebounds_per_game' => (float) $playerGameStats[$playerId]->avg_rebounds,
                        'average_assists_per_game' => (float) $playerGameStats[$playerId]->avg_assists,
                        'average_steals_per_game' => (float) $playerGameStats[$playerId]->avg_steals,
                        'average_blocks_per_game' => (float) $playerGameStats[$playerId]->avg_blocks,
                        'average_turnovers_per_game' => (float) $playerGameStats[$playerId]->avg_turnovers,
                        'average_fouls_per_game' => (float) $playerGameStats[$playerId]->avg_fouls,
                        'bpg_game_leader' => (float) 0,
                        'field_goal_percentage' => (float) $playerGameStats[$playerId]->field_goal_percentage,
                        'per_game_score' => (float) $playerGameStats[$playerId]->per_game_score,
                        'games_played' => (int) $playerGameStats[$playerId]->games_played,
                        'average_eff' => (float) $playerGameStats[$playerId]->avg_eff,
                    ];
                }

                $totalSeasonsPlayed = DB::table('player_season_stats')
                    ->where('player_id', $playerId)
                    ->distinct('season_id') // Ensure distinct season_id values
                    ->count(); // Count the number of distinct seasons

                $seasonsPlayedWithTeam = DB::table('player_season_stats')
                    ->where('player_id', $player->id)
                    ->where('team_id', $teamId)
                    ->count('team_id');
                
                $totalSeasonGameSchedule = $this->getScheduleCount($teamId, $seasonId);
                // Only include players with games played > 0 if season status is 11
                if ($seasonStatus != 11 || $stats['games_played'] > 0) {

                    $playerStats[] = [
                        'player_id' => $playerId,
                        'name' => $player->name,
                        'position' => $player->position,
                        'age' => $player->age,
                        'role' => $player->role,
                        'is_active' => $player->is_active,
                        'is_rookie' => $player->is_rookie,
                        'contract_years' => $player->contract_years,
                        'retirement_age' => $player->retirement_age,
                        'draft_id' => $player->draft_id,
                        'drafted_team' => $player->drafted_team,
                        'draft_status' => $player->draft_status,
                        'draft_class' => $player->draft_class,
                        'overall_rating' => $player->overall_rating,
                        'status' => $player->team_id == $teamId ? ($player->is_active ? 1 : 0) : 2,
                        'average_minutes_per_game' => $stats['average_minutes_per_game'],
                        'average_points_per_game' => $stats['average_points_per_game'],
                        'average_rebounds_per_game' => $stats['average_rebounds_per_game'],
                        'average_assists_per_game' => $stats['average_assists_per_game'],
                        'average_steals_per_game' => $stats['average_steals_per_game'],
                        'average_blocks_per_game' => $stats['average_blocks_per_game'],
                        'average_turnovers_per_game' => $stats['average_turnovers_per_game'],
                        'average_fouls_per_game' => $stats['average_fouls_per_game'],
                        'bpg_game_leader' =>  $stats['bpg_game_leader'],
                        'effeciency' => number_format($stats['average_eff'],2),
                        'games_played' => $stats['games_played'],
                        'field_goal_percentage' => number_format( $stats['field_goal_percentage'],2),
                        'three_point_percentage' => number_format( $stats['three_point_percentage'],2),
                        'two_point_percentage' => number_format( $stats['two_point_percentage'],2),
                        'free_throw_percentage' =>  number_format( $stats['free_throw_percentage'],2),
                        'per_game_score' =>number_format( $stats['per_game_score'],2),
                        'total_score' => number_format(0, 2),
                        'combined_score' => number_format(0, 2),
                        'seasons_played_with_team' => $seasonsPlayedWithTeam + 1,
                        'team_total_games' => (float)$totalSeasonGameSchedule,
                        'total_seasons_played' => $totalSeasonsPlayed + 1,
                        'latest_season' => $latestSeasonId,
                        // 'status' => 'new-season'
                    ];
                }
            }
        }

        // Sort players by the combined score in descending order
        // Define role-based priority
        if (!empty($playerStats)) {
            // Define role-based priority
            $rolePriority = [
                'star player' => 1,
                'all star' => 2,
                'starter' => 3,
                'role player' => 4,
                'bench' => 5
            ];
        
            usort($playerStats, function ($a, $b) use ($rolePriority) {
                // Move players with status = 2 to the bottom
                if ($a['status'] == 2 && $b['status'] != 2) {
                    return 1; // $a goes below $b
                }
                if ($b['status'] == 2 && $a['status'] != 2) {
                    return -1; // $b goes below $a
                }
        
                // Sort by role priority (ascending order, lower number is higher priority)
                $roleA = $rolePriority[$a['role']] ?? 6; // Default to lowest priority if role is missing
                $roleB = $rolePriority[$b['role']] ?? 6;
        
                if ($roleA !== $roleB) {
                    return $roleA <=> $roleB;
                }
        
                // If roles are the same, sort by efficiency (descending order)
                return $b['per_game_score'] <=> $a['per_game_score'];
            });
        }
        
        

        return response()->json([
            'players' => $playerStats,
            'season_id' => $seasonId,
            'team_id' => $teamId,
            'stats_count' => count($playerStatsData),
        ]);
    }

    public function getFreeAgents(Request $request)
    {
        // Get pagination parameters from the request
        $perPage = $request->input('itemsperpage', 10); // Number of items per page
        $currentPage = $request->input('page_num', 1); // Current page number
        $search = $request->input('search', ''); // Search term

        // Build the query with optional search filter
        $query = Player::select(
            'players.*',
            'teams.acronym as drafted_team',
            DB::raw("(SELECT GROUP_CONCAT(CONCAT(award_name, ' (Season ', season_id, ')') SEPARATOR ', ') FROM season_awards WHERE season_awards.player_id = players.id) as awards"),
            DB::raw("(SELECT  CONCAT('Finals MVP (Season ', seasons.id, ')')  FROM seasons WHERE seasons.finals_mvp_id = players.id LIMIT 1) as finals_mvp"),
            DB::raw("CASE WHEN players.id = (SELECT finals_mvp_id FROM seasons WHERE seasons.finals_mvp_id = players.id) THEN 1 ELSE 0 END as is_finals_mvp"),
            DB::raw("(SELECT GROUP_CONCAT(seasons.name SEPARATOR ', ') FROM seasons WHERE seasons.finals_mvp_id = players.id) as finals_mvp_seasons")
        )
            ->where('players.contract_years', 0)
            ->where('players.is_active', 1)
            ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id'); // Join teams on players.drafted_team_id

        // Apply search filter if provided
        if ($search) {
            $query->where('players.name', 'like', "%{$search}%");
        }

        // Add ordering for awards, finals MVP status, and role priority
        $query->orderByRaw("
            LENGTH(awards) DESC,
            is_finals_mvp DESC,
            FIELD(role, 'star player','all star', 'starter', 'role player', 'bench')
        ");

        // Get total number of records
        $total = $query->count();

        // Calculate the offset for the query
        $offset = ($currentPage - 1) * $perPage;

        // Fetch the paginated data
        $freeAgents = $query->offset($offset)
            ->limit($perPage)
            ->get();

        // Calculate total pages
        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'free_agents' => $freeAgents,
        ]);
    }

    public function getAllPlayers(Request $request)
    {
        // Get pagination parameters from the request
        $perPage = $request->input('itemsperpage', 10); // Number of items per page
        $currentPage = $request->input('page_num', 1); // Current page number
        $search = $request->input('search', ''); // Search term

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
                DB::raw("IF(players.team_id = 0, 'none', teams.name) as team_name"),

                // Get the list of awards for the player
                DB::raw("
                    (SELECT GROUP_CONCAT(
                            CONCAT(award_name, ' (Season ', season_awards.season_id, ')')
                            SEPARATOR ', ')
                     FROM season_awards
                     WHERE season_awards.player_id = players.id
                    ) as awards
                "),

                // Get the Finals MVP for the player, if applicable
                DB::raw("
                    COALESCE(
                        (SELECT
                            CONCAT('Finals MVP (Season ', seasons.id, ')')
                         FROM seasons
                         WHERE seasons.finals_mvp_id = players.id
                         LIMIT 1
                        ), '') as finals_mvp
                "),

                // Check if player is finals MVP (this is somewhat redundant with the previous subquery)
                DB::raw("CASE WHEN players.id = (SELECT finals_mvp_id FROM seasons WHERE seasons.finals_mvp_id = players.id) THEN 1 ELSE 0 END as is_finals_mvp")
            )
            ->leftJoin('teams', 'players.team_id', '=', 'teams.id');

        // Apply search filter if provided
        if ($search) {
            $query->where('players.name', 'like', "%{$search}%");
        }

        // Add sorting by is_active status, then by role priority
        $query->orderBy('players.is_active', 'desc') // Active players first
            ->orderByRaw("FIELD(players.role, 'star player','all star', 'starter', 'role player', 'bench')");

        // Get total number of records
        $total = $query->count();

        // Fetch the paginated data
        $freeAgents = $query->offset($offset)
            ->limit($perPage)
            ->get();

        // Calculate total pages
        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'free_agents' => $freeAgents,
        ]);
    }
    // Add a player to a team with random attributes
    public function addPlayer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'team_id' => 'required|exists:teams,id',
        ]);

        // Check if the team already has 15 players
        $playerCount = Player::where('team_id', $request->team_id)
            ->where('is_active', 1) // Ensure players are active
            ->count();

        if ($playerCount >= 15) {
            return response()->json([
                'error' => true,
                'message' => 'Team already has 15 players. Cannot add more.',
            ], 400);
        }

        // Check if a player with the same name already exists in any team
        $existingPlayer = Player::where('name', $request->name)->first();
        if ($existingPlayer) {
            return response()->json([
                'error' => true,
                'message' => 'A player with this name already exists in another team.',
            ], 400);
        }

        // Generate random attributes
        $age = mt_rand(18, 25);
        $retirementAge = rand($age + 1, 45); // Retirement age should be greater than current age
        $injuryPronePercentage = rand(0, 100); // Random injury-prone percentage between 0 and 100
        $contractYears = rand(1, 5); // Random contract years between 1 and 5

        // Randomize player role
        $roles = ['starter','all star', 'star player', 'role player', 'bench'];
        $role = $roles[array_rand($roles)];

        // Randomize player ratings
        $shootingRating = rand(1, 100);
        $defenseRating = rand(1, 100);
        $passingRating = rand(1, 100);
        $reboundingRating = rand(1, 100);
        $overallRating = ($shootingRating + $defenseRating + $passingRating + $reboundingRating) / 4;

        // Calculate contract expiration date
        $contractExpiresAt = Carbon::now()->addYears($contractYears);

        $player = Player::create([
            'name' => $request->name,
            'team_id' => $request->team_id,
            'age' => $age,
            'retirement_age' => $retirementAge,
            'injury_prone_percentage' => $injuryPronePercentage,
            'contract_years' => $contractYears,
            'contract_expires_at' => $contractExpiresAt,
            'is_active' => true,
            'role' => $role,
            'shooting_rating' => $shootingRating,
            'defense_rating' => $defenseRating,
            'passing_rating' => $passingRating,
            'rebounding_rating' => $reboundingRating,
            'overall_rating' => $overallRating,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Player added successfully',
            'player' => $player,
        ]);
    }
    

    public function addFreeAgentPlayer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:players,name',
            'address' => 'required|string|max:255',
            'country' => 'required|string',
        ]);

        $latestSeasonId = get_current_season_id();
        $currentSeasonId = $latestSeasonId ? (int) $latestSeasonId + 1 : 1;

        // Check if player already exists
        $existingPlayer = Player::where('name', $request->name)->first();
        if ($existingPlayer) {
            return response()->json([
                'error' => true,
                'message' => 'A player with this name already exists in another team.',
            ], 400);
        }

        // Generate player attributes
        $age = rand(18, 25);
        $contractYears = rand(1, 5);
        $attributes = $this->getRandomArchetypeAndAttributes();

        // Check generational limit
        if ($attributes['archetype'] === 'generational') {
            $generationalCount = Player::where('is_rookie', true)
                ->where('type', $attributes['archetype'])
                ->count();

            $maxGenLimit = ($currentSeasonId % 4 == 0) ? 30 : 15;
            if ($generationalCount >= $maxGenLimit) {
                return response()->json(['error' => 'Maximum limit of '.$maxGenLimit.' generational rookies reached for this season.'], 400);
            }
        }

        // 📌 Check and prioritize underfilled positions (including hybrids)
        $totalTeams = DB::table('teams')->count();
        $requiredPlayersPerPosition = $totalTeams * 5;

        $corePositions = ['PG', 'SG', 'SF', 'PF', 'C'];
        $underfilledPositions = [];

        foreach ($corePositions as $corePos) {
            $count = DB::table('players')
                ->where(function ($query) use ($corePos) {
                    $query->where('position', 'like', $corePos)
                        ->orWhere('position', 'like', $corePos . '/%')
                        ->orWhere('position', 'like', '%/' . $corePos)
                        ->orWhere('position', 'like', '%/' . $corePos . '/%');
                })
                ->where('is_active', 1)
                ->count();

            if ($count < $requiredPlayersPerPosition) {
                $underfilledPositions[] = $corePos;
            }
        }

        // Assign position based on need
        if (!empty($underfilledPositions)) {
            $forcedCore = $underfilledPositions[array_rand($underfilledPositions)];
            $possibleHybrids = [
                'PG' => ['PG', 'PG/SG'],
                'SG' => ['SG', 'SG/SF', 'PG/SG'],
                'SF' => ['SF', 'SF/PF', 'SG/SF'],
                'PF' => ['PF', 'PF/C', 'SF/PF'],
                'C'  => ['C', 'PF/C'],
            ];
            $position = $possibleHybrids[$forcedCore][array_rand($possibleHybrids[$forcedCore])];
        } else {
            $position = $attributes['position'];
        }

        // Assign other attributes
        $selectedArchetype = $attributes['archetype'];
        $shootingRating = $attributes['shooting_rating'];
        $defenseRating = $attributes['defense_rating'];
        $passingRating = $attributes['passing_rating'];
        $reboundingRating = $attributes['rebounding_rating'];
        $athleticism = $attributes['athleticism_rating'];
        $basketballIq = $attributes['basketball_iq_rating'];
        $strength = $attributes['strength_rating'];
        $stamina = $attributes['stamina_rating'];
        $clutch = $attributes['clutch_rating'];
        $leadership = $attributes['leadership_rating'];
        $workEthic = $attributes['work_ethic_rating'];
        $twoPointRating = $attributes['two_point_rating'];
        $threePointRating = $attributes['three_point_rating'];
        $freeThrowRating = $attributes['free_throw_rating'];
        $injuryPercentage = $attributes['health_rating'];
        $healthRatings = 99 - $injuryPercentage;

        $overallRating = round((
            $defenseRating + $passingRating + $reboundingRating + 
            $athleticism + $basketballIq + $strength + $stamina + 
            $clutch + $leadership + $workEthic + $healthRatings +
            $twoPointRating + $threePointRating + $freeThrowRating
        ) / 14, 2);

        if ($overallRating >= 90) {
            $role = 'star player';
        } elseif ($overallRating >= 85) {
            $role = 'all star';
        } elseif ($overallRating >= 75) {
            $role = 'starter';
        } elseif ($overallRating >= 60) {
            $role = 'role player';
        } else {
            $role = 'bench';
        }

        $contractExpiresAt = Carbon::now()->addYears($contractYears);
        $minRetirementAge = max($age + 1, 35);
        $maxRetirementAge = 45 - (int)((99 - $healthRatings) / 5);
        $maxRetirementAge = max($minRetirementAge, $maxRetirementAge);
        $retirementAge = rand($minRetirementAge, $maxRetirementAge);

        // Save the player
        $player = Player::create([
            'name' => $request->name,
            'address' => $request->address,
            'country' => $request->country,
            'team_id' => 0,
            'age' => $age,
            'retirement_age' => $retirementAge,
            'injury_prone_percentage' => $injuryPercentage,
            'contract_years' => 0,
            'contract_expires_at' => $contractExpiresAt,
            'is_active' => true,
            'role' => $role,
            'position' => $position,
            'type' => $selectedArchetype, 
            'shooting_rating' => $shootingRating,
            'defense_rating' => $defenseRating,
            'passing_rating' => $passingRating,
            'rebounding_rating' => $reboundingRating,
            'athleticism_rating' => $athleticism,
            'basketball_iq_rating' => $basketballIq,
            'strength_rating' => $strength,
            'stamina_rating' => $stamina,
            'clutch_rating' => $clutch,
            'leadership_rating' => $leadership,
            'work_ethic_rating' => $workEthic,
            'two_point_rating' => $twoPointRating,
            'three_point_rating' => $threePointRating,
            'free_throw_rating' => $freeThrowRating,
            'overall_rating' => $overallRating,
            'draft_id' => $currentSeasonId,
            'draft_order' => 0,
            'drafted_team_id' => 0,
            'is_drafted' => 0,
            'draft_status' => 'Undrafted',
            'is_rookie' => true,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Player added successfully',
            'player' => $player,
        ]);
    }

    /**
     * Get a random archetype and its attributes.
     *
     * @return array
     */
    private function getRandomArchetypeAndAttributes()
    {
        $seasonId = get_current_season_id();  // Get latest season
        
        // Define archetypes with expanded attributes
        $archetypes = [
            'playmaker' => [
                'shooting' => [70, 85], 'defense' => [65, 80], 'passing' => [85, 99], 'rebounding' => [60, 75],
                'athleticism' => [75, 90], 'basketball_iq' => [85, 99], 'strength' => [60, 75], 'stamina' => [80, 95],
                'clutch' => [70, 90], 'leadership' => [80, 95], 'work_ethic' => [75, 90],
                'two_point_rating' => [75, 90], 'three_point_rating' => [65, 80], 'free_throw_rating' => [70, 85], 'health_rating' => [0,100]
            ],
            'defender' => [
                'shooting' => [60, 75], 'defense' => [85, 99], 'passing' => [60, 75], 'rebounding' => [70, 85],
                'athleticism' => [70, 85], 'basketball_iq' => [75, 90], 'strength' => [75, 90], 'stamina' => [75, 90],
                'clutch' => [65, 85], 'leadership' => [70, 85], 'work_ethic' => [80, 95],
                'two_point_rating' => [65, 80], 'three_point_rating' => [55, 70], 'free_throw_rating' => [60, 75], 'health_rating' => [0,100]
            ],
            'scorer' => [
                'shooting' => [85, 99], 'defense' => [60, 75], 'passing' => [65, 80], 'rebounding' => [60, 75],
                'athleticism' => [80, 95], 'basketball_iq' => [70, 85], 'strength' => [65, 80], 'stamina' => [75, 90],
                'clutch' => [85, 99], 'leadership' => [70, 85], 'work_ethic' => [70, 85],
                'two_point_rating' => [85, 99], 'three_point_rating' => [80, 95], 'free_throw_rating' => [75, 90], 'health_rating' => [0,100]
            ],
            'sharpshooter' => [
                'shooting' => [90, 99], 'defense' => [55, 70], 'passing' => [60, 75], 'rebounding' => [50, 65],
                'athleticism' => [70, 85], 'basketball_iq' => [80, 95], 'strength' => [50, 65], 'stamina' => [75, 90],
                'clutch' => [80, 95], 'leadership' => [70, 85], 'work_ethic' => [75, 90],
                'two_point_rating' => [75, 90], 'three_point_rating' => [90, 99], 'free_throw_rating' => [85, 99], 'health_rating' => [0,100]
            ],
            'big_man' => [
                'shooting' => [60, 75], 'defense' => [75, 90], 'passing' => [55, 70], 'rebounding' => [85, 99],
                'athleticism' => [70, 85], 'basketball_iq' => [75, 90], 'strength' => [85, 99], 'stamina' => [75, 90],
                'clutch' => [65, 80], 'leadership' => [70, 85], 'work_ethic' => [80, 95],
                'two_point_rating' => [80, 95], 'three_point_rating' => [50, 65], 'free_throw_rating' => [55, 70], 'health_rating' => [0,100]
            ],
            'generational' => [
                'shooting' => [95, 99], 'defense' => [95, 99], 'passing' => [95, 99], 'rebounding' => [95, 99],
                'athleticism' => [95, 99], 'basketball_iq' => [95, 99], 'strength' => [95, 99], 'stamina' => [95, 99],
                'clutch' => [95, 99], 'leadership' => [95, 99], 'work_ethic' => [95, 99],
                'two_point_rating' => [95, 99], 'three_point_rating' => [95, 99], 'free_throw_rating' => [95, 99], 'health_rating' => [0,10]
            ],
            'athletic_finisher' => [
                'shooting' => [70, 85], 'defense' => [75, 90], 'passing' => [60, 75], 'rebounding' => [80, 95],
                'athleticism' => [90, 99], 'basketball_iq' => [75, 90], 'strength' => [80, 95], 'stamina' => [80, 95],
                'clutch' => [75, 90], 'leadership' => [70, 85], 'work_ethic' => [80, 95],
                'two_point_rating' => [85, 99], 'three_point_rating' => [60, 75], 'free_throw_rating' => [70, 85], 'health_rating' => [0,100]
            ],
            'slasher' => [
                'shooting' => [75, 90], 'defense' => [70, 85], 'passing' => [60, 75], 'rebounding' => [65, 80],
                'athleticism' => [90, 99], 'basketball_iq' => [70, 85], 'strength' => [75, 90], 'stamina' => [75, 90],
                'clutch' => [70, 85], 'leadership' => [60, 75], 'work_ethic' => [75, 90],
                'two_point_rating' => [85, 99], 'three_point_rating' => [65, 80], 'free_throw_rating' => [70, 85], 'health_rating' => [0,100]
            ],
            'stretch_four' => [
                'shooting' => [80, 95], 'defense' => [70, 85], 'passing' => [65, 80], 'rebounding' => [75, 90],
                'athleticism' => [75, 90], 'basketball_iq' => [75, 90], 'strength' => [70, 85], 'stamina' => [75, 90],
                'clutch' => [75, 90], 'leadership' => [70, 85], 'work_ethic' => [75, 90],
                'two_point_rating' => [75, 90], 'three_point_rating' => [85, 99], 'free_throw_rating' => [75, 85], 'health_rating' => [0,100]
            ],
            'sixth_man' => [
                'shooting' => [75, 90], 'defense' => [70, 85], 'passing' => [65, 80], 'rebounding' => [65, 80],
                'athleticism' => [80, 95], 'basketball_iq' => [70, 85], 'strength' => [65, 80], 'stamina' => [75, 90],
                'clutch' => [85, 99], 'leadership' => [70, 85], 'work_ethic' => [80, 95],
                'two_point_rating' => [75, 90], 'three_point_rating' => [70, 85], 'free_throw_rating' => [75, 90], 'health_rating' => [0,100]
            ],
            'floor_general' => [
                'shooting' => [80, 90], 'defense' => [70, 85], 'passing' => [90, 99], 'rebounding' => [60, 75],
                'athleticism' => [75, 90], 'basketball_iq' => [90, 99], 'strength' => [65, 80], 'stamina' => [80, 95],
                'clutch' => [75, 90], 'leadership' => [90, 99], 'work_ethic' => [80, 95],
                'two_point_rating' => [80, 90], 'three_point_rating' => [70, 85], 'free_throw_rating' => [75, 90], 'health_rating' => [0,100]
            ]
        ];
        
        
    
        // Check if next season allows generational players
        $nextSeasonId = $seasonId + 1;
        if ($nextSeasonId % 4 === 0) {
            $archetypesToChooseFrom = array_merge($archetypes, [
                'generational' => $archetypes['generational']
            ]);
        } else {
            $archetypesToChooseFrom = $archetypes;
        }
    
        // Select archetype
        $archetypeKeys = array_keys($archetypesToChooseFrom);
        $selectedArchetype = $archetypeKeys[array_rand($archetypeKeys)];
        $archetypeAttributes = $archetypesToChooseFrom[$selectedArchetype];
    
        // Generate ratings
       
        $shooting = rand($archetypeAttributes['shooting'][0], $archetypeAttributes['shooting'][1]);
        $twoPoint = rand($archetypeAttributes['two_point_rating'][0], $archetypeAttributes['two_point_rating'][1]);
        $threePoint = rand($archetypeAttributes['three_point_rating'][0], $archetypeAttributes['three_point_rating'][1]);
        $freeThrow = rand($archetypeAttributes['free_throw_rating'][0], $archetypeAttributes['free_throw_rating'][1]);
        $defense = rand($archetypeAttributes['defense'][0], $archetypeAttributes['defense'][1]);
        $passing = rand($archetypeAttributes['passing'][0], $archetypeAttributes['passing'][1]);
        $rebounding = rand($archetypeAttributes['rebounding'][0], $archetypeAttributes['rebounding'][1]);
        $athleticism = rand($archetypeAttributes['athleticism'][0], $archetypeAttributes['athleticism'][1]);
        $basketballIq = rand($archetypeAttributes['basketball_iq'][0], $archetypeAttributes['basketball_iq'][1]);
        $strength = rand($archetypeAttributes['strength'][0], $archetypeAttributes['strength'][1]);
        $stamina = rand($archetypeAttributes['stamina'][0], $archetypeAttributes['stamina'][1]);
        $clutch = rand($archetypeAttributes['clutch'][0], $archetypeAttributes['clutch'][1]);
        $leadership = rand($archetypeAttributes['leadership'][0], $archetypeAttributes['leadership'][1]);
        $workEthic = rand($archetypeAttributes['work_ethic'][0], $archetypeAttributes['work_ethic'][1]);
        $healthRating = $this->generateHealthRating(); // i dont use arhetype health rating property hence i use probability
    
    
        // Assign position
        if ($passing >= 85) {
            if ($shooting >= 80) {
                $position = 'PG/SG';
            } elseif ($defense >= 80) {
                $position = 'PG/SF'; // crafty, defensive-minded PG
            } else {
                $position = 'PG';
            }
        } elseif ($shooting >= 85) {
            if ($defense >= 75) {
                $position = 'SG/SF';
            } elseif ($rebounding >= 70) {
                $position = 'SG/PF'; // aggressive shooting forward
            } else {
                $position = 'SG';
            }
        } elseif ($defense >= 85) {
            if ($rebounding >= 75) {
                $position = 'SF/PF';
            } elseif ($passing >= 70) {
                $position = 'SF/PG'; // defensive wing who can handle
            } else {
                $position = 'SF';
            }
        } elseif ($rebounding >= 85) {
            if ($defense >= 75) {
                $position = 'PF/C';
            } elseif ($passing >= 75) {
                $position = 'PF/PG'; // rare playmaking forward
            } else {
                $position = 'C';
            }
        } else {
            // Fallback for balanced or undeveloped players
            $hybrids = ['PG', 'SG', 'SF', 'PF', 'C', 'PG/SG', 'SG/SF', 'SF/PF', 'PF/C', 'SG/PF', 'SF/C', 'PG/SF'];
            $position = $hybrids[array_rand($hybrids)];
        }
        
    
        return [
            'archetype' => $selectedArchetype,
            'position' => $position,
            'shooting_rating' => $shooting,
            'two_point_rating' => $twoPoint,
            'three_point_rating' => $threePoint,
            'free_throw_rating' => $freeThrow,
            'defense_rating' => $defense,
            'passing_rating' => $passing,
            'rebounding_rating' => $rebounding,
            'athleticism_rating' => $athleticism,
            'basketball_iq_rating' => $basketballIq,
            'strength_rating' => $strength,
            'stamina_rating' => $stamina,
            'clutch_rating' => $clutch,
            'leadership_rating' => $leadership,
            'work_ethic_rating' => $workEthic,
            'health_rating' => $healthRating,
        ];
    }
    
    public function getPlayerSeasonPerformance(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);
    
        $playerId = $request->player_id;
    
        // Fetch player season stats for the given player
        $playerStats = \DB::table('player_season_stats')
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
            'seasons.name as season_name', // Select season name
            \DB::raw('GROUP_CONCAT(DISTINCT teams.name ORDER BY teams.name ASC) as team_names'), // Concatenate team names, ordered
            \DB::raw('COALESCE(player_ratings.role, players.role) as player_role'), // Use COALESCE to handle NULL roles
            \DB::raw('AVG(player_season_stats.avg_points_per_game) as avg_points_per_game'),
            \DB::raw('AVG(player_season_stats.avg_rebounds_per_game) as avg_rebounds_per_game'),
            \DB::raw('AVG(player_season_stats.avg_assists_per_game) as avg_assists_per_game'),
            \DB::raw('AVG(player_season_stats.avg_steals_per_game) as avg_steals_per_game'),
            \DB::raw('AVG(player_season_stats.avg_blocks_per_game) as avg_blocks_per_game'),
            \DB::raw('AVG(player_season_stats.avg_turnovers_per_game) as avg_turnovers_per_game'),
            \DB::raw('AVG(player_season_stats.avg_fouls_per_game) as avg_fouls_per_game'),
            \DB::raw('SUM(player_season_stats.total_points) as total_points'),
            \DB::raw('SUM(player_season_stats.total_rebounds) as total_rebounds'),
            \DB::raw('SUM(player_season_stats.total_assists) as total_assists'),
            \DB::raw('SUM(player_season_stats.total_steals) as total_steals'),
            \DB::raw('SUM(player_season_stats.total_blocks) as total_blocks'),
            \DB::raw('SUM(player_season_stats.total_turnovers) as total_turnovers'),
            \DB::raw('SUM(player_season_stats.total_fouls) as total_fouls'),
            \DB::raw('SUM(player_season_stats.total_minutes_played) as total_minutes_played'),
            \DB::raw('SUM(player_season_stats.total_games_played) as total_games_played'),
            \DB::raw('AVG(player_season_stats.per) as per'),
            \DB::raw('AVG(player_season_stats.ts_percent) as ts_percent'),
            \DB::raw('AVG(player_season_stats.eff) as eff'), // Efficiency
            \DB::raw('SUM(player_season_stats.total_field_goals_made) as total_field_goals_made'),
            \DB::raw('SUM(player_season_stats.total_field_goal_attempts) as total_field_goal_attempts'),
            \DB::raw('SUM(player_season_stats.total_two_pointers_made) as total_two_pointers_made'),
            \DB::raw('SUM(player_season_stats.total_two_point_attempts) as total_two_point_attempts'),
            \DB::raw('SUM(player_season_stats.total_three_pointers_made) as total_three_pointers_made'),
            \DB::raw('SUM(player_season_stats.total_three_point_attempts) as total_three_point_attempts'),
            \DB::raw('SUM(player_season_stats.total_free_throws_made) as total_free_throws_made'),
            \DB::raw('SUM(player_season_stats.total_free_throw_attempts) as total_free_throw_attempts')
        )
        ->where('player_season_stats.player_id', $playerId)
        ->groupBy(
            'players.id', 'players.name', 'player_season_stats.season_id', 
            'player_ratings.overall_rating', 'seasons.name','player_ratings.role','players.role'
        )
        ->orderBy('player_season_stats.season_id', 'desc') // Sort by season_id in descending order
        ->get();
    
    
        if ($playerStats->isEmpty()) {
            return response()->json([
                'error' => 'No stats found for the given player.',
                'player_stats' => [],
            ], 404);
        }
    
        // Initialize an array to hold formatted player stats
        $formattedPlayerStats = [];
    
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
    

    public function getPlayerPlayoffPerformance(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);
    
        $playerId = $request->player_id;
    
        // Fetch player stats for the given player across specified playoff rounds
        $playerStats = \DB::table('player_game_stats')
            ->join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->join('teams', 'player_game_stats.team_id', '=', 'teams.id')
            ->join('seasons', 'player_game_stats.season_id', '=', 'seasons.id') // Join with seasons table
            ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
            ->leftJoin('player_ratings', function ($join) {
                $join->on('player_game_stats.player_id', '=', 'player_ratings.player_id')
                    ->on('player_game_stats.season_id', '=', 'player_ratings.season_id');
            }) // Left join with player_ratings table
            ->select(
                'players.id as player_id',
                'players.name as player_name',
                'players.team_id',
                'teams.name as team_name',
                'teams.conference_id',
                'player_game_stats.season_id',
                'player_ratings.overall_rating',
                'seasons.name as season_name', // Select season name
                \DB::raw('SUM(player_game_stats.points) as total_points'),
                \DB::raw('SUM(player_game_stats.rebounds) as total_rebounds'),
                \DB::raw('SUM(player_game_stats.assists) as total_assists'),
                \DB::raw('SUM(player_game_stats.steals) as total_steals'),
                \DB::raw('SUM(player_game_stats.blocks) as total_blocks'),
                \DB::raw('SUM(player_game_stats.turnovers) as total_turnovers'),
                \DB::raw('SUM(player_game_stats.fouls) as total_fouls'),
                \DB::raw('COUNT(DISTINCT CASE WHEN player_game_stats.minutes > 0 THEN player_game_stats.game_id END) as games_played'), // Exclude DNP games
                \DB::raw('COALESCE(player_ratings.role, players.role) as role'), // Use COALESCE to handle NULL roles
                \DB::raw('SUM(player_game_stats.field_goal_attempts) as total_field_goal_attempts'),
                \DB::raw('SUM(player_game_stats.field_goals_made) as total_field_goals_made'),
                \DB::raw('SUM(player_game_stats.two_point_attempts) as total_two_point_attempts'),
                \DB::raw('SUM(player_game_stats.two_pointers_made) as total_two_pointers_made'),
                \DB::raw('SUM(player_game_stats.three_point_attempts) as total_three_point_attempts'),
                \DB::raw('SUM(player_game_stats.three_pointers_made) as total_three_pointers_made'),
                \DB::raw('SUM(player_game_stats.free_throw_attempts) as total_free_throw_attempts'),
                \DB::raw('SUM(player_game_stats.free_throws_made) as total_free_throws_made')
            )
            ->where('player_game_stats.player_id', $playerId)
            ->whereIn('schedules.round', config('playoffs')) // Filter by playoff rounds
            ->groupBy('players.id', 'players.name', 'players.team_id', 'players.role', 'player_ratings.overall_rating', 'teams.name', 'teams.conference_id', 'player_game_stats.season_id', 'seasons.name', 'player_ratings.role')
            ->orderBy('player_game_stats.season_id', 'desc') // Sort by season_id in descending order
            ->get();
    
        if ($playerStats->isEmpty()) {
            return response()->json([
                'error' => 'No stats found for the given player.',
                'player_stats' => [],
            ], 404);
        }
    
        // Initialize an array to hold formatted player stats
        $formattedPlayerStats = [];
    
        foreach ($playerStats as $stats) {
            // Calculate averages
            $averagePointsPerGame = $stats->games_played > 0 ? $stats->total_points / $stats->games_played : 0;
            $averageReboundsPerGame = $stats->games_played > 0 ? $stats->total_rebounds / $stats->games_played : 0;
            $averageAssistsPerGame = $stats->games_played > 0 ? $stats->total_assists / $stats->games_played : 0;
            $averageStealsPerGame = $stats->games_played > 0 ? $stats->total_steals / $stats->games_played : 0;
            $averageBlocksPerGame = $stats->games_played > 0 ? $stats->total_blocks / $stats->games_played : 0;
            $averageTurnoversPerGame = $stats->games_played > 0 ? $stats->total_turnovers / $stats->games_played : 0;
            $averageFoulsPerGame = $stats->games_played > 0 ? $stats->total_fouls / $stats->games_played : 0;
    
            // Calculate shooting percentages (ensure no division by zero)
            $fieldGoalPercentage = $stats->total_field_goal_attempts > 0 ? ($stats->total_field_goals_made / $stats->total_field_goal_attempts) * 100 : 0;
            $twoPointPercentage = $stats->total_two_point_attempts > 0 ? ($stats->total_two_pointers_made / $stats->total_two_point_attempts) * 100 : 0;
            $threePointPercentage = $stats->total_three_point_attempts > 0 ? ($stats->total_three_pointers_made / $stats->total_three_point_attempts) * 100 : 0;
            $freeThrowPercentage = $stats->total_free_throw_attempts > 0 ? ($stats->total_free_throws_made / $stats->total_free_throw_attempts) * 100 : 0;
    
            // Append player with stats and team name
            $formattedPlayerStats[] = [
                'player_id' => $stats->player_id,
                'player_name' => $stats->player_name,
                'overall_rating' => $stats->overall_rating,
                'team_name' => $stats->team_name,
                'team_id' => $stats->team_id,
                'conference_id' => $stats->conference_id,
                'role' => $stats->role, // Add player role
                'season_id' => $stats->season_id,
                'season_name' => $stats->season_name, // Add season name
                'total_points' => $stats->total_points,
                'total_rebounds' => $stats->total_rebounds,
                'total_assists' => $stats->total_assists,
                'total_steals' => $stats->total_steals,
                'total_blocks' => $stats->total_blocks,
                'total_turnovers' => $stats->total_turnovers,
                'total_fouls' => $stats->total_fouls,
                'games_played' => $stats->games_played,
                'average_points_per_game' => $averagePointsPerGame,
                'average_rebounds_per_game' => $averageReboundsPerGame,
                'average_assists_per_game' => $averageAssistsPerGame,
                'average_steals_per_game' => $averageStealsPerGame,
                'average_blocks_per_game' => $averageBlocksPerGame,
                'average_turnovers_per_game' => $averageTurnoversPerGame,
                'average_fouls_per_game' => $averageFoulsPerGame,
                'field_goal_percentage' => $fieldGoalPercentage,
                'two_point_percentage' => $twoPointPercentage,
                'three_point_percentage' => $threePointPercentage,
                'free_throw_percentage' => $freeThrowPercentage,
            ];
        }
    
        return response()->json([
            'player_stats' => $formattedPlayerStats,
        ]);
    }
    

    public function getPlayerMainPerformance(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        $playerId = $request->player_id;

        // Fetch player and team details
        $playerDetails = \DB::table('players')
            ->join('teams', 'players.team_id', '=', 'teams.id', 'left') // Join teams table to get team details
            ->join('teams as drafted_teams', 'players.drafted_team_id', '=', 'drafted_teams.id', 'left') // Join teams table to get team details
            ->join('seasons', 'players.draft_id', '=', 'seasons.id', 'left')
            ->where('players.id', $playerId)
            ->select('players.id as player_id','players.type as archetype','players.hardship_contract','players.position as position', 'players.name as player_name','players.injury_prone_percentage', 'players.country as country', 'players.address as address', 'players.age as age', 'players.retirement_age as retirement_age', 'teams.name as team_name', 'players.role', 'players.contract_years', 'players.is_rookie', 'players.overall_rating','players.shooting_rating','players.defense_rating','players.passing_rating','players.rebounding_rating', 'players.type', 'players.draft_status as draft_status', 'seasons.name as draft_class', 'drafted_teams.acronym as drafted_team','players.injury_recovery_games as injury_recovery_game_count')
            ->first();

        if (!$playerDetails) {
            return response()->json([
                'error' => 'Player not found.',
            ], 404);
        }

        // Fetch playoff performance
        $playoffPerformance = \DB::table('player_playoff_appearances')
        ->select(
            'round_of_16_appearances',
            'quarter_finals_appearances',
            'semi_finals_appearances',
            'interconference_semi_finals_appearances',
            'finals_appearances',
            'play_ins_finals_appearances',
            'play_ins_elims_round_1_appearances',
            'play_ins_elims_round_2_appearances'
        )
        ->where('player_id', $playerId)
        ->first();
    
        // Set default values if no performance data found
        $playoffPerformance = $playoffPerformance ?: (object)[
            'round_of_16_appearances' => 0,
            'quarter_finals_appearances' => 0,
            'semi_finals_appearances' => 0,
            'interconference_semi_finals_appearances' => 0,
            'finals_appearances' => 0,
            'play_ins_finals_appearances' => 0,
            'play_ins_elims_round_1_appearances' => 0,
            'play_ins_elims_round_2_appearances' => 0
        ];
        
        // Fetch MVP count and seasons
        $awardsData = \DB::table('season_awards')
            ->join('players', 'season_awards.player_id', '=', 'players.id')
            ->join('teams', 'season_awards.team_id', '=', 'teams.id')
            ->leftJoin('seasons', 'season_awards.season_id', '=', 'seasons.id') // Join the seasons table
            ->where('season_awards.player_id', $playerId)
            ->select(
                'season_awards.award_name as award_name',
                'season_awards.season_id as season',
                'seasons.name as season_name', // Select the season name
                'teams.name as team_name'
            )
            ->distinct()
            ->get();


        // Fetch MVP count and seasons
        $mvpData = \DB::table('seasons')
            ->where('seasons.finals_mvp_id', $playerId)
            ->select('seasons.name as season_name')
            ->get();

        $mvpCount = $mvpData->count();

        // Fetch championship count and season names
        $championships = \DB::table('seasons')
            ->join('player_game_stats', 'seasons.id', '=', 'player_game_stats.season_id')
            ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id')
            ->join('teams', 'player_game_stats.team_id', '=', 'teams.id')
            ->select('seasons.name as season_name', 'seasons.finals_winner_name as championship_team')
            ->where('player_game_stats.player_id', $playerId)
            ->where('schedules.round', 'finals')
            ->whereColumn('seasons.id', 'player_game_stats.season_id')
            ->whereExists(function ($query) use ($playerId) {
                $query->select(\DB::raw(1))
                    ->from('schedules as s')
                    ->join('player_game_stats as pg', 's.game_id', '=', 'pg.game_id')
                    ->where('pg.team_id', '=', \DB::raw('player_game_stats.team_id'))
                    ->where('s.round', 'finals')
                    ->where('pg.player_id', $playerId)
                    ->whereColumn('pg.season_id', 'player_game_stats.season_id')
                    ->where(function ($q) {
                        $q->where(function ($q) {
                            $q->whereColumn('s.home_id', 'player_game_stats.team_id')
                                ->whereColumn('s.home_score', '>', 's.away_score');
                        })
                            ->orWhere(function ($q) {
                                $q->whereColumn('s.away_id', 'player_game_stats.team_id')
                                    ->whereColumn('s.away_score', '>', 's.home_score');
                            });
                    });
            })
            ->groupBy('seasons.name', 'seasons.finals_winner_name')
            ->get();

        $conference_championships = \DB::table('seasons')
            ->join('player_game_stats', 'seasons.id', '=', 'player_game_stats.season_id')
            ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id')
            ->join('teams as team', 'player_game_stats.team_id', '=', 'team.id') // Join with player’s team
            ->join('teams as home_team', 'schedules.home_id', '=', 'home_team.id') // Join with home team
            ->join('teams as away_team', 'schedules.away_id', '=', 'away_team.id') // Join with away team
            ->select(
                'seasons.name as season_name',
                \DB::raw('CASE
                    WHEN (schedules.home_id = team.id AND schedules.home_score > schedules.away_score) THEN home_team.name
                    WHEN (schedules.away_id = team.id AND schedules.away_score > schedules.home_score) THEN away_team.name
                    ELSE NULL
                END as championship_team')
            )
            ->where('player_game_stats.player_id', $playerId)
            ->where('schedules.round', 'semi_finals')
            ->whereColumn('seasons.id', 'player_game_stats.season_id')
            ->whereExists(function ($query) use ($playerId) {
                $query->select(\DB::raw(1))
                    ->from('schedules as s')
                    ->join('player_game_stats as pg', 's.game_id', '=', 'pg.game_id')
                    ->where('pg.team_id', '=', \DB::raw('player_game_stats.team_id'))
                    ->where('s.round', 'semi_finals')
                    ->where('pg.player_id', $playerId)
                    ->whereColumn('pg.season_id', 'player_game_stats.season_id')
                    ->where(function ($q) {
                        $q->where(function ($q) {
                            $q->whereColumn('s.home_id', 'player_game_stats.team_id')
                                ->whereColumn('s.home_score', '>', 's.away_score');
                        })
                            ->orWhere(function ($q) {
                                $q->whereColumn('s.away_id', 'player_game_stats.team_id')
                                    ->whereColumn('s.away_score', '>', 's.home_score');
                            });
                    });
            })
            ->groupBy('seasons.name', 'team.id', 'home_team.name', 'away_team.name', 'schedules.home_id', 'schedules.away_id', 'schedules.home_score', 'schedules.away_score')
            ->get();

        // Fetch career high stats
        $careerHighs = \DB::table('player_game_stats')
            ->select(
                \DB::raw('MAX(points) as career_high_points'),
                \DB::raw('MAX(rebounds) as career_high_rebounds'),
                \DB::raw('MAX(assists) as career_high_assists'),
                \DB::raw('MAX(steals) as career_high_steals'),
                \DB::raw('MAX(blocks) as career_high_blocks'),
                \DB::raw('MAX(turnovers) as career_high_turnovers'),
                \DB::raw('MAX(fouls) as career_high_fouls')
            )
            ->where('player_id', $playerId)
            ->first();

        // Calculate season count
        $seasonCount = \DB::table('player_game_stats')
            ->where('player_id', $playerId)
            ->distinct('season_id')
            ->count('season_id');

        // Calculate playoff count
        $playoffCount = \DB::table('player_game_stats')
            ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id')
            ->where('player_game_stats.player_id', $playerId)
            ->whereIn('schedules.round', ['play_ins_elims_round_1','play_ins_elims_round_2','play_ins_finals','round_of_16', 'quarter_finals', 'semi_finals', 'interconference_semi_finals', 'finals'])
            ->distinct('schedules.season_id')
            ->count('schedules.round');

        $overallRankSeasons = \DB::table('player_season_stats')
            ->join('standings_view', function ($join) {
                $join->on('player_season_stats.team_id', '=', 'standings_view.team_id')
                     ->on('player_season_stats.season_id', '=', 'standings_view.season_id');
            })
            ->join('seasons', 'standings_view.season_id', '=', 'seasons.id')
            ->join('teams', 'player_season_stats.team_id', '=', 'teams.id')
            ->where('player_season_stats.player_id', $playerId)
            ->where('standings_view.overall_rank', 1)
            ->distinct()
            ->get([
                'standings_view.season_id',
                'seasons.name as season_name',
                'standings_view.overall_rank',
                'teams.name as team_name'
            ]);
        
        
    
        $conferenceRankSeasons = \DB::table('player_season_stats')
            ->join('standings_view', function ($join) {
                $join->on('player_season_stats.team_id', '=', 'standings_view.team_id')
                     ->on('player_season_stats.season_id', '=', 'standings_view.season_id');
            })
            ->join('seasons', 'standings_view.season_id', '=', 'seasons.id')
            ->join('teams', 'player_season_stats.team_id', '=', 'teams.id')
            ->where('player_season_stats.player_id', $playerId)
            ->where('standings_view.conference_rank', 1)
            ->distinct()
            ->get([
                'standings_view.season_id',
                'seasons.name as season_name',
                'standings_view.conference_rank',
                'teams.name as team_name'
            ]);
        
        
        $latestSeasonId = get_current_season_id();

        return response()->json([
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
        ]);
    }

    public function getPlayerGameLogs(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
            'season_id' => 'required|exists:seasons,id',
            'page_num' => 'required|integer|min:1',
            'itemsperpage' => 'required|integer|min:1',
        ]);

        $playerId = $request->player_id;
        $seasonId = $request->season_id;
        $page = $request->page_num;
        $perPage = $request->itemsperpage;

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        $playerName = DB::table('players')
            ->join('teams', 'players.team_id', '=', 'teams.id','left')
            ->where('players.id', $playerId)
            ->select('players.name as player_name', 'teams.name as team_name') // Select player name and team name
            ->first();
    

        // Fetch player game logs for the given player and season with pagination
        $playerGameLogs = \DB::table('player_game_stats')
            ->join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->join('teams as player_team', 'player_game_stats.team_id', '=', 'player_team.id') // Join with player's team to get team name
            ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
            ->join('seasons', 'schedules.season_id', '=', 'seasons.id') // Join with seasons table
            ->leftJoin('teams as home_team', 'schedules.home_id', '=', 'home_team.id') // Join with home team
            ->leftJoin('teams as away_team', 'schedules.away_id', '=', 'away_team.id') // Join with away team
            ->select(
                'player_game_stats.id as stat_id', // Include player_game_stats.id in the select
                'player_game_stats.game_id',
                'player_team.name as team_name', // Player's team name
                \DB::raw('CASE
                WHEN player_game_stats.team_id = schedules.home_id THEN away_team.name
                ELSE home_team.name
            END as opponent_team_name'), // Determine opponent team name
                'schedules.round as round', // Add round info
                'seasons.name as season_name', // Include season name
                'player_game_stats.*',
                \DB::raw('(CASE
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
        $totalRecords = \DB::table('player_game_stats')
            ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
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
        ]);
    }

    public function getPlayerLatestGameLogs(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
            'season_id' => 'required|exists:seasons,id',
            'page_num' => 'required|integer|min:1',
            'itemsperpage' => 'required|integer|min:1',
        ]);

        $playerId = $request->player_id;
        $seasonId = $request->season_id;
        $page = $request->page_num;
        $perPage = $request->itemsperpage;

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        $playerName = DB::table('players')
            ->join('teams', 'players.team_id', '=', 'teams.id','left')
            ->where('players.id', $playerId)
            ->select('players.name as player_name', 'teams.name as team_name') // Select player name and team name
            ->first();
    

        // Fetch player game logs for the given player and season with pagination
        $playerGameLogs = \DB::table('player_game_stats')
            ->join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->join('teams as player_team', 'player_game_stats.team_id', '=', 'player_team.id') // Join with player's team to get team name
            ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
            ->join('seasons', 'schedules.season_id', '=', 'seasons.id') // Join with seasons table
            ->leftJoin('teams as home_team', 'schedules.home_id', '=', 'home_team.id') // Join with home team
            ->leftJoin('teams as away_team', 'schedules.away_id', '=', 'away_team.id') // Join with away team
            ->select(
                'player_game_stats.id as stat_id', // Include player_game_stats.id in the select
                'player_game_stats.game_id',
                'player_team.name as team_name', // Player's team name
                \DB::raw('CASE
                WHEN player_game_stats.team_id = schedules.home_id THEN away_team.name
                ELSE home_team.name
            END as opponent_team_name'), // Determine opponent team name
                'schedules.round as round', // Add round info
                'seasons.name as season_name', // Include season name
                'player_game_stats.*',
                \DB::raw('(CASE
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
        $totalRecords = \DB::table('player_game_stats')
            ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id') // Join with schedules table
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
    public function getPlayersWithFilters(Request $request)
    {
        $sortColumn = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'desc');
        $perPage = $request->input('itemsperpage', 10);
        $page = $request->input('page_num', 1);
        $offset = ($page - 1) * $perPage;

        // Base query to get filtered players from the player_playoff_appearances table
        $query = DB::table('player_playoff_appearances as ppa')
            ->join('players as p', 'ppa.player_id', '=', 'p.id')  // Join with players table to get player names
            ->join('teams as t', 'p.team_id', '=', 't.id','left')  // Join with teams table to get current team names
            ->select(
                'ppa.player_id',
                'p.is_active AS active_status',
                'p.name as player_name',
                't.name as current_team_name',
                'ppa.round_of_32_appearances',
                'ppa.round_of_16_appearances',
                'ppa.quarter_finals_appearances',
                'ppa.semi_finals_appearances',
                'ppa.interconference_semi_finals_appearances',
                'ppa.finals_appearances',
                'ppa.total_playoff_appearances',
                'ppa.seasons_played_in_playoffs',
                'ppa.total_seasons_played',
                'ppa.championships_won'
            );

        // Apply sorting
        switch ($sortColumn) {
            case 'playoff_appearances':
                $query->orderBy('ppa.total_playoff_appearances', $sortOrder);
                break;
            case 'big_four':
                $query->orderBy('ppa.interconference_semi_finals_appearances', $sortOrder);
                break;
            case 'finals_appearances':
                $query->orderBy('ppa.finals_appearances', $sortOrder);
                break;
            case 'seasons_played':
                $query->orderBy('ppa.total_seasons_played', $sortOrder);
                break;
            case 'championships_won':
                $query->orderBy('ppa.championships_won', $sortOrder);
                break;
            default:
                // Default sorting if invalid sort column
                $query->orderBy('p.name', 'asc');
        }

        // Fetch total number of records
        $total = DB::table('player_playoff_appearances as ppa')
            ->join('players as p', 'ppa.player_id', '=', 'p.id')
            ->count();

        // Fetch paginated results
        $players = $query->skip($offset)->take($perPage)->get();

        // Return paginated response
        return response()->json([
            'data' => $players,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    public function getTop20PlayersAllTime()
    {
        // Combine stats for players by player_id
        $top20PlayersAllTime = DB::table('player_season_stats')
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
        $playerStatsForTeam = DB::table('player_season_stats')
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
            ->where('player_season_stats.team_id',$teamId)
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
    
    public function getStarPlayersByTeam(Request $request)
    {
        // Validate the request
        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);
    
        $teamId = $request->team_id;
    
       
        // Query to fetch star players for the given team across all seasons with all stats
        $starPlayers = DB::table('player_season_stats AS pss')
            ->join('players AS p', 'pss.player_id', '=', 'p.id')
            ->join('seasons AS s', 'pss.season_id', '=', 's.id')
            ->join('teams AS ct', 'p.team_id', '=', 'ct.id','left') 
            ->join('teams AS t', 'pss.team_id', '=', 't.id','left') // Joining teams to get current team name
            ->where('pss.team_id', $teamId)
            ->where('pss.role', 'star player') // Filtering only star players
            ->orderByDesc('pss.season_id') // Sort by latest season
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
                
                // Player Season Stats
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
    
    public function getPlayerTransactions(Request $request)
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
        ->orderByDesc('transactions.id')
        ->get();
    
        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'No transactions found for this player.'], 404);
        }
        
        return response()->json($transactions);
    
    }
    public function getRoleChangeHistory(Request $request)
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
        ->whereIn('transactions.status', ['transfer', 'star player change', 'role change'])
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
        ->orderByDesc('transactions.id')
        ->get();
    
        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'No transactions found for this player.'], 404);
        }
        
        return response()->json($transactions);
    
    }

    public function getPlayerInjuryHistory(Request $request)
    {
        // Retrieve the player_id from the request
        $player_id = $request->input('player_id');

        // Check if player_id is provided
        if (!$player_id) {
            return response()->json(['error' => 'Player ID is required'], 400);
        }

        // Query the injured_players_view for the player's injury history
        $injuryHistory = DB::table('injured_players_view')
                            ->where('player_id', $player_id)
                            ->orderByDesc('game_id')
                            ->get();  // Retrieve the data from the view

        // Check if injury history is found
        if ($injuryHistory->isEmpty()) {
            return response()->json(['message' => 'No injury history found for this player.'], 404);
        }

        // Return the injury history as a JSON response
        return response()->json([
            'data' => $injuryHistory
        ]);
    }
    private function getScheduleCount($teamId, $seasonId)
    {
        $count = DB::table('schedules')
            ->where('season_id', $seasonId) // Filter by season_id
            ->where(function($query) use ($teamId) {
                // Check if team_id is in either home_id or away_id
                $query->where('home_id', $teamId)
                      ->orWhere('away_id', $teamId);
            })
            ->count();
            
        return $count;
    }
    
    private function generateHealthRating()
    {
        $probabilityRanges = [
            [90, 100, 2],  // 2% chance
            [70, 89, 3],   // 3% chance
            [50, 69, 4],   // 4% chance
            [30, 49, 4],   // 4% chance
            [20, 29, 10],  // 10% chance
            [10, 19, 15],  // 15% chance
            [0, 9, 70],    // 70% chance
        ];

        $randomRoll = rand(1, 100);
        $sum = 0;

        foreach ($probabilityRanges as [$min, $max, $chance]) {
            $sum += $chance;
            if ($randomRoll <= $sum) {
                return rand($min, $max);
            }
        }

        return 0; // Fallback (should never happen)
    }
    
}
