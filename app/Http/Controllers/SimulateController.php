<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Exception;
use Inertia\Inertia;
use App\Models\Seasons;
use App\Models\Teams;
use App\Models\Schedules;
use App\Models\Conference;
use App\Models\Player;
use App\Models\PlayerGameStats;
use App\Http\Controllers\AwardsController;
use App\Http\Controllers\PlayersController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SimulateController extends Controller
{
    //
    private function maxPoints()
    {
        return [
            'star player' => [
                'points' => rand(1, 100),
                'rebounds' => rand(1, 50),
                'assists' => rand(1, 30),
                'steals' => rand(1, 20),
                'blocks' => rand(1, 20),
            ],
            'all star' => [
                'points' => rand(1, 70),
                'rebounds' => rand(1, 30),
                'assists' => rand(1, 25),
                'steals' => rand(1, 15),
                'blocks' => rand(1, 15),
            ],
            'starter' => [
                'points' => rand(1, 40),
                'rebounds' => rand(1, 30),
                'assists' => rand(1, 25),
                'steals' => rand(1, 15),
                'blocks' => rand(1, 15),
            ],
            'role player' => [
                'points' => rand(1, 30),
                'rebounds' => rand(1, 20),
                'assists' => rand(1, 20),
                'steals' => rand(1, 15),
                'blocks' => rand(1, 15),
            ],
            'bench' => [
                'points' => rand(1, 20),
                'rebounds' => rand(1, 15),
                'assists' => rand(1, 10),
                'steals' => rand(1, 10),
                'blocks' => rand(1, 100),
            ]
        ];
    }

    public function simulatePlayoff(Request $request)
    {
        // Validate the request data
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);
        
        $isGameFinished = DB::table('schedules')
            ->where('id', $request->schedule_id)
            ->where('status', 2)  // Fetch previous round and current round in one query
            ->exists(); // Use exists() for a boolean result

        if ($isGameFinished) {
            return response()->json([
                'message' => 'Game already simulated!',
            ], 400); // 400 - Bad Request is more appropriate for this scenario
        }

        // Fetch game data
        $gameData = Schedules::join('teams as home', 'schedules.home_id', '=', 'home.id')
            ->join('teams as away', 'schedules.away_id', '=', 'away.id')
            ->join('standings_view as home_standings', function ($join) {
                $join->on('home.id', '=', 'home_standings.team_id')
                    ->whereColumn('home_standings.season_id', 'schedules.season_id');
            })
            ->join('standings_view as away_standings', function ($join) {
                $join->on('away.id', '=', 'away_standings.team_id')
                    ->whereColumn('away_standings.season_id', 'schedules.season_id');
            })
            ->select(
                'schedules.id',
                'schedules.round',
                'schedules.season_id',
                'schedules.conference_id',
                'schedules.game_id',
                'home.id as home_team_id',
                'home.name as home_team_name',
                'home.primary_color as home_primary_color',
                'home.secondary_color as home_secondary_color',
                'away.id as away_team_id',
                'away.name as away_team_name',
                'away.primary_color as away_primary_color',
                'away.secondary_color as away_secondary_color',
                'home_standings.overall_rank as home_overall_rank',
                'away_standings.overall_rank as away_overall_rank',
                'home_standings.conference_name as home_conference_name',
                'away_standings.conference_name as away_conference_name',
                'home_standings.conference_rank as home_conference_rank',
                'away_standings.conference_rank as away_conference_rank',
                'home_standings.wins as home_current_performance',
                'away_standings.wins as away_current_performance',
                'schedules.home_score',
                'schedules.away_score',
                'schedules.status'
            )
            ->findOrFail($request->schedule_id);
        
        $this->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);
        $this->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);

        //check if home team is injury depleted
        $homeTeamInjuries = DB::table('players')
        ->where('team_id', $gameData->home_team_id)
        ->where('is_injured', true)
        ->get();

        if ($homeTeamInjuries->count() > 5) {
            //run the fire leoparad rule
            $initiateFLRule = $this->fireLeopardRule($gameData->home_team_id);
            // return response()->json([
            //     'error' => true,
            //     'fire' =>  $initiateFLRule,
            //     'injured' => $homeTeamInjuries->count(),
            //     'team' => $gameData->home_team_id,
            //     'message' => $gameData->home_team_name.' team is injury depleted!.Cant proceed, game postponed!',
            // ], 400);
        }

        $awayTeamInjuries = DB::table('players')
        ->where('team_id', $gameData->away_team_id)
        ->where('is_injured', true)
        ->get();

        if ($awayTeamInjuries->count() > 5) {
            //run the fire leoparad rule
            $initiateFLRule =  $this->fireLeopardRule($gameData->away_team_id);
            // return response()->json([
            //     'error' => true,
            //     'fire' =>  $initiateFLRule,
            //     'injured' => $awayTeamInjuries->count(),
            //     'team' => $gameData->away_team_id,
            //     'message' => $gameData->away_team_name.' team is injury depleted!.Cant proceed, game postponed!',
            // ], 400);
        }


        // Fetch current season ID
        $currentSeasonId = $gameData->season_id;

        // Define role-based priority and maximum points
        $rolePriority = [
            'star player' => 1,
            'all star' => 2,
            'starter' => 2,
            'role player' => 5,
            'bench' => 5,
        ];

        // Define total minutes available for each team
        $totalMinutes = 240;


        // Fetching sorted active players for both teams
        $homeTeamPlayers = $this->getActivePlayersSorted($gameData->home_team_id, $rolePriority);
        $awayTeamPlayers = $this->getActivePlayersSorted($gameData->away_team_id, $rolePriority);

        // Initialize arrays to hold player game stats and minutes
        $playerGameStats = [];

        // Distribute minutes to players considering injury status
        $homeMinutes = $this->distributeMinutes($homeTeamPlayers, $totalMinutes, $request->schedule_id);
        $awayMinutes = $this->distributeMinutes($awayTeamPlayers, $totalMinutes, $request->schedule_id);

        // Simulate player game stats for home team
        // Simulate home team player stats with detailed shooting metrics
        foreach ($homeTeamPlayers as $player) {
            $minutes = (float) $homeMinutes[$player->id];
            if ($minutes === 0 || $player->is_injured) {
                $playerGameStats[] = $this->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->calculatePerformanceFactor($player);
            $defensiveImpact = $this->calculateDefensiveImpact($gameData->away_team_id);
            
            $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor,$defensiveImpact);
            $fouls = $this->calculateFoul($player, $minutes, $performanceFactor,$defensiveImpact);


            $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact,$fouls, $turnovers);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points = $this->calculatePoints($twoPointMade, $threePointMade, $freeThrowMade);

            // Simulate other stats
            $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor);
            $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor);
            $steals = $this->calculateSteals($player, $minutes, $performanceFactor);

            $playerGameStats[] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'points' => $points,
                'rebounds' => $rebounds,
                'assists' => 0, // Temporary value
                'steals' => $steals,
                'blocks' => $blocks,
                'turnovers' => $turnovers,
                'fouls' => $fouls,
                'minutes' => $minutes,
                'field_goal_attempts' => $twoPointAttempts + $threePointAttempts,
                'field_goals_made' => $twoPointMade + $threePointMade,
                'three_point_attempts' => $threePointAttempts,
                'three_pointers_made' => $threePointMade,
                'two_pointers_made' => $twoPointMade,
                'two_point_attempts' => $twoPointAttempts,
                'free_throw_attempts' => $freeThrowAttempts,
                'free_throws_made' => $freeThrowMade,
            ];
        }
        // Repeat similar simulation for away team players...
        foreach ($awayTeamPlayers as $player) {
            $minutes = (float) $awayMinutes[$player->id];
            if ($minutes === 0 || $player->is_injured) {
                $playerGameStats[] = $this->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->calculatePerformanceFactor($player);
            $defensiveImpact = $this->calculateDefensiveImpact($gameData->away_team_id);
            
            $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor,$defensiveImpact);
            $fouls = $this->calculateFoul($player, $minutes, $performanceFactor,$defensiveImpact);


            $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact,$fouls, $turnovers);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];
            
            $points = $this->calculatePoints($twoPointMade, $threePointMade, $freeThrowMade);

            // Simulate other stats
            $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor);
            $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor);
            $steals = $this->calculateSteals($player, $minutes, $performanceFactor);

            $playerGameStats[] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'points' => $points,
                'rebounds' => $rebounds,
                'assists' => 0, // Temporary value
                'steals' => $steals,
                'blocks' => $blocks,
                'turnovers' => $turnovers,
                'fouls' => $fouls,
                'minutes' => $minutes,
                'field_goal_attempts' => $twoPointAttempts + $threePointAttempts,
                'field_goals_made' => $twoPointMade + $threePointMade,
                'three_point_attempts' => $threePointAttempts,
                'two_pointers_made' => $twoPointMade,
                'two_point_attempts' => $twoPointAttempts,
                'three_pointers_made' => $threePointMade,
                'free_throw_attempts' => $freeThrowAttempts,
                'free_throws_made' => $freeThrowMade,
            ];
        }
        // Convert to arrays
        $homeTeamPlayers = $homeTeamPlayers->toArray();
        $awayTeamPlayers = $awayTeamPlayers->toArray();

        // Calculate total points for each team
        $totalHomePoints = array_sum(array_map(function ($stat) use ($gameData) {
            return $stat['team_id'] === $gameData->home_team_id ? $stat['points'] : 0;
        }, $playerGameStats));

        $totalAwayPoints = array_sum(array_map(function ($stat) use ($gameData) {
            return $stat['team_id'] === $gameData->away_team_id ? $stat['points'] : 0;
        }, $playerGameStats));


        // Assuming $homeTeamPlayers and $awayTeamPlayers are arrays of player stats with player ids
        // Retrieve passing ratings for home and away team players from the player table
        $homePassingTotal = 0;
        $homePassingAverage = 0;
        $awayPassingTotal = 0;
        $awayPassingAverage = 0;

        // Sum up passing ratings for home team players
        foreach ($homeTeamPlayers as $player) {
            $passingRating = $player['passing_rating'] ?? 0;  // Default to 0 if passing_rating is missing
            $homePassingTotal += $passingRating;
        }

        // Sum up passing ratings for away team players
        foreach ($awayTeamPlayers as $player) {
            $passingRating = $player['passing_rating'] ?? 0;  // Default to 0 if passing_rating is missing
            $awayPassingTotal += $passingRating;
        }

        // Calculate passing averages
        $homePassingAverage = count($homeTeamPlayers) > 0 ? $homePassingTotal / count($homeTeamPlayers) : 0;
        $awayPassingAverage = count($awayTeamPlayers) > 0 ? $awayPassingTotal / count($awayTeamPlayers) : 0;

        // Define maximum assists based on total points and completion rate
        $maxHomeAssists = round(($totalHomePoints / 2) * ($homePassingAverage / 100));
        $maxAwayAssists = round(($totalAwayPoints / 2) * ($awayPassingAverage / 100));

        // Track assists assigned to each team
        $homeAssistsAssigned = 0;
        $awayAssistsAssigned = 0;

        // Check if passing_rating exists in player stats before sorting
        foreach ($playerGameStats as &$stats) {
            // Ensure passing_rating exists, default to 0 if not
            if (!isset($stats['passing_rating'])) {
                $stats['passing_rating'] = 0;  // Default passing rating to 0 if it's missing
            }
        }

        // Sort players by passing rating in descending order
        usort($playerGameStats, function ($a, $b) {
            return $b['passing_rating'] <=> $a['passing_rating'];
        });

        // Function to distribute assists
        function distributeAssistsPlayoffs(&$playerGameStats, $teamId, $maxAssists, &$assistsAssigned)
        {
            $playmakerIndex = 0; // Track number of players assigned assists in this iteration

            // Calculate the assist range (half to 3/4 of max assists)
            $assistRange = rand(floor($maxAssists / 2), floor($maxAssists * 3 / 4));

            // Distribute assists among the top 5 to 7 playmakers
            $remainingAssists = $assistRange; // Remaining assists to distribute among top 5 to 7 playmakers
            $playmakers = [];

            foreach ($playerGameStats as &$stats) {
                if ($stats['team_id'] === $teamId && $stats['minutes'] > 0) { // Check if player has more than 0 minutes
                    // Collect the top playmakers (5-7 based on passing rating)
                    if ($playmakerIndex < 7) {
                        $playmakers[] = &$stats; // Add the player to the playmaker list
                    }
                    $playmakerIndex++;
                }
            }

            // Sort the players by passing rating in descending order
            usort($playmakers, function ($a, $b) {
                return $b['passing_rating'] <=> $a['passing_rating'];
            });

            // Randomly distribute the assistRange among the top 5 to 7 players
            $assistCount = count($playmakers);
            if ($assistCount > 0) {
                foreach ($playmakers as &$playmaker) {
                    // Randomly assign assists to each playmaker in the range of 0 to remaining assists
                    $maxForThisPlayer = min($remainingAssists, rand(0, floor($remainingAssists / 2)));
                    $playmaker['assists'] = $maxForThisPlayer;  // Assign assists

                    // Deduct from remaining assists
                    $remainingAssists -= $maxForThisPlayer;

                    // If there are no more assists to distribute, break early
                    if ($remainingAssists <= 0) {
                        break;
                    }
                }
            }

            // Any remaining assists to be distributed among the rest of the players
            $remainingAssistsToDistribute = $maxAssists - $assistRange - $remainingAssists;
            foreach ($playerGameStats as &$stats) {
                if ($stats['team_id'] === $teamId && !in_array($stats, $playmakers) && $stats['minutes'] > 0) { // Ensure player has minutes > 0
                    // Assign remaining assists to players who are not in the top playmaker group and have played minutes
                    $stats['assists'] = rand(0, floor($remainingAssistsToDistribute / 2));
                }
            }

            // Update the assists assigned counter
            $assistsAssigned = $maxAssists - $remainingAssists;
        }

        // Distribute assists for the home team
        distributeAssistsPlayoffs($playerGameStats, $gameData->home_team_id, $maxHomeAssists, $homeAssistsAssigned);

        // Distribute assists for the away team
        distributeAssistsPlayoffs($playerGameStats, $gameData->away_team_id, $maxAwayAssists, $awayAssistsAssigned);

        // Clear reference
        // unset($stats);

        // Update or insert player game stats
        $this->updateSeasonStats($playerGameStats,$gameData,true);
        // foreach ($playerGameStats as $stats) {

        //     // Assuming you have a Player model
        //     Player::where('id', $stats['player_id'])->update(['fatigue' => 0]);

        //     PlayerGameStats::updateOrCreate(
        //         [
        //             'player_id' => $stats['player_id'],
        //             'game_id' => $stats['game_id'],
        //             'season_id' => $stats['season_id'],
        //             'team_id' => $stats['team_id'],
        //         ],
        //         $stats
        //     );

        //     AwardsController::storeplayerseasonstats($stats['team_id'], $stats['player_id']);
        //     // $this->updatePlayerPlayoffAppearance($stats['player_id'], $gameData);
        // }

        // Calculate scores based on player stats
        $homeScore = PlayerGameStats::where('team_id', $gameData->home_team_id)
            ->where('game_id', $gameData->game_id)
            ->sum('points');

        $awayScore = PlayerGameStats::where('team_id', $gameData->away_team_id)
            ->where('game_id', $gameData->game_id)
            ->sum('points');

        // Check if the game is tied
        $reasons = [
            'due to bad weather',
            'because of unforeseen technical issues',
            'due to a power failure at the stadium',
            'because of security concerns',
            'due to a transportation issue for the teams',
            'because of an equipment malfunction',
        ];
        
        $randomReason = $reasons[array_rand($reasons)];
        
        if ($homeScore === $awayScore) {
            DB::rollBack();
            return response()->json([
                'message' => 'The game is postponed ' . $randomReason . '!',
            ], 500);
        }

        // Update the scores
        $gameData->home_score = $homeScore;
        $gameData->away_score = $awayScore;
        
        $gameData->winner_id = $homeScore > $awayScore
            ? $gameData->home_team_id
            : $gameData->away_team_id;
        
        $gameData->status = 2; // Marking the game as completed

        // Save the updated scores
        $gameData->save();

        // Determine the winner
        $winnerId = $gameData->winner_id;
        $winnerName = ($gameData->home_team_id == $gameData->winner_id) ? $gameData->home_team_name : $gameData->away_team_name;
        // Prepare an array to hold the update data for the seasons table if it's finals
        $seasonUpdateData = [];
        if ($gameData->round === 'semi_finals') {
            $this->updateConferenceChampions($gameData, $winnerId);
        }
        if ($gameData->round === 'finals') {
            // Find the MVP of the winning team
            $this->updateFinalsWinner($gameData, $winnerId, $homeScore, $awayScore);

            // Update the finals contract
            // $this->updateFinalsBonusContract($gameData->home_team_id, $gameData->season_id,$gameData->home_team_name);
            // $this->updateFinalsBonusContract($gameData->away_team_id, $gameData->season_id,$gameData->away_team_name);
        }

        // Update the seasons table if there are updates
        if (!empty($seasonUpdateData)) {
            DB::table('seasons')
                ->where('id', $gameData->season_id)
                ->update($seasonUpdateData);
        }

        $this->updateAllTeamStreaks();
        $this->updateHeadToHeadResults($gameData->id);
        $this->updateInjuryFreeAgents();

        $this->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
        $this->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

        $this->updatePlayerMoraleBasedOnStats($gameData->home_team_id,$gameData->winner_id);
        $this->updatePlayerMoraleBasedOnStats($gameData->away_team_id,$gameData->winner_id);

        // Prepare the schedule response data it will update team score card only
        $schedule = [
            'id' => $gameData->id,
            'game_id' => $gameData->game_id,
            'home_team' => [
                'id' => $gameData->home_team_id,
                'name' => $gameData->home_team_name,
                'home_score' => $gameData->home_score,
                'conference' => $gameData->home_conference_name,
                'conference_rank' => $gameData->home_conference_rank,
                'overall_rank' => $gameData->home_overall_rank,
                'primary_color' => $gameData->home_primary_rank,
                'secondary_color' => $gameData->home_secondary_rank,
                
            ],
            'away_team' => [
                'id' => $gameData->away_team_id,
                'name' => $gameData->away_team_name,
                'away_score' => $gameData->away_score,
                'conference' => $gameData->away_conference_name,
                'conference_rank' => $gameData->away_conference_rank,
                'overall_rank' => $gameData->away_overall_rank,
            ],
            'winner' => $winnerId,
            'round' => $gameData->round,
        ];

        // Return the simulation result
        return response()->json([
            'message' => 'Game simulated successfully',
            'schedule' => $schedule
        ]);
    }
    public function simulateRegular(Request $request)
    {
        // Validate the request data
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);
        
        $isGameFinished = DB::table('schedules')
            ->where('id', $request->schedule_id)
            ->where('status', config('timeline.play_offs'))  // Fetch previous round and current round in one query
            ->exists(); // Use exists() for a boolean result

        if ($isGameFinished) {
            return response()->json([
                'message' => 'Game already simulated!',
            ], 400); // 400 - Bad Request is more appropriate for this scenario
        }
        
        $storeStats = new AwardsController;

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Fetch game data (existing code remains unchanged)
            $gameData = Schedules::join('teams as home', 'schedules.home_id', '=', 'home.id')
                ->join('teams as away', 'schedules.away_id', '=', 'away.id')
                ->join('standings_view as home_standings', function ($join) {
                    $join->on('home.id', '=', 'home_standings.team_id')
                        ->whereColumn('home_standings.season_id', 'schedules.season_id');
                })
                ->join('standings_view as away_standings', function ($join) {
                    $join->on('away.id', '=', 'away_standings.team_id')
                        ->whereColumn('away_standings.season_id', 'schedules.season_id');
                })
                ->select(
                    'schedules.id',
                    'schedules.round',
                    'schedules.conference_id',
                    'schedules.season_id',
                    'schedules.game_id',
                    'home.id as home_team_id',
                    'home.name as home_team_name',
                    'away.id as away_team_id',
                    'away.name as away_team_name',
                    'home_standings.overall_rank as home_overall_rank',
                    'away_standings.overall_rank as away_overall_rank',
                    'home_standings.conference_name as home_conference_name',
                    'away_standings.conference_name as away_conference_name',
                    'home_standings.conference_rank as home_conference_rank',
                    'away_standings.conference_rank as away_conference_rank',
                    'home_standings.wins as home_current_performance',
                    'away_standings.wins as away_current_performance',
                    'schedules.home_score',
                    'schedules.away_score',
                    'schedules.status'
                )
                ->findOrFail($request->schedule_id);
            if ($gameData) {
                return response()->json([
                    'message' => 'Erro Fetchin game data',
                ], 400);
            }

            if ($gameData->status == 2) {
                return response()->json([
                    'message' => 'Game has already been simulated.',
                ], 400);
            }
            
            $this->fixTeamPositionBalance($gameData->home_team_id);
            $this->fixTeamPositionBalance($gameData->away_team_id);

            $this->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);
            $this->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);
            //check first to balance team positions
                //check if home team is injury depleted
            $homeTeamInjuries = DB::table('players')
            ->where('team_id', $gameData->home_team_id)
            ->where('is_injured', true)
            ->get();

            if ($homeTeamInjuries->count() > 5) {
                //run the fire leoparad rule
                $initiateFLRule = $this->fireLeopardRule($gameData->home_team_id);
                // return response()->json([
                //     'error' => true,
                //     'fire' =>  $initiateFLRule,
                //     'injured' => $homeTeamInjuries->count(),
                //     'team' => $gameData->home_team_id,
                //     'message' => $gameData->home_team_name.' team is injury depleted!.Cant proceed, game postponed!',
                // ], 400);
            }

            $awayTeamInjuries = DB::table('players')
            ->where('team_id', $gameData->away_team_id)
            ->where('is_injured', true)
            ->get();

            if ($awayTeamInjuries->count() > 5) {
                //run the fire leoparad rule
                $initiateFLRule =  $this->fireLeopardRule($gameData->away_team_id);
                // return response()->json([
                //     'error' => true,
                //     'fire' =>  $initiateFLRule,
                //     'injured' => $awayTeamInjuries->count(),
                //     'team' => $gameData->away_team_id,
                //     'message' => $gameData->away_team_name.' team is injury depleted!.Cant proceed, game postponed!',
                // ], 400);
            }

            $currentSeasonId = $gameData->season_id;
            $rolePriority = [
                'star player' => 1,
                'all star' => 2,
                'starter' => 2,
                'role player' => 5,
                'bench' => 5,
            ];
            $totalMinutes = 240;

            // Fetching sorted active players for both teams
            $homeTeamPlayers = $this->getActivePlayersSorted($gameData->home_team_id, $rolePriority);
            $awayTeamPlayers = $this->getActivePlayersSorted($gameData->away_team_id, $rolePriority);

            $playerGameStats = [];
            $homeMinutes = $this->distributeMinutes($homeTeamPlayers, $totalMinutes, $request->schedule_id);
            $awayMinutes = $this->distributeMinutes($awayTeamPlayers, $totalMinutes, $request->schedule_id);
            
            // Simulate home team player stats with detailed shooting metrics
            foreach ($homeTeamPlayers as $player) {
                $minutes = (float) $homeMinutes[$player->id];
                if ($minutes === 0 || $player->is_injured) {
                    $playerGameStats[] = $this->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                    continue;
                }

                $performanceFactor = $this->calculatePerformanceFactor($player);
                $defensiveImpact = $this->calculateDefensiveImpact($gameData->away_team_id);
                
                $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor,$defensiveImpact);
                $fouls = $this->calculateFoul($player, $minutes, $performanceFactor,$defensiveImpact);
    
    
                $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact,$fouls, $turnovers);

                // Assign returned values to variables
                $twoPointAttempts = $shotStats['two_point_attempts'];
                $twoPointMade = $shotStats['two_point_made'];

                $threePointAttempts = $shotStats['three_point_attempts'];
                $threePointMade = $shotStats['three_point_made'];

                $freeThrowAttempts = $shotStats['free_throw_attempts'];
                $freeThrowMade = $shotStats['free_throw_made'];
                
                $points = $this->calculatePoints($twoPointMade, $threePointMade, $freeThrowMade);

                // Simulate other stats
                $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor);
                $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor);
                $steals = $this->calculateSteals($player, $minutes, $performanceFactor);

                $playerGameStats[] = [
                    'player_id' => $player->id,
                    'game_id' => $gameData->game_id,
                    'season_id' => $currentSeasonId,
                    'team_id' => $player->team_id,
                    'points' => $points,
                    'rebounds' => $rebounds,
                    'assists' => 0, // Temporary value
                    'steals' => $steals,
                    'blocks' => $blocks,
                    'turnovers' => $turnovers,
                    'fouls' => $fouls,
                    'minutes' => $minutes,
                    'field_goal_attempts' => $twoPointAttempts + $threePointAttempts,
                    'field_goals_made' => $twoPointMade + $threePointMade,
                    'three_point_attempts' => $threePointAttempts,
                    'three_pointers_made' => $threePointMade,
                    'two_pointers_made' => $twoPointMade,
                    'two_point_attempts' => $twoPointAttempts,
                    'free_throw_attempts' => $freeThrowAttempts,
                    'free_throws_made' => $freeThrowMade,
                ];
            }
            // Repeat similar simulation for away team players...
            foreach ($awayTeamPlayers as $player) {
                $minutes = (float) $awayMinutes[$player->id];
                if ($minutes === 0 || $player->is_injured) {
                    $playerGameStats[] = $this->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                    continue;
                }

                $performanceFactor = $this->calculatePerformanceFactor($player);
                $defensiveImpact = $this->calculateDefensiveImpact($gameData->away_team_id);
                
                $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor,$defensiveImpact);
                $fouls = $this->calculateFoul($player, $minutes, $performanceFactor,$defensiveImpact);
    
    
                $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact,$fouls, $turnovers);

                // Assign returned values to variables
                $twoPointAttempts = $shotStats['two_point_attempts'];
                $twoPointMade = $shotStats['two_point_made'];
            
                $threePointAttempts = $shotStats['three_point_attempts'];
                $threePointMade = $shotStats['three_point_made'];
            
                $freeThrowAttempts = $shotStats['free_throw_attempts'];
                $freeThrowMade = $shotStats['free_throw_made'];
                              

                $points = $this->calculatePoints($twoPointMade, $threePointMade, $freeThrowMade);

                // Simulate other stats
                $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor);
                $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor);
                $steals = $this->calculateSteals($player, $minutes, $performanceFactor);

                $playerGameStats[] = [
                    'player_id' => $player->id,
                    'game_id' => $gameData->game_id,
                    'season_id' => $currentSeasonId,
                    'team_id' => $player->team_id,
                    'points' => $points,
                    'rebounds' => $rebounds,
                    'assists' => 0, // Temporary value
                    'steals' => $steals,
                    'blocks' => $blocks,
                    'turnovers' => $turnovers,
                    'fouls' => $fouls,
                    'minutes' => $minutes,
                    'field_goal_attempts' => $twoPointAttempts + $threePointAttempts,
                    'field_goals_made' => $twoPointMade + $threePointMade,
                    'three_point_attempts' => $threePointAttempts,
                    'two_pointers_made' => $twoPointMade,
                    'two_point_attempts' => $twoPointAttempts,
                    'three_pointers_made' => $threePointMade,
                    'free_throw_attempts' => $freeThrowAttempts,
                    'free_throws_made' => $freeThrowMade,
                ];
            }
            // Assist distribution logic remains similar but ensures 15-player roster
            // Convert to arrays
            $homeTeamPlayers = $homeTeamPlayers->toArray();
            $awayTeamPlayers = $awayTeamPlayers->toArray();

            // Calculate total points for each team
            $totalHomePoints = array_sum(array_map(function ($stat) use ($gameData) {
                return $stat['team_id'] === $gameData->home_team_id ? $stat['points'] : 0;
            }, $playerGameStats));

            $totalAwayPoints = array_sum(array_map(function ($stat) use ($gameData) {
                return $stat['team_id'] === $gameData->away_team_id ? $stat['points'] : 0;
            }, $playerGameStats));


            // Assuming $homeTeamPlayers and $awayTeamPlayers are arrays of player stats with player ids
            // Retrieve passing ratings for home and away team players from the player table
            $homePassingTotal = 0;
            $homePassingAverage = 0;
            $awayPassingTotal = 0;
            $awayPassingAverage = 0;

            // Sum up passing ratings for home team players
            foreach ($homeTeamPlayers as $player) {
                $passingRating = $player['passing_rating'] ?? 0;  // Default to 0 if passing_rating is missing
                $homePassingTotal += $passingRating;
            }

            // Sum up passing ratings for away team players
            foreach ($awayTeamPlayers as $player) {
                $passingRating = $player['passing_rating'] ?? 0;  // Default to 0 if passing_rating is missing
                $awayPassingTotal += $passingRating;
            }

            // Calculate passing averages
            $homePassingAverage = count($homeTeamPlayers) > 0 ? $homePassingTotal / count($homeTeamPlayers) : 0;
            $awayPassingAverage = count($awayTeamPlayers) > 0 ? $awayPassingTotal / count($awayTeamPlayers) : 0;

            // Define maximum assists based on total points and completion rate
            $maxHomeAssists = round(($totalHomePoints / 2) * ($homePassingAverage / 100));
            $maxAwayAssists = round(($totalAwayPoints / 2) * ($awayPassingAverage / 100));

            // Track assists assigned to each team
            $homeAssistsAssigned = 0;
            $awayAssistsAssigned = 0;

            // Check if passing_rating exists in player stats before sorting
            foreach ($playerGameStats as &$stats) {
                // Ensure passing_rating exists, default to 0 if not
                if (!isset($stats['passing_rating'])) {
                    $stats['passing_rating'] = 0;  // Default passing rating to 0 if it's missing
                }
            }

            // Sort players by passing rating in descending order
            usort($playerGameStats, function ($a, $b) {
                return $b['passing_rating'] <=> $a['passing_rating'];
            });

            // Function to distribute assists
            function distributeAssists(&$playerGameStats, $teamId, $maxAssists, &$assistsAssigned)
            {
                $playmakerIndex = 0; // Track number of players assigned assists in this iteration

                // Calculate the assist range (half to 3/4 of max assists)
                $assistRange = rand(floor($maxAssists / 2), floor($maxAssists * 3 / 4));

                // Distribute assists among the top 5 to 7 playmakers
                $remainingAssists = $assistRange; // Remaining assists to distribute among top 5 to 7 playmakers
                $playmakers = [];

                foreach ($playerGameStats as &$stats) {
                    if ($stats['team_id'] === $teamId && $stats['minutes'] > 0) { // Check if player has more than 0 minutes
                        // Collect the top playmakers (5-7 based on passing rating)
                        if ($playmakerIndex < 7) {
                            $playmakers[] = &$stats; // Add the player to the playmaker list
                        }
                        $playmakerIndex++;
                    }
                }

                // Sort the players by passing rating in descending order
                usort($playmakers, function ($a, $b) {
                    return $b['passing_rating'] <=> $a['passing_rating'];
                });

                // Randomly distribute the assistRange among the top 5 to 7 players
                $assistCount = count($playmakers);
                if ($assistCount > 0) {
                    foreach ($playmakers as &$playmaker) {
                        // Randomly assign assists to each playmaker in the range of 0 to remaining assists
                        $maxForThisPlayer = min($remainingAssists, rand(0, floor($remainingAssists / 2)));
                        $playmaker['assists'] = $maxForThisPlayer;  // Assign assists

                        // Deduct from remaining assists
                        $remainingAssists -= $maxForThisPlayer;

                        // If there are no more assists to distribute, break early
                        if ($remainingAssists <= 0) {
                            break;
                        }
                    }
                }

                // Any remaining assists to be distributed among the rest of the players
                $remainingAssistsToDistribute = $maxAssists - $assistRange - $remainingAssists;
                foreach ($playerGameStats as &$stats) {
                    if ($stats['team_id'] === $teamId && !in_array($stats, $playmakers) && $stats['minutes'] > 0) { // Ensure player has minutes > 0
                        // Assign remaining assists to players who are not in the top playmaker group and have played minutes
                        $stats['assists'] = rand(0, floor($remainingAssistsToDistribute / 2));
                    }
                }

                // Update the assists assigned counter
                $assistsAssigned = $maxAssists - $remainingAssists;
            }

            // Distribute assists for the home team
            distributeAssists($playerGameStats, $gameData->home_team_id, $maxHomeAssists, $homeAssistsAssigned);

            // Distribute assists for the away team
            distributeAssists($playerGameStats, $gameData->away_team_id, $maxAwayAssists, $awayAssistsAssigned);


            // Update database records with new stats
            $this->updateSeasonStats($playerGameStats,$gameData,false);
            // foreach ($playerGameStats as $stats) {
            //     if (isset($stats['passing_rating'])) {
            //         unset($stats['passing_rating']);
            //     }
            //     PlayerGameStats::updateOrCreate(
            //         ['player_id' => $stats['player_id'], 'game_id' => $stats['game_id']],
            //         $stats
            //     );

            //     AwardsController::storeplayerseasonstats($stats['team_id'], $stats['player_id']);
            // }

             // Calculate scores based on player stats
            $homeScore = PlayerGameStats::where('team_id', $gameData->home_team_id)
            ->where('game_id', $gameData->game_id)
            ->sum('points');

            $awayScore = PlayerGameStats::where('team_id', $gameData->away_team_id)
            ->where('game_id', $gameData->game_id)
            ->sum('points');

            // Check if the game is tied
            $reasons = [
                'due to bad weather',
                'because of unforeseen technical issues',
                'due to a power failure at the stadium',
                'because of security concerns',
                'due to a transportation issue for the teams',
                'because of an equipment malfunction',
            ];
            
            $randomReason = $reasons[array_rand($reasons)];
            
            if ($homeScore === $awayScore) {
                DB::rollBack();
                return response()->json([
                    'message' => 'The game is postponed ' . $randomReason . '!',
                ], 500);
            }

            
            $gameData->home_score = $homeScore;
            $gameData->away_score = $awayScore;
            
            $gameData->winner_id = $homeScore > $awayScore
                ? $gameData->home_team_id
                : $gameData->away_team_id;
            
            $gameData->status = 2;
            $gameData->save();

            // Check if all rounds have been simulated for the season
            $allRoundsSimulatedForSeason = Schedules::where('season_id', $currentSeasonId)
                ->where('status', 1)
                ->doesntExist();

            $this->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
            $this->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

            $this->updatePlayerMoraleBasedOnStats($gameData->home_team_id,$gameData->winner_id);
            $this->updatePlayerMoraleBasedOnStats($gameData->away_team_id,$gameData->winner_id);

            $this->updateInjuryFreeAgents();
            $this->updateAllTeamStreaks();
            $this->updateHeadToHeadResults($gameData->id);
            if ($allRoundsSimulatedForSeason) {
                // Update the season's status to 2
                $season = Seasons::find($currentSeasonId);
                if ($season) {
                    $season->status = 2;
                    $season->save();
                }
            }

            // Commit the transaction
            DB::commit();

         // $gameResult = $this->getBoxScore($gameData->game_id);

         // Return the simulation result
         return response()->json([
             'message' => 'Game simulated successfully',
             'game_id' => $gameData->game_id,
             // 'data' => $gameResult,
             // 'playerGameStats' => $playerGameStats,
         ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Helper methods for stat calculation
    private function createInactivePlayerStats($player, $gameData, $seasonId)
    {
        return [
            'player_id' => $player->id,
            'game_id' => $gameData->game_id,
            'season_id' => $seasonId,
            'team_id' => $player->team_id,
            'points' => 0,
            'rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'fouls' => 0,
            'minutes' => 0,
            'field_goal_attempts' => 0,
            'field_goals_made' => 0,
            'three_point_attempts' => 0,
            'three_pointers_made' => 0,
            'free_throw_attempts' => 0,
            'free_throws_made' => 0,
        ];
    }
    private function calculatePerformanceFactor($player)
    {
        try {
            // Base performance factor with a random value between 100 and 120
            $basePerformanceFactor = rand(100, 120) / 100;

            // Adjust the factor based on player fatigue
            $fatigueFactor = (100 - $player->fatigue) / 100;
            $performanceFactor = $basePerformanceFactor * $fatigueFactor;

            // Further adjustments based on player injury status
            if ($player->is_injured) {
                // If injured, reduce performance factor by a percentage (e.g., 50% less)
                $performanceFactor *= 0.5;
            }

            // Optionally adjust further based on player ratings, like leadership or basketball IQ
            if ($player->leadership_rating > 70) {
                // If the player has a high leadership rating, boost performance slightly
                $performanceFactor *= 1.05;
            }

            // Return the final performance factor
            return round($performanceFactor, 2);

        } catch (\Exception $e) {
          
            return 1.0; // Default performance factor in case of error
        }
    }

    private function calculateDefensiveImpact($opponentTeamId)
    {
        $seasonId = get_current_season_id();
        // Step 1: Get average defense_rating, rebounding_rating, and morale
        $playerAverages = DB::table('players')
            ->where('team_id', $opponentTeamId)
            ->where('is_active', 1)
            ->selectRaw('AVG(defense_rating) as defense_rating, AVG(rebounding_rating) as rebounding_rating')
            ->first();
    
        $defenseRating = $playerAverages->defense_rating ?? 0;
        $reboundingRating = $playerAverages->rebounding_rating ?? 0;
        $morale = $playerAverages->morale ?? 0;
    
        // Step 3: Calculate the overall defensive score
        $overallDefensiveRating = ($defenseRating + $reboundingRating) / 2;
    
        // Step 4: Combine skill, morale, and chemistry
        $combinedImpact = (
            ($overallDefensiveRating * 0.6) + ($morale * 0.2)
        );
    
        // Step 5: Normalize
        return floor($combinedImpact / 30);
    }
    

    private function calculatePoints($twoPointMade, $threePointMade, $freeThrowsMade)
    {
        $points = ($twoPointMade * 2) + ($threePointMade * 3) + $freeThrowsMade;
        return max($points, 0);
    }

    private function calculateRebounds($player, $minutes, $performanceFactor)
    {
        $reboundPerMinute = 0.3 + (
            ($player->rebounding_rating * 0.6 + $player->athleticism_rating * 0.3 + $player->basketball_iq_rating * 0.1) / 300
        );
        return round($reboundPerMinute * $minutes * $performanceFactor / 2);
    }
    
    private function calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact)
    {
        if ($minutes === 0) return 0;
    
        $baseRates = [
            'PG' => 0.07,
            'SG' => 0.06,
            'SF' => 0.05,
            'PF' => 0.04,
            'C'  => 0.03,
        ];
    
        $positions = explode('/', $player->position ?? 'SF');
        $baseRate = collect($positions)
            ->map(fn($pos) => $baseRates[trim($pos)] ?? 0.05)
            ->average();
    
        $iqPassFactor = (200 - ($player->passing_rating + $player->basketball_iq_rating)) / 200;
        $adjustedRate = ($baseRate + ($defensiveImpact / 250)) * (1 + $iqPassFactor / 2);
    
        $turnovers = round($minutes * $adjustedRate * $performanceFactor);
        return min($turnovers, 8);
    }
    
    private function calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact)
    {
        if ($minutes === 0) return 0;
    
        $baseRates = [
            'PG' => 0.04,
            'SG' => 0.05,
            'SF' => 0.06,
            'PF' => 0.07,
            'C'  => 0.08,
        ];
    
        $positions = explode('/', $player->position ?? 'SF');
        $baseRate = collect($positions)
            ->map(fn($pos) => $baseRates[trim($pos)] ?? 0.06)
            ->average();
    
        $foulControl = ($player->defense_rating + $player->basketball_iq_rating + $player->athleticism_rating) / 3;
        $adjustedRate = $baseRate + ($defensiveImpact / 200) * (100 - $foulControl) / 100;
    
        $fouls = round($minutes * $adjustedRate * $performanceFactor);
        return min($fouls, 6);
    }
    
    private function calculateBlocks($player, $minutes, $performanceFactor)
    {
        $blocksPerMinute = 0.3 + (
            ($player->blocks_rating * 0.5 + $player->athleticism_rating * 0.3 + $player->defense_rating * 0.2) / 200
        );
        return round($blocksPerMinute * $minutes * $performanceFactor / 4);
    }
    
    private function calculateSteals($player, $minutes, $performanceFactor)
    {
        $stealsPerMinute = 0.3 + (
            ($player->steals_rating * 0.4 + $player->defense_rating * 0.3 + $player->basketball_iq_rating * 0.2 + $player->athleticism_rating * 0.1) / 200
        );
        return round($stealsPerMinute * $minutes * $performanceFactor / 4);
    }
    
    private function calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $chemistry = 50, $isClutchTime = false)
    {
        $positionWeights = [
            'PG' => ['two_point' => 0.5, 'three_point' => 0.5, 'free_throw' => 0.6],
            'SG' => ['two_point' => 0.4, 'three_point' => 0.6, 'free_throw' => 0.5],
            'SF' => ['two_point' => 0.5, 'three_point' => 0.5, 'free_throw' => 0.5],
            'PF' => ['two_point' => 0.7, 'three_point' => 0.3, 'free_throw' => 0.5],
            'C'  => ['two_point' => 0.8, 'three_point' => 0.2, 'free_throw' => 0.4],
        ];
    
        $roleMultipliers = [
            'star player' => 1.1,
            'all star'    => 1.05,
            'starter'     => 1.0,
            'role player' => 0.85,
            'bench'       => 0.7,
        ];
    
        $positions = explode('/', $player->position ?? 'SF');
        $positionCount = count($positions);
    
        $positionFactor = ['two_point' => 0, 'three_point' => 0, 'free_throw' => 0];
        foreach ($positions as $pos) {
            $pos = trim($pos);
            $weights = $positionWeights[$pos] ?? $positionWeights['SF'];
            $positionFactor['two_point'] += $weights['two_point'] / $positionCount;
            $positionFactor['three_point'] += $weights['three_point'] / $positionCount;
            $positionFactor['free_throw'] += $weights['free_throw'] / $positionCount;
        }
    
        $roleFactor = $roleMultipliers[strtolower($player->role)] ?? 1.0;
        $fatigueFactor = max(0.5, (100 - ($player->fatigue ?? 0)) / 100);
        $injuryFactor = $player->is_injured ? 0.3 : 1.0;
        $clutchBoost = ($isClutchTime && ($player->clutch_rating ?? 50) > 80) ? 1.2 : 1.0;
    
        // 🆕 New: Chemistry and Morale factors
        $morale = $player->morale ?? 50;
        $moraleFactor = 0.9 + ($morale / 1000);     // 0.9 ~ 1.4 range (at morale 40 ~ 100)
        $chemistryFactor = 0.9 + ($chemistry / 1000); // 0.9 ~ 1.4 range (at chemistry 40 ~ 100)
    
        $baseAttempts = max(1, round($minutes * 0.8));
        $foulImpact = $fouls * 0.05;
        $turnoverImpact = $turnovers * 0.1;
        $adjustedBaseAttempts = max(0, $baseAttempts - ($foulImpact + $turnoverImpact));
    
        $attemptBias = rand(85, 115) / 100;
        $threePointWeight = $positionFactor['three_point'] * $attemptBias;
        $twoPointWeight = 1 - $threePointWeight;
    
        $totalFactor = $roleFactor * $fatigueFactor * $injuryFactor * $clutchBoost * $moraleFactor * $chemistryFactor;
    
        $rawAdjustedAttempts = $adjustedBaseAttempts * $totalFactor;
        $maxAttempts = ($player->role === 'star player') ? 40 : 35;
        $adjustedAttempts = min($rawAdjustedAttempts, $maxAttempts);
    
        $threePointAttempts = round($adjustedAttempts * $threePointWeight);
        $twoPointAttempts = round($adjustedAttempts * $twoPointWeight);
    
        $freeThrowAttempts = round(
            ($twoPointAttempts * 0.3 + $threePointAttempts * 0.1) * (($player->strength_rating ?? 70) / 100)
        );
    
        // Defense impact
        $defenseScaling = 1 + ($adjustedAttempts / 50);
        $adjustedTwoPointAttempts = max(0, $twoPointAttempts - ($defensiveImpact * $defenseScaling));
        $adjustedThreePointAttempts = max(0, $threePointAttempts - ($defensiveImpact * $defenseScaling));
        $adjustedFreeThrowAttempts = max(0, $freeThrowAttempts - ($defensiveImpact * 0.5));
    
        // Efficiency drop from high volume
        $volumePenalty = 1 - min(0.15, max(0, $adjustedAttempts - 25) * 0.01);
    
        $twoPointAccuracy = (
            ($player->two_point_rating ?? 60) / 100 *
            ($player->basketball_iq_rating ?? 60) / 100 *
            $fatigueFactor * $injuryFactor * $volumePenalty * $moraleFactor * $chemistryFactor
        );
    
        $threePointAccuracy = (
            ($player->three_point_rating ?? 60) / 100 *
            ($player->basketball_iq_rating ?? 60) / 100 *
            $fatigueFactor * $injuryFactor * $volumePenalty * $moraleFactor * $chemistryFactor
        );
    
        $freeThrowAccuracy = (
            ($player->free_throw_rating ?? 60) / 100 *
            ($player->work_ethic_rating ?? 60) / 100 *
            $fatigueFactor * $injuryFactor * $moraleFactor
        );
    
        $twoPointMade = min(rand(0, round($adjustedTwoPointAttempts * $twoPointAccuracy)), $adjustedTwoPointAttempts);
        $threePointMade = min(rand(0, round($adjustedThreePointAttempts * $threePointAccuracy)), $adjustedThreePointAttempts);
        $freeThrowMade = min(rand(0, round($adjustedFreeThrowAttempts * $freeThrowAccuracy)), $adjustedFreeThrowAttempts);
    
        // Cap scoring to a realistic points per minute
        $estimatedPoints = ($twoPointMade * 2) + ($threePointMade * 3) + $freeThrowMade;
        $maxPointsPerMinute = 3.0;
        $maxPoints = round($minutes * $maxPointsPerMinute);
    
        if ($estimatedPoints > $maxPoints) {
            $scalingFactor = $maxPoints / $estimatedPoints;
    
            $twoPointMade = round($twoPointMade * $scalingFactor);
            $threePointMade = round($threePointMade * $scalingFactor);
            $freeThrowMade = round($freeThrowMade * $scalingFactor);
        }
    
        return [
            'two_point_attempts'     => $adjustedTwoPointAttempts,
            'two_point_made'         => $twoPointMade,
            'three_point_attempts'   => $adjustedThreePointAttempts,
            'three_point_made'       => $threePointMade,
            'free_throw_attempts'    => $adjustedFreeThrowAttempts,
            'free_throw_made'        => $freeThrowMade,
        ];
    }
    

    public function getScheduleIds(Request $request)
    {
        // Validate the request data
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'round' => 'required',
        ]);

        $seasonId = $request->season_id;
        $round = $request->round;
        $excludedRounds = config('playoffs');

         // Get the latest season status (assuming you store this status in the 'seasons' table)
        $latestSeasonStatus = DB::table('seasons')
            ->where('id', $seasonId)
            ->value('status'); // Get the 'status' of the current season
      
             // Get the number of rounds that are already simulated (status != 2)
        $simulatedRounds = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->whereNotIn('round', $excludedRounds)
            ->where('status', '=', 2) // Check if any game is not yet simulated
            ->distinct('round')
            ->count();
     
        // Get the total number of rounds in the season
        $totalRounds = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->whereNotIn('round', $excludedRounds)
            ->distinct('round')
            ->count();
     
        // Retrieve schedule records for the given season and round
        $schedules = Schedules::where('season_id', $seasonId)
            ->where('round', $round)
            ->where('status', 1)
            ->orderBy('id')
            ->select('id', 'conference_id')
            ->get();

             // Check if half of the rounds are simulated
        $isTradeDeadline = $simulatedRounds >= ($totalRounds / 2) - 2 && $latestSeasonStatus == 1;

        // Group by conference_id
        $groupedByConference = $schedules->groupBy('conference_id');

        // Interleave results to alternate by conference
        $interleaved = [];
        $hasData = true;

        while ($hasData) {
            $hasData = false;

            foreach ($groupedByConference as $conferenceId => $games) {
                if (!$games->isEmpty()) {
                    $interleaved[] = $games->shift(); // Take the first available game
                    $hasData = true;
                }
            }
        }

        // Count distinct conferences in this round
        $conferenceCount = $groupedByConference->count();

        return response()->json([
            'schedule_ids' => $interleaved,
            'conference_count' => $conferenceCount,
            'is_trade_deadline' => $isTradeDeadline, // Add trade deadline info
            'simulated_rounds' => $simulatedRounds,
            'total_rounds' => $totalRounds,
            'status' => $latestSeasonStatus,
        ]);
    }

    private function distributeMinutes($playersArray, $totalMinutes, $gameId)
    {
        $rolePriority = [
            'star player' => 1,
            'all star'    => 2,
            'starter'     => 3,
            'role player' => 4,
            'bench'       => 5,
        ];

        $roleMinuteRanges = [
            'star player' => [36, 42],
            'all star'    => [32, 38],
            'starter'     => [28, 34],
            'role player' => [15, 24],
            'bench'       => [0, 20],
        ];

        $positionTargets = [
            'PG' => 48,
            'SG' => 48,
            'SF' => 48,
            'PF' => 48,
            'C'  => 48,
        ];

        $sorted = collect($playersArray)
            ->sortBy(fn($p) => $rolePriority[$p['role']] ?? 5)
            ->values();

        // Step 1: Sit injured players
        $dnpPlayers = $sorted->filter(fn($p) => $p['is_injured'])->take(2);

        // Step 2: Fill remaining DNP slots
        if ($dnpPlayers->count() < 2) {
            $remainingSlots = 2 - $dnpPlayers->count();
            $additionalDNP = $sorted
                ->reject(fn($p) => $dnpPlayers->contains('id', $p['id']) || $p['is_injured'])
                ->sortBy([
                    ['per', 'asc'],
                    ['eff', 'asc'],
                ])
                ->take($remainingSlots);

            $dnpPlayers = $dnpPlayers->merge($additionalDNP);
        }

        // ✅ Ensure minimum of 5 players with minutes
        $rotation = $sorted->reject(fn($p) => $dnpPlayers->contains('id', $p['id']));

        if ($rotation->count() < 5) {
            $needed = 5 - $rotation->count();

            $reAddCandidates = $dnpPlayers
                ->filter(fn($p) => !$p['is_injured'])
                ->sortBy([
                    ['per', 'desc'],
                    ['eff', 'desc'],
                ])
                ->take($needed);

            $dnpPlayers = $dnpPlayers->reject(fn($p) => $reAddCandidates->contains('id', $p['id']));
            $rotation = $rotation->merge($reAddCandidates);
        }

        $minutes = [];
        foreach ($dnpPlayers as $p) {
            $minutes[$p['id']] = 0;
        }

        // Step 3: Assign minutes based on role and position
        $assignedTotal = 0;

        foreach ($rotation as $player) {
            $role = $player['role'];
            $range = $roleMinuteRanges[$role] ?? [5, 15];
            $baseMinutes = rand($range[0], $range[1]);

            $positions = explode('/', $player['position']);
            $positionAssigned = false;

            foreach ($positions as $pos) {
                if (($positionTargets[$pos] ?? 0) > 0) {
                    $assigned = min($baseMinutes, $positionTargets[$pos]);
                    $minutes[$player['id']] = $assigned;
                    $positionTargets[$pos] -= $assigned;
                    $assignedTotal += $assigned;
                    $positionAssigned = true;
                    break;
                }
            }

            if (!$positionAssigned) {
                foreach ($positions as $pos) {
                    if (isset($positionTargets[$pos])) {
                        $assigned = min($baseMinutes, $positionTargets[$pos]);
                        $minutes[$player['id']] = $assigned;
                        $positionTargets[$pos] -= $assigned;
                        $assignedTotal += $assigned;
                        break;
                    }
                }
            }

            if (!isset($minutes[$player['id']])) {
                $minutes[$player['id']] = 0;
            }

            $this->fatigueRate($player, $minutes[$player['id']], $gameId);
            $this->handleInjuredPlayer($player, $gameId);
        }

        // Step 4: Normalize to total minutes (usually 240)
        $difference = $totalMinutes - array_sum($minutes);

        if (abs($difference) > 0) {
            $eligible = $rotation->sortBy(fn($p) => $rolePriority[$p['role']] ?? 5)->values();
            $i = 0;

            while ($difference !== 0 && $eligible->isNotEmpty()) {
                $player = $eligible[$i % $eligible->count()];
                $id = $player['id'];

                if (!isset($minutes[$id])) {
                    $minutes[$id] = 0;
                }

                if ($difference > 0 && $minutes[$id] < 48) {
                    $minutes[$id]++;
                    $difference--;
                } elseif ($difference < 0 && $minutes[$id] > 0) {
                    $minutes[$id]--;
                    $difference++;
                }

                $i++;
            }
        }

        return $minutes;
    }
    
    public function fatigueRate($player, $minutes, $gameId)
    {
        try {
            if (is_array($player)) {
                $player = (object) $player;
            }

            $seasonId = get_current_season_id() ?? 1;
            $staminaFactor  = $player->stamina_rating / 100;
            $strengthFactor = $player->strength_rating / 100;
            $currentFatigue = $player->fatigue;
            $retirementAge = $player->retirement_age ?? 36;
            $age = $player->age;

            // STEP 1: Calculate recovery rate
            $baseRecoveryRate = ($staminaFactor + $strengthFactor) * 0.1;

            // Age-based slowdown
            $ageGap = $retirementAge - $age;
            if ($ageGap <= 0) {
                $recoverySlowdown = 0.5;
            } elseif ($ageGap <= 5) {
                $recoverySlowdown = 1 - (0.1 * (5 - $ageGap));
            } else {
                $recoverySlowdown = 1;
            }

            $recoveryRate = $baseRecoveryRate * $recoverySlowdown;

            // STEP 2: Apply Recovery
            if (!$player->is_injured && $currentFatigue > 0) {
                $currentFatigue = max(0, $currentFatigue - $recoveryRate);
            }

            // STEP 3: Add fatigue from this game
            if ($minutes == 0) {
                $newFatigue = max(0, $currentFatigue - 20); // Auto-recovery for DNP
            } else {
                $fatigueIncrease = $minutes * (1 - $staminaFactor * 0.5);
                $newFatigue = min(20, $currentFatigue + round($fatigueIncrease)); // Cap at 20
            }

            // STEP 4: Injury chance check using injury_prone_percentage
            if ($newFatigue >= 20) {
                $triggerInjuryChance = rand(1, 100);
            
                if ($triggerInjuryChance <= 30) { // 30% chance to trigger injury logic
                    $injuryRoll = rand(1, 100);
                    if ($injuryRoll <= $player->injury_prone_percentage) {
                        $this->causeInjury($player, $gameId, $seasonId);
                        return;
                    }
                }
            
                // If not injured, reset fatigue
                $newFatigue = 0;
            }
            

            // STEP 5: Save fatigue
            DB::table('players')->where('id', $player->id)->update([
                'fatigue' => $newFatigue,
            ]);

        } catch (\Exception $e) {
            \Log::error("Error updating fatigue for player {$player->id}: " . $e->getMessage());
        }
    }

    public function calculateInjuryChance($fatigue)
    {
        // Calculate injury chance based on fatigue
        // Injury chance increases as fatigue gets higher, starting at 80
        if ($fatigue >= 80) {
            return min(100, ($fatigue - 80) * 2); // Injury chance increases 2% for each point above 80
        }
        return 0; // No injury chance if fatigue is below 80
    }

    public function causeInjury($player, $gameId, $seasonId)
    {
        // **Injury Logic**
        $injuryTypes = config('injuries');
        if (!empty($injuryTypes)) {
            $injuryTypeName = array_rand($injuryTypes);
            $recoveryGames = $injuryTypes[$injuryTypeName]['recovery_games'];

            // **Update Injury in Database**
            DB::table('players')->where('id', $player->id)->update([
                'fatigue' => 100,
                'is_injured' => true,
                'injury_type' => $injuryTypeName,
                'injury_recovery_games' => $recoveryGames,
            ]);

            DB::table('players')->where('id', $player->id)->increment('injury_history', 1);

            // Insert injury history
            DB::table('injury_histories')->insert([
                'player_id' => $player->id,
                'game_id' => $gameId,
                'team_id' => $player->team_id,
                'season_id' => $seasonId,
                'injury_type' => $injuryTypeName,
                'recovery_games' => $recoveryGames,
                'performance_impact' => $injuryTypes[$injuryTypeName]['performance_impact'],
                'injury_date' => now(),
                'recovery_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            \Log::error("Injury types configuration is missing.");
        }
    }

    public function handleInjuredPlayer($player, $gameId)
    {
        try {
            if (is_array($player)) {
                $player = (object) $player;
            }

            $seasonId = get_current_season_id() ?? 1;
            $seasonStatus = DB::table('seasons')->where('id', $seasonId)->value('status');

            // **If Player is Injured, Process Recovery or Waiving**
            if ($player->is_injured) {
                // **Injury Recovery Process**
                $recoveryGamesLeft = $player->injury_recovery_games;

                if ($recoveryGamesLeft > 0) {
                    // Decrement the recovery games for the injured player
                    DB::table('players')->where('id', $player->id)->decrement('injury_recovery_games', 1);
                    
                }

                // **Check if Player Fully Recovered**
                if ($recoveryGamesLeft <= 1) {
                    // Mark the player as recovered
                    DB::table('players')->where('id', $player->id)->update([
                        'is_injured' => false,
                        'injury_type' => null, // Clear injury type
                    ]);

                    // Log the recovery event
                    DB::table('injury_histories')
                        ->where('player_id', $player->id)
                        ->whereNull('recovery_date')
                        ->latest()
                        ->update(['recovery_date' => now(), 'updated_at' => now()]);

                    \Log::info("Player {$player->name} has fully recovered from injury.");
                }
            } else {
                \Log::info("Player {$player->name} is not injured.");
            }

            // **Waive Player if Injury Recovery is Taking Too Long**
            $requiredRecoveryGames = 25; // Example: Player should recover within 25 games to avoid waiving

            //can replace injured players until in season trade deadline...
            if ($player->injury_recovery_games >= $requiredRecoveryGames && $seasonStatus <= 1) {
                // **20% Chance to Waive Player if Recovery is Too Long**
                if (rand(1, 100) <= 20) {
                    // Player is waived due to extended injury recovery period
                    DB::table('transactions')->insert([
                        'player_id' => $player->id,
                        'season_id' => $seasonId,
                        'details' => 'Waived due to extended injury recovery period',
                        'from_team_id' => $player->team_id,
                        'to_team_id' => 0,
                        'status' => 'waived',
                    ]);

                    // Set contract to zero, indicating that the player is now waived
                    DB::table('players')->where('id', $player->id)->update([
                        'contract_years' => 0,
                        'team_id' => 0,
                    ]);

                    // **Replace Waived Player with Best Free Agent Available**
                    $replacement = $this->getBestFreeAgentAvailable($player->position);
                    if ($replacement) {
                        $contractYears = $this->getContractYearsBasedOnRole($player->role);
                        DB::table('players')->where('id', $replacement->player_id)->update([
                            'team_id' => $player->team_id,
                            'contract_years' => $contractYears,
                        ]);

                        DB::table('transactions')->insert([
                            'player_id' => $replacement->player_id,
                            'season_id' => $seasonId,
                            'details' => 'Signed as injury replacement for ' . $player->name . '. Contract Years: ' . $contractYears,
                            'from_team_id' => 0,
                            'to_team_id' => $player->team_id,
                            'status' => 'signed',
                        ]);

                        (new AwardsController)->storePlayerCurrentSeasonStats($player->team_id, $replacement->player_id);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error handling injured player {$player->id}: " . $e->getMessage());
        }
    }

    public function getActivePlayersSorted($teamId, $rolePriority)
    {
        $seasonId = get_current_season_id();
    
        // Get all columns from the players table except created_at and updated_at
        $playerColumns = Schema::getColumnListing('players');
        $playerColumns = array_filter($playerColumns, fn($col) => !in_array($col, ['created_at', 'updated_at']));
    
        // Add AVG(per) and AVG(eff) from the joined table
        $selectColumns = array_map(fn($col) => "players.$col", $playerColumns);
        $selectColumns[] = DB::raw('AVG(player_season_stats.per) as per');
        $selectColumns[] = DB::raw('AVG(player_season_stats.eff) as eff');
    
        return Player::select($selectColumns)
            ->where('players.team_id', $teamId)
            ->where('players.is_active', 1)
            ->leftJoin('player_season_stats', function ($join) use ($seasonId) {
                $join->on('players.id', '=', 'player_season_stats.player_id')
                     ->where('player_season_stats.season_id', '=', $seasonId);
            })
            ->groupBy(...array_map(fn($col) => "players.$col", $playerColumns))
            ->orderByDesc('per')
            ->orderByDesc('eff')
            ->get();
    }

    private function fireLeopardRule($teamId)
    {
        $seasonId = get_current_season_id();

        // Count the number of active (non-injured) players
        $activePlayersCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_injured', false)
            ->count();

        // If the team has at least 7 healthy players, no action is needed
        if ($activePlayersCount >= 7) {
            return $activePlayersCount;
        }

        // Determine how many players need to be added
        $playersNeeded = 7 - $activePlayersCount;
        $signedPlayers = [];

        // Find free agents for temporary contracts
        $freeAgents = DB::table('players')
            ->where('team_id', 0) // Free agent pool
            ->where('is_injured', 0) // Not injured
            ->where('is_active', 1) // Ensure the player is active
            ->where('role', '!=', 'star player') // Exclude star players
            ->orderByDesc('overall_rating') // Sort by highest overall rating
            ->orderBy('injury_prone_percentage', 'asc') // Lowest injury-prone percentage first
            ->orderBy('age', 'asc') // Then sort by youngest age
            ->take($playersNeeded)
            ->get();

    
        foreach ($freeAgents as $freeAgent) {
            // Assign a temporary hardship contract (10-game contract)
            DB::table('players')->where('id', $freeAgent->id)->update([
                'team_id' => $teamId,
                'contract_years' => 0, // Temporary contract
                'hardship_contract' => 10, // The player is signed for 10 games only
            ]);

            // Log transaction
            DB::table('transactions')->insert([
                'player_id' => $freeAgent->id,
                'season_id' => $seasonId,
                'details' => 'Signed under hardship exception (10-game contract)',
                'from_team_id' => 0, 
                'to_team_id' => $teamId,
                'status' => 'signed-hardship',
            ]);

            $storeStats = new AwardsController;
            $storeStats->storePlayerSeasonStats($teamId, $freeAgent->id);

            $signedPlayers[] = $freeAgent;
        }

        return $signedPlayers;
    }

    private function updateInjuryFreeAgents()
    {
        // Update injury recovery games for free agents and mark them as not injured if recovery games reach 0
        $totalGamesPerDay = 32;
        $deductionPerGame = 1 / $totalGamesPerDay; // 0.03125
        
        $deductInjuryGames = DB::table('players')
            ->where('is_injured', true)
            ->where('injury_recovery_games', '>', 0)
            ->update([
                'injury_recovery_games' => DB::raw("GREATEST(injury_recovery_games - $deductionPerGame, 0)")
            ]);
        
    
        // Check if any rows were actually updated
        $affectedRows = DB::table('players')
            ->where('is_injured', true)
            ->where('injury_recovery_games', 0)
            ->count(); // Count players whose recovery reached 0

        if ($affectedRows > 0) {
            DB::table('players')
                ->where('is_injured', true)
                ->where('injury_recovery_games','<=', 0)
                ->update(['is_injured' => 0]);
        }

    }

    private function getBestFreeAgentAvailable($position)
    {
        // Split the position into components (if dual position is passed, otherwise it will be an array with one element)
        $positions = explode('/', $position);

        // Start building the query for players
        $bestPlayer = DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where(function ($query) use ($positions) {
                if (count($positions) == 1) {
                    // Single position - match exact position
                    $query->where('players.position', $positions[0]);
                } else {
                    // Dual positions - match either position
                    $query->where('players.position', $positions[0])
                        ->orWhere('players.position', 'LIKE', '%' . $positions[0] . '%')
                        ->orWhere('players.position', $positions[1])
                        ->orWhere('players.position', 'LIKE', '%' . $positions[1] . '%');
                }
            })
            ->select(
                'players.id as player_id',
                'players.team_id',
                'players.overall_rating', 
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->first();

        // If no player found with specified role, get random available player
        if (!$bestPlayer) {
            $bestPlayer = DB::table('players')
                ->where('players.is_active', 1)
                ->where('players.is_injured', 0) 
                ->where('players.team_id', 0)
                ->select(
                    'players.id as player_id',
                    'players.team_id',
                    'players.overall_rating',
                    'players.injury_history', 
                    'players.age',
                    'players.role'
                )
                ->inRandomOrder() // Pick random player
                ->first();
        }

        return $bestPlayer;
    }

    private function updateFinalsBonusContract($teamId, $seasonId, $teamName) {
        // Retrieve all active players for the specified team
        $players = Player::where('is_active', 1)
                        ->where('team_id', $teamId)
                        ->where('is_injured', 0)  // Exclude injured players
                        ->get();
    
        foreach ($players as $player) {
            // Determine the additional contract years based on the player's role
            $additionalContractYears = 0;
            if ($player->role == 'star player') {
                $additionalContractYears = rand(2, 3);  // 2 to 3 years for star players
            } 
            if ($player->role == 'all star') {
                $additionalContractYears = rand(1, 3);  // 1 to 3 years for all star players
            }
            if ($player->role == 'starter') {
                $additionalContractYears = rand(0, 2);  // 1 to 3 years for all star players
            }
            
            //only core players will have a bonus
            if($additionalContractYears > 0){
                // Update the player's contract years
                $player->contract_years += $additionalContractYears;
                $player->save();
        
                // Insert transaction log
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => 'Re-signed with ' . $teamName . ' for a contract extension(Finals Bonus) of ' . $additionalContractYears . ' years',
                    'from_team_id' => $player->team_id,
                    'to_team_id' => $player->team_id,
                    'status' => 'resigned',
                ]);
            }
        }
    }
    
    private function getContractYearsBasedOnRole($role)
    {
        switch ($role) {
            case 'star player':
                return mt_rand(1, 7);
            case 'starter':
                return mt_rand(1, 5);
            case 'role player':
                return mt_rand(1, 4);
            case 'bench':
            default:
                return mt_rand(1, 3);
        }
    }
    // Method to handle semi-finals logic
    private function updateConferenceChampions($gameData, $winnerId)
    {
        // Determine the conference based on home or away conference name
        $conferenceName = $gameData->home_conference_name;

        // Define the columns to update
        $columnsToUpdate = [];

        // Determine the winner's team name based on the winner ID
        $winnerName = null;
        if ($gameData->home_team_id === $winnerId) {
            $winnerName = $gameData->home_team_name;
        } elseif ($gameData->away_team_id === $winnerId) {
            $winnerName = $gameData->away_team_name;
        }

        // Check the conference and set the champion ID and name columns
        switch ($conferenceName) {
            case 'Luzon':
                $columnsToUpdate = [
                    'east_champion_id' => $winnerId,
                    'east_champion_name' => $winnerName,
                ];
                break;
            case 'NCR':
                $columnsToUpdate = [
                    'west_champion_id' => $winnerId,
                    'west_champion_name' => $winnerName,
                ];
                break;
            case 'Visayas':
                $columnsToUpdate = [
                    'north_champion_id' => $winnerId,
                    'north_champion_name' => $winnerName,
                ];
                break;
            case 'Mindanao':
                $columnsToUpdate = [
                    'south_champion_id' => $winnerId,
                    'south_champion_name' => $winnerName,
                ];
                break;
        }

        // Update the seasons table with the determined columns
        DB::table('seasons')
            ->where('id', $gameData->season_id)
            ->update($columnsToUpdate);
    }

    // Method to handle finals logic
    private function updateFinalsWinner($gameData, $winnerId, $homeScore, $awayScore)
    {
        // Find the best statistical player (MVP) from the winning team
        $mvpPlayer = PlayerGameStats::join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->where('player_game_stats.team_id', $winnerId)
            ->where('player_game_stats.game_id', $gameData->game_id)
            // Calculate a weighted performance metric for the MVP
            ->select(
                'player_game_stats.*',
                'players.name as mvp_name', // Include the player's name
                DB::raw('(
        player_game_stats.points * 1.0 +
        player_game_stats.rebounds * 1.2 +
        player_game_stats.assists * 1.5 +
        player_game_stats.steals * 2.0 +
        player_game_stats.blocks * 2.0 -
        player_game_stats.turnovers * 1.5
    ) as mvp_score')
            )
            ->orderByDesc('mvp_score') // Order by the calculated performance score
            ->first();

        // If an MVP player is found, set the player's name and id
        $finalsMVP = $mvpPlayer ? $mvpPlayer->mvp_name : ''; // Use the player's name from the 'mvp_name' alias
        $finalsMVPId = $mvpPlayer ? $mvpPlayer->player_id : '';

        $this->updateFinalsMVPBonusContract($winnerId, $gameData->season_id, $finalsMVPId);
        // Update the season's finals information
        DB::table('seasons')
            ->where('id', $gameData->season_id)
            ->update([
                'finals_winner_id' => $winnerId,
                'finals_loser_id' => $winnerId === $gameData->home_team_id ? $gameData->away_team_id : $gameData->home_team_id,
                'finals_winner_name' => $winnerId === $gameData->home_team_id ? $gameData->home_team_name : $gameData->away_team_name,
                'finals_loser_name' => $winnerId === $gameData->home_team_id ? $gameData->away_team_name : $gameData->home_team_name,
                'finals_winner_score' => $winnerId === $gameData->home_team_id ? $homeScore : $awayScore,
                'finals_loser_score' => $winnerId === $gameData->home_team_id ? $awayScore : $homeScore,
                'finals_mvp' => $finalsMVP,
                'finals_mvp_id' => $finalsMVPId,
            ]);
    }

    public function fixTeamPositionBalance($teamId)
    {
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
    
        if (!$counts) {
            return response()->json(['error' => 'Team not found in view.'], 404);
        }
    
        $posCounts = collect($counts)->only($positions)->map(fn($val) => (int) $val)->toArray();
        $positionsNeeding = collect($posCounts)->filter(fn($count) => $count < 3);
        $positionsOverfilled = collect($posCounts)->filter(fn($count) => $count > 3);
        
    
        // =============== CASE 1: Roster < 15 ====================
        if ($rosterCount < 15) {
            while ($rosterCount < 15) {
                $lowestPosition = $posCounts->sort()->keys()->first();
    
                $agent = $this->getBestFreeAgentAvailable($lowestPosition);
                $contractYears = $this->getContractYearsBasedOnRole($agent->role);
                if (!$agent) break;

                DB::table('players')->where('id', $agent->player_id)->update([
                    'team_id' => $teamId,
                    'contract_years' => $contractYears,
                ]);
    
                DB::table('transactions')->insert([
                    'player_id' => $agent->player_id,
                    'season_id' => $seasonId,
                    'details' => "Signed to fill position {$lowestPosition}",
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'signed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                (new AwardsController)->storePlayerCurrentSeasonStats($teamId, $agent->player_id);
    
                $rosterCount++;
    
                // Update position counts
                $posCounts[$lowestPosition]++;
            }
    
            return response()->json(['message' => 'Signed players to reach 15-man roster.']);
        }
    
        // =============== CASE 2: Roster == 15 && underfilled positions ================
        if ($rosterCount == 15 && $positionsNeeding->isNotEmpty()) {
            foreach ($positionsNeeding as $position => $missing) {
                for ($i = 0; $i < $missing; $i++) {
                    // Find a position to waive from
                    $overflow = $positionsOverfilled->sortDesc()->keys()->first();
    
                    if (!$overflow || $posCounts[$overflow] <= 3) break;
    
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
                        ->get()
                        ->map(function ($player) use ($seasonId) {
                            // Get all season stats for this player (regardless of team) in this season
                            $stats = DB::table('player_season_stats')
                                ->where('player_id', $player->id)
                                ->where('season_id', $seasonId)
                                ->get();

                            // Add up or average stats
                            $player->total_games = $stats->sum('games_played');
                            $player->total_minutes = $stats->sum('minutes');
                            $player->avg_per = $stats->avg('per');
                            $player->total_per = $stats->sum('per'); // Optional

                            return $player;
                        })
                        ->sortBy([
                            ['contract_years', 'asc'],
                            ['avg_per', 'asc'], // or use 'total_per' if you want total contribution
                        ])
                        ->first();

    
                    if (!$playerToWaive) continue;
    
                    // Waive player
                    DB::table('players')->where('id', $playerToWaive->id)->update([
                        'contract_years' => 0,
                        'team_id' => 0,
                    ]);
    
                    DB::table('transactions')->insert([
                        'player_id' => $playerToWaive->id,
                        'season_id' => $seasonId,
                        'details' => "Waived to rebalance position",
                        'from_team_id' => $teamId,
                        'to_team_id' => 0,
                        'status' => 'waived',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
    
                    // Sign replacement
                    $replacement = $this->getBestFreeAgentAvailable($position);
                    $contractYears = $this->getContractYearsBasedOnRole($replacement->role);
                    if (!$replacement) continue;
                    
                    DB::table('players')->where('id', $replacement->player_id)->update([
                        'team_id' => $teamId,
                        'contract_years' => $contractYears,
                    ]);
    
                    DB::table('transactions')->insert([
                        'player_id' => $replacement->player_id,
                        'season_id' => $seasonId,
                        'details' => "Signed to fill underfilled position $position",
                        'from_team_id' => 0,
                        'to_team_id' => $teamId,
                        'status' => 'signed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
    
                    (new AwardsController)->storePlayerCurrentSeasonStats($teamId, $replacement->player_id);
                    // Update in-memory counts
                    $posCounts[$overflow]--;
                    $posCounts[$position]++;
                }
            }
    
            return response()->json(['message' => 'Roster balanced by waiving and signing players.']);
        }
    
        // =============== CASE 3: Roster is full and all positions are fine ================
        return response()->json(['message' => 'Roster already full and positionally balanced.']);
    }
    

    private function updateFinalsMVPBonusContract($winnerId, $seasonId, $finalsMVPId) {
        $extensionYears = 3; // Number of years to extend the contract for the Finals MVP
        $awardName = 'Finals MVP'; // Name of the award
        // Add years to player's contract
        DB::table('players')
            ->where('id', $finalsMVPId)
            ->update([
                'contract_years' => DB::raw("contract_years + $extensionYears"),
                'updated_at' => now()
            ]);
    
        // Record contract extension transaction
        DB::table('transactions')->insert([
            'player_id' => $finalsMVPId,
            'season_id' => $seasonId,
            'details' => "Contract extended by {$extensionYears} year(s) for winning {$awardName}",
            'from_team_id' => $winnerId,
            'to_team_id' => $winnerId,
            'status' => 'extension',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    private function updateAllTeamStreaks()
    {
        // Fetch all games from the earliest to the latest
        $games = \DB::table('schedule_view')
            ->where('status', 2)
            ->orderBy('id', 'asc') // Order by id to process games chronologically
            ->get();

        if ($games->isEmpty()) {
            return; // No games to process
        }

        // Initialize an array to store streak information for each team
        $teamStreaks = [];

        // Iterate over each game to calculate streaks
        foreach ($games as $game) {
            // Home team processing
            $this->processGameStreak($teamStreaks, $game->home_id, $game->home_score, $game->away_score, $game->id);

            // Away team processing
            $this->processGameStreak($teamStreaks, $game->away_id, $game->away_score, $game->home_score, $game->id);
        }

        // Update the streak table for each team
        foreach ($teamStreaks as $teamId => $streak) {
            // Fetch the existing streak record for the team
            $streakRecord = \DB::table('streak')->where('team_id', $teamId)->first();

            if ($streakRecord) {
                // Update the best streak if the current one is greater
                if ($streak['best_winning_streak'] > $streakRecord->best_winning_streak) {
                    \DB::table('streak')->where('team_id', $teamId)->update([
                        'best_winning_streak' => $streak['best_winning_streak'],
                        'best_winning_streak_start_id' => $streak['best_winning_streak_start_id'],
                        'best_winning_streak_end_id' => $streak['best_winning_streak_end_id'],
                    ]);
                }
                if ($streak['best_losing_streak'] > $streakRecord->best_losing_streak) {
                    \DB::table('streak')->where('team_id', $teamId)->update([
                        'best_losing_streak' => $streak['best_losing_streak'],
                        'best_losing_streak_start_id' => $streak['best_losing_streak_start_id'],
                        'best_losing_streak_end_id' => $streak['best_losing_streak_end_id'],
                    ]);
                }
            } else {
                // Insert a new record if none exists for the team
                \DB::table('streak')->insert([
                    'team_id' => $teamId,
                    'best_winning_streak' => $streak['best_winning_streak'],
                    'best_losing_streak' => $streak['best_losing_streak'],
                    'best_winning_streak_start_id' => $streak['best_winning_streak_start_id'],
                    'best_winning_streak_end_id' => $streak['best_winning_streak_end_id'],
                    'best_losing_streak_start_id' => $streak['best_losing_streak_start_id'],
                    'best_losing_streak_end_id' => $streak['best_losing_streak_end_id'],
                ]);
            }
        }
    }

    // Modify processGameStreak to track start and end game IDs
    private function processGameStreak(&$teamStreaks, $teamId, $teamScore, $opponentScore, $gameId)
    {
        // Initialize streaks for the team if not already set
        if (!isset($teamStreaks[$teamId])) {
            $teamStreaks[$teamId] = [
                'current_streak' => 0,
                'is_winning_streak' => null,
                'best_winning_streak' => 0,
                'best_losing_streak' => 0,
                'best_winning_streak_start_id' => 0,
                'best_winning_streak_end_id' => 0,
                'best_losing_streak_start_id' => 0,
                'best_losing_streak_end_id' => 0,
            ];
        }

        $streak = &$teamStreaks[$teamId]; // Reference to the team's streak data

        // Determine if the game is a win or loss
        $isWin = $teamScore > $opponentScore;

        if ($isWin) {
            if ($streak['is_winning_streak'] === false) {
                // Streak direction changed from losing to winning
                $streak['current_streak'] = 1;
                $streak['best_winning_streak_start_id'] = $gameId; // Start of new winning streak
                $streak['best_losing_streak_start_id'] = 0; // Reset losing streak
                $streak['best_losing_streak_end_id'] = 0; // Reset losing streak
            } else {
                // Continue winning streak
                $streak['current_streak']++;
            }
            $streak['is_winning_streak'] = true;
            $streak['best_winning_streak'] = max($streak['best_winning_streak'], $streak['current_streak']);
            $streak['best_winning_streak_end_id'] = $gameId; // Update end of winning streak
        } else {
            if ($streak['is_winning_streak'] === true) {
                // Streak direction changed from winning to losing
                $streak['current_streak'] = 1;
                $streak['best_losing_streak_start_id'] = $gameId; // Start of new losing streak
                $streak['best_winning_streak_end_id'] = 0; // Reset winning streak
            } else {
                // Continue losing streak
                $streak['current_streak']++;
            }
            $streak['is_winning_streak'] = false;
            $streak['best_losing_streak'] = max($streak['best_losing_streak'], $streak['current_streak']);
            $streak['best_losing_streak_end_id'] = $gameId; // Update end of losing streak
        }
    }

    private function updateHeadToHeadResults($gameId)
    {
        // Fetch the game details from the schedules table
        $game = DB::table('schedules')
            ->where('id', $gameId)
            ->where('status', 2) // Ensure the game is completed
            ->first();

        if (!$game) {
            return response()->json([
                'error' => 'Game not found or not completed for game_id: ' . $gameId
            ], 404); // Game not found or not completed
        }

        // Determine the outcome of the game
        $teamWins = $game->home_score > $game->away_score ? 1 : 0;
        $opponentWins = $game->away_score > $game->home_score ? 1 : 0;
        $draws = $game->home_score == $game->away_score ? 1 : 0;

        // Update for the team's perspective (home vs away)
        $this->updateHeadToHeadMatchup($game->home_id, $game->away_id, $teamWins, $opponentWins, $draws);

        // Update for the opponent's perspective (away vs home)
        $this->updateHeadToHeadMatchup($game->away_id, $game->home_id, $opponentWins, $teamWins, $draws);

        return response()->json([
            'message' => 'Successfully updated head-to-head matchups for game_id: ' . $gameId
        ], 200); // Success
    }

    private function updateHeadToHeadMatchup($teamId, $opponentId, $teamWins, $opponentWins, $draws)
    {
        try {
            // Check if this matchup already exists in the head_to_head table
            $matchup = DB::table('head_to_head')
                ->where('team_id', $teamId)
                ->where('opponent_id', $opponentId)
                ->first();

            if ($matchup) {
                // If matchup exists, update the match count and win/loss records
                DB::table('head_to_head')
                    ->where('team_id', $teamId)
                    ->where('opponent_id', $opponentId)
                    ->update([
                        'wins' => $matchup->wins + $teamWins,
                        'losses' => $matchup->losses + $opponentWins,
                        'draws' => $matchup->draws + $draws,
                    ]);
            } else {
                // If matchup does not exist, insert a new record
                DB::table('head_to_head')
                    ->insert([
                        'team_id' => $teamId,
                        'opponent_id' => $opponentId,
                        'wins' => $teamWins,
                        'losses' => $opponentWins,
                        'draws' => $draws,
                    ]);
            }

            // Return true if successful
            return true;
        } catch (\Exception $e) {
            // Log the error message
            \Log::error('Error updating head-to-head matchup for team_id ' . $teamId . ' vs opponent_id ' . $opponentId . ': ' . $e->getMessage());

            // Return a structured error response
            return response()->json([
                'error' => 'Error updating head-to-head matchup: ' . $e->getMessage()
            ], 500); // Internal server error
        }
    }

    private function updatePlayerPlayoffAppearance($playerId, $gameData)
    {
        if (!$playerId || !$gameData) {
            return;
        }

        $gameId = $gameData->game_id;
        $seasonId = $gameData->season_id;
        $round = $gameData->round;
        $homeTeamId = $gameData->home_team_id;
        $awayTeamId = $gameData->away_team_id;
        $winningTeamId = ($gameData->home_score > $gameData->away_score) ? $homeTeamId : $awayTeamId;

        // Get the player's team for this game
        $playerTeamId = DB::table('player_game_stats')
            ->where('player_id', $playerId)
            ->where('game_id', $gameId)
            ->value('team_id');

        // Check if the player has a record in the appearances table
        $exists = DB::table('player_playoff_appearances')
            ->where('player_id', $playerId)
            ->exists();

        // Define appearance columns based on round
        $roundColumn = $this->getRoundColumn($round);

        if ($exists) {
            // Update existing record: increment appearances
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->update([
                    'total_playoff_appearances' => DB::raw('total_playoff_appearances + 1'),
                    $roundColumn => DB::raw("$roundColumn + 1"),
                    'seasons_played_in_playoffs' => DB::raw("seasons_played_in_playoffs + IF(NOT FIND_IN_SET($seasonId, seasons_played_in_playoffs), 1, 0)"),
                    'total_seasons_played' => DB::raw("total_seasons_played + IF(NOT FIND_IN_SET($seasonId, total_seasons_played), 1, 0)"),
                    'championships_won' => DB::raw("championships_won + IF($playerTeamId = $winningTeamId AND '$round' = 'finals', 1, 0)")
                ]);
        } else {
            // Insert new record
            DB::table('player_playoff_appearances')->insert([
                'player_id' => $playerId,
                'total_playoff_appearances' => 1,
                $roundColumn => 1,
                'seasons_played_in_playoffs' => 1,
                'total_seasons_played' => 1,
                'championships_won' => ($playerTeamId == $winningTeamId && $round == 'finals') ? 1 : 0
            ]);
        }
    }

    // Helper function to get the round column based on the round name
    private function getRoundColumn($round)
    {
        $roundMapping = [
            'play_ins_elims_round_1' => 'play_ins_elims_round_1_appearances',
            'play_ins_elims_round_2' => 'play_ins_elims_round_2_appearances',
            'play_ins_finals' => 'play_ins_finals_appearances',
            'round_of_32' => 'round_of_32_appearances',
            'round_of_16' => 'round_of_16_appearances',
            'quarter_finals' => 'quarter_finals_appearances',
            'semi_finals' => 'semi_finals_appearances',
            'interconference_semi_finals' => 'interconference_semi_finals_appearances',
            'finals' => 'finals_appearances',
        ];

        return $roundMapping[$round] ?? null; // Return the column name based on the round
    }
    public function updateRolesForAllTeams()
    {
        DB::beginTransaction();
    
        try {
            // Get all the team IDs
            $teamIds = DB::table('teams')->pluck('id'); // Assuming your teams table is 'teams'
    
            $round = 30;
            $update = [];
    
            foreach ($teamIds as $teamId) {
                $updateRoles = $this->updateTeamRolesBasedOnStatsV2($teamId, $round);  
                $update[$teamId] = $updateRoles; // Store the result for each team
            }
    
            DB::commit(); // ✅ Ensure the transaction commits
    
            return response()->json([
                'update' => $update, // ✅ Return response after committing
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating roles for all teams: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update roles for all teams',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    //relaksdlkajsdlkajsdlk
     private function updateTeamRolesBasedOnStats($teamId, $round)
    {
        if (!$teamId) {
            return false;
        }
    
        DB::beginTransaction();
        try {
            $seasonId = get_current_season_id();
    
            // Get all active, healthy players on the team
            $players = DB::table('player_season_stats')
                ->join('players', 'player_season_stats.player_id', '=', 'players.id')
                ->where('player_season_stats.season_id', $seasonId)
                ->where('players.contract_years', '>', 0)
                ->where('players.is_injured', false) // ✅ exclude injured players
                ->where('players.team_id', $teamId)
                ->selectRaw('
                    players.id as player_id,
                    players.role,
                    players.position,
                    SUM(player_season_stats.eff) as total_eff
                ')
                ->groupBy('players.id', 'players.role', 'players.position')
                ->orderByDesc('total_eff')
                ->get();
    
            // Step 1: Select one player per position for the starting five
            $positions = ['PG', 'SG', 'SF', 'PF', 'C'];
            $startingFive = [];
            $usedPlayerIds = [];
    
            foreach ($positions as $neededPosition) {
                foreach ($players as $player) {
                    if (in_array($player->player_id, $usedPlayerIds)) continue;
    
                    $playerPositions = explode('/', strtoupper($player->position));
    
                    if (in_array($neededPosition, $playerPositions)) {
                        $startingFive[] = $player;
                        $usedPlayerIds[] = $player->player_id;
                        break;
                    }
                }
            }
    
            // Fill in extra players for starting five if not enough positions matched
            foreach ($players as $player) {
                if (count($startingFive) >= 5) break;
                if (!in_array($player->player_id, $usedPlayerIds)) {
                    $startingFive[] = $player;
                    $usedPlayerIds[] = $player->player_id;
                }
            }
    
            // Step 2: Assign roles to the starting five
            $newRoles = [];
            $roleOrder = ['star player', 'all star', 'all star', 'starter', 'starter'];
            foreach ($startingFive as $i => $player) {
                $newRoles[$player->player_id] = $roleOrder[$i];
            }
    
            // Step 3: Next 5 become role players
            $rolePlayerCount = 0;
            foreach ($players as $player) {
                if (in_array($player->player_id, $usedPlayerIds)) continue;
                if ($rolePlayerCount >= 5) break;
    
                $newRoles[$player->player_id] = 'role player';
                $usedPlayerIds[] = $player->player_id;
                $rolePlayerCount++;
            }
    
            // Step 4: Remaining players are bench
            foreach ($players as $player) {
                if (!in_array($player->player_id, $usedPlayerIds)) {
                    $newRoles[$player->player_id] = 'bench';
                }
            }
    
            // Step 5: Update roles in DB
            foreach ($newRoles as $playerId => $newRole) {
                $currentRole = collect($players)->firstWhere('player_id', $playerId)->role;
    
                if ($currentRole !== $newRole) {
                    $roleStatus = ($newRole == 'star player') ? 'star player change' : 'role change';
                    $roundName = is_numeric($round) ? "Round $round" : "Playoffs";

                    DB::table('transactions')->insert([
                        'player_id' => $playerId,
                        'season_id' => $seasonId,
                        'details' => "Has moved from $currentRole to $newRole for the upcoming games. $roundName",
                        'from_team_id' => $teamId,
                        'to_team_id' => $teamId,
                        'status' => $roleStatus,
                    ]);
                }
    
                DB::table('players')->where('id', $playerId)->update(['role' => $newRole]);
    
                DB::table('player_season_stats')
                    ->where('player_id', $playerId)
                    ->where('season_id', $seasonId)
                    ->where('team_id', $teamId)
                    ->update(['role' => $newRole]);
            }
    
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updating team $teamId roles: " . $e->getMessage());
            return false;
        }
    
        return true;
    }

    private function updateSeasonStats($playerGameStats,$gameData,$isPlayoff)
    {
        if (empty($playerGameStats)) {
            throw new Exception("Player game stats are empty. Cannot update season stats.");
        }

        try {
            // Find max values for each category
            $maxPoints = max(array_column($playerGameStats, 'points'));
            $maxRebounds = max(array_column($playerGameStats, 'rebounds'));
            $maxAssists = max(array_column($playerGameStats, 'assists'));
            $maxSteals = max(array_column($playerGameStats, 'steals'));
            $maxBlocks = max(array_column($playerGameStats, 'blocks'));

            // Determine Best Player of the Game (BPG) using Efficiency Formula
            $bestPlayerId = null;
            $bestEfficiency = -INF;

            foreach ($playerGameStats as &$stats) {
                if (isset($stats['passing_rating'])) {
                    unset($stats['passing_rating']);
                }

                // Update Player Game Stats
                PlayerGameStats::updateOrCreate(
                    [
                        'player_id' => $stats['player_id'],
                        'game_id' => $stats['game_id'],
                        'season_id' => $stats['season_id'],
                        'team_id' => $stats['team_id'],
                    ],
                    $stats
                );

                if($isPlayoff){
                    $this->updatePlayerPlayoffAppearance($stats['player_id'], $gameData);
                }
                // Calculate efficiency (EFF) for Best Player of the Game
                $efficiency = ($stats['points'] + $stats['rebounds'] + $stats['assists'] + $stats['steals'] + $stats['blocks'])
                            - (($stats['fouls'] ?? 0) + ($stats['turnovers'] ?? 0)); // Assuming fg_missed exists

                if ($efficiency > $bestEfficiency) {
                    $bestEfficiency = $efficiency;
                    $bestPlayerId = $stats['player_id'];
                }

                // Assign Game Leader Titles
                $stats['points_game_leader'] = ($stats['points'] == $maxPoints) ? 1 : 0;
                $stats['rebounds_game_leader'] = ($stats['rebounds'] == $maxRebounds) ? 1 : 0;
                $stats['assists_game_leader'] = ($stats['assists'] == $maxAssists) ? 1 : 0;
                $stats['steals_game_leader'] = ($stats['steals'] == $maxSteals) ? 1 : 0;
                $stats['blocks_game_leader'] = ($stats['blocks'] == $maxBlocks) ? 1 : 0;
            }

            // Mark the Best Player of the Game (BPG)
            foreach ($playerGameStats as &$stats) {
                $stats['bpg_game_leader'] = ($stats['player_id'] == $bestPlayerId) ? 1 : 0;
            
                $storeStats = new AwardsController;
                $storeStats->storePlayerSeasonStats($stats['team_id'], $stats['player_id']);
            
                // Update Player Season Stats (Incrementing Leader Fields)
                DB::table('player_season_stats')->updateOrInsert(
                    ['player_id' => $stats['player_id'], 'season_id' => $stats['season_id'], 'team_id' => $stats['team_id']],
                    [
                        'points_game_leader' => DB::raw("points_game_leader + {$stats['points_game_leader']}"),
                        'rebounds_game_leader' => DB::raw("rebounds_game_leader + {$stats['rebounds_game_leader']}"),
                        'assists_game_leader' => DB::raw("assists_game_leader + {$stats['assists_game_leader']}"),
                        'steals_game_leader' => DB::raw("steals_game_leader + {$stats['steals_game_leader']}"),
                        'blocks_game_leader' => DB::raw("blocks_game_leader + {$stats['blocks_game_leader']}"),
                        'bpg_game_leader' => DB::raw("bpg_game_leader + {$stats['bpg_game_leader']}"),
                    ]
                );
            
                // Reduce hardship contract games for players on temporary contracts
                $player = DB::table('players')->where('id', $stats['player_id'])->first();
            
                if ($player && $player->hardship_contract > 0) {
                    $newHardshipGames = $player->hardship_contract - 1;
            
                    if ($newHardshipGames > 0) {
                        // Reduce remaining hardship games
                        DB::table('players')->where('id', $player->id)->update([
                            'hardship_contract' => $newHardshipGames
                        ]);
                    } else {
                        // Hardship contract expired -> Release player back to free agency
                        DB::table('players')->where('id', $player->id)->update([
                            'team_id' => 0, // Free agent pool
                            'contract_years' => 0, // Reset contract
                            'hardship_contract' => 0 // Clear hardship flag
                        ]);
            
                        // Log transaction
                        DB::table('transactions')->insert([
                            'player_id' => $player->id,
                            'season_id' => $stats['season_id'],
                            'details' => 'Released after hardship contract expired.',
                            'from_team_id' => $stats['team_id'],
                            'to_team_id' => 0, // Free agent pool
                            'status' => 'released-hardship'
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            // Log error for debugging
            // Log::error("Error updating season stats: " . $e->getMessage());

            // Optionally, throw the error again to stop execution
            throw new Exception("Failed to update season stats. Please check logs.".$e->getMessage());
        }
    }

    private function updateSeasonTeamChemistryBeforeGame($teamId)
    {
        $seasonId = get_current_season_id();

        $chemistryRow = DB::table('team_season_info')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->first();

        if (!$chemistryRow) {
            DB::table('team_season_info')->insert([
                'team_id' => $teamId,
                'season_id' => $seasonId,
                'chemistry' => 75, // default
            ]);
            $chemistry = 75;
        } else {
            $chemistry = $chemistryRow->chemistry;
        }

        $team = DB::table('teams')->where('id', $teamId)->first();
        if (!$team) return;

        $coachIQ = $team->coach_iq;

        // 🎯 Last game outcome
        $lastGame = DB::table('schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->where('season_id', $seasonId)
            ->where('status', 2)
            ->orderByDesc('created_at')
            ->first();

        if ($lastGame) {
            $wonLastGame = $lastGame->winner_id === $teamId;
            $chemistry += $wonLastGame ? 2 : -2;
        }

        // 🎯 Coach IQ
        if ($coachIQ >= 90) $chemistry += 1;
        elseif ($coachIQ <= 65) $chemistry -= 1;

        // 🎯 Leadership
        $leaders = DB::table('players')
            ->where('team_id', $teamId)
            ->orderByDesc('leadership_rating')
            ->limit(3)
            ->pluck('leadership_rating');

        if ($leaders->isNotEmpty()) {
            $avgLeadership = $leaders->avg();
            if ($avgLeadership >= 85) $chemistry += 2;
            elseif ($avgLeadership <= 60) $chemistry -= 2;
        }

        // 🎯 Season win percentage
        $seasonGames = DB::table('schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->where('season_id', $seasonId)
            ->where('status', 2)
            ->get();

        $totalGames = $seasonGames->count();
        $wins = $seasonGames->filter(fn($g) => $g->winner_id === $teamId)->count();

        if ($totalGames >= 5) {
            $winRate = $wins / $totalGames;
            if ($winRate >= 0.7) $chemistry += 2;
            elseif ($winRate <= 0.3) $chemistry -= 2;
        }

        // 🎯 Morale
        $moraleAvg = DB::table('players')
            ->where('team_id', $teamId)
            ->avg('morale');

        if (!is_null($moraleAvg)) {
            if ($moraleAvg >= 85) $chemistry += 2;
            elseif ($moraleAvg <= 60) $chemistry -= 2;
        }

        // 🧼 Clamp
        $chemistry = max(50, min(100, round($chemistry)));

        // ✅ Update
        DB::table('team_season_info')
            ->updateOrInsert(
                ['team_id' => $teamId, 'season_id' => $seasonId],
                ['chemistry' => $chemistry]
            );
    }

    private function updatePlayerMoraleBasedOnStats($teamId,$winnerId)
    {
        $seasonId = get_current_season_id();
        $wonGame = ($teamId == $winnerId);

        $players = DB::table('players')->where('team_id', $teamId)->get();
        $chemistry = DB::table('team_season_info')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->value('chemistry') ?? 75;

        foreach ($players as $player) {
            // Fetch the most recent game stats for the player
            $gameStats = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $seasonId)
                ->orderByDesc('created_at')
                ->first(); // Assuming `created_at` is a valid column for ordering by game

            if (!$gameStats) continue;

            // Calculate initial morale (base)
            $morale = $player->morale ?? 75;
            $role = $player->role ?? 'bench'; // Default role

            // 🎯 1. Game result impact
            $morale += $wonGame ? 2 : -2;

            // 🎯 2. Performance-based morale adjustment (efficiency or scoring impact)
            $efficiency = $gameStats->eff ?? 0; // Assuming eff (efficiency) is stored in the stats table
            if ($efficiency > 20) {
                $morale += 2; // Good performance boost
            } elseif ($efficiency < 5) {
                $morale -= 2; // Poor performance drop
            }

            // 🎯 3. Minutes played impact (role vs minutes)
            $expectedMin = match ($role) {
                'star' => 32,
                'all star' => 28,
                'starter' => 24,
                'bench' => 10,
                default => 5,
            };

            if ($gameStats->minutes < $expectedMin - 5) $morale -= 2;
            elseif ($gameStats->minutes > $expectedMin + 5) $morale += 1;

            // 🎯 4. Chemistry impact
            if ($chemistry < 60) $morale -= 1;
            elseif ($chemistry >= 85) $morale += 1;

            // 🎯 5. Clamp between 50 and 100
            $morale = max(50, min(100, round($morale)));

            // ✅ Update player morale
            DB::table('players')
                ->where('id', $player->id)
                ->update(['morale' => $morale]);
        }
    }

    // Add this helper method to get the last round number
    private function getLastRoundNumber()
    {
        return DB::table('schedules')
            ->where('season_id', get_current_season_id())
            ->whereNotIn('round', config('playoffs'))
            ->where(function($query) {
                $query->whereRaw('round REGEXP \'^[0-9]+$\'')  // Only get numeric rounds
                      ->orWhereRaw('CAST(round AS UNSIGNED) > 0');  // Ensure it can be cast to number
            })
            ->max(DB::raw('CAST(round AS UNSIGNED)'));  // Convert to number before finding max
    }

}
