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

   
    public function listTeamRoster(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'season_id' => 'nullable||exists:seasons,id',
        ]);

        $teamId = $request->team_id;
        $seasonId = $request->season_id;


        $remainingCapSpace = $this->contract->getRemainingCapSpace($teamId);

        // Initialize an array to hold player stats
        $playerStats = [];
        $latestSeasonId = get_current_season_id();

        $isLatestSeason = ($seasonId == $latestSeasonId);
        // Fetch the season status
        $seasonStatus = DB::table('seasons')->where('id', $seasonId)->value('status');

        $seasonStatsDBName = $this->helper->getSeasonStatsDBName($seasonId);

        // Fetch player stats for the given team_id and season_id
        $playerStatsData = DB::table($seasonStatsDBName)
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->get();

        if (count($playerStatsData) > 0) {
            foreach ($playerStatsData as $stats) {
                // Fetch the player with playoff appearances and awards
                $player = DB::table('players')
                    ->select(
                        'players.*',
                        'teams.acronym as drafted_team',
                        'seasons.name as draft_class',
                        DB::raw('(SELECT COALESCE(SUM(interconference_semi_finals_appearances), 0) FROM player_playoff_appearances WHERE player_playoff_appearances.player_id = players.id) as conference_championships_won'),
                        DB::raw('(SELECT COALESCE(SUM(championships_won), 0) FROM player_playoff_appearances WHERE player_playoff_appearances.player_id = players.id) as championships_won'),
                        DB::raw('(SELECT COUNT(*) FROM season_awards WHERE season_awards.player_id = players.id) as awards_won')
                    )
                    ->leftJoin('seasons', 'players.draft_id', '=', 'seasons.id')
                    ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id')
                    ->where('players.id', $stats->player_id)
                    ->first();

                if ($player) {
                    // Count the number of games played for the player
                    // $gamesPlayed = DB::table('player_game_stats')
                    //     ->where('player_id', $player->id)
                    //     ->where('team_id', $teamId)
                    //     ->where('season_id', $seasonId)
                    //     ->where('minutes', '>=', 0)
                    //     ->count();

                    $latestRatings = DB::table('player_ratings')
                        ->where('player_id', $player->id)
                        ->where('season_id', $seasonId - 1)
                        ->value('overall_rating') ??  75;

                    $prevRatings = ($seasonId == 1) ? $player->overall_rating  :  DB::table('player_ratings')
                        ->where('player_id', $player->id)
                        ->where('season_id', $seasonId - 2)
                        ->value('overall_rating') ?? 75;

                    $latestRatings = ($seasonId < 3 || $isLatestSeason) ? $player->overall_rating : $latestRatings;


                    $prevRatings = ($seasonId == 1) ? $player->overall_rating : $prevRatings;

                    $hasImproved = $this->helper->hasImproved($latestRatings, $prevRatings);
                    // $hasImproved = 'Latest: '.$latestRatings.' - Prev: '.$prevRatings;                  // Count seasons played with the team
                    $seasonsPlayedWithTeam = DB::table('player_season_stats_archives')
                        ->select(DB::raw('DISTINCT season_id, team_id'))
                        ->where('player_id', $player->id)
                        ->where('team_id', $teamId)
                        ->where('season_id', '<=', $seasonId)
                        ->get()
                        ->count() + 1;

                    $totalSeasonsPlayed = DB::table('player_season_stats_archives')
                        ->where('player_id', $player->id)
                        ->where('season_id', '<=', $seasonId)
                        ->distinct('season_id')
                        ->count('season_id') + 1;

                    $playerStatus = $player->team_id == $teamId ? ($player->is_active ? 1 : 0) : 2;
                    $playerCUtfromFinalRoster = ($player->team_id != $teamId && $player->is_active && $stats->total_games_played == 0) ? 1 : 0;
                    // Add player stats to the array
                    //if ($playerCUtfromFinalRoster) continue;
                    $playerRole = $stats->role == '' ? 'reserved' : $stats->role;

                    $playerStats[] = [
                        'player_id' => $player->id,
                        'name' => $player->name,
                        'position' => $player->position,
                        'age' => $player->age,
                        'role' => $playerRole,
                        'is_active' => $player->is_active,
                        'morale' => $player->morale,
                        'hardship_contract' => $player->hardship_contract,
                        'salary' => '₱'.number_format($player->salary,2,'.',','),
                        'contract_type' => $player->contract_type,
                        'injury_recovery_games' => $player->injury_recovery_games,
                        'is_rookie' => $player->is_rookie,
                        'is_injured' => $player->is_injured,
                        'is_reserved' => $player->is_reserved,
                        'injury_type' => $player->injury_type,
                        'contract_years' => $player->contract_years,
                        'retirement_age' => $player->retirement_age,
                        'drafted_team' => $player->drafted_team,
                        'draft_id' => $player->draft_id,
                        'draft_class' => $player->draft_class,
                        'draft_status' => $player->draft_status,
                        'overall_rating' => $latestRatings,
                        'potential_rating' => $player->potential_rating,
                        'potential_status' => $player->potential_rating == $latestRatings ? 'MAX' : '-',
                        'status' => $playerStatus,
                        'is_cut' => $playerCUtfromFinalRoster,
                        'has_improved' => $hasImproved,
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
                        'conference_championships_won' => (int)$player->conference_championships_won,
                        'championships_won' => (int)$player->championships_won,
                        'awards_won' => (int)$player->awards_won,
                        'player_valuation' => (int)$stats->player_valuation,
                        'is_latest' => $isLatestSeason,
                    ];
                }
            }
        } else {
            // Fetch players from the players table and set all stats to zero
            $players = DB::table('players')
                ->select(
                    'players.*',
                    'teams.acronym as drafted_team',
                    'seasons.name as draft_class',
                    DB::raw('(SELECT COALESCE(SUM(interconference_semi_finals_appearances), 0) FROM player_playoff_appearances WHERE player_playoff_appearances.player_id = players.id) as conference_championships_won'),
                    DB::raw('(SELECT COALESCE(SUM(championships_won), 0) FROM player_playoff_appearances WHERE player_playoff_appearances.player_id = players.id) as championships_won'),
                    DB::raw('(SELECT COUNT(*) FROM season_awards WHERE season_awards.player_id = players.id) as awards_won')
                )
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
                    DB::raw('SUM(CASE WHEN eff > 0 THEN eff ELSE 0 END) / NULLIF(COUNT(CASE WHEN eff > 0 THEN 1 END), 0) as avg_eff'),
                    DB::raw('SUM(CASE WHEN field_goal_percentage > 0 THEN field_goal_percentage ELSE 0 END) / NULLIF(COUNT(CASE WHEN field_goal_percentage > 0 THEN 1 END), 0) as field_goal_percentage'),
                    DB::raw('SUM(CASE WHEN per > 0 THEN per ELSE 0 END) / NULLIF(COUNT(CASE WHEN per > 0 THEN 1 END), 0) as per_game_score')
                )
                ->where('season_id', $seasonId)
                ->groupBy('player_id')
                ->get()
                ->keyBy('player_id');

            foreach ($players as $player) {
                $playerId = $player->id;

                $totalSeasonsPlayed = DB::table('player_season_stats_archives')
                    ->where('player_id', $playerId)
                    ->distinct('season_id')
                    ->count();

                $seasonsPlayedWithTeam = DB::table('player_season_stats_archives')
                    ->where('player_id', $player->id)
                    ->where('team_id', $teamId)
                    ->count('team_id') + 1;

                $totalSeasonGameSchedule = $this->scheduleService->getScheduleCount($teamId, $seasonId);

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
                    'bpg_game_leader' => (float)0,
                    'field_goal_percentage' => (float)0,
                    'three_point_percentage' => (float)0,
                    'two_point_percentage' => (float)0,
                    'free_throw_percentage' => (float)0,
                    'per_game_score' => (float)0,
                    'total_score' => (float)0,
                    'combined_score' => (float)0,
                    'seasons_played_with_team' => $seasonsPlayedWithTeam,
                    'team_total_games' => (float)$totalSeasonGameSchedule,
                    'total_seasons_played' => $totalSeasonsPlayed + 1,
                    'average_eff' => (float)0,
                    'games_played' => (int)0,
                ];

                // If there are stats for this player, update values
                if (isset($playerGameStats[$playerId])) {
                    $stats = [
                        'average_minutes_per_game' => (float)$playerGameStats[$playerId]->avg_minutes,
                        'average_points_per_game' => (float)$playerGameStats[$playerId]->avg_points,
                        'average_rebounds_per_game' => (float)$playerGameStats[$playerId]->avg_rebounds,
                        'average_assists_per_game' => (float)$playerGameStats[$playerId]->avg_assists,
                        'average_steals_per_game' => (float)$playerGameStats[$playerId]->avg_steals,
                        'average_blocks_per_game' => (float)$playerGameStats[$playerId]->avg_blocks,
                        'average_turnovers_per_game' => (float)$playerGameStats[$playerId]->avg_turnovers,
                        'average_fouls_per_game' => (float)$playerGameStats[$playerId]->avg_fouls,
                        'bpg_game_leader' => (float)0,
                        'field_goal_percentage' => (float)$playerGameStats[$playerId]->field_goal_percentage,
                        'per_game_score' => (float)$playerGameStats[$playerId]->per_game_score,
                        'games_played' => (int)$playerGameStats[$playerId]->games_played,
                        'average_eff' => (float)$playerGameStats[$playerId]->avg_eff,
                    ];
                }

                // Only include players with games played > 0 if season status is 11
                if ($seasonStatus != 11 || $stats['games_played'] > 0) {
                    $playerStats[] = [
                        'player_id' => $playerId,
                        'name' => $player->name,
                        'position' => $player->position,
                        'age' => $player->age,
                        'role' => $player->role ?? 'reserved',
                        'is_active' => $player->is_active,
                        'morale' => $player->morale,
                        'is_rookie' => $player->is_rookie,
                        'is_reserved' => $player->is_reserved,
                        'injury_type' => $player->injury_type,
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
                        'bpg_game_leader' => $stats['bpg_game_leader'],
                        'effeciency' => number_format($stats['average_eff'], 2),
                        'games_played' => $stats['games_played'],
                        'field_goal_percentage' => number_format($stats['field_goal_percentage'], 2),
                        'three_point_percentage' => number_format($stats['three_point_percentage'], 2),
                        'two_point_percentage' => number_format($stats['two_point_percentage'], 2),
                        'free_throw_percentage' => number_format($stats['free_throw_percentage'], 2),
                        'per_game_score' => number_format($stats['per_game_score'], 2),
                        'total_score' => number_format(0, 2),
                        'combined_score' => number_format(0, 2),
                        'seasons_played_with_team' => $seasonsPlayedWithTeam,
                        'team_total_games' => (float)$totalSeasonGameSchedule,
                        'total_seasons_played' => $totalSeasonsPlayed + 1,
                        'latest_season' => $latestSeasonId,
                        'playoff_appearances' => (int)$player->conference_championships_won,
                        'championships_won' => (int)$player->championships_won,
                        'awards_won' => (int)$player->awards_won,
                        'player_valuation' =>(int) $stats['player_valuation'] ?? 0,
                    ];
                }
            }
        }

        // Sort players by role and efficiency
        if (!empty($playerStats)) {
            $rolePriority = [
                'star player' => 1,
                'all star' => 2,
                'starter' => 3,
                'role player' => 4,
                'bench' => 5
            ];

            usort($playerStats, function ($a, $b) use ($rolePriority) {
                if ($a['status'] == 2 && $b['status'] != 2) {
                    return 1;
                }
                if ($b['status'] == 2 && $a['status'] != 2) {
                    return -1;
                }

                $roleA = $rolePriority[$a['role']] ?? 6;
                $roleB = $rolePriority[$b['role']] ?? 6;

                if ($roleA !== $roleB) {
                    return $a['is_reserved'] <=> $b['is_reserved'] ?: $roleA <=> $roleB;
                }

                return $a['is_reserved'] <=> $b['is_reserved'] ?: $b['per_game_score'] <=> $a['per_game_score'];
            });
        }

        return response()->json([
            'players' => $playerStats,
            'season_id' => $seasonId,
            'team_id' => $teamId,
            'stats_count' => count($playerStatsData),
            'table' => $seasonStatsDBName,
            'remaining_cap_space' => $remainingCapSpace,
        ]);
    }

    public function getFreeAgents($request)
    {
        // Get pagination parameters from the request
        $perPage = $request->itemsperpage ?? 10; // Number of items per page
        $currentPage = $request->page_num ?? 1; // Current page number
        $search = $request->search; // Search term

        
        // Build the query with optional search filter
        $query = Player::select(
            'players.*',
            'teams.acronym as drafted_team',
            DB::raw("(
                SELECT GROUP_CONCAT(CONCAT(award_name, ' (Season ', season_id, ')') SEPARATOR ', ') 
                FROM season_awards 
                WHERE season_awards.player_id = players.id
            ) as awards"),
            DB::raw("(
                SELECT CONCAT('Finals MVP (Season ', seasons.id, ')') 
                FROM seasons 
                WHERE seasons.finals_mvp_id = players.id 
                ORDER BY seasons.id DESC 
                LIMIT 1
            ) as finals_mvp"),
            DB::raw("CASE 
                WHEN EXISTS (
                    SELECT 1 FROM seasons WHERE seasons.finals_mvp_id = players.id
                ) THEN 1 ELSE 0 
            END as is_finals_mvp"),
            DB::raw("(
                SELECT GROUP_CONCAT(seasons.name SEPARATOR ', ') 
                FROM seasons 
                WHERE seasons.finals_mvp_id = players.id
            ) as finals_mvp_seasons")
        )
            ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id')
            ->where('players.contract_years', 0)
            ->where('players.is_active', 1);

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
        $playerDetails = DB::table('players')
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


        if (!$playerDetails) {
            return response()->json([
                'error' => 'Player not found.',
            ], 404);
        }

        // Fetch playoff performance
        $playoffPerformance = DB::table('player_playoff_appearances')
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
        $awardsData = DB::table('season_awards')
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
        $mvpData = DB::table('seasons')
            ->where('seasons.finals_mvp_id', $playerId)
            ->select('seasons.name as season_name')
            ->get();

        $mvpCount = $mvpData->count();

        // Fetch championship count and season names
        $championships = DB::table('seasons')
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
            ->where('playoff_series.round', 'finals')
            ->where('playoff_series.status', 2) // Series is finished
            ->whereColumn('playoff_series.winner_team_id', 'player_season_stats.team_id') // Match columns correctly
            ->groupBy('seasons.id', 'seasons.name', 'winner_team.name')
            ->distinct()
            ->get();

        // Fetch conference championships (using playoff_series table)
        $conference_championships = DB::table('seasons')
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
            ->where('playoff_series.round', 'semi_finals')
            ->where('playoff_series.status', 2) // Series is finished
            ->whereColumn('playoff_series.winner_team_id', 'player_season_stats.team_id') // Match columns correctly
            ->groupBy('seasons.id', 'seasons.name', 'winner_team.name')
            ->distinct()
            ->get();


        // Fetch career high stats
        $careerHighs = DB::table('player_game_highs')
            ->where('player_id', $playerId)
            ->first();

        // Calculate season count
        $seasonCount = DB::table('player_season_stats_archives')
            ->where('player_id', $playerId)
            ->distinct('season_id')
            ->count('season_id');

        // Calculate playoff count
        $playoffCount = DB::table('player_season_playoff_stats_archives')
            ->where('player_id', $playerId)
            ->distinct('season_id')
            ->count('season_id');

        $overallRankSeasons = DB::table('player_season_stats_archives as pssa')
            ->join('standings_snapshots', function ($join) {
                $join->on('pssa.team_id', '=', 'standings_snapshots.team_id')
                    ->on('pssa.season_id', '=', 'standings_snapshots.season_id');
            })
            ->join('seasons', 'standings_snapshots.season_id', '=', 'seasons.id')
            ->join('teams', 'pssa.team_id', '=', 'teams.id')
            ->where('pssa.player_id', $playerId)
            ->where('standings_snapshots.overall_rank', 1)
            ->distinct()
            ->get([
                'standings_snapshots.season_id',
                'seasons.name as season_name',
                'standings_snapshots.overall_rank',
                'teams.name as team_name'
            ]);

        $conferenceRankSeasons = DB::table('player_season_stats_archives as pssa')
            ->join('standings_snapshots', function ($join) {
                $join->on('pssa.team_id', '=', 'standings_snapshots.team_id')
                    ->on('pssa.season_id', '=', 'standings_snapshots.season_id');
            })
            ->join('seasons', 'standings_snapshots.season_id', '=', 'seasons.id')
            ->join('teams', 'pssa.team_id', '=', 'teams.id')
            ->where('pssa.player_id', $playerId)
            ->where('standings_snapshots.conference_rank', 1)
            ->distinct()
            ->get([
                'standings_snapshots.season_id',
                'seasons.name as season_name',
                'standings_snapshots.conference_rank',
                'teams.name as team_name'
            ]);

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
            case 'big_four':
                $query->orderBy('ppa.interconference_semi_finals_appearances', $sortOrder);
                break;
            case 'finals_appearances':
                $query->orderBy('ppa.finals_appearances', $sortOrder);
                break;
            case 'seasons_played':
                $query->orderBy('ppa.total_seasons_played', $sortOrder); // NOTE: column must exist or be handled differently
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
            ->selectRaw('MAX(eff) as max_eff, season_id')
            ->where('team_id', $teamId)
            ->where('role', 'star player')
            ->groupBy('season_id');

        // Join with player and season data
        $starPlayers = DB::table('player_season_stats_archives AS pss')
            ->joinSub($sub, 'max_eff_stats', function ($join) {
                $join->on('pss.season_id', '=', 'max_eff_stats.season_id')
                    ->on('pss.eff', '=', 'max_eff_stats.max_eff');
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


}

