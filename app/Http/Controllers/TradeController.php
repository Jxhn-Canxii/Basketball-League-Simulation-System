<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TradeController extends Controller
{
    public function getPendingTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        $tradeType = $request->is_off_season ? 'off-season' : 'in-season';

        $latestSeasonId = $request->is_off_season ? get_current_season_id() + 1 : get_current_season_id();
    
        $proposals = DB::table('trade_proposals')
            ->join('teams as team_from', 'trade_proposals.team_from_id', '=', 'team_from.id')
            ->join('teams as team_to', 'trade_proposals.team_to_id', '=', 'team_to.id')
            ->join('players as player_from', 'trade_proposals.player_from_id', '=', 'player_from.id')
            ->join('players as player_to', 'trade_proposals.player_to_id', '=', 'player_to.id')
            ->select(
                'trade_proposals.id',
                'trade_proposals.season_id',
                'trade_proposals.type',
                'trade_proposals.status',
                'trade_proposals.created_at',
                'player_from.name as player_from_name',
                'player_from.role as player_from_role',
                'player_from.id as player_from_id',
                'player_to.name as player_to_name',
                'player_to.role as player_to_role',
                'player_to.id as player_to_id',
                'team_from.name as from_team',
                'team_to.name as to_team'
            )
            ->where('trade_proposals.status', 'pending')
            ->where('trade_proposals.type', $tradeType)
            ->where('trade_proposals.season_id', $latestSeasonId)
            ->orderBy('trade_proposals.created_at', 'desc')
            ->get();
    
        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $latestSeasonId,
            'trade_type' => $tradeType
        ]);
    }
    public function getApprovedTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        $tradeType = $request->is_off_season ? 'off-season' : 'in-season';

        $latestSeasonId = $request->is_off_season ? get_current_season_id() + 1 : get_current_season_id();

        $latestSeasonStatus = DB::table('seasons')
        ->where('id', DB::table('seasons')->max('id'))  // Find the latest season by the max ID
        ->value('status');  // Get the status of the latest season
    

        $tradeSeasonEnd = config('timeline.off_season_trade') === $latestSeasonStatus;

        $proposals = DB::table('trade_proposals')
            ->join('teams as team_from', 'trade_proposals.team_from_id', '=', 'team_from.id')
            ->join('teams as team_to', 'trade_proposals.team_to_id', '=', 'team_to.id')
            ->join('players as player_from', 'trade_proposals.player_from_id', '=', 'player_from.id')
            ->join('players as player_to', 'trade_proposals.player_to_id', '=', 'player_to.id')
            ->select(
                'trade_proposals.id',
                'trade_proposals.season_id',
                'trade_proposals.type',
                'trade_proposals.status',
                'trade_proposals.created_at',
                'player_from.name as player_from_name',
                'player_from.role as player_from_role',
                'player_from.id as player_from_id',
                'team_from.id as from_team_id',
                'team_from.name as from_team',
                'player_to.name as player_to_name',
                'player_to.role as player_to_role',
                'player_to.id as player_to_id',
                'team_to.id as to_team_id',
                'team_to.name as to_team'
            )
            ->where('trade_proposals.status', 'approved')
            ->where('trade_proposals.type', $tradeType)
            ->where('trade_proposals.season_id', $latestSeasonId)
            ->orderBy('trade_proposals.created_at', 'desc')
            ->get();
    
        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $latestSeasonId,
            'trade_season_end' => $tradeSeasonEnd,
            'current_status' => $latestSeasonStatus,
            'trade_type' => $tradeType
        ]);
    }
    public function automatedTradeDecision(Request $request)
    {   
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);
    
        $isOffSeason = (boolean) $request->is_off_season;
        $latestSeasonId = ($isOffSeason) ? get_current_season_id() + 1 : get_current_season_id();
        $storeStats = new AwardsController();
    
        // Fetch all pending trade proposals for the current season
        $proposals = DB::table('trade_proposals')
            ->where('season_id', $latestSeasonId)
            ->where('status', 'pending')
            ->get();
    
        $decisions = [];
    
        foreach ($proposals as $proposal) {
            try {
                // Step 1: Check if players are already involved in an approved trade
                $isPlayerInApprovedTrade = DB::table('trade_proposals')
                    ->where('season_id', $latestSeasonId)
                    ->where('status', 'approved')
                    ->where(function ($query) use ($proposal) {
                        $query->where('player_from_id', $proposal->player_from_id)
                              ->orWhere('player_to_id', $proposal->player_from_id)
                              ->orWhere('player_from_id', $proposal->player_to_id)
                              ->orWhere('player_to_id', $proposal->player_to_id);
                    })
                    ->exists();
    
                if ($isPlayerInApprovedTrade) {
                    DB::table('trade_proposals')
                        ->where('id', $proposal->id)
                        ->update(['status' => 'rejected', 'updated_at' => now()]);
    
                    $decisions[] = [
                        'proposal_id' => $proposal->id,
                        'status' => 'rejected',
                        'reason' => 'Player already involved in an approved trade this season.'
                    ];
                    continue;
                }
    
                // Step 2: Check if players exist
                $playerFromExists = DB::table('players')->where('id', $proposal->player_from_id)->exists();
                $playerToExists = DB::table('players')->where('id', $proposal->player_to_id)->exists();
    
                if (!$playerFromExists || !$playerToExists) {
                    Log::error("Trade failed: Player not found", ['player_from_id' => $proposal->player_from_id, 'player_to_id' => $proposal->player_to_id]);
                    continue;
                }
    
                // Step 3: Calculate performance scores
                $playerFromScore = $this->calculatePerformanceScore($this->getPlayerStats($proposal->player_from_id));
                $playerToScore = $this->calculatePerformanceScore($this->getPlayerStats($proposal->player_to_id));
    
                $scoreDifference = abs($playerFromScore - $playerToScore);
                $approvalChance = (!$isPlayerInApprovedTrade) ? 60 : 70; // Adjust approval chance based on trade history
    
                // Step 4: Decide trade outcome
                if (rand(1, 100) <= $approvalChance) {
                    // Approve and execute trade
                    DB::transaction(function () use ($proposal, $isOffSeason, $storeStats) {
                        $updated1 = DB::table('players')
                            ->where('id', $proposal->player_from_id)
                            ->update(['team_id' => $proposal->team_to_id]);
    
                        $updated2 = DB::table('players')
                            ->where('id', $proposal->player_to_id)
                            ->update(['team_id' => $proposal->team_from_id]);
    
                        if (!$updated1 || !$updated2) {
                            throw new \Exception("Player update failed");
                        }
    
                        DB::table('trade_proposals')
                            ->where('id', $proposal->id)
                            ->update(['status' => 'approved', 'updated_at' => now()]);
    
                        $tradeMessage = 'Trade Accepted.';
                        
                        if($isOffSeason){
                            $storeStats->storePlayerNextSeasonStats($proposal->team_to_id, $proposal->player_from_id);
                            $storeStats->storePlayerNextSeasonStats($proposal->team_from_id, $proposal->player_to_id);
                        }else{
                            $storeStats->storePlayerCurrentSeasonStats($proposal->team_to_id, $proposal->player_from_id);
                            $storeStats->storePlayerCurrentSeasonStats($proposal->team_from_id, $proposal->player_to_id);
                        }

                        $this->logTrade($proposal->team_to_id, $proposal->team_from_id, $proposal->player_to_id, $proposal->player_from_id, $tradeMessage, $isOffSeason);
                        $this->logTrade($proposal->team_from_id, $proposal->team_to_id, $proposal->player_from_id, $proposal->player_to_id, $tradeMessage, $isOffSeason);
                        
                    });
    
                    $decisions[] = [
                        'proposal_id' => $proposal->id,
                        'status' => 'approved',
                        'reason' => 'Trade approved successfully.'
                    ];
                } else {
                    // Reject the trade
                    DB::table('trade_proposals')
                        ->where('id', $proposal->id)
                        ->update(['status' => 'rejected', 'updated_at' => now()]);
    
                    $decisions[] = [
                        'proposal_id' => $proposal->id,
                        'status' => 'rejected',
                        'reason' => 'Trade rejected based on approval chance.'
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Trade processing error: " . $e->getMessage(), ['proposal_id' => $proposal->id]);
    
                $decisions[] = [
                    'proposal_id' => $proposal->id,
                    'status' => 'error',
                    'reason' => 'An error occurred while processing the trade.'
                ];
            }
        }
    
        return response()->json(['decisions' => $decisions]);
    }
    
    public static function logTrade($teamId, $opponentId, $playerId, $tradePlayerId, $message, $isOffSeason)
    {
        try {
            $latestSeasonId = get_current_season_id();
            $tradeType = ($isOffSeason == true) ? 'off-season trade' : 'in-season trade';
    
            // Fetch player details in a single query (avoiding multiple DB calls)
            $player = DB::table('players')->select('name', 'role')->where('id', $playerId)->first();
            $tradePlayer = DB::table('players')->select('name', 'role')->where('id', $tradePlayerId)->first();
    
            // Fetch team names
            $teamFrom = DB::table('teams')->where('id', $teamId)->value('name');
            $teamTo = DB::table('teams')->where('id', $opponentId)->value('name');
    
            // Ensure all required data exists before proceeding
            if (!$player || !$tradePlayer || empty($teamFrom) || empty($teamTo)) {
                Log::error("Trade log failed: Missing player or team data.", [
                    'player_id' => $playerId,
                    'trade_player_id' => $tradePlayerId,
                    'team_from' => $teamFrom,
                    'team_to' => $teamTo
                ]);
                return false; // Exit if required data is missing
            }
            
            // Insert into transactions table with both players and team names in details
            DB::table('transactions')->insert([
                'player_id' => $playerId,
                'season_id' => $latestSeasonId,
                'details' => "Traded {$player->name} ({$teamFrom}) in exchange for {$tradePlayer->name} ({$teamTo})",
                'from_team_id' => $teamId,
                'to_team_id' => $opponentId,
                'status' => $tradeType,
            ]);
            
             // Insert into trade_logs
             DB::table('trade_logs')->insert([
                'season_id' => $latestSeasonId,
                'team_from_id' => $teamId,
                'team_to_id' => $opponentId,
                'player_id' => $playerId,
                'player_name' => $player->name,
                'role' => $player->role,
                'trade_reason' => $message,
            ]);
            
            //Log::info("Trade logged successfully: {$player->name} ({$teamFrom}) swapped with {$tradePlayer->name} ({$teamTo})");
    
            //return true; // Indicate successful logging
        } catch (\Exception $e) {
            //Log::error("Trade Log Error: " . $e->getMessage(), ['exception' => $e]);
            //return false; // Indicate failure
        }
    }
    public function endInSeasonTradeWindow(){
        $latestSeasonId = get_current_season_id();

        DB::table('seasons')
        ->where('id',  $latestSeasonId)
        ->update(['status' => config('timeline.in_season_trade')]);

        return response()->json(['message' => 'Trade window ended!']);
    }
    public function endOffSeasonTradeWindow(){
        $latestSeasonId = get_current_season_id();

        DB::table('seasons')
        ->where('id',  $latestSeasonId)
        ->update(['status' => config('timeline.off_season_trade')]);

        return response()->json(['message' => 'Trade window ended!']);
    } 
    public function generateTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        $isOffSeason = $request->is_off_season;
        $tradeType =  $isOffSeason ? 'off-season' : 'in-season';
        $latestSeasonId = $isOffSeason ? get_current_season_id() + 1 : get_current_season_id();

        $teams = DB::table('teams')->pluck('id');
        $tradeProposals = [];
        $tradeablePlayers = [];
        $usedPlayers = [];  // Keep track of players already used in trades for this season
    
        // Step 1: Collect all tradeable players and calculate their scores
        foreach ($teams as $teamId) {
            $players = $this->findUnderperformingPlayers($teamId);
            foreach ($players as &$player) {
                $player['composite_score'] = $this->calculatePerformanceScore((object) $player);
            }
            $tradeablePlayers = array_merge($tradeablePlayers, $players);
        }
    
        // Add unhappy stars and calculate their scores
        // $unhappyStars = $this->findUnhappyStars();
        // foreach ($unhappyStars as &$star) {
        //     $star['composite_score'] = $this->calculatePerformanceScore((object) $star);
        // }
        // $tradeablePlayers = array_merge($tradeablePlayers, $unhappyStars);
    
        // Step 2: Sort players by performance score (highest first)
        usort($tradeablePlayers, fn($a, $b) => $b['composite_score'] <=> $a['composite_score']);
    
        // Step 3: Process multi-team trades
        while (!empty($tradeablePlayers)) {
            $bestPlayer = array_shift($tradeablePlayers); // Get highest-value player
            $tradePartners = [];
            $remainingScore = $bestPlayer['composite_score'];
    
            // Mark this player as used for this season
            $usedPlayers[] = $bestPlayer['player_id'];
    
            foreach ($tradeablePlayers as $key => $player) {
                // Ensure cross-team trade and player hasn't been used in another trade this season
                if ($player['team_id'] !== $bestPlayer['team_id'] && !in_array($player['player_id'], $usedPlayers)) {
                    $tradePartners[] = $player;
                    $remainingScore -= $player['composite_score'];
                    unset($tradeablePlayers[$key]); // Remove traded player
    
                    // Mark the player as used for this season
                    $usedPlayers[] = $player['player_id'];
    
                    if (abs($remainingScore) <= 10) { // Allow slight imbalance (10-point threshold)
                        break;
                    }
                }
            }
            
          
            // Multi-team trade formation
            if (!empty($tradePartners)) {
                foreach ($tradePartners as $tradePlayer) {
                    $tradeProposals[] = [
                        'season_id' => $latestSeasonId,
                        'team_from_id' => $bestPlayer['team_id'],
                        'team_to_id' => $tradePlayer['team_id'],
                        'player_from_id' => $bestPlayer['player_id'],
                        'player_to_id' => $tradePlayer['player_id'],
                        'type' => $tradeType,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
        }
    
        // Step 4: Store trade proposals in the database
        if (!empty($tradeProposals)) {
            DB::table('trade_proposals')->insert($tradeProposals);
        }
    
        return response()->json([
            'message' => 'Multi-team trade proposals generated successfully.',
            'trades' => $tradeProposals
        ]);
    }
    
    
    private function calculatePerformanceScore($player)
    {
        return (float) (
            ($player->avg_points_per_game ?? 0) * 2 +
            ($player->avg_rebounds_per_game ?? 0) * 1.5 +
            ($player->avg_assists_per_game ?? 0) * 1.5 +
            ($player->avg_steals_per_game ?? 0) * 2 +
            ($player->avg_blocks_per_game ?? 0) * 2 -
            ($player->avg_turnovers_per_game ?? 0) * 1.5
        );
    }

    private function findUnderperformingPlayers($teamId)
    {
        $latestSeasonId = get_current_season_id();
        $previousSeasonId = get_previous_season_id(); // Assuming seasons are sequential

        // Get top 6 teams per conference
        $topTeams = DB::table('standings_snapshots')
            ->where('season_id', $latestSeasonId)
            ->where('conference_rank', '<=', 6) // Top 6 teams per conference
            ->pluck('team_id')
            ->toArray();

        // Get star players and all-stars from top 6 teams in each conference
        $starPlayers = DB::table('players')
            ->whereIn('team_id', $topTeams)
            ->whereIn('players.role', ['star player', 'all star', 'starter']) // Filter by role
            ->pluck('players.id')
            ->toArray();

        // Fetch latest player stats, excluding star players and all-stars from top 6 teams
        $latestStats = DB::table('player_season_stats')
            ->join('players', 'player_season_stats.player_id', '=', 'players.id')
            ->where('players.team_id', $teamId)
            ->whereNotIn('players.id', $starPlayers) // Exclude stars and all-stars
            ->where('players.is_injured', 0)
            ->where('players.contract_years', '<=', 2)
            ->where('player_season_stats.season_id', $latestSeasonId)
            ->select(
                'players.id as player_id',
                'players.team_id',
                'players.role',
                'players.name as player_name',
                'player_season_stats.total_games',
                'player_season_stats.total_games_played',
                'player_season_stats.avg_minutes_per_game',
                'player_season_stats.avg_points_per_game',
                'player_season_stats.avg_rebounds_per_game',
                'player_season_stats.avg_assists_per_game',
                'player_season_stats.avg_steals_per_game',
                'player_season_stats.avg_blocks_per_game',
                'player_season_stats.avg_turnovers_per_game',
                'player_season_stats.avg_fouls_per_game'
            )
            ->get();

        $underperformingPlayers = [];

        foreach ($latestStats as $playerStats) {
            $previousStats = DB::table('player_season_stats')
                ->where('player_id', $playerStats->player_id)
                ->where('season_id', $previousSeasonId)
                ->first();

            $latestScore = $this->calculatePerformanceScore($playerStats);
            $previousScore = $previousStats ? $this->calculatePerformanceScore($previousStats) : 0;

            if ($previousScore > 0) {
                $declinePercentage = (($previousScore - $latestScore) / $previousScore) * 100;

                if ($declinePercentage >= 20) { // Threshold for a **super decline** (e.g., 20% drop)
                    $playerStats->super_decline = true;
                }
            }

            if ($latestScore < $previousScore) {
                $underperformingPlayers[] = (array) $playerStats;
            }
        }

        return $underperformingPlayers;
    }

    private function findUnhappyStars()
    {
        $latestSeasonId = get_current_season_id();

        $starPlayers = DB::table('players')
            ->join('player_season_stats', 'players.id', '=', 'player_season_stats.player_id')
            ->join('standings_snapshots', 'players.team_id', '=', 'standings_snapshots.team_id')
            ->where('standings_snapshots.season_id', $latestSeasonId)
            ->where('standings_snapshots.overall_rank', '>=', 56) // Teams ranked 56 or lower
            ->where('player_season_stats.season_id', $latestSeasonId)
            ->whereIn('players.role', ['star player', 'starter', 'all star']) // Filter by role
            ->select(
                'players.id as player_id',
                'players.team_id',
                'players.role',
                'players.name as player_name',
                'player_season_stats.total_games',
                'player_season_stats.total_games_played',
                'player_season_stats.avg_minutes_per_game',
                'player_season_stats.avg_points_per_game',
                'player_season_stats.avg_rebounds_per_game',
                'player_season_stats.avg_assists_per_game',
                'player_season_stats.avg_steals_per_game',
                'player_season_stats.avg_blocks_per_game',
                'player_season_stats.avg_turnovers_per_game',
                'player_season_stats.avg_fouls_per_game'
            )
            ->get();

        return $starPlayers->map(fn($player) => (array) $player)->toArray();
    }
    
    private function getPlayerStats($playerId)
    {
        $latestSeasonId = get_current_season_id();
        
        return DB::table('player_season_stats')
            ->where('player_id', $playerId)
            ->where('season_id', $latestSeasonId)
            ->first();
    }
}
