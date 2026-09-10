<?php

namespace App\Services\Player;

ini_set('max_execution_time', 300); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Exception;
use Inertia\Inertia;
use App\Models\Seasons;
use App\Models\Teams;
use App\Models\Player;
use App\Services\Coach\CoachDecisionService;
use App\Services\Contract\ContractService;
use Illuminate\Support\Facades\DB;

class PlayerRatingsService
{
    protected  $contractService;
    protected  $coachDecisionService;

    public function __construct()
    {
        $this->coachDecisionService = new CoachDecisionService();
        $this->contractService = new ContractService();
    }

    public function updateRookieContract($teamId){
         $contracts = DB::table('player_contracts as pc')
                    ->select('pc.*','p.name as player_name','p.is_rookie')
                    ->join('players as p','p.id','=','pc.player_id')
                    ->where('pc.team_id',$teamId)
                    ->where('p.is_rookie',1)
                    ->get();

        // dd($contracts);

        $players = [];

        foreach ($contracts as $contract) {
                DB::table('players')
                    ->where('id', $contract->player_id)
                    ->update([
                        'salary' => $contract->salary,
                        'contract_type' => $contract->contract_type,
                        'player_option' => $contract->player_option,
                        'team_option' => $contract->team_option,
                        'no_trade_clause' => $contract->no_trade_clause,
                    ]);
                
                $players[] = $contract->player_name;
        }

        return $players;

    }
    //
    public function updateActivePlayers($request)
    {
        DB::beginTransaction(); // Start transaction

        try {

            $teamId = $request->team_id;
            $isLast = $request->is_last;

            // Get the last (highest) team ID in the teams table
            $lastTeamId = DB::table('teams')->max('id');

            // Fetch all active players, filtered by team_id if provided
            $query = Player::where('is_active', 1)
                ->where('age', '<=', 65);

            if ($teamId) {
                $query->where('team_id', $teamId);
            }
            $players = $query->get();

            $seasonId = get_current_season_id();
            $nextSeasonId = get_current_season_id() + 1;
            // Fetch the team name if team_id is provided
            $teamName = '';
            if ($teamId) {
                $team = Teams::find($teamId);
                if ($team) {
                    $teamName = $team->name;
                }
                $this->updateCoachContract($teamId);
            }

            $improvedPlayers = [];
            $declinedPlayers = [];
            $reSignedPlayers = []; // Track re-signed players

            // Fetch player statistics for the current season
            $stats = DB::table('player_season_stats')
                ->join('players', 'players.id', '=', 'player_season_stats.player_id') // Join on player ID
                ->where('player_season_stats.season_id', $seasonId)
                ->where('players.team_id', $teamId)
                ->select(
                    'player_season_stats.*',
                    'players.name as player_name',
                    'players.contract_years as contract_years',
                    'players.hardship_contract as hardship_contract',
                    'players.role as role',
                    'players.age as age',
                    'players.shooting_rating as shooting_rating',
                    'players.defense_rating as defense_rating',
                    'players.passing_rating as passing_rating',
                    'players.rebounding_rating as rebounding_rating',
                    'players.overall_rating as overall_rating',
                    'players.basketball_iq_rating as basketball_iq_rating',
                    'players.injury_prone_percentage as injury_prone_percentage',
                    'players.is_rookie as is_rookie',
                    'players.is_active as is_active',
                    'players.retirement_age as retirement_age',
                    'players.injury_recovery_games as injury_recovery_games',
                    'players.position', // Add any additional player fields
                    'players.salary', // Add any additional player fields
                    'players.player_option', // Add any additional player fields
                    'players.team_option', // Add any additional player fields
                    'players.loyalty_rating', // Add any additional player fields
                    'players.satisfaction_rating', // Add any additional player fields
                    'players.negotation_skill_rating', // Add any additional player fields
                    'players.ambition_rating' // Add any additional player fields
                )
                ->orderByDesc('player_season_stats.eff') // Sort directly in the query
                ->get();

            // Rank players and assign roles
            $rankedPlayers = $stats->values();

            // Assign the top 3 players as "star player"
            $rankedPlayers->take(1)->each(function ($playerStat) {
                Player::where('id', $playerStat->player_id)->update(['role' => 'star player','is_reserved' => false]);
            });
            // Assign the next 2 players as "all star"
            $rankedPlayers->slice(1, 2)->each(function ($playerStat) {
                Player::where('id', $playerStat->player_id)->update(['role' => 'all star','is_reserved' => false]);
            });

            // Assign the next 2 players as "starter"
            $rankedPlayers->slice(3, 2)->each(function ($playerStat) {
                Player::where('id', $playerStat->player_id)->update(['role' => 'starter','is_reserved' => false]);
            });

            // Assign the next 5 players as "role players"
            foreach ($rankedPlayers->slice(5, 5) as $playerStat) {
                Player::where('id', $playerStat->player_id)->update(['role' => 'role player','is_reserved' => false]);
            }

            // Assign the next 2 players as "bench players"
            foreach ($rankedPlayers->slice(10, 2) as $playerStat) {
                Player::where('id', $playerStat->player_id)->update(['role' => 'bench','is_reserved' => false]);
            }

            foreach ($rankedPlayers->slice(12, 3) as $playerStat) {
                Player::where('id', $playerStat->player_id)->update(['role' => 'bench','is_reserved' => true]);
            }

            // foreach ($rankedPlayers as $player) {
    
            //     $totalGames = $player->total_games ?? 0;
            //     $rolePctMap = [
            //         'star player' => 0.80,
            //         'all star'    => 0.70,
            //         'starter'     => 0.60,
            //         'role player' => 0.50,
            //         'bench'       => 0.40,
            //     ];

            //     $defaultPct = 0.30;
            //     $pct = $rolePctMap[strtolower($player->role)] ?? $defaultPct;

            //     // Base total games across contract
            //     $totalContractGames = $totalGames * max($player->contract_years, 1);

            //     // Cap to avoid excessive tolerance (realism)
            //     $baseRecoveryGames = ceil($totalContractGames * $pct);
            //     $maxRecoveryGames = 30;
            //     $requiredRecoveryGames = min($baseRecoveryGames, $maxRecoveryGames);

            //     // 🔥 ADJUST BASED ON TALENT
            //     if ($player->overall_rating >= 90) {
            //         $requiredRecoveryGames += 5; // elite talent, more forgiveness
            //     } elseif ($player->overall_rating >= 80) {
            //         $requiredRecoveryGames += 2;
            //     } elseif ($player->overall_rating <= 70) {
            //         $requiredRecoveryGames -= 2; // low-rated, less tolerance
            //     } elseif ($player->overall_rating <= 60) {
            //         $requiredRecoveryGames -= 4; // waiver bait
            //     }

            //     // Clamp within logical bounds
            //     $requiredRecoveryGames = max(2, min($requiredRecoveryGames, $totalContractGames));

            //     // Check if the player has recovered from injury for over 30 games and contract years is less than 4, may be waived 
            //     if ($player->injury_recovery_games > $requiredRecoveryGames) {
            //         $waiveChance = rand(1, 100); // Random chance for waiving the player
            //         if ($waiveChance <= 60) { // 50% chance to waive
            //             DB::table('transactions')->insert([
            //                 'player_id' => $player->id,
            //                 'season_id' => $seasonId,
            //                 'details' => 'Waived by (' . $teamName . ') due to extended injury recovery period',
            //                 'from_team_id' => $teamId,
            //                 'to_team_id' => 0,
            //                 'status' => 'waived',
            //             ]);

            //             DB::table('player_contracts')
            //                 ->where('player_id', $player->id)
            //                 ->update(['status' => 'terminated']);
                        
            //             DB::table('players')
            //                 ->where('id', $player->id)
            //                 ->update([
            //                     'team_id' => 0,
            //                     'contract_years' => 0,
            //                     'salary' => 0,
            //                     'contract_type' => 0,
            //                     'player_option' => 0,
            //                     'team_option' => 0,
            //                     'no_trade_clause' => 0
            //                 ]);
                
            //             $player->team_id = 0; // Set team_id to 0 (free agent)
                        
            //             $player->contract_years = 0; // Remove contract
            //         }
            //     }

            // }
            // Fetch updated players
            $players = $query->get();

            foreach ($players as $player) {
                // Check if the player's ratings have already been updated for the current season
                $ratingExists = DB::table('player_ratings')
                    ->where('player_id', $player->id)
                    ->where('season_id', $seasonId)
                    ->exists();

                if ($ratingExists) {
                    // Skip updating this player if already updated
                    continue;
                }

                // Store old ratings and role for comparison
                $oldRatings = [
                    'shooting' => $player->shooting_rating,
                    'defense' => $player->defense_rating,
                    'passing' => $player->passing_rating,
                    'rebounding' => $player->rebounding_rating,
                    'overall' => $player->overall_rating,
                ];
                $oldRole = $player->role;

                // Deduct contract_years by 1 and increment age by 1
                $player->contract_years -= 1;
                $player->age += 1;
                $player->is_rookie = 0; // All players are no longer rookies

                // Check for retirement
                if ($player->age >= $player->retirement_age) {

                    DB::table('transactions')->insert([
                        'player_id' => $player->id,
                        'season_id' => $seasonId,
                        'details' => 'has retired from the league.[Last team: ' . $teamName . ']',
                        'from_team_id' => $player->team_id,
                        'to_team_id' => 0,
                        'status' => 'retired',
                    ]);

                    DB::table('player_contracts')
                        ->where('player_id', $player->id)
                        ->where('season_id',$seasonId)
                        ->where('status','signed')
                        ->update(['status' => 'terminated']);

                    DB::table('players')
                        ->where('id', $player->id)
                        ->update([
                            'team_id' => 0,
                            'contract_years' => 0,
                            'salary' => 0,
                            'contract_type' => 0,
                            'player_option' => 0,
                            'team_option' => 0,
                            'no_trade_clause' => 0
                        ]);

                    $player->is_active = 0;
                    $player->contract_years = 0;
                    $player->team_id = 0;
                }

                // Check if the player was injured during the season
                $injury = DB::table('injured_players_view')
                    ->where('player_id', $player->id)
                    ->where('season_id', $seasonId)
                    ->where('status', 'Injured') // Check if the injury is still ongoing
                    ->first();

                if ($injury) {
                    // Apply a penalty to ratings based on the injury
                    $penaltyFactor = 0.8;  // Example: Reduce by 20%
                    $player->shooting_rating *= $penaltyFactor;
                    $player->defense_rating *= $penaltyFactor;
                    $player->passing_rating *= $penaltyFactor;
                    $player->rebounding_rating *= $penaltyFactor;
                    $player->overall_rating *= $penaltyFactor;

                }

                $performanceData = $this->comparePerformanceBetweenSeasons($player->id);

                // $latestPerformance = $performanceData['latest_performance'];
                // $previousPerformance = $performanceData['previous_performance'];
                $performanceChange = $performanceData['performance_change'];

                // Apply performance change to further adjust the ratings
                foreach (['shooting', 'defense', 'passing', 'rebounding'] as $category) {
                    if ($performanceChange[$category] > 0) {
                        // Increase the rating if performance has improved
                        $player->{$category . '_rating'} = min($player->{$category . '_rating'} + 2, 99);
                    } elseif ($performanceChange[$category] < 0) {
                        // Decrease the rating if performance has declined
                        $player->{$category . '_rating'} = max($player->{$category . '_rating'} - 2, 40);
                    }
                }

                // Recalculate the player's overall rating
                $player->overall_rating = ($player->shooting_rating + $player->defense_rating + $player->passing_rating + $player->rebounding_rating) / 4;

                // Check for improvements or declines
                if ($player->overall_rating > $oldRatings['overall'] || $this->rolePriority[$player->role] < $this->rolePriority[$oldRole]) {
                    // Player improved if the overall rating increased or the role was promoted (higher priority)
                    $improvedPlayers[] = $player;
                } elseif ($player->overall_rating < $oldRatings['overall'] || $this->rolePriority[$player->role] > $this->rolePriority[$oldRole]) {
                    // Player declined if the overall rating decreased or the role was demoted (lower priority)
                    $declinedPlayers[] = $player;
                }

                $player->overall_rating = ($player->overall_rating > $player->potential_rating) ? $player->potential_rating : $player->overall_rating;
                $updateTeamId = ($player->contract_years == 0) ? 0 : $player->team_id;

                DB::table('players')->where('id', $player->id)->update([
                    'contract_years' => $player->contract_years,
                    'team_id' => $updateTeamId,
                    'is_active' => $player->is_active,
                    'is_rookie' => $player->is_rookie,
                    'age' => $player->age,
                    'role' => $player->role,
                    'shooting_rating' => $player->shooting_rating,
                    'defense_rating' => $player->defense_rating,
                    'passing_rating' => $player->passing_rating,
                    'rebounding_rating' => $player->rebounding_rating,
                    'overall_rating' => $player->overall_rating,
                    'injury_prone_percentage' => $player->injury_prone_percentage,
                ]);

                // Log the updated ratings
                $this->logPlayerRatings($player, $seasonId);

                if($player->contract_years == 0 || ($player->contract_years == 1 && $player->team_option == 1)){
                    $this->playerCoachDecision($player,$teamId, $teamName,$nextSeasonId);
                }

                if($player->contract_years == 1 && $player->player_option == 1){
                    $this->playerDecision($player,$teamId, $teamName,$nextSeasonId);
                }
            }

            // Show alert if this is the last update
            if ($teamId == $lastTeamId) {

                $this->updatePlayerAndCoachAge();
                $this->promoteRetiredPlayersToCoaches();

                // Update season status
                $season = Seasons::find($seasonId);
                if ($season) {
                    $season->status = config('timeline.player_update');
                    $season->save();
                }

                DB::commit(); // Commit transaction

                return response()->json([
                    'error' => false,
                    'message' => 'All player statuses have been updated successfully. Update finished.',
                    'team_name' => $teamName,
                    'improved_players' => $improvedPlayers,
                    'declined_players' => $declinedPlayers,
                    're_signed_players' => $reSignedPlayers, // Include re-signed players in response
                ]);
            }

            DB::commit(); // Commit transaction

            return response()->json([
                'error' => false,
                'message' => 'All player statuses have been updated successfully.',
                'team_name' => $teamName,
                'improved_players' => $improvedPlayers,
                'declined_players' => $declinedPlayers,
                're_signed_players' => $reSignedPlayers, // Include re-signed players in response
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error

            return response()->json([
                'error' => true,
                'message' => 'Failed to update player statuses.',
                'error_message' => $e->getMessage(), // Display the exception message
            ], 500);
        }
    }

    public function updateCoachContract($teamId)
    {
        $team = DB::table('teams')->where('id', $teamId)->first();

        if (!$team) {
            return response()->json([
                'message' => 'Team not found!'
            ], 404);
        }

        $coachId = $team->coach_id;

        if ($coachId == 0) {
            return response()->json([
                'message' => 'This team does not have a coach assigned.'
            ], 400);
        }

        $coach = DB::table('coaches')->where('id', $coachId)->first();

        if (!$coach || $coach->contract_years <= 0) {
            return response()->json([
                'message' => 'The coach does not have any contract years remaining.'
            ], 400);
        }

        $newContractYears = $coach->contract_years - 1;
        $newAge = $coach->age + 1;
        $newExperienceYears = $coach->experience_years + 1;  // Adding experience years

        // Check if the new age has reached or exceeded the retirement age
        $isRetired = $newAge >= $coach->retirement_age;

        // Update the coach's contract, age, experience years, and active status
        DB::table('coaches')
            ->where('id', $coachId)
            ->update([
                'contract_years' => $newContractYears,
                'age' => $newAge,
                'experience_years' => $newExperienceYears,
                'is_active' => $isRetired ? 0 : 1,  // Set is_active to false if retired
                'updated_at' => now(),
            ]);




        if ($newContractYears == 0) {
            $latestSeasonId = get_current_season_id();

            $teamPerformance = DB::table('standings_view')
                ->where('team_id', $teamId)
                ->where('season_id', $latestSeasonId)
                ->select('wins', 'losses')
                ->first();

            if (!$teamPerformance) {
                // If no standings data, automatically waive coach
                DB::table('teams')
                    ->where('id', $teamId)
                    ->update([
                        'coach_id' => 0,
                        'updated_at' => now(),
                    ]);

                DB::table('coaches')
                    ->where('id', $coachId)
                    ->update([
                        'team_id' => 0,
                        'updated_at' => now(),
                    ]);
                // Insert into transactions table (waived)
                DB::table('transactions')->insert([
                    'player_id' => 0,
                    'season_id' => $latestSeasonId,
                    'details' => $coach->name . ' has been waived as head coach of ' . $team->name,
                    'from_team_id' => $team->id,
                    'to_team_id' => 0,
                    'status' => 'waived',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return true;
            }

            $wins = $teamPerformance->wins ?? 0;
            $losses = $teamPerformance->losses ?? 0;
            $gamesPlayed = $wins + $losses;
            $winRate = $gamesPlayed > 0 ? ($wins / $gamesPlayed) : 0;

            if ($winRate >= 0.55) {
                // Re-sign coach
                $newContractYears = rand(3, 5);

                DB::table('coaches')
                    ->where('id', $coachId)
                    ->update([
                        'contract_years' => $newContractYears,
                        'updated_at' => now(),
                    ]);

                // Insert into transactions table (re-signed)
                DB::table('transactions')->insert([
                    'player_id' => 0,
                    'season_id' => $latestSeasonId,
                    'details' => $coach->name . ' has been re-signed as head coach of ' . $team->name . ' after a good season performance.',
                    'from_team_id' => 0,
                    'to_team_id' => $team->id,
                    'status' => 're-signed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return true;
            } else {
                // Waive coach
                DB::table('teams')
                    ->where('id', $teamId)
                    ->update([
                        'coach_id' => 0,
                        'updated_at' => now(),
                    ]);

                DB::table('coaches')
                    ->where('id', $coachId)
                    ->update([
                        'team_id' => 0,
                        'updated_at' => now(),
                    ]);
                // Insert into transactions table (waived)
                DB::table('transactions')->insert([
                    'player_id' => 0,
                    'season_id' => $latestSeasonId,
                    'details' => $coach->name . ' has been fired as a head coach of ' . $team->name . ' due to poor season performance.',
                    'from_team_id' => $team->id,
                    'to_team_id' => 0,
                    'status' => 'fired',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return true;
            }
        }

        return true;
    }

    private function updatePlayerAndCoachAge()
    {
        // Update active, free agent players with age <= 65
        DB::table('players')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->update([
                'is_rookie' => 0,
                'age' => DB::raw('age + 1'),
                'contract_years' => DB::raw("CASE WHEN age + 1 >= retirement_age THEN 0 ELSE contract_years END"),
                'team_id' => 0,
                'is_active' => DB::raw("CASE WHEN age + 1 >= retirement_age THEN 0 ELSE is_active END"),
            ]);

        // Update non-active coaches with age <= 65
        DB::table('coaches')
            ->where('team_id', 0)
            ->update([
                'team_id' => 0,
                'contract_years' => 0,
                'age' => DB::raw('age + 1'),
            ]);
    }

    private function promoteRetiredPlayersToCoaches()
    {
        $retiredHighIQPlayers = DB::table('players')
            ->where('is_active', 0)
            ->where('basketball_iq_rating', '>=', 85)
            ->get();

        foreach ($retiredHighIQPlayers as $player) {
            $alreadyCoach = DB::table('coaches')
                ->where('name', $player->name)
                ->exists();
            
            if ($alreadyCoach) {
                continue;
            }

            $retirementAge = rand($player->age + 5, 65);
            $coachingStyle = array_rand(['defensive', 'offensive', 'balanced', 'fast-paced', 'slow-tempo'],1);
            $preferredTeamComposition = array_rand(['balanced','offensive','defensive','any','contenders','rebuilding'],1);
        
           
            DB::table('coaches')->insert([
                'name' => $player->name,
                'nationality' => $player->nationality,
                'coach_iq' => $player->basketball_iq_rating,
                'coaching_style' => $coachingStyle ?? 'balanced',
                'retirement_age' => $retirementAge,
                'experience_years' => 0,
                'offensive_rating' => rand(50,99), 
                'defensive_rating' => rand(50,99), 
                'development_rating'=> rand(50,99),
                'leadership_rating'=> rand(50,99),
                'strategy_rating'=> rand(50,99),
                'work_ethic_rating' => rand(50,99),
                'preferred_team_composition' => $preferredTeamComposition,
                'team_id' => 0,
                'career_wins' => 0,
                'career_losses' => 0,
                'is_active' => 1,
                'player_id' => $player->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
        }
    }

    // Define role priority (lower number = higher priority)
    protected $rolePriority = [
        'star player' => 1,
        'all star' => 2,
        'starter' => 2,
        'role player' => 3,
        'bench' => 4,
    ];
    
    protected $roleThresholds = [
        'star player' => 90,  // Star players should maintain at least 90 overall rating
        'all star' => 85,      // Starters should maintain at least 85 overall rating
        'starter' => 75,      // Starters should maintain at least 75 overall rating
        'role player' => 60,  // Role players should maintain at least 60 overall rating
        'bench' => 40,        // Bench players should maintain at least 40 overall rating
    ];

    private function logPlayerRatings($player, $seasonId)
    {
        // Check if the player ratings for the current season already exist
        $existingRecord = DB::table('player_ratings')
            ->where('player_id', $player->id)
            ->where('season_id', $seasonId)
            ->exists();

        // Only update or insert ratings if no record exists for the current season
        if (!$existingRecord) {
            DB::table('player_ratings')->updateOrInsert(
                [
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                ],
                [
                    'role' => $player->role,
                    'team_id' => $player->team_id,
                    'shooting_rating' => $player->shooting_rating,
                    'defense_rating' => $player->defense_rating,
                    'passing_rating' => $player->passing_rating,
                    'rebounding_rating' => $player->rebounding_rating,
                    'overall_rating' => $player->overall_rating,
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function comparePerformanceBetweenSeasons($playerId)
    {
        // Get the latest season ID and the previous season ID
        $latestSeasonId = get_current_season_id();
        $previousSeasonId = get_previous_season_id();

        // Fetch player's role for the upcoming season from the players table
        $upcomingSeasonRole = Player::where('id', $playerId)
            ->value('role'); // Assume 'role' column exists in players table

        // Fetch player's stats for the latest season from player_season_stats table
        $latestSeasonStats = DB::table('player_season_stats')
            ->where('player_id', $playerId)
            ->where('season_id', $latestSeasonId)
            ->first(); // Get latest season stats

        // Fetch player's stats for the previous season from player_season_stats_archives table
        $previousSeasonStats = DB::table('player_season_stats_archives')
            ->where('player_id', $playerId)
            ->where('season_id', $previousSeasonId)
            ->first(); // Get previous season stats
        // Fetch player's role for the latest season from player_season_stats
        $latestSeasonRole = $latestSeasonStats->role ?? 'role player'; // Default to 'role player' if no stats found

        // Define role-based adjustments
        $roleAdjustments = [
            'star player' => ['shooting' => 1.2, 'defense' => 1.2, 'passing' => 1.2, 'rebounding' => 1.2],
            'all star' => ['shooting' => 1.1, 'defense' => 1.1, 'passing' => 1.1, 'rebounding' => 1.1],
            'starter' => ['shooting' => 1.0, 'defense' => 1.0, 'passing' => 1.0, 'rebounding' => 1.0],
            'role player' => ['shooting' => 0.9, 'defense' => 0.9, 'passing' => 0.9, 'rebounding' => 0.9],
            'bench' => ['shooting' => 0.7, 'defense' => 0.7, 'passing' => 0.7, 'rebounding' => 0.7],
        ];

        // Get role adjustment factors for both roles
        $latestAdjustmentFactors = $roleAdjustments[$latestSeasonRole] ?? $roleAdjustments['role player'];
        $upcomingAdjustmentFactors = $roleAdjustments[$upcomingSeasonRole] ?? $roleAdjustments['role player'];

        // Initialize performance metrics for both seasons
        $latestPerformance = ['shooting' => 0, 'defense' => 0, 'passing' => 0, 'rebounding' => 0];
        $previousPerformance = ['shooting' => 0, 'defense' => 0, 'passing' => 0, 'rebounding' => 0];

        // Aggregate stats for the latest season
        if ($latestSeasonStats) {
            $latestPerformance['shooting'] = $latestSeasonStats->avg_points_per_game;
            $latestPerformance['defense'] = $latestSeasonStats->avg_blocks_per_game + $latestSeasonStats->avg_steals_per_game;
            $latestPerformance['passing'] = $latestSeasonStats->avg_assists_per_game;
            $latestPerformance['rebounding'] = $latestSeasonStats->avg_rebounds_per_game;

            // Apply role-based adjustments
            foreach ($latestPerformance as $key => $value) {
                $latestPerformance[$key] *= $latestAdjustmentFactors[$key] ?? 1;
            }
        }

        // Aggregate stats for the previous season
        if ($previousSeasonStats) {
            $previousPerformance['shooting'] = $previousSeasonStats->avg_points_per_game;
            $previousPerformance['defense'] = $previousSeasonStats->avg_blocks_per_game + $previousSeasonStats->avg_steals_per_game;
            $previousPerformance['passing'] = $previousSeasonStats->avg_assists_per_game;
            $previousPerformance['rebounding'] = $previousSeasonStats->avg_rebounds_per_game;

            // Apply role-based adjustments
            foreach ($previousPerformance as $key => $value) {
                $previousPerformance[$key] *= $latestAdjustmentFactors[$key] ?? 1;
            }
        }

        // Compare performance between the two seasons
        $performanceChange = [];
        foreach ($latestPerformance as $key => $latestValue) {
            $previousValue = $previousPerformance[$key] ?? 0; // Default to 0 if no previous stats
            $performanceChange[$key] = $latestValue - $previousValue; // Calculate performance difference
        }

        // Apply drastic adjustments if the role for the upcoming season is different from the latest season's role
        if ($upcomingSeasonRole !== $latestSeasonRole) {
            foreach ($performanceChange as $key => $value) {
                $performanceChange[$key] *= 2; // Apply a drastic adjustment factor (e.g., 2x)
            }
        }

        return [
            'latest_performance' => $latestPerformance,
            'previous_performance' => $previousPerformance,
            'performance_change' => $performanceChange, // How much the player improved or declined
        ];
    }

    public function playerCoachDecision($player,$teamId,$teamName,$seasonId){

        $coach = $this->coachDecisionService
                ->getTeamCoach($teamId);

        /*
        |--------------------------------------------------------------------------
        | Get normal contract offer
        |--------------------------------------------------------------------------
        */

        $offer = $this->contractService
            ->getContractOffer(
                $player,
                $teamId
            );

        if (!$offer) {
            return false;
        }

        $baseScore = (float) (
                $offer['valuation']
                ?? $offer['salary']
                ?? 50
            );
        
        $signingScore = $this->coachDecisionService
                ->getSigningScore(
                    $coach,
                    $player,
                    $baseScore
                );

        $approvalChance = $this->coachDecisionService
                ->getSigningApprovalChance(
                    $coach,
                    $signingScore
                );

        $isMaxContract = (
                ($offer['contract_type'] ?? null) === 'max'
            );

        $salary = (float) (
                $offer['salary']
                ?? 0
            );

        if ($isMaxContract) {
                $shouldSign = true;
            } 
            else {

                /*
                | Existing MLE rule becomes the base decision,
                | then coach can influence it.
                */

                $baseDecision = (
                    $salary >= $this->contractService->getMLE()
                );

                if ($baseDecision) {

                    /*
                    | Coach can still reject a player he strongly dislikes.
                    */

                    $shouldSign =
                        $this->coachDecisionService
                            ->randomDecision(
                                max(45, $approvalChance)
                            );

                } else {

                    /*
                    | Lower-valued players require stronger coach support.
                    */

                    $shouldSign =
                        $this->coachDecisionService
                            ->randomDecision(
                                $approvalChance * 0.70
                            );
                }
            }
            /*
            |--------------------------------------------------------------------------
            | Sign player
            |--------------------------------------------------------------------------
            */

            if ($shouldSign) {

                $years = (int) (
                    $offer['years']
                    ?? $offer['contract_years']
                    ?? 1
                );

                $updatedYears = ($player->contract_years == 0) ? $years : $player->contract_years + $years;

                DB::table('players')
                    ->where('id', $player->id)
                    ->update([
                        'contract_years' => $updatedYears,
                        'updated_at' => now(),
                    ]);

                $results[] = [
                    'player_id' => $player->id,
                    'team_id' => $teamId,
                    'coach_id' => $coach?->id,
                    'decision' => 'signed',
                    'signing_score' => round($signingScore, 2),
                    'approval_chance' => round($approvalChance, 2),
                ];

                $transactionMessage = ($player->contract_years == 0) ? ' has signed for '.$teamName.' for ' : ', The team decided to sign via team-option for ';
                // Insert the transaction record into the transactions table
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => $player->name . $transactionMessage. $years . ' years on a ' . $offer['contract_type'] . ' contract worth ₱' . number_format((float) $offer['salary'], 2) . '.',
                    'from_team_id' => 0, // Assuming the player is a free agent and has no previous team
                    'to_team_id' => $teamId,
                    'status' => 'signed',
                ]);

                DB::table('player_contracts')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'team_id' => $teamId,
                    'salary' => $offer['salary'],
                    'contract_years' => $offer['years'],
                    'contract_type' => $offer['contract_type'],
                    'player_option' => $offer['player_option'] ?? false,
                    'team_option' => $offer['team_option'] ?? false,
                    'no_trade_clause' => $offer['no_trade_clause'] ?? false,
                    'status' => 'signed',
                ]);

            }
            else{
                if($player->contract_years == 0)
                {
                    DB::table('players')
                        ->where('id', $player->id)
                        ->update([
                            'team_id' => 0,
                            'contract_years' => 0,
                            'salary' => 0,
                            'contract_type' => 0,
                            'player_option' => 0,
                            'team_option' => 0,
                            'no_trade_clause' => 0
                        ]);

                    
                        DB::table('player_contracts')
                        ->where('player_id', $player->id)
                        ->where('status', 'signed')
                        ->where('season_id',$seasonId - 1)
                        ->update(['status' => 'ended']);
                }
                
                $transactionMessage = ($player->contract_years == 0) 
                        ? $player->name.' has been waived by '.$teamName .' and is now a free agent!.' 
                        : $teamName.', chooses to decline the team option for '.$player->name;

                // Insert the transaction record into the transactions table
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => $transactionMessage,
                    'from_team_id' => $teamId,
                    'to_team_id' => ($player->contract_years == 0) ? 0 : $teamId,
                    'status' => ($player->contract_years == 0) ? 'waived' : 'declined',
                ]);
            }
    }

    public function playerDecision($player,$teamId,$teamName,$seasonId){

        $coach = $this->coachDecisionService
                ->getTeamCoach($teamId);

        /*
        |--------------------------------------------------------------------------
        | Get normal contract offer
        |--------------------------------------------------------------------------
        */

        $offer = $this->contractService
            ->getContractOffer(
                $player,
                $teamId
            );

        if (!$offer) {
            return false;
        }

        $baseScore = (float) (
                $offer['valuation']
                ?? $offer['salary']
                ?? 50
            );
        
        $signingScore = $this->coachDecisionService
                ->getSigningScore(
                    $coach,
                    $player,
                    $baseScore
                );

        $approvalChance = $this->coachDecisionService
                ->getSigningApprovalChance(
                    $coach,
                    $signingScore
                );

        $isMaxContract = (
                ($offer['contract_type'] ?? null) === 'max'
            );

        $salary = (float) (
                $offer['salary']
                ?? 0
            );

        // 'players.loyalty_rating', // Add any additional player fields
        // 'players.satisfaction_rating', // Add any additional player fields
        // 'players.negotation_skill_rating', // Add any additional player fields
        // 'players.ambition_rating' // Add any additional player fields

        if($isMaxContract && $player->satisfaction_rating > 60) {
                $shouldSign = true;
        } 
        else {

            $isLoyalandSatisfied = ($player->satisfaction_rating > 75) && ($player->loyalty_rating > 90);
            $isCoachCompetent = DB::table('coaches')->where('id',$coach->id)->sum('winning_percentage') > 150;
            $isTeamWinner = DB::table('team_season_info')->where('team_id',$teamId)->sum('is_playoff_qualified') > 1;
            $salaryFactor = $salary >= ($player->salary - ($player->salary * 0.15));
            $shouldSign = ($isCoachCompetent && $isTeamWinner) || $isLoyalandSatisfied || $salaryFactor;

            if($shouldSign) {

                $years = (int) (
                    $offer['years']
                    ?? $offer['contract_years']
                    ?? 1
                );

                DB::table('players')
                    ->where('id', $player->id)
                    ->update([
                        'contract_years' => $player->contract_years + $years,
                        'updated_at' => now(),
                    ]);

                $results[] = [
                    'player_id' => $player->id,
                    'team_id' => $teamId,
                    'coach_id' => $coach?->id,
                    'decision' => 'signed',
                    'signing_score' => round($signingScore, 2),
                    'approval_chance' => round($approvalChance, 2),
                ];

                // Insert the transaction record into the transactions table
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => $player->name . ' has decided to sign for ' . $teamName . 'on his player option for ' . $years . ' years on a ' . $offer['contract_type'] . ' contract worth ₱' . number_format((float) $offer['salary'], 2) . '.',
                    'from_team_id' => 0, // Assuming the player is a free agent and has no previous team
                    'to_team_id' => $teamId,
                    'status' => 'signed',
                ]);

                DB::table('player_contracts')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'team_id' => $teamId,
                    'salary' => $offer['salary'],
                    'contract_years' => $offer['years'],
                    'contract_type' => $offer['contract_type'],
                    'player_option' => $offer['player_option'] ?? false,
                    'team_option' => $offer['team_option'] ?? false,
                    'no_trade_clause' => $offer['no_trade_clause'] ?? false,
                    'status' => 'signed',
                ]);

            }
            else{
                // Insert the transaction record into the transactions table
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => $player->name . ' has declined his player option for' . $teamName .'.',
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'declined',
                ]);
            }
        }
    }
    
}