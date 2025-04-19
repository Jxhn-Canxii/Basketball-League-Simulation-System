<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seasons;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionsController extends Controller
{

    public function getRecentNonTransferTransactions()
    {
        $transactions = DB::table('transactions')
            ->select(
                'transactions.id',
                'transactions.player_id',
                'players.name as player_name',
                'players.role as player_role',
                'players.position as position',
                'players.draft_status as draft_status',
                'players.draft_id as draft_season_id',
                DB::raw("CASE WHEN players.drafted_team_id = 0 THEN 'Undrafted' ELSE drafted_team.acronym END as drafted_team_abbre"),
                'players.age as age',
                'transactions.season_id',
                'seasons.name as season_name',
                'transactions.details',
                'transactions.from_team_id',
                DB::raw("CASE WHEN transactions.from_team_id = 0 THEN 'Free Agent' ELSE from_teams.name END as from_team_name"),
                DB::raw("CASE WHEN transactions.from_team_id = 0 THEN 'None' ELSE from_teams.city END as from_team_city"),
                'transactions.to_team_id',
                DB::raw("CASE WHEN transactions.to_team_id = 0 THEN 'Free Agent' ELSE to_teams.name END as to_team_name"),
                DB::raw("CASE WHEN transactions.to_team_id = 0 THEN 'None' ELSE to_teams.city END as to_team_city"),
                'transactions.status',
                'transactions.created_at',
                'transactions.updated_at',
                // Add award information
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT award_name SEPARATOR ', ') 
                    FROM season_awards 
                    WHERE season_awards.player_id = transactions.player_id) as awards"),
                // Check if player is Finals MVP
                DB::raw("CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM seasons 
                        WHERE seasons.finals_mvp_id = transactions.player_id
                    ) THEN 'Finals MVP'
                    ELSE NULL 
                END as finals_mvp_status"),
                // Get Finals MVP seasons
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT seasons.name) 
                    FROM seasons 
                    WHERE seasons.finals_mvp_id = transactions.player_id) as finals_mvp_seasons")
            )
            ->join('players', 'transactions.player_id', '=', 'players.id')
            ->join('seasons', 'transactions.season_id', '=', 'seasons.id')
            ->leftJoin('teams as drafted_team', 'players.drafted_team_id', '=', 'drafted_team.id')
            ->leftJoin('teams as from_teams', 'transactions.from_team_id', '=', 'from_teams.id')
            ->leftJoin('teams as to_teams', 'transactions.to_team_id', '=', 'to_teams.id')
            ->whereNotIn('transactions.status', ['transfer', 'role change'])
            ->orderBy('transactions.id', 'desc')
            ->limit(6)
            ->get();

        // Format the response data
        $transactions = $transactions->map(function ($transaction) {
            $awardsInfo = [];
            
            // Add regular awards if any
            if ($transaction->awards) {
                $awardsInfo[] = $transaction->awards;
            }
            
            // Add Finals MVP information if applicable
            if ($transaction->finals_mvp_status) {
                $awardsInfo[] = "Finals MVP (" . $transaction->finals_mvp_seasons . ")";
            }
            
            // Add awards information to transaction
            $transaction->awards_info = !empty($awardsInfo) ? implode(', ', $awardsInfo) : null;
            
            // Remove raw fields
            unset($transaction->awards);
            unset($transaction->finals_mvp_status);
            unset($transaction->finals_mvp_seasons);
            
            return $transaction;
        });

        return response()->json([
            'transactions' => $transactions
        ]);
    }


    public function getTransactions(Request $request)
    {
        $seasonId = $request->season_id;
        $teamId = $request->team_id;
        $type = $request->type; // 'normal' or 'notable'
        $perPage = $request->get('itemsperpage', 10); // Default items per page is 10
        $page = $request->get('page_num', 1); // Default to page 1

        // Build the initial query with necessary joins
        $query = DB::table('transactions as t')
            ->leftJoin('season_awards as sa', function ($join) {
                $join->on('sa.player_id', '=', 't.player_id');
            })
            ->leftJoin('seasons as s', 's.id', '=', 't.season_id')
            ->leftJoin('players as p', 'p.id', '=', 't.player_id')
            ->leftJoin('teams as from_team', 'from_team.id', '=', 't.from_team_id')
            ->leftJoin('teams as to_team', 'to_team.id', '=', 't.to_team_id')
            ->leftJoin('teams as award_team', 'award_team.id', '=', 'sa.team_id') // Join teams for the awards
            ->leftJoin('player_season_stats as ps', function ($join) {
                $join->on('ps.player_id', '=', 't.player_id')
                    ->on('ps.season_id', '=', 't.season_id');
            })
            ->select(
                't.id',
                't.player_id',
                't.season_id',
                't.details',
                't.from_team_id',
                't.to_team_id',
                't.status',
                'p.name as player_name',
                'p.is_active as is_active',
                'from_team.name as from_team_name',
                'to_team.name as to_team_name',
                'p.role', // Fetch player's role
                DB::raw("CASE
                WHEN sa.player_id IS NOT NULL  /* Player has an award */
                    OR s.finals_mvp_id = t.player_id  /* Player is Finals MVP */
                    OR p.role = 'star player'  /* Player is a star player */
                THEN 'notable'
                ELSE 'normal'
                END AS transaction_type"),
                DB::raw("GROUP_CONCAT(DISTINCT CONCAT(sa.award_name, ' (Season: ', sa.season_id, ', Team: ', IFNULL(award_team.name, 'N/A'), ')') ORDER BY sa.season_id ASC) AS player_awards"),
                DB::raw("(SELECT CONCAT('Finals MVP (Season ', s.id, ', Team: ', t.name, ')')
                FROM seasons AS s
                LEFT JOIN teams AS t ON t.id = s.finals_winner_id
                WHERE s.finals_mvp_id = p.id
                LIMIT 1) AS finals_mvp"),
                DB::raw("(SELECT CONCAT('Finals Winner (Season ', s.id, ', Team: ', winner_team.name, ')')
                FROM seasons AS s
                LEFT JOIN teams AS winner_team ON winner_team.id = s.finals_winner_id
                WHERE s.finals_winner_id = p.id
                LIMIT 1) AS player_finals_winner"),
                DB::raw("CASE
                    WHEN s.finals_mvp_id = p.id THEN 1
                    ELSE 0
                END as is_finals_mvp"),
                // Fetch player career championships (all seasons they were champions)
                DB::raw("MAX(CASE WHEN t.status = 'retired' THEN 1 ELSE 0 END) AS is_retired"),  // Check if the player has any 'retired' status
                's.finals_winner_name'  // Add finals_winner_name from the seasons table
            )
            ->whereNotIn('t.status', ['draft', 'released']); // Filter out 'draft' and 'released' transactions

        // Apply filters for 'normal' or 'notable' transaction type based on the CASE logic
        if ($type) {
            $query->whereRaw("
                (sa.player_id IS NOT NULL
                OR s.finals_mvp_id = t.player_id
                OR p.role = 'star player') = ?
            ", [$type === 'notable' ? 1 : 0]);
        }

        // Apply season_id filter if provided (if you want transactions from a specific season)
        if ($seasonId) {
            $query->where('t.season_id', $seasonId);
        }

        // Apply team_id filter if provided (filter by from_team_id or to_team_id)
        if ($teamId) {
            $query->where(function ($subQuery) use ($teamId) {
                $subQuery->where('t.from_team_id', $teamId)
                    ->orWhere('t.to_team_id', $teamId);
            });
        }

        // Add GROUP BY clause to ensure proper grouping for each transaction and player
        $query->groupBy(
            't.id',
            't.player_id',
            't.season_id',
            'sa.player_id',
            't.details',
            't.from_team_id',
            't.to_team_id',
            't.status',
            'p.name',
            'p.is_active',
            'from_team.name',
            'to_team.name',
            'p.role',
            's.id',
            'p.id',
            's.finals_mvp_id',
            'award_team.name',
            's.champion_id',       // Added champion_id in GROUP BY
            's.finals_winner_id',   // Added finals_winner_id in GROUP BY
            's.finals_winner_name'  // Added finals_winner_name in GROUP BY
        );

        // Fetch all transactions without pagination
        $transactions = $query->get();


        foreach ($transactions as $transaction) {
            $playerId = $transaction->player_id;
            $championships = \DB::table('seasons')
                ->join('player_game_stats', 'seasons.id', '=', 'player_game_stats.season_id')
                ->join('schedules', 'player_game_stats.game_id', '=', 'schedules.game_id')
                ->join('teams', 'player_game_stats.team_id', '=', 'teams.id')
                ->select('seasons.id as season_id', 'teams.name as championship_team', 'seasons.name as championship_season')
                ->where('player_game_stats.player_id',  $playerId)
                ->where('schedules.round', 'finals')
                ->whereColumn('seasons.id', 'player_game_stats.season_id')
                ->whereExists(function ($query) use ($playerId) {
                    $query->select(\DB::raw(1))
                        ->from('schedules as s')
                        ->join('player_game_stats as pg', 's.game_id', '=', 'pg.game_id')
                        ->where('pg.team_id', '=', \DB::raw('player_game_stats.team_id'))
                        ->where('s.round', 'finals')
                        ->where('pg.player_id',  $playerId)
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
                ->groupBy('seasons.id', 'teams.name', 'seasons.name') // Group by season_id, championship team and season name
                ->get();

            // Convert the championships to a comma-separated string
            $championshipsFormatted = $championships->map(function ($championship) {
                return "{$championship->championship_season} Champion ({$championship->championship_team})";
            })->implode(', ');
            // Assign the championship data to the transaction, if available
            $transaction->player_career_championships = $championshipsFormatted;

            // If the player is retired, set their status as 'retired'
            if ($transaction->is_retired) {
                $transaction->status = 'retired';
            }

            // Remove the temporary 'is_retired' field from the response
            unset($transaction->is_retired);
        }


        // Return the data with total_items set to 0 (no pagination)
        return response()->json([
            'data' => $transactions,  // The actual data for all transactions
            'current_page' => 1,      // Page 1 (since we're not paginating)
            'total_items' => 0,       // Set total_items to 0 as requested
            'total_pages' => 1,       // One page since no pagination
            'per_page' => count($transactions),  // The number of items fetched
        ]);
    }

    // Waive a player (make them inactive)
    public function waivePlayer(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:players,id',
        ]);

        $player = Player::findOrFail($request->id);
        $player->update(['is_active' => false, 'team_id' => null]);

        return response()->json([
            'message' => 'Player waived successfully',
            'player' => $player,
        ]);
    }

    // Extend player's contract
    public function extendContract(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:players,id',
            'additional_years' => 'required|integer|min:1|max:5',
        ]);

        $player = Player::findOrFail($request->id);

        $newContractEnd = $player->contract_expires_at
            ? $player->contract_expires_at->addYears($request->additional_years)
            : now()->addYears($request->additional_years);

        $player->update([
            'contract_years' => min($player->contract_years + $request->additional_years, 5),
            'contract_expires_at' => $newContractEnd,
        ]);

        return response()->json([
            'message' => 'Contract extended successfully',
            'player' => $player,
        ]);
    }


    public function assignPlayerToRandomTeam(Request $request)
    {
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        // Fetch teams with fewer than 15 players
        $teamIds = DB::table('teams')
            ->leftJoin('players', 'teams.id', '=', 'players.team_id')
            ->select('teams.id')
            ->groupBy('teams.id')
            ->havingRaw('SUM(CASE WHEN players.is_active = 1 THEN 1 ELSE 0 END) < 15')
            ->pluck('teams.id');


        $teamsCount = $teamIds->count();

        if ($teamsCount === 0) {
            return response()->json([
                'message' => 'No teams available with fewer than 15 players.',
            ], 400);
        }

        // Select a random team
        $teamId = $teamIds->random();

        // Fetch the player
        $player = Player::find($request->player_id);

        if (!$player) {
            return response()->json([
                'message' => 'Player not found.',
            ], 404);
        }

        // Set contract years based on the player's role
        $contractYears = 1; // Default value
        switch ($player->role) {
            case 'star player':
                $contractYears = 5;
                break;
            case 'all star':
                $contractYears = 3;
                break;
            case 'starter':
                $contractYears = 3;
                break;
            case 'role player':
                $contractYears = 2;
                break;
            case 'bench':
                $contractYears = 1;
                break;
        }

        // Update the player's team and contract years
        $player->update([
            'team_id' => $teamId,
            'contract_years' => $contractYears,
        ]);

        return response()->json([
            'message' => 'Player successfully assigned to a team. Remaining Teams that needed players: ' . $teamsCount,
            'team_id' => $teamId,
            'team_count' =>  $teamsCount,
        ]);
    }
    
    public function assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId)
    {
        // Determine contract years based on the player's role
        $contractYears = $this->determineContractYears($player->role);
    
        // Team information
        $teamId = $team->id;
        $teamName = $team->name;
    
        // Start a database transaction to ensure atomicity
        DB::beginTransaction();
    
        try {
            // Update the player's team_id and contract_years using DB
            if($seasonId == 0){
                DB::table('players')
                ->where('id', $player->id)
                ->update([
                    'team_id' => $teamId,
                    'drafted_team_id' => $teamId,
                    'is_drafted' => true,
                    'contract_years' => $contractYears,
                    'draft_status' => 'Special Draft'
                ]);
            }else{
                DB::table('players')
                ->where('id', $player->id)
                ->update([
                    'team_id' => $teamId,
                    'contract_years' => $contractYears
                ]);
            }
            
        
            // Insert the transaction record into the transactions table
            DB::table('transactions')->insert([
                'player_id' => $player->id,
                'season_id' => $currentSeasonId,
                'details' => $player->name . ' has signed for ' . $teamName . ' for ' . $contractYears . ' years on a standard contract.',
                'from_team_id' => 0, // Assuming the player is a free agent and has no previous team
                'to_team_id' => $teamId,
                'status' => 'signed',
            ]);
        
            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollback();
        
            // Enhanced logging for better debugging
            Log::error('Error assigning player to team: ' . $e->getMessage(), [
                'player_id' => $player->id,
                'team_id' => $teamId,
                'contract_years' => $contractYears,
                'season_id' => $currentSeasonId,
                'exception' => $e,
            ]);
            
            // Rethrow or handle the error as needed
            throw $e;
        }
        
    }
    
    public function assignRemainingFreeAgents()
    {
        $seasonId = get_current_season_id() ?? 0;
        $currentSeasonId = $seasonId + 1;
        
        // Minimum required players per position for each team
        $minimumPositionCounts = [
            'PG' => 3, 'SG' => 3, 'SF' => 3, 'PF' => 3, 'C' => 3,
        ];
        
        // Step 1: Check position availability first
        $positionCheck = $this->checkPositionAvailability();

        // If checkPositionAvailability returns a response (indicating an error)
        if ($positionCheck !== true) {
            // If there is an issue (e.g., not enough players for some positions), return the response
            return $positionCheck;
        }
        
        // Get all active players and group by position
        $activePlayers = DB::table('players')
            ->where('is_active', 1) // Only active players
            ->select('id', 'position')
            ->get()
            ->groupBy('position'); // Group players by position
        

        // Proceed with assigning players to teams
        $teamsWithFewMembers = DB::table('teams')
            ->leftJoin('players', 'teams.id', '=', 'players.team_id')
            ->select('teams.id', 'teams.name', DB::raw('COUNT(players.id) as player_count'))
            ->groupBy('teams.id', 'teams.name')
            ->havingRaw('COUNT(players.id) < 15')
            ->get();
    
        $teamsCount = $teamsWithFewMembers->count();
        if ($teamsCount === 0) {
            $update = ($currentSeasonId == 1) ? $this->updateTeamRolesBasedOnStatsByRating() : $this->storeNextSeasonStatsPerTeam();
    
            if ($update) {
                if ($seasonId == 0) {
                    DB::table('players')
                        ->where('draft_id', 1)
                        ->where('is_drafted', 0)
                        ->update([
                            'draft_id' => 1,
                            'team_id' => 0,
                            'contract_years' => 0,
                            'draft_status' => 'Undrafted',
                            'is_rookie' => 1,
                        ]);
                } else {
                    DB::table('seasons')
                        ->where('id',  $seasonId)
                        ->update(['status' => config('timeline.player_signings')]);
                }
    
                return response()->json([
                    'error' => true,
                    'message' => 'All teams have signed 15 players, and roles have been updated based on last season\'s stats.',
                    'team_count' => $teamsCount,
                    'current_season_id' => $currentSeasonId,
                    'update' => $update
                ], 401);
            } else {
                return response()->json([
                    'message' => 'Role assigning error!',
                    'update' => $update,
                    'current_season_id' => $currentSeasonId
                ], 200);
            }
        }else{
            
            $usedPlayerIds = []; // Keep track of already-assigned players
    
            foreach ($teamsWithFewMembers as $team) {
                $teamPosCounts = $this->getTeamPositionCounts($team->id);
                $currentPlayerCount = $team->player_count;
        
                // Fill minimum position requirements first
                foreach ($minimumPositionCounts as $position => $minRequired) {
                    while (($teamPosCounts[$position] ?? 0) < $minRequired && $currentPlayerCount < 15) {
                        // Get the best available player for the current position
                        $player = $this->getBestAvailableFreeAgent($position, $usedPlayerIds);
                        if (!$player) break;
        
                        // Assign player to team
                        $this->assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId);
                        $usedPlayerIds[] = $player->id; // Mark as used
                        $teamPosCounts[$position] = ($teamPosCounts[$position] ?? 0) + 1;
                        $currentPlayerCount++;
                    }
                }
        
                // Fill remaining roster spots with best available players
                while ($currentPlayerCount < 15) {
                    // Get the best available player (no position requirement)
                    $player = $this->getBestAvailableFreeAgent('SG', $usedPlayerIds);
                    if (!$player) break;
        
                    // Assign player to team
                    $this->assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId);
                    foreach (explode('/', $player->position) as $pos) {
                        $teamPosCounts[$pos] = ($teamPosCounts[$pos] ?? 0) + 1;
                    }
                    $usedPlayerIds[] = $player->id; // Mark as used
                    $currentPlayerCount++;
                }
            }
        
            // Final check for incomplete teams
            $incompleteTeams = DB::table('teams')
                ->leftJoin('players', 'teams.id', '=', 'players.team_id')
                ->select('teams.name', DB::raw('COUNT(players.id) as player_count'))
                ->groupBy('teams.name')
                ->havingRaw('COUNT(players.id) < 15')
                ->get()
                ->map(function ($team) {
                    return [
                        'team_name' => $team->name,
                        'players_needed' => 15 - $team->player_count,
                    ];
                })->filter(fn($team) => $team['players_needed'] > 0);
        
            return response()->json([
                'message' => 'Players have been assigned to teams.',
                'remaining_players' => count($activePlayers) - count($usedPlayerIds), // Calculate remaining players
                'incomplete_teams' => $incompleteTeams,
            ]);
        }
    }
    
    public function getTeamPositionCounts($teamId)
    {
        $positionCounts = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->select('position', DB::raw('COUNT(*) as count'))
            ->groupBy('position')
            ->pluck('count', 'position')
            ->toArray();

        return $positionCounts;
    }

    public function checkPositionAvailability()
    {
        // Define the minimum number of players required per position for each team
        $minimumPositionCounts = [
            'PG' => 3, 'SG' => 3, 'SF' => 3, 'PF' => 3, 'C' => 3,
        ];
    
        // Get the total number of teams
        $totalTeams = DB::table('teams')->count();
    
        // Calculate the total required players per position (5 positions, 3 per position)
        $totalRequiredPlayers = $totalTeams * 3;
    
        // Loop through each position to check the availability of players
        $positions = ['PG', 'SG', 'SF', 'PF', 'C'];
    
        // Initialize an array to track position shortages
        $positionShortages = [];
    
        foreach ($positions as $position) {
            // Get the count of active players for the current position
            $availablePlayers = DB::table('players')
                ->where('position', 'like', '%' . $position . '%')  // Checks if position contains $position value
                ->where('is_active', 1)  // Only active players
                ->count();
    
            // Check if the available players are less than the required total players
            if ($availablePlayers < $totalRequiredPlayers) {
                // If there aren't enough players, record the shortage for that position
                $positionShortages[$position] = [
                    'needed' => $totalRequiredPlayers,
                    'available' => $availablePlayers,
                ];
            }
        }
    
        // If there are shortages, return a response with the message containing the shortages
        if (!empty($positionShortages)) {
            $shortageMessage = "Not enough players available for some positions: ";
            foreach ($positionShortages as $position => $data) {
                $shortageMessage .= "{$position} (Needed: {$data['needed']}, Available: {$data['available']}), ";
            }
            $shortageMessage = rtrim($shortageMessage, ', '); // Remove trailing comma
    
            return response()->json([
                'message' => $shortageMessage,
            ], 500);
        }
    
        // If no shortages, return true (or any success response)
        return true;
    }
    


    private function updateTeamRolesBasedOnStatsByRating()
    {
        $seasonId = get_current_season_id();
        $teams = DB::table('teams')->pluck('id');
        $storeStats = new AwardsController; // Instantiate once outside the loop
    
        foreach ($teams as $teamId) {
            DB::beginTransaction();
    
            try {
                // Fetch all players on the team, ordered by overall rating (veterans and rookies combined)
                $players = DB::table('players')
                    ->where('team_id', $teamId)
                    ->orderByDesc('overall_rating')
                    ->get();
    
                // Assign roles based on overall rating
                $starCount = 0;
                $allStarCount = 0;
                $starterCount = 0;
                $rolePlayerCount = 0;
    
                foreach ($players as $index => $player) {
                    if ($starCount < 1) {
                        // Highest PER player gets 'star player'
                        $newRole = 'star player';
                        $starCount++;
                    } elseif ($allStarCount < 2) {
                        // Next two highest PER players get 'all star'
                        $newRole = 'all star';
                        $allStarCount++;
                    } elseif ($starterCount < 2) {
                        // Next two highest PER players get 'starter'
                        $newRole = 'starter';
                        $starterCount++;
                    } elseif ($rolePlayerCount < 5) {
                        // Next five highest PER players get 'role player'
                        $newRole = 'role player';
                        $rolePlayerCount++;
                    } else {
                        // Remaining players get 'bench'
                        $newRole = 'bench';
                    }
    
                    $updateRole = DB::table('players')
                        ->where('id', $player->id)
                        ->update(['role' => $newRole]);
    
                    // Store player stats for the next season
                    $storeStats->storePlayerNextSeasonStats($teamId, $player->id);
                }
    
                // Commit the transaction for this team
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
    
                // Log the error message with more details
                \Log::error('Error assigning role for team ' . $teamId . ': ' . $e->getMessage());
    
                return false; // Return false if an error occurs during the update
            }
        }
    
        return true; // Return true if all updates succeed
    }
    

    private function storeNextSeasonStatsPerTeam()
    {
        $teams = DB::table('teams')->pluck('id');

        foreach ($teams as $teamId) {
            DB::beginTransaction(); // Keep transaction but check rollback issues

            try {
                //Log::info("Processing Team ID: {$teamId}");

                $allPlayersStats = DB::table('players')
                    ->where('team_id', $teamId)
                    ->where('is_active', 1)
                    ->where('contract_years','>', 0)
                    ->get();

                foreach ($allPlayersStats as $playerStat) {
                    $storeStats = new AwardsController;
                    $storeStats->storePlayerNextSeasonStats($teamId, $playerStat->id);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                //Log::error("Error assigning role for team {$teamId}: " . $e->getMessage());
                return $e->getMessage();
            }
        }

        return true;
    }


    private function determineContractYears($role)
    {
        switch ($role) {
            case 'star player':
                return rand(3, 7);
            case 'all star':
                return rand(3, 5);
            case 'starter':
                return rand(1, 5);
            case 'role player':
                return rand(1, 3);
            case 'bench':
                return rand(1, 2);
            default:
                return 1;
        }
    }

    //getFreeAgentsByPositionAndCompositeScore
    private function getBestAvailableFreeAgent($position, $usedPlayerIds)
    {
        $query = Player::select(
                'players.*',
                'teams.acronym as drafted_team',
                DB::raw("(SELECT GROUP_CONCAT(CONCAT(award_name, ' (Season ', season_id, ')') SEPARATOR ', ') FROM season_awards WHERE season_awards.player_id = players.id) as awards"),
                DB::raw("(SELECT CONCAT('Finals MVP (Season ', seasons.id, ')') FROM seasons WHERE seasons.finals_mvp_id = players.id LIMIT 1) as finals_mvp"),
                DB::raw("CASE WHEN players.id = (SELECT finals_mvp_id FROM seasons WHERE seasons.finals_mvp_id = players.id) THEN 1 ELSE 0 END as is_finals_mvp"),
                DB::raw("(SELECT GROUP_CONCAT(seasons.name SEPARATOR ', ') FROM seasons WHERE seasons.finals_mvp_id = players.id) as finals_mvp_seasons")
            )
            ->where('players.contract_years', 0) // Only free agents
            ->where('players.is_active', 1) // Only active players
            ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id'); // Join with teams for drafted team info
        
        // Filter by position if specified
        if ($position) {
            $query->where('players.position', 'LIKE', "%$position%"); // Like to handle multiple positions (e.g., PG/SG)
        }
        
        // Exclude already used players
        if (!empty($usedPlayerIds)) {
            $query->whereNotIn('players.id', $usedPlayerIds);
        }

        // Order by award value and role
        $query->orderByRaw("
            LENGTH(awards) DESC,
            is_finals_mvp DESC,
            FIELD(role, 'star player', 'all star', 'starter', 'role player', 'bench')
        ");

        return $query->first(); // Return just one best-fit free agent
    }

    private function getFreeAgentsByCompositeScore($currentSeasonId)
    {
        $freeAgents = Player::select(
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
        
        $freeAgents->orderByRaw("
            LENGTH(awards) DESC,
            is_finals_mvp DESC,
            FIELD(role, 'star player','all star', 'starter', 'role player', 'bench')
        ");

        return  $freeAgents->get();


    }
}