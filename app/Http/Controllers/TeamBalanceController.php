<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PlayerSeasonStatsController;
use App\Http\Controllers\FreeAgentController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\HelperController;

class TeamBalanceController extends Controller
{
    protected $storeStats;
    protected $contract;
    protected $freeAgent;
    protected $helper;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->storeStats = new PlayerSeasonStatsController();
        $this->contract = new ContractController();
        $this->freeAgent = new FreeAgentController();
        $this->helper = new HelperController();
    }

    public function fixTeamPositionBalance($teamId,$startSeason)
    {
        //remove positional balance functions
        // return true;

        $seasonId = get_current_season_id();
        $positions = ['PG', 'SG', 'SF', 'PF', 'C'];

        // Step 1: Get current roster count
        $rosterCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->count();

        // Step 2: Get position counts from view
        $counts = DB::table('players_by_team_and_position')
            ->where('team_id', $teamId)
            ->first();

        $teamName = $this->helper->getTeamName($teamId);

        $simulatedRounds = 0;

        if (!$counts) {
            return response()->json(['error' => 'Team not found in view.'], 404);
        }

        if(!$startSeason){

            // Get the latest season status (assuming you store this status in the 'seasons' table)
            $latestSeasonStatus = $this->helper->seasonStatus($seasonId);

            // Get the number of rounds that are already simulated (status != 2)
            $simulatedRounds = $this->helper->simulatedRounds($seasonId);

            // Get the total number of rounds in the season
            $totalRounds = $this->helper->totalRounds($seasonId);

            $canBalanceTeamRoster = ceil($totalRounds / 2);

            //total rounds is 15, round simulated is 12, this can automatically eligible to sign free agent players for playoff roster
            $isNotEligibleToBalanceTeam = $simulatedRounds >= $canBalanceTeamRoster && $latestSeasonStatus == 1;

            if($isNotEligibleToBalanceTeam) return;
        }

        $minimumPlayersPerPosition = 3;
        $posCounts = collect($counts)->only($positions)->map(fn($val) => (int) $val)->toArray();
        $positionsNeeding = collect($posCounts)->filter(fn($count) => $count < $minimumPlayersPerPosition);
        $positionsOverfilled = collect($posCounts)->filter(fn($count) => $count > $minimumPlayersPerPosition);

        // dd($rosterCount);
        // =============== CASE 1: Roster < 15 ====================
        if ($rosterCount < 15) {
            while ($rosterCount < 15) {
                $lowestPosition = collect($posCounts)->sort()->keys()->first();

                // Sign free agent
                $agent = $this->freeAgent->getBestFreeAgentAvailable($lowestPosition);
                if (!$agent) break;

                $contractYears = $this->contract->getContractYearsBasedOnRole($agent->role);

                DB::table('players')->where('id', $agent->player_id)->update([
                    'team_id' => $teamId,
                    'contract_years' => $contractYears,
                ]);

                DB::table('transactions')->insert([
                    'player_id' => $agent->player_id,
                    'season_id' => $seasonId,
                    'details' => "Signed by {$teamName} for a regular contract for {$contractYears} yrs",
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'signed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->storeStats->storePlayerCurrentSeasonStats($teamId, $agent->player_id);

                $posCounts[$lowestPosition]++;
                $rosterCount++;
            }

            return response()->json(['message' => 'Signed free agents to reach 15-man roster.']);
        }

        // =============== CASE 2: Roster == 15 && underfilled positions ================
        if ($rosterCount == 15 && $positionsNeeding->isNotEmpty() && !$startSeason) {
            foreach ($positionsNeeding as $position => $missing) {
                $minimumPlayersNeeded = $minimumPlayersPerPosition - $missing;
                $overflow = $positionsOverfilled->sortDesc()->keys()->first();

                // dd($teamId);
                $playerToWaive = DB::table('players')
                            ->where('players.team_id', $teamId)
                            ->where('players.is_active', true)
                            // ->where('players.is_injured', false)
                            // ->where('players.role','!=', 'star player')
                            ->whereNotIn('players.role', ['all star', 'star player'])
                            ->where(function ($query) use ($overflow) {
                                $query->where('players.position', $overflow)
                                    ->orWhere('players.position', 'like', $overflow . '/%')
                                    ->orWhere('players.position', 'like', '%/' . $overflow)
                                    ->orWhere('players.position', 'like', '%/' . $overflow . '/%');
                            })
                            ->select('players.*')
                            ->limit($minimumPlayersNeeded)
                            ->get()
                            ->map(function ($player) use ($seasonId) {
                                $stats = DB::table('player_season_stats')
                                    ->where('player_id', $player->id)
                                    ->where('season_id', $seasonId)
                                    ->get();

                                $player->total_games = $stats->sum('games_played');
                                $player->total_minutes = $stats->sum('minutes');
                                $player->avg_eff = $stats->avg('eff');
                                return $player;
                            })
                            ->sortBy([
                                ['avg_eff', 'asc'],
                                ['total_games', 'asc'],
                                ['total_minutes', 'asc'],
                                ['injury_history', 'desc'],
                                ['age', 'desc'],
                                ['contract_years', 'asc'],
                            ]);

                // dd($simulatedRounds);
                if($simulatedRounds > 2){
                    for ($i = 0; $i < $minimumPlayersNeeded ; $i++) {

                        if (!$overflow || $posCounts[$overflow] <= $minimumPlayersPerPosition) break;

                        // Try to trade first
                        $tradeData[$i] = $this->findTradePlayer($teamId, $position, $overflow, $seasonId);    

                        if ($tradeData[$i]) {
                            // Execute two-way trade
                            // Update player from other team to current team
                            DB::table('players')->where('id', $tradeData[$i]['incomingPlayer']->player_id)->update([
                                'team_id' => $teamId,
                            ]);

                            // Update player from current team to other team
                            DB::table('players')->where('id', $tradeData[$i]['outgoingPlayer']->player_id)->update([
                                'team_id' => $tradeData[$i]['otherTeamId'],
                            ]);

                            $tradedTeamName = $this->helper->getTeamName($tradeData[$i]['otherTeamId']);
                            $incomingTradeDetails = "Traded to {$teamName} in exchange for {$tradeData[$i]['outgoingPlayer']->player_name} ({$tradedTeamName})";
                            // Record transaction for incoming player
                            DB::table('transactions')->insert([
                                'player_id' => $tradeData[$i]['incomingPlayer']->player_id,
                                'season_id' => $seasonId,
                                'details' =>  $incomingTradeDetails,
                                'from_team_id' => $tradeData[$i]['otherTeamId'],
                                'to_team_id' => $teamId,
                                'status' => 'in-season trade',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $this->storeStats->storePlayerCurrentSeasonStats($teamId, $tradeData[$i]['incomingPlayer']->player_id);
                            // Record transaction for outgoing 
                            $outgoingTradeDetails = "Traded to {$tradedTeamName} in exchange for {$tradeData[$i]['incomingPlayer']->player_name} ({$teamName})";
                            
                            DB::table('transactions')->insert([
                                'player_id' => $tradeData[$i]['outgoingPlayer']->player_id,
                                'season_id' => $seasonId,
                                'details' => $outgoingTradeDetails,
                                'from_team_id' => $teamId,
                                'to_team_id' => $tradeData[$i]['otherTeamId'],
                                'status' => 'in-season trade',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $this->storeStats->storePlayerCurrentSeasonStats($tradeData[$i]['otherTeamId'], $tradeData[$i]['outgoingPlayer']->player_id);
                            // Update position counts
                            // $posCounts[$overflow]--;
                            // $posCounts[$position]++;
                        } else {
                            // Fall back to waiving and signing free agent
                            if (!$playerToWaive[$i]) continue;

                            // Waive player
                            DB::table('players')->where('id', $playerToWaive[$i]->id)->update([
                                'contract_years' => 0,
                                'team_id' => 0,
                            ]);

                            DB::table('transactions')->insert([
                                'player_id' => $playerToWaive[$i]->id,
                                'season_id' => $seasonId,
                                'details' => "Waived by {$teamName} to create a roster spot.",
                                'from_team_id' => $teamId,
                                'to_team_id' => 0,
                                'status' => 'waived',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Sign free agent
                            $replacement[$i] = $this->freeAgent->getBestFreeAgentAvailable($position);
                            if (!$replacement) continue;

                            $contractYears = $this->contract->getContractYearsBasedOnRole($replacement[$i]->role);

                            DB::table('players')->where('id', $replacement[$i]->player_id)->update([
                                'team_id' => $teamId,
                                'contract_years' => $contractYears,
                            ]);

                            DB::table('transactions')->insert([
                                'player_id' => $replacement[$i]->player_id,
                                'season_id' => $seasonId,
                                'details' => "Signed by {$teamName} for a regular contract for {$contractYears} yrs",
                                'from_team_id' => 0,
                                'to_team_id' => $teamId,
                                'status' => 'signed',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $this->storeStats->storePlayerCurrentSeasonStats($teamId, $replacement[$i]->player_id);
                        }

                        $posCounts[$overflow]--;
                        $posCounts[$position]++;
                    }
                }
            }

            return response()->json(['message' => 'Roster balanced by trading or waiving/signing players.']);
        }

        // =============== CASE 3: Roster is full and all positions are fine ================
        return response()->json(['message' => 'Roster already full and positionally balanced.']);
    }

    public function testFixTeamPositionBalance($teamId,$startSeason)
    {
        //remove positional balance functions
        // return true;

        $seasonId = get_current_season_id();
        $positions = ['PG', 'SG', 'SF', 'PF', 'C'];

        // Step 1: Get current roster count
        $rosterCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->count();

        // Step 2: Get position counts from view
        $counts = DB::table('players_by_team_and_position')
            ->where('team_id', $teamId)
            ->first();

        $teamName = $this->helper->getTeamName($teamId);

        if (!$counts) {
            return response()->json(['error' => 'Team not found in view.'], 404);
        }

        $minimumPlayersPerPosition = 3;
        $posCounts = collect($counts)->only($positions)->map(fn($val) => (int) $val)->toArray();
        $positionsNeeding = collect($posCounts)->filter(fn($count) => $count < $minimumPlayersPerPosition);
        $positionsOverfilled = collect($posCounts)->filter(fn($count) => $count > $minimumPlayersPerPosition);

        $balanceData = [];

        // =============== CASE 1: Roster < 15 ====================
        if ($rosterCount < 15) {
            while ($rosterCount < 15) {
                $lowestPosition = collect($posCounts)->sort()->keys()->first();

                // Sign free agent
                $agent = $this->freeAgent->getBestFreeAgentAvailable($lowestPosition);
                if (!$agent) break;

                $contractYears = $this->contract->getContractYearsBasedOnRole($agent->role);

                $balanceData['less-than-15'] = [
                    'player_id' => $agent->player_name,
                    'player_position' => $agent->position,
                    'lowest_position' => $lowestPosition,
                ];
                
                $posCounts[$lowestPosition]++;
                $rosterCount++;
            }

            // return response()->json(['message' => 'Signed free agents to reach 15-man roster.']);
        }

        $balanceData['is-needed'] = $positionsNeeding->isNotEmpty();
        // =============== CASE 2: Roster == 15 && underfilled positions ================
        if ($rosterCount == 15 && $positionsNeeding->isNotEmpty()) {
            $balanceData['positions-needed-count'] = $positionsNeeding;
            $balanceData['positions-overfilled-count'] = $positionsOverfilled;
            foreach ($positionsNeeding as $position => $missing) {
                $minimumPlayersNeeded = $minimumPlayersPerPosition - $missing;
                $overflow = $positionsOverfilled->sortDesc()->keys()->first();

                $playerToWaive = DB::table('players')
                            ->where('players.team_id', $teamId)
                            ->where('players.is_active', true)
                            ->where(function ($query) use ($overflow) {
                                $query->where('players.position', $overflow)
                                    ->orWhere('players.position', 'like', $overflow . '/%')
                                    ->orWhere('players.position', 'like', '%/' . $overflow)
                                    ->orWhere('players.position', 'like', '%/' . $overflow . '/%');
                            })
                            ->select('players.*')
                            ->limit($minimumPlayersNeeded)
                            ->get()
                            ->map(function ($player) use ($seasonId) {
                                $stats = DB::table('player_season_stats')
                                    ->where('player_id', $player->id)
                                    ->where('season_id', $seasonId)
                                    ->get();

                                $player->total_games = $stats->sum('games_played');
                                $player->total_minutes = $stats->sum('minutes');
                                $player->avg_eff = $stats->avg('eff');
                                return $player;
                            })
                            ->sortBy([
                                ['avg_eff', 'asc'],
                                ['total_games', 'asc'],
                                ['total_minutes', 'asc'],
                                ['injury_history', 'desc'],
                                ['age', 'desc'],
                                ['contract_years', 'asc'],
                            ]);

                for ($i = 0; $i < $minimumPlayersNeeded ; $i++) {

                    $balanceData['overflow'] = $overflow;
                    if (!$overflow || $posCounts[$overflow] <= $minimumPlayersPerPosition) break;

                    $balanceData['position-counts-z'] = $i;
                    // Try to trade first
                    $tradeData[$i] = $this->findTradePlayer($teamId, $position, $overflow, $seasonId);

                    $balanceData['trade-data'][$i] = $tradeData[$i];
                    if($tradeData[$i]) {

                        $balanceData['balance-roster-via-trade-incoming'][$i] = [
                            'player_id' => $tradeData[$i]['incomingPlayer']->player_name,
                            'player_position' => $tradeData[$i]['incomingPlayer']->position,
                            'lowest_position' => $position,
                        ];

                        $balanceData['balance-roster-via-trade-outgoing'][$i] = [
                            'player_id' => $tradeData[$i]['outgoingPlayer']->player_name,
                            'player_position' => $tradeData[$i]['outgoingPlayer']->position,
                            'lowest_position' => $position,
                        ];

                    } else {
                        // Fall back to waiving and signing free agent
                        if (!$playerToWaive[$i]) continue;

                        $balanceData['balance-roster-via-waiving'][$i] = [
                            'player_id' => $playerToWaive[$i]->name,
                            'player_position' => $playerToWaive[$i]->position,
                            'lowest_position' => $position,
                        ];
                        // Sign free agent
                        $replacement[$i] = $this->freeAgent->getBestFreeAgentAvailable($position);
                        if (!$replacement) continue;

                        $balanceData['balance-roster-via-replacement'][$i] = [
                            'player_id' => $replacement[$i]->name,
                            'player_position' => $replacement[$i]->position,
                            'lowest_position' => $position,
                        ];

                        $contractYears = $this->contract->getContractYearsBasedOnRole($replacement[$i]->role);
                        
                    }

                    $posCounts[$overflow]--;
                    $posCounts[$position]++;
                }
            }

            return $balanceData;
            // return response()->json(['data' => $balanceData, 'message' => 'Roster balanced by trading or waiving/signing players.']);
        }

        // =============== CASE 3: Roster is full and all positions are fine ================
        // return $balanceData;
    }

    public function signPlayerOffWaiver($teamId)
    {

        $seasonId = get_current_season_id();

        // Get the latest season status (assuming you store this status in the 'seasons' table)
        $latestSeasonStatus = $this->helper->seasonStatus($seasonId);

         // Get the number of rounds that are already simulated (status != 2)
        $simulatedRounds = $this->helper->simulatedRounds($seasonId);

        // Get the total number of rounds in the season
        $totalRounds = $this->helper->totalRounds($seasonId);

        $currentConferenceRank = $this->helper->currentSeasonConferenceRank($teamId);

        $canSignPlayerOffWaiver = $simulatedRounds >= ($totalRounds - 3);

        $allowedTeamsToSign = ($currentConferenceRank >= 12) && ($currentConferenceRank < 6);

        $secondQuarterRegularSeason = $latestSeasonStatus == 2;

        $chancesToSign = 50 > rand(0,100);

        //total rounds is 15, round simulated is 12, this can automatically eligible to sign free agent players for playoff roster
        $isEligibleToSignOffWaiver = $chancesToSign && $allowedTeamsToSign && $canSignPlayerOffWaiver && $secondQuarterRegularSeason;

        if(!$isEligibleToSignOffWaiver) return;

        // Sign free agent
        $bestPlayerOffWaiver = $this->freeAgent->getBestFreeAgentOffWaiver();
        $position = $bestPlayerOffWaiver->position;
        
        $contractYears = $this->contract->getContractYearsBasedOnRole($bestPlayerOffWaiver->role);

        $claimedViaWaiverTresholdLimit = 1;
        
        $claimedViaWaiverTresholdCount = DB::table('transactions')
            ->where('to_team_id', $teamId)
            ->where('season_id', $seasonId)
            ->where('status', 'claimed-via-waiver')
            ->count('id');

        if($claimedViaWaiverTresholdCount > $claimedViaWaiverTresholdLimit) return;

        if(!$bestPlayerOffWaiver) return;

                // Fall back to waiving and signing free agent
        $playerToWaive = DB::table('players')
            ->leftJoin('player_season_stats as pss', 'players.id', '=', 'pss.player_id')
            ->where('players.team_id', $teamId)
            ->where('players.is_active', true)
            ->where('pss.season_id', $seasonId)
            ->where(function ($query) use ($position) {
                $query->where('players.position', $position)
                    ->orWhere('players.position', 'like', $position . '/%')
                    ->orWhere('players.position', 'like', '%/' . $position)
                    ->orWhere('players.position', 'like', '%/' . $position . '/%');
            })
            ->select('players.*')
            ->orderBy('pss.eff')
            ->orderBy('pss.performance_points')
            ->get()
            ->first();
        
        if(!$playerToWaive) return;

        DB::table('players')->where('id', $bestPlayerOffWaiver->player_id)->update([
            'team_id' => $teamId,
            'contract_years' => $contractYears,
        ]);

        DB::table('transactions')->insert([
            'player_id' => $bestPlayerOffWaiver->player_id,
            'season_id' => $seasonId,
            'details' => "Team has signed player off waiver",
            'from_team_id' => 0,
            'to_team_id' => $teamId,
            'status' => 'claimed-via-waiver',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'player_id' => $bestPlayerOffWaiver->player_id,
            'season_id' => $seasonId,
            'details' => "Signed player off waiver",
            'from_team_id' => 0,
            'to_team_id' => $teamId,
            'status' => 'signed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->storeStats->storePlayerCurrentSeasonStats($teamId, $bestPlayerOffWaiver->player_id);

        // Waive player
        DB::table('players')->where('id', $playerToWaive->id)->update([
            'contract_years' => 0,
            'team_id' => 0,
        ]);

        DB::table('transactions')->insert([
            'player_id' => $playerToWaive->id,
            'season_id' => $seasonId,
            'details' => "Waived to claim other player off waiver",
            'from_team_id' => $teamId,
            'to_team_id' => 0,
            'status' => 'waived',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function findTradePlayer($teamId, $neededPosition, $overfilledPosition, $seasonId)
    {
        // Find teams with overfilled needed position and underfilled position matching current team's overfilled position

        $tradeCandidate = DB::table('players_by_team_and_position')
            ->join('players', function ($join) use ($neededPosition) {
                $join->on('players_by_team_and_position.team_id', '=', 'players.team_id')
                    ->where('players.is_active', true)
                    ->where(function ($query) use ($neededPosition) {
                        $query->where('players.position', $neededPosition)
                            ->orWhere('players.position', 'like', $neededPosition . '/%')
                            ->orWhere('players.position', 'like', '%/' . $neededPosition)
                            ->orWhere('players.position', 'like', '%/' . $neededPosition . '/%');
                    });
            })
            ->where('players_by_team_and_position.' . $neededPosition, '>', 3)
            ->where('players_by_team_and_position.' . $overfilledPosition, '<', 3)
            ->where('players_by_team_and_position.team_id', '!=', $teamId)
            ->select(
                'players.id as player_id',
                'players.name as player_name',
                'players.team_id as current_team_id',
                'players.position',
                'players.contract_years',
                'players_by_team_and_position.team_id as other_team_id'
            )
            ->get()
            ->map(function ($player) use ($seasonId) {
                $stats = DB::table('player_season_stats')
                    ->where('player_id', $player->player_id)
                    ->where('season_id', $seasonId)
                    ->get();

                $player->total_games = $stats->sum('games_played');
                $player->total_minutes = $stats->sum('minutes');
                $player->avg_eff = $stats->avg('eff');
                return $player;
            })
            ->sortBy([
                ['contract_years', 'asc'],
                ['avg_eff', 'asc'],
            ])
            ->first();

        if (!$tradeCandidate) {
            return null;
        }

        // Find a player from the current team to trade back (from the overfilled position)
        $outgoingPlayer = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->where(function ($query) use ($overfilledPosition) {
                $query->where('position', $overfilledPosition)
                    ->orWhere('position', 'like', $overfilledPosition . '/%')
                    ->orWhere('position', 'like', '%/' . $overfilledPosition)
                    ->orWhere('position', 'like', '%/' . $overfilledPosition . '/%');
            })
            ->select(
                'id as player_id',
                'name as player_name',
                'team_id as current_team_id',
                'position',
                'contract_years'
            )
            ->get()
            ->map(function ($player) use ($seasonId) {
                $stats = DB::table('player_season_stats')
                    ->where('player_id', $player->player_id)
                    ->where('season_id', $seasonId)
                    ->get();

                $player->total_games = $stats->sum('games_played');
                $player->total_minutes = $stats->sum('minutes');
                $player->avg_per = $stats->avg('per');
                return $player;
            })
            ->sortBy([
                ['contract_years', 'asc'],
                ['avg_per', 'asc'],
            ])
            ->first();

        if (!$outgoingPlayer) {
            return null;
        }

        return [
            'incomingPlayer' => $tradeCandidate,
            'outgoingPlayer' => $outgoingPlayer,
            'otherTeamId' => $tradeCandidate->other_team_id,
            'outgoingPosition' => $overfilledPosition,
        ];
    }
}
