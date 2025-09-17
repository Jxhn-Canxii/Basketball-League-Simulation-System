<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 0); // Unlimited execution time

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
use Illuminate\Support\Carbon;

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
        // DB::beginTransaction(); // Start transaction

        // try {
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
                'schedules.winner_id',
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
        $homeTeamPlayers = $this->getActivePlayersSorted($gameData->home_team_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->getActivePlayersSorted($gameData->away_team_id, $rolePriority, $gameData->round);

        // Initialize arrays to hold player game stats and minutes
        $playerGameStats = [];

        // Distribute minutes to players considering injury status
        $homeMinutes = $this->distributeMinutes($homeTeamPlayers, $totalMinutes, $request->schedule_id);
        $awayMinutes = $this->distributeMinutes($awayTeamPlayers, $totalMinutes, $request->schedule_id);

        $homeChemistry = $this->getTeamChemistry($currentSeasonId, $gameData->home_team_id);
        $awayChemistry = $this->getTeamChemistry($currentSeasonId, $gameData->away_team_id);
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

            $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls = $this->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $homeChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points = $this->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals = $this->calculateSteals($player, $minutes, $performanceFactor, $fouls);

            $playerGameStats[] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => $player->is_injured,
                'role' => $player->role,
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

            $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls = $this->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $awayChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points = $this->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals = $this->calculateSteals($player, $minutes, $performanceFactor, $fouls);

            $playerGameStats[] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => $player->is_injured,
                'role' => $player->role,
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
        $this->updateSeasonStats($playerGameStats, $gameData, true);

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
            ], 200);
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

        // check if round games is simulated
        $isRoundsSimulatedForSeason = $this->isRoundSimulated($currentSeasonId, $gameData->round);

        $this->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
        $this->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

        $this->updatePlayerMoraleBasedOnStats($gameData->home_team_id, $gameData->winner_id);
        $this->updatePlayerMoraleBasedOnStats($gameData->away_team_id, $gameData->winner_id);

        $this->updateInjuryAndWaiving($gameData->home_team_id);
        $this->updateInjuryAndWaiving($gameData->away_team_id);

        $this->updateTeamStreaks($gameData->id);
        $this->updateHeadToHeadResults($gameData->id);
        // $this->createGameNewsFromGame($gameData->id);

        $this->updatePlayoffAppearancesForGame($gameData);

        if ($isRoundsSimulatedForSeason) {
            $this->updateInjuryFreeAgents();
        }

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
        // } catch (\Exception $e) {
        //     DB::rollBack(); // Rollback transaction on error

        //     \Log::error('Failed to update playoffs', ['exception' => $e]);

        //     return response()->json([
        //         'error' => true,
        //         'message' => 'Failed to update playoffs.',
        //         'error_message' => $e->getMessage(), // Display the exception message
        //     ], 500);
        // }
    }

    public function simulatePlayoffSeries(Request $request)
    {
        // DB::beginTransaction(); // Start transaction

        // try {
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
                'schedules.series_id',
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
                'schedules.winner_id',
                'schedules.status'
            )
            ->findOrFail($request->schedule_id);

        $isSeriesFinished = DB::table('playoff_series')
            ->where('series_id', $gameData->series_id)
            ->where('status', 2)
            ->first();

        if ($isSeriesFinished) {
            return response()->json([
                'error' => true,
                'message' => 'This playoff series is already finished.'
            ], 200);
        }

        // Check previous game in same series
        if ($gameData->game_number > 1) {
            $prevGame = DB::table('schedules')
                ->where('series_id', $gameData->series_id)
                ->where('game_number', $gameData->game_number - 1)
                ->first();

            if ($prevGame && $prevGame->status != 2) {
                return response()->json([
                    'error' => true,
                    'message' => 'Previous game in this series is not yet finished.'
                ], 200);
            }
        }

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
        $homeTeamPlayers = $this->getActivePlayersSorted($gameData->home_team_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->getActivePlayersSorted($gameData->away_team_id, $rolePriority, $gameData->round);

        // Initialize arrays to hold player game stats and minutes
        $playerGameStats = [];

        // Distribute minutes to players considering injury status
        $homeMinutes = $this->distributeMinutes($homeTeamPlayers, $totalMinutes, $request->schedule_id);
        $awayMinutes = $this->distributeMinutes($awayTeamPlayers, $totalMinutes, $request->schedule_id);

        $homeChemistry = $this->getTeamChemistry($currentSeasonId, $gameData->home_team_id);
        $awayChemistry = $this->getTeamChemistry($currentSeasonId, $gameData->away_team_id);
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

            $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls = $this->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $homeChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points = $this->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals = $this->calculateSteals($player, $minutes, $performanceFactor, $fouls);

            $playerGameStats[] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => $player->is_injured,
                'role' => $player->role,
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

            $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls = $this->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $awayChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points = $this->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals = $this->calculateSteals($player, $minutes, $performanceFactor, $fouls);

            $playerGameStats[] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => $player->is_injured,
                'role' => $player->role,
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
        $this->updateSeasonStats($playerGameStats, $gameData, true);

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
            ], 400);
        }

        // Update the scores
        $gameData->home_score = $homeScore;
        $gameData->away_score = $awayScore;

        $gameData->winner_id = $homeScore > $awayScore
            ? $gameData->home_team_id
            : $gameData->away_team_id;

        $gameData->status = 2; // Marking the game as completed

        $gameData->save();
        // Update playoff_series
        $series = DB::table('playoff_series')
            ->where('series_id', $gameData->series_id)
            ->first();

        if (!$series) {
            return response()->json([
                'message' => 'Series not found for the given schedule!',
            ], 404);
        }

        // Fetch updated series data
        $series = DB::table('playoff_series')
            ->select(
                'playoff_series.id',
                'playoff_series.series_id',
                'playoff_series.season_id',
                'conferences.name as conference',
                'playoff_series.round',
                'playoff_series.home_team_id',
                'playoff_series.away_team_id',
                'playoff_series.series_length as best_of',
                'playoff_series.home_wins',
                'playoff_series.away_wins',
                DB::raw('CASE WHEN playoff_series.status = 2 THEN 1 ELSE 0 END as completed'),
                'playoff_series.winner_team_id',
                'playoff_series.loser_team_id',
                'playoff_series.created_at',
                'playoff_series.updated_at'
            )
            ->leftJoin('conferences', 'playoff_series.conference_id', '=', 'conferences.id')
            ->where('playoff_series.series_id', $gameData->series_id)
            ->first();


        $teamIds = collect([$series->home_team_id, $series->away_team_id])->unique();
        $standingsTable = ($gameData->season_id == $currentSeasonId) ? 'standings_view' : 'standings_snapshots';
        $standingsData = DB::table($standingsTable)
            ->whereIn('team_id', $teamIds)
            ->where('season_id', $gameData->season_id)
            ->get()
            ->keyBy('team_id');

        $homeTeamName = $standingsData[$series->home_team_id]->name ?? DB::table('teams')->where('id', $series->home_team_id)->value('name');
        $awayTeamName = $standingsData[$series->away_team_id]->name ?? DB::table('teams')->where('id', $series->away_team_id)->value('name');
        // Determine series lead or result
        $seriesLead = '';
        $winnerTeamId = 0;
        if ($series->completed) {
            $winnerName = $series->winner_team_id == $series->home_team_id ? $homeTeamName : $awayTeamName;
            $loserName = $series->winner_team_id == $series->away_team_id ? $awayTeamName : $homeTeamName;

            $winnerTeamId = $series->winner_team_id == $series->home_team_id ? $series->home_team_id : $series->away_team_id;
            $loserTeamId = $series->winner_team_id == $series->away_team_id ? $series->away_team_id : $series->home_team_id;

            $winnerTeamSeriesScore = $series->winner_team_id == $series->home_team_id ? $series->home_wins : $series->away_wins;
            $loserTeamISeriesScore = $series->winner_team_id == $series->away_team_id ? $series->away_wins : $series->home_wins;

            $seriesLead = "{$winnerName} Wins {$series->home_wins}-{$series->away_wins}";
        } else {
            if ($series->home_wins == $series->away_wins) {
                $seriesLead = "Series Tied {$series->home_wins}-{$series->away_wins}";
            } else {
                $leaderName = $series->home_wins > $series->away_wins ? $homeTeamName : $awayTeamName;
                $leadWins = max($series->home_wins, $series->away_wins);
                $trailWins = min($series->home_wins, $series->away_wins);
                $seriesLead = "{$leaderName} Leads {$leadWins}-{$trailWins}";
            }
        }

        // Save game data and update other tables in a transaction
        $winnerId = $gameData->winner_id;
        DB::transaction(function () use ($gameData, $playerGameStats, $currentSeasonId, $winnerId) {
            $this->updateSeasonStats($playerGameStats, $gameData, true);
            $this->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
            $this->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);
            $this->updatePlayerMoraleBasedOnStats($gameData->home_team_id,  $winnerId);
            $this->updatePlayerMoraleBasedOnStats($gameData->away_team_id,  $winnerId);
            $this->updateInjuryAndWaiving($gameData->home_team_id);
            $this->updateInjuryAndWaiving($gameData->away_team_id);
            $this->updateTeamStreaks($gameData->id);
            $this->updateHeadToHeadResults($gameData->id);
            // $this->createGameNewsFromGame($gameData->id);

            $isRoundsSimulatedForSeason = $this->isRoundSimulated($currentSeasonId,  $gameData->round);
            $isRoundSeriesSimulatedForSeason = $this->isRoundSeriesSimulated($currentSeasonId,  $gameData->round);
            if (!$isRoundSeriesSimulatedForSeason) {
                $this->updateSeriesAndSchedule($gameData, $winnerId);
            }
            if ($isRoundsSimulatedForSeason) {
                $this->updateInjuryFreeAgents();
            }

            if ($gameData->round === 'semi_finals') {
                $this->updateSeriesConferenceChampions($gameData);
            }
            if ($gameData->round === 'finals') {
                $this->updateSeriesFinalsWinner($gameData);
            }

            $this->updatePlayoffSeriesAppearancesForGame($gameData);
        });

        // Format series response
        $seriesResponse = [
            'id' => $series->id,
            'game_id' => $gameData->game_id,
            'series_id' => $series->series_id,
            'season_id' => $series->season_id,
            'conference' => $series->conference ?? 'Interconference',
            'round' => $series->round,
            'best_of' => $series->best_of,
            'home_team' => [
                'id' => $series->home_team_id,
                'name' => $homeTeamName,
                'wins' => $series->home_wins,
                'conference' => $standingsData[$series->home_team_id]->conference_name ?? null,
                'conference_rank' => $standingsData[$series->home_team_id]->conference_rank ?? null,
                'overall_rank' => $standingsData[$series->home_team_id]->overall_rank ?? null,
                'primary_color' => $standingsData[$series->home_team_id]->primary_color ?? '00000',
                'secondary_color' => $standingsData[$series->home_team_id]->secondary_color ?? '00000',
            ],
            'away_team' => [
                'id' => $series->away_team_id,
                'name' => $awayTeamName,
                'wins' => $series->away_wins,
                'conference' => $standingsData[$series->away_team_id]->conference_name ?? null,
                'conference_rank' => $standingsData[$series->away_team_id]->conference_rank ?? null,
                'overall_rank' => $standingsData[$series->away_team_id]->overall_rank ?? null,
                'primary_color' => $standingsData[$series->away_team_id]->primary_color ?? '00000',
                'secondary_color' => $standingsData[$series->away_team_id]->secondary_color ?? '00000',
            ],
            'series_lead' => $seriesLead,
            'completed' => $series->completed,
            'winner_id' => $series->winner_team_id,
            'loser_id' => $series->loser_team_id,
            'created_at' => $series->created_at,
            'updated_at' => $series->updated_at,
        ];

        // Return the simulation result
        return response()->json([
            'message' => 'Game simulated successfully',
            'series' => $seriesResponse
        ]);
        // } catch (\Exception $e) {
        //     DB::rollBack(); // Rollback transaction on error

        //     \Log::error('Failed to update playoffs', ['exception' => $e]);

        //     return response()->json([
        //         'error' => true,
        //         'message' => 'Failed to update playoffs.',
        //         'error_message' => $e->getMessage(), // Display the exception message
        //     ], 500);
        // }
    }

    public function simulateRegular(Request $request)
    {
        // DB::beginTransaction(); // Start transaction

        // try {
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

        $storeStats = new AwardsController;

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
                'schedules.winner_id',
                'schedules.status'
            )
            ->findOrFail($request->schedule_id);

        if (!$gameData) {
            return response()->json([
                'message' => 'Error Fetching game data',
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
        $homeTeamPlayers = $this->getActivePlayersSorted($gameData->home_team_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->getActivePlayersSorted($gameData->away_team_id, $rolePriority, $gameData->round);


        $playerGameStats = [];
        $homeMinutes = $this->distributeMinutes($homeTeamPlayers, $totalMinutes, $request->schedule_id);
        $awayMinutes = $this->distributeMinutes($awayTeamPlayers, $totalMinutes, $request->schedule_id);

        $homeChemistry = $this->getTeamChemistry($currentSeasonId, $gameData->home_team_id);
        $awayChemistry = $this->getTeamChemistry($currentSeasonId, $gameData->away_team_id);
        // Simulate home team player stats with detailed shooting metrics
        foreach ($homeTeamPlayers as $player) {
            $minutes = (float) $homeMinutes[$player->id];
            if ($minutes === 0 || $player->is_injured) {
                $playerGameStats[] = $this->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->calculatePerformanceFactor($player);
            $defensiveImpact = $this->calculateDefensiveImpact($gameData->away_team_id);

            $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls = $this->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $homeChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points = $this->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals = $this->calculateSteals($player, $minutes, $performanceFactor, $fouls);

            $playerGameStats[] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => $player->is_injured,
                'role' => $player->role,
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

            $turnovers = $this->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls = $this->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats = $this->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $awayChemistry, true, false);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];


            $points = $this->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds = $this->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks = $this->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals = $this->calculateSteals($player, $minutes, $performanceFactor, $fouls);

            $playerGameStats[] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => $player->is_injured,
                'role' => $player->role,
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
        $this->updateSeasonStats($playerGameStats, $gameData, false);

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
            ], 200);
        }


        $gameData->home_score = $homeScore;
        $gameData->away_score = $awayScore;

        $gameData->winner_id = $homeScore > $awayScore
            ? $gameData->home_team_id
            : $gameData->away_team_id;

        $gameData->status = 2;
        $gameData->save();

        // Check if all rounds have been simulated for the season
        $allRoundsSimulatedForSeason =  $this->allRoundsSimulatedForSeason($currentSeasonId);

        // check if round games is simulated
        $isRoundsSimulatedForSeason = $this->isRoundSimulated($currentSeasonId,  $gameData->round);

        $this->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
        $this->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

        $this->updatePlayerMoraleBasedOnStats($gameData->home_team_id, $gameData->winner_id);
        $this->updatePlayerMoraleBasedOnStats($gameData->away_team_id, $gameData->winner_id);

        $this->updateInjuryAndWaiving($gameData->home_team_id);
        $this->updateInjuryAndWaiving($gameData->away_team_id);

        $this->updateTeamStreaks($gameData->id);
        $this->updateHeadToHeadResults($gameData->id);
        $this->createGameNewsFromGame($gameData->id);
        if ($isRoundsSimulatedForSeason) {
            $this->updateInjuryFreeAgents();
        }

        if ($allRoundsSimulatedForSeason) {
            // Update the season's status to 2
            $season = Seasons::find($currentSeasonId);
            if ($season) {
                $season->status = 2;
                $season->save();

                $this->saveStandingsSnapshot();
                $this->updatePlayoffQualifiedFlags();
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
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'message' => 'An error occurred: ' . $e->getMessage(),
        //     ], 500);
        // }
    }

    private function updatePlayoffQualifiedFlags()
    {
        $seasonId = get_current_season_id();

        // Step 1: Get all team IDs in the top 10 of their conference for this season
        $qualifiedTeamIds = DB::table('standings_view')
            ->where('season_id', $seasonId)
            ->where('conference_rank', '<=', 10)
            ->pluck('team_id')
            ->toArray();

        // Step 2: Set is_playoff_qualified = 1 for qualified teams
        DB::table('team_season_info')
            ->where('season_id', $seasonId)
            ->whereIn('team_id', $qualifiedTeamIds)
            ->update(['is_playoff_qualified' => 1]);

        // Step 3 (optional): Set is_playoff_qualified = 0 for all others
        DB::table('team_season_info')
            ->where('season_id', $seasonId)
            ->whereNotIn('team_id', $qualifiedTeamIds)
            ->update(['is_playoff_qualified' => 0]);
    }
    // Helper methods for stat calculation
    private function createInactivePlayerStats($player, $gameData, $seasonId)
    {
        return [
            'player_id' => $player->id,
            'game_id' => $gameData->game_id,
            'season_id' => $seasonId,
            'team_id' => $player->team_id,
            'is_injured' => $player->is_injured,
            'role' => $player->role,
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

    private function saveStandingsSnapshot()
    {
        try {
            $snapshots = DB::table('standings_view')
                ->select(
                    'team_id',
                    'team_name',
                    'team_city',
                    'team_acronym',
                    'primary_color',
                    'secondary_color',
                    'conference_id',
                    'conference_name',
                    'season_id',
                    'wins',
                    'losses',
                    'total_home_score',
                    'total_away_score',
                    'home_ppg',
                    'away_ppg',
                    'score_difference',
                    'conference_rank',
                    'overall_rank',
                    'is_defending_champion',
                    'chemistry',
                    'last_playoff_season_name',
                    'playoff_appearances',
                    'finals_appearances',
                    'conference_finals_appearances',
                    'conference_championships',
                    'championships',
                    'streak_status',
                    'last_5_games'
                )
                ->get();

            foreach ($snapshots as $snapshot) {
                DB::table('standings_snapshots')->updateOrInsert(
                    [
                        'team_id' => $snapshot->team_id,
                        'season_id' => $snapshot->season_id,
                    ],
                    (array) $snapshot
                );
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Standing Snapshot Error' . $e->getMessage(),
            ], 500);
        }
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
    /**
     * Fetches Coach IQ and Team Chemistry for a player's team.
     * 
     * @param int $teamId
     * @return array ['coach_iq' => int, 'chemistry' => int]
     */
    private function getTeamCoachAndChemistry(int $teamId): array
    {
        $teamSeasonInfo = DB::table('team_season_info')
            ->where('team_id', $teamId)
            ->select('coach_iq', 'chemistry')
            ->first();

        return [
            'coach_iq' => $teamSeasonInfo->coach_iq ?? 50, // Default if missing
            'chemistry' => $teamSeasonInfo->chemistry ?? 50,
        ];
    }

    private function calculateFoul(Player $player, int $minutes, float $performanceFactor, float $defensiveImpact): int
    {
        if ($minutes === 0) return 0;

        // 1. BASE FOUL RATE BY POSITION/ROLE (Primary driver)
        $positionRates = [
            'PG' => 0.03,  // Point guards foul least
            'SG' => 0.04,
            'SF' => 0.05,
            'PF' => 0.07,
            'C'  => 0.09   // Big men foul most
        ];

        // Handle multi-position roles (e.g., "PG/SG")
        $positions = explode('/', $player->role ?? $player->position ?? 'SF');
        $baseRate = collect($positions)
            ->map(fn($pos) => $positionRates[trim($pos)] ?? 0.05)
            ->average();

        // 2. PLAYER ATTRIBUTE MODIFIERS (Weighted contributions)
        $controlFactors = [
            'positive' => [
                'basketball_iq_rating' => 0.25,  // Smart players foul less
                'work_ethic_rating'    => 0.20,  // Disciplined players
                'stamina_rating'       => 0.15,  // Better conditioning
                'morale'               => 0.15,  // Happy players
                'leadership_rating'    => 0.10   // On-court decision making
            ],
            'negative' => [
                'bashing_factor'      => 0.30,  // Aggressive players foul more
                'fatigue'             => 0.25,  // Tired players
                'injury_prone_percentage' => 0.10 // Injury-prone players
            ]
        ];

        // Calculate control score (0-100 scale)
        $positiveScore = array_reduce(
            array_keys($controlFactors['positive']),
            fn($carry, $attr) => $carry + ($player->$attr * $controlFactors['positive'][$attr]),
            0
        );

        $negativeScore = array_reduce(
            array_keys($controlFactors['negative']),
            fn($carry, $attr) => $carry + ($player->$attr * $controlFactors['negative'][$attr]),
            0
        );

        $controlScore = ($positiveScore - $negativeScore) / 100;

        // 3. DYNAMIC MODIFIERS
        $defensiveAggression = ($player->defense_rating / 100) * ($defensiveImpact / 100);
        $fatiguePenalty = 1 + ($player->fatigue / 100); // 1.0-2.0 multiplier
        $rookiePenalty = $player->is_rookie ? 1.15 : 1.0; // Rookies foul 15% more

        // 4. FINAL FOUL CALCULATION
        $adjustedRate = $baseRate
            * (1 + $defensiveAggression)  // Aggressive defense
            * (1.5 - $controlScore)       // Control score impact
            * $fatiguePenalty             // Fatigue effect
            * $rookiePenalty;             // Rookie adjustment

        // 5. APPLY PERFORMANCE FACTOR & RANDOMNESS
        $fouls = round($minutes * $adjustedRate * $performanceFactor) + rand(-1, 1);

        // 6. SPECIAL CASES
        // Injured players foul more carelessly
        if ($player->is_injured) {
            $fouls += rand(0, 1);
        }

        // "Hack-a-Shaq" rule: Poor FT shooters get targeted
        if ($player->free_throw_rating < 50 && rand(1, 100) > 70) {
            $fouls += 1;
        }

        return min(max($fouls, 0), 6); // Clamp to 0-6 fouls
    }

    private function poissonRandomizer(float $lambda): int
    {
        // For small lambda (<30) use direct Knuth method
        if ($lambda <= 0) {
            return 0;
        }
        if ($lambda < 30.0) {
            $L = exp(-$lambda);
            $p = 1.0;
            $k = 0;
            while ($p > $L) {
                $k++;
                // mt_rand returns int — scale to (0,1]
                $p *= mt_rand() / mt_getrandmax();
            }
            return $k - 1;
        }

        // For large lambda use normal approximation (mu=lambda, sigma=sqrt(lambda))
        // sample standard normal using Box-Muller
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
        $sample = (int) round($lambda + sqrt($lambda) * $z);
        return max(0, $sample);
    }

    // Helper: binomial randomizer
    private function binomialRandomizer(int $n, float $p): int
    {
        if ($n <= 0 || $p <= 0.0) return 0;
        if ($p >= 1.0) return $n;

        $success = 0;
        for ($i = 0; $i < $n; $i++) {
            if (mt_rand() / mt_getrandmax() < $p) $success++;
        }
        return $success;
    }


    private function calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowsMade, $fouls)
    {
        // Base points
        $points = ($twoPointMade * 2) + ($threePointMade * 3) + $freeThrowsMade;

        // Foul trouble lowers aggressiveness (less shot attempts)
        $foulPenalty = max(0, 1 - ($fouls * 0.05));

        // Work ethic + stamina help players maintain scoring despite fouls
        $resilience = ($player->work_ethic_rating + $player->stamina_rating) / 200; // 0 - 1

        return max(round($points * ($foulPenalty + ($resilience * 0.1))), 0);
    }

    private function calculateRebounds(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        // Position weights tuned to NBA averages
        $positionWeights = [
            'C' => 0.35,
            'PF' => 0.30,
            'SF' => 0.20,
            'SG' => 0.15,
            'PG' => 0.10
        ];
        $positionFactor = $positionWeights[$player->position] ?? 0.20;

        // Base per-minute expected rebounds (ceiling keeps averages realistic)
        $reboundPerMinute = min(
            0.70,
            $positionFactor * (
                ($player->rebounding_rating * 0.65 +
                    $player->athleticism_rating * 0.25 +
                    $player->strength_rating * 0.10) / 100.0
            )
        );

        // Softer foul penalty (players still rebound with fouls)
        $foulPenalty = max(0.5, 1 - ($fouls * 0.08));

        // Expected (lambda) rebounds
        $expected = $reboundPerMinute * $minutes * $performanceFactor * $foulPenalty;

        // --- Monster spike gating (ultra-rare) ---
        // Example: base 0.0005 ~ 1 in 2000. Adjust as needed.
        $monsterChance = 0.0005;
        $spike = 0;
        if (mt_rand() / mt_getrandmax() < $monsterChance && $player->rebounding_rating >= 88 && in_array($player->position, ['C', 'PF'])) {
            // Add spike to expected before sampling (so stochastic)
            $expected += rand(10, 18);
        }

        //actual rebounds from Poisson(lambda = expected)
        $actual = $this->poissonRandomizer($expected);

        return (int) $actual;
    }


    private function calculateBlocks(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        // Position-based caps
        $positionCaps = ['C' => 0.40, 'PF' => 0.35, 'SF' => 0.25, 'SG' => 0.15, 'PG' => 0.10];

        $blocksPerMinute = min(
            $positionCaps[$player->position] ?? 0.20,
            ($player->blocks_rating * 0.6 +
                $player->athleticism_rating * 0.25 +
                $player->defense_rating * 0.15) / 250
        );

        // Stronger foul impact
        $foulPenalty = max(0.2, 1 - ($fouls * 0.15));

        $expected = round($blocksPerMinute * $minutes * $performanceFactor * $foulPenalty);

        //actual blocks from Poisson(lambda = expected)
        $actual = $this->poissonRandomizer($expected);

        return (int) $actual;
    }

    private function calculateSteals(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        // More conservative base rate
        $stealsPerMinute = min(
            0.30, // Absolute max
            ($player->steals_rating * 0.5 +
                $player->basketball_iq_rating * 0.3 +
                $player->athleticism_rating * 0.2) / 300
        );

        // Fouls hurt steals more
        $foulPenalty = max(0.4, 1 - ($fouls * 0.08));

        // Leadership reduces reckless steals
        $discipline = 1 - ($player->leadership_rating / 500);

        $expected = round($stealsPerMinute * $minutes * $performanceFactor * $foulPenalty * $discipline);

        //actual steals from Poisson(lambda = expected)
        $actual = $this->poissonRandomizer($expected);

        return (int) $actual;
    }

    // $this->calculateShotAttempts($player, $minutes, $defensiveImpact,$fouls, $turnovers,$homeChemistry, true, true);
    private function calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $chemistry = 50, $isClutchTime = false, $isHomeAdvantage)
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
        $homeAdvantageFactor = $isHomeAdvantage ? 1.05 : 1.0; // 5% boost if at home

        $baseAttempts = max(1, round($minutes * 0.8));
        $foulImpact = $fouls * 0.05;
        $turnoverImpact = $turnovers * 0.1;
        $adjustedBaseAttempts = max(0, $baseAttempts - ($foulImpact + $turnoverImpact));

        $attemptBias = rand(85, 115) / 100;
        $threePointWeight = $positionFactor['three_point'] * $attemptBias;
        $twoPointWeight = 1 - $threePointWeight;

        $totalFactor = $roleFactor * $fatigueFactor * $injuryFactor * $clutchBoost * $moraleFactor * $chemistryFactor * $homeAdvantageFactor;

        $rawAdjustedAttempts = $adjustedBaseAttempts * $totalFactor;

        $maxPointsPerMinute = 3.0;
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

        // $twoPointMade = $this->binomialRandomizer($adjustedTwoPointAttempts, min(0.75, $twoPointAccuracy));
        // $threePointMade = $this->binomialRandomizer($adjustedThreePointAttempts, min(0.55, $threePointAccuracy));
        // $freeThrowMade = $this->binomialRandomizer($adjustedFreeThrowAttempts, min(0.95, $freeThrowAccuracy));

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
        $now = now();
        $currentHour = $now->hour;

        // // Check if the time is between 6 PM (18) and 6 AM (6)
        // $isTimeRestricted = ($currentHour >= 18 || $currentHour < 6);

        $isTradeDeadline = $simulatedRounds >= ($totalRounds / 2) - 2 && $latestSeasonStatus == 1;

        if ($isTradeDeadline) {
            // Update the season status to indicate trade deadline
            DB::table('seasons')
                ->where('id', $seasonId)
                ->update(['status' => config('timeline.in_season_trade')]);

            $isTradeDeadline = false; // Reset after executing
        }

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
        $dnpPlayers = $sorted->filter(fn($p) => $p['is_injured']);

        $maxDNPs = 3;
        // Step 2: Fill remaining DNP slots, but protect star players and all-stars
        if ($dnpPlayers->count() < $maxDNPs) {
            $remainingSlots = $maxDNPs - $dnpPlayers->count();
            $additionalDNP = $sorted
                ->reject(
                    fn($p) =>
                    $dnpPlayers->contains('id', $p['id']) ||
                        $p['is_injured'] ||
                        $p['role'] === 'star player' ||
                        $p['role'] === 'all star'
                )
                ->sortBy([
                    ['per', 'asc'],
                    ['eff', 'asc'],
                ])
                ->take($remainingSlots);

            $dnpPlayers = $dnpPlayers->merge($additionalDNP);
        }

        // Ensure minimum of 8 players with minutes
        $rotation = $sorted->reject(fn($p) => $dnpPlayers->contains('id', $p['id']));

        if ($rotation->count() < 8) {
            $needed = 8 - $rotation->count();

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

        // First pass: Assign minimum minutes to star players and all-stars
        foreach ($rotation as $player) {
            if ($player['role'] === 'star player' || $player['role'] === 'all star') {
                $role = $player['role'];
                $range = $roleMinuteRanges[$role];
                $baseMinutes = rand($range[0], $range[1]);

                $positions = explode('/', $player['position']);
                foreach ($positions as $pos) {
                    if (($positionTargets[$pos] ?? 0) > 0) {
                        $assigned = min($baseMinutes, $positionTargets[$pos]);
                        $minutes[$player['id']] = $assigned;
                        $positionTargets[$pos] -= $assigned;
                        $assignedTotal += $assigned;
                        break;
                    }
                }
            }
        }

        // Second pass: Assign minutes to remaining players
        foreach ($rotation as $player) {
            if (!isset($minutes[$player['id']])) {
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
            }

            $this->fatigueRate($player, $minutes[$player['id']], $gameId);
        }

        // Step 4: Normalize to total minutes (usually 240)
        $difference = $totalMinutes - array_sum($minutes);

        if (abs($difference) > 0) {
            // Prioritize star players and all-stars when distributing remaining minutes
            $eligible = $rotation->sortBy(function ($p) use ($rolePriority) {
                $role = $p['role'];
                if ($role === 'star player' || $role === 'all star') {
                    return 0; // Highest priority
                }
                return $rolePriority[$role] ?? 5;
            })->values();

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

    public function handleInjuredPlayer($player, $seasonId, $seasonStatus)
    {
        try {
            // Check if player is already retired or inactive
            if (!$player->is_active) {
                \Log::info("Player {$player->name} is already inactive or retired. No action needed.");
                return;
            }

            // Track if retirement age was adjusted due to severe injury
            $retirementReason = 'reached retirement age';
            $injuryHistoryCount = 0;

            // Handle injury if player is injured
            if ($player->is_injured) {
                // Process injury recovery
                $deductionPerGame = 1;

                if ($player->injury_recovery_games > 0) {
                    // Decrement injury recovery games
                    DB::table('players')->where('id', $player->id)->decrement('injury_recovery_games', $deductionPerGame);
                    $updatedRecoveryGames = DB::table('players')->where('id', $player->id)->value('injury_recovery_games');
                    \Log::info("Decremented recovery games for {$player->name}. Remaining: {$updatedRecoveryGames}");
                } else {
                    $updatedRecoveryGames = $player->injury_recovery_games;
                    \Log::info("No recovery games left for {$player->name}.");
                }

                // Load injury config
                $injuries = config('injuries');
                $currentInjury = $player->injury_type;

                // Define severe injury criteria
                $severeInjuryThreshold = [
                    'recovery_games' => 15, // Severe if recovery takes 15+ games
                    'performance_impact' => 0.3, // Severe if performance impact is 30% or less
                ];
                $injuryHistoryThreshold = 5; // Threshold for "too many" injuries
                $retirementAgeReduction = 2; // Years to reduce retirement age
                $minimumRetirementAge = max($player->age, 30); // Minimum retirement age

                // Non-injury factors that shouldn't affect retirement age
                $nonInjuryFactors = [
                    'resting',
                    'suspension',
                    'personal_reason',
                    'logistics_issue',
                    'family_emergency',
                    'contract_dispute',
                    'mental_health',
                    'player_protest',
                    'travel_fatigue'
                ];

                // Check if the current injury is severe and not a non-injury factor
                $isSevereInjury = false;
                if (array_key_exists($currentInjury, $injuries) && !in_array($currentInjury, $nonInjuryFactors)) {
                    $injuryDetails = $injuries[$currentInjury];
                    if (
                        $injuryDetails['recovery_games'] >= $severeInjuryThreshold['recovery_games'] ||
                        $injuryDetails['performance_impact'] <= $severeInjuryThreshold['performance_impact']
                    ) {
                        $isSevereInjury = true;
                    }
                }

                // Get injury history count
                $injuryHistoryCount = DB::table('injury_histories')
                    ->where('player_id', $player->id)
                    ->count();

                // Adjust retirement age if injury is severe and injury history is high
                if ($isSevereInjury && $injuryHistoryCount > $injuryHistoryThreshold) {
                    $newRetirementAge = max($player->retirement_age - $retirementAgeReduction, $minimumRetirementAge);
                    if ($newRetirementAge < $player->retirement_age) {
                        DB::table('players')->where('id', $player->id)->update([
                            'retirement_age' => $newRetirementAge,
                            'updated_at' => now(),
                        ]);
                        \Log::info("Adjusted retirement age for {$player->name} from {$player->retirement_age} to {$newRetirementAge} due to severe injury ({$currentInjury}) and high injury history ({$injuryHistoryCount} injuries).");
                        // Update player object and retirement reason
                        $player->retirement_age = $newRetirementAge;
                        $retirementReason = "severe injury history ({$injuryHistoryCount} injuries)";
                    }
                }

                // If player fully recovered
                if ($updatedRecoveryGames <= 0) {
                    DB::table('players')->where('id', $player->id)->update([
                        'is_injured' => false,
                        'injury_type' => null,
                    ]);

                    // Update injury history recovery date
                    DB::table('injury_histories')
                        ->where('player_id', $player->id)
                        ->whereNull('recovery_date')
                        ->latest()
                        ->update([
                            'recovery_date' => now(),
                            'updated_at' => now(),
                        ]);

                    \Log::info("Player {$player->name} has fully recovered from injury.");
                }
            }

            // Check for forced retirement
            if ($player->age >= $player->retirement_age) {
                // Get team name for transaction log
                $teamName = $player->team_id
                    ? DB::table('teams')->where('id', $player->team_id)->value('name') ?? 'Unknown Team'
                    : 'No Team';

                // Create detailed transaction message
                $details = "{$player->name} retired from the league at age {$player->age} (retirement age: {$player->retirement_age}) due to {$retirementReason}. Last team: {$teamName}";
                if ($retirementReason === 'severe injury history') {
                    $details .= " (injury count: {$injuryHistoryCount})";
                }

                // Update player to retired status
                DB::table('players')->where('id', $player->id)->update([
                    'is_active' => false,
                    'team_id' => null,
                    'updated_at' => now(),
                ]);

                // Log retirement in transactions table
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => $details,
                    'from_team_id' => $player->team_id ?? 0,
                    'to_team_id' => 0,
                    'status' => 'retired',
                ]);

                \Log::info("Player {$player->name} has been forced to retire due to {$retirementReason}.");
                return;
            }
        } catch (\Exception $e) {
            \Log::error("Error handling player {$player->id}: " . $e->getMessage());
        }
    }

    private function playerWaiverEvaluator($player, $seasonId, $seasonStatus)
    {
        if (is_array($player)) {
            $player = (object) $player;
        }

        $evaluation = $this->shouldWaivePlayer($player, $seasonId, $seasonStatus);

        if ($evaluation['waived']) {
            $reason = $evaluation['reason'] ?? 'No specific reason provided';
            $teamId = $player->team_id;

            // 🔁 Log waiver transaction
            DB::table('transactions')->insert([
                'player_id' => $player->id,
                'season_id' => $seasonId,
                'details' => 'Waived: ' . $reason,
                'from_team_id' => $teamId,
                'to_team_id' => 0,
                'status' => 'waived',
            ]);

            // 🚫 Remove player from team
            DB::table('players')->where('id', $player->id)->update([
                'contract_years' => 0,
                'team_id' => 0,
            ]);

            // ✅ Find replacement
            $replacement = $this->getBestFreeAgentAvailable($player->position);
            if ($replacement) {
                $contractYears = $this->getContractYearsBasedOnRole($player->role);

                DB::table('players')->where('id', $replacement->player_id)->update([
                    'team_id' => $teamId,
                    'contract_years' => $contractYears,
                ]);

                // Check if replacement is same as waived player
                $replacementDetails = ($replacement->player_id === $player->id)
                    ? 'Re-signed ' . $player->name . ' after reevaluation. Contract renewed for ' . $contractYears . ' year(s).'
                    : 'Signed as replacement for ' . $player->name . '. Contract Years: ' . $contractYears;

                DB::table('transactions')->insert([
                    'player_id' => $replacement->player_id,
                    'season_id' => $seasonId,
                    'details' => $replacementDetails,
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'signed',
                ]);

                (new AwardsController)->storePlayerCurrentSeasonStats($teamId, $replacement->player_id);
            }

            return true;
        }

        return false;
    }

    public function getActivePlayersSorted($teamId, $rolePriority, $round)
    {
        $seasonId = get_current_season_id();
        $previousSeasonId = get_previous_season_id(); // You must implement this

        $players = Player::where('team_id', $teamId)
            ->where('is_active', 1)
            ->get();

        $playerEfficiencies = [];

        foreach ($players as $player) {
            $playerId = $player->id;
            $role = $player->role;

            // Years pro = distinct seasons
            $yearsPro = DB::table('player_season_stats')
                ->where('player_id', $playerId)
                ->distinct('season_id')
                ->count('season_id');

            // Current season efficiency sum
            $currentEff = DB::table('player_season_stats')
                ->where('season_id', $seasonId)
                ->where('player_id', $playerId)
                ->sum('eff') ?? 0;

            // Last 5 games from previous season (only if early season)
            $lastFiveGamesEff = DB::table('player_game_stats')
                ->where('season_id', $previousSeasonId)
                ->where('player_id', $playerId)
                ->orderByDesc('id')
                ->limit(5)
                ->sum('eff') ?? 0;

            $totalEff = $currentEff + $lastFiveGamesEff;

            // Draft info
            $draft = DB::table('drafts')
                ->where('player_id', $playerId)
                ->where('season_id', $seasonId)
                ->first();

            $playerEfficiencies[] = [
                'player' => $player,
                'role' => $player->role,
                'total_eff' => $totalEff,
                'years_pro' => $yearsPro,
                'is_rookie' => $draft ? true : false,
                'draft_round' => $draft->round ?? null,
                'draft_pick' => $draft->pick_number ?? null,
                'role_rank' => array_search($player->role, $rolePriority) !== false
                    ? array_search($player->role, $rolePriority)
                    : PHP_INT_MAX,
            ];
        }

        // Sort by: total_eff DESC, years_pro DESC, role_priority ASC
        $sortedPlayers = collect($playerEfficiencies)->sort(function ($a, $b) {
            return $b['total_eff'] <=> $a['total_eff']
                ?: $b['years_pro'] <=> $a['years_pro']
                ?: $a['role_rank'] <=> $b['role_rank'];
        })->pluck('player')->values();

        return $sortedPlayers;
    }

    private function fireLeopardRule($teamId)
    {
        $seasonId = get_current_season_id();

        // Count the number of active (non-injured) players
        $activePlayersCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_injured', false)
            ->count();

        // If the team has at least 11 healthy players, no action is needed
        if ($activePlayersCount >= 11) {
            return $activePlayersCount;
        }

        // Determine how many players need to be added
        $playersNeeded = 1;
        $signedPlayers = [];

        // Find free agents for temporary contracts
        $freeAgents = DB::table('players')
            ->where('team_id', 0) // Free agent pool
            ->where('is_injured', 0) // Not injured
            ->where('is_active', 1) // Ensure the player is active
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
        $deductionPerGame = 1; // Deduct 1 of a game


        $deductInjuryGames = DB::table('players')
            ->where('is_active', 1)
            ->where('is_injured', 1)
            ->where('injury_recovery_games', '>', 0)
            ->update([
                'injury_recovery_games' => DB::raw("GREATEST(injury_recovery_games - $deductionPerGame, 0)")
            ]);


        // Check if any rows were actually updated
        $affectedRows = DB::table('players')
            ->where('is_active', 1)
            ->where('is_injured', 1)
            ->where('injury_recovery_games', 0)
            ->count(); // Count players whose recovery reached 0

        if ($affectedRows > 0) {
            DB::table('players')
                ->where('is_active', 1)
                ->where('is_injured', 1)
                ->where('injury_recovery_games', '<=', 0)
                ->update(['is_injured' => 0]);
        }
    }

    public function getBestFreeAgent(Request $request)
    {
        // Validate the request data
        $position = $request->position;

        if (!$position) {
            return response()->json(['error' => 'Position is required'], 400);
        }

        $freeAgent = $this->getBestFreeAgentAvailable($position);

        return response()->json(['data' =>  $freeAgent]);
    }

    private function getBestFreeAgentAvailable($position)
    {
        $positions = explode('/', strtoupper($position)); // Normalize casing

        // Flexible position filter: match any part of multi-position fields
        $positionFilter = function ($query) use ($positions) {
            $query->where(function ($q) use ($positions) {
                foreach ($positions as $pos) {
                    $q->orWhere('players.position', 'LIKE', '%' . $pos . '%');
                }
            });
        };

        // Get latest season id (adjust if your season logic is different)
        $latestSeasonId = get_current_season_id();

        // Top 10 by overall_rating
        $byOverall = DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(10)
            ->get();

        // Top 10 by awards count
        $byAwards = DB::table('players')
            ->leftJoin('season_awards', 'players.id', '=', 'season_awards.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                DB::raw('COUNT(season_awards.id) as awards_count')
            )
            ->groupBy(
                'players.id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('awards_count')
            ->limit(10)
            ->get();

        // Top 10 by EFF in latest season
        $byEff = DB::table('players')
            ->leftJoin('player_season_stats', 'players.id', '=', 'player_season_stats.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where('player_season_stats.season_id', $latestSeasonId)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                'player_season_stats.eff'
            )
            ->orderByDesc('player_season_stats.eff')
            ->limit(10)
            ->get();

        // Merge all and deduplicate by player_id
        $merged = $byOverall->merge($byAwards)->merge($byEff)->unique('player_id')->values();

        // Return a random player from the merged top candidates
        if ($merged->isNotEmpty()) {
            return $merged->random();
        }

        // Fallback: any available player at the position
        return DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(1)
            ->first();
    }

    private function updateSeriesAndSchedule($gameData, $winnerId)
    {
        // Fetch the series
        $series = DB::table('playoff_series')
            ->where('series_id', $gameData->series_id)
            ->first();

        if (!$series) {
            throw new \Exception("Series not found for series_id: {$gameData->series_id}");
        }

        if ($series->status == 2) {
            return false; // Series already completed, stop here
        }

        // Update wins based on the winner
        $updateData = [
            'home_wins' => $series->home_team_id == $winnerId ? $series->home_wins + 1 : $series->home_wins,
            'away_wins' => $series->away_team_id == $winnerId ? $series->away_wins + 1 : $series->away_wins,
            'updated_at' => Carbon::now(),
            'status' => 1, // Default to "in progress"
        ];

        // Check if the series is completed
        if (
            $updateData['home_wins'] >= $series->best_of ||
            $updateData['away_wins'] >= $series->best_of
        ) {
            $updateData['status'] = 2; // Mark as completed

            if ($updateData['home_wins'] >= $series->best_of) {
                $updateData['winner_team_id'] = $series->home_team_id;
                $updateData['loser_team_id'] = $series->away_team_id;
            } else {
                $updateData['winner_team_id'] = $series->away_team_id;
                $updateData['loser_team_id'] = $series->home_team_id;
            }
        }

        // Update playoff_series table
        DB::table('playoff_series')
            ->where('series_id', $gameData->series_id)
            ->update($updateData);
    }

    private function updateFinalsBonusContract($teamId, $seasonId, $teamName)
    {
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
            if ($additionalContractYears > 0) {
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
                    'status' => 'contract extension',
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

    private function updateSeriesConferenceChampions($gameData)
    {
        // Get series info including home and away team IDs and names, plus conference info
        $series = DB::table('playoff_series as ps')
            ->join('teams as home_team', 'ps.home_team_id', '=', 'home_team.id')
            ->join('teams as away_team', 'ps.away_team_id', '=', 'away_team.id')
            ->join('conferences as home_conf', 'home_team.conference_id', '=', 'home_conf.id')
            ->join('conferences as away_conf', 'away_team.conference_id', '=', 'away_conf.id')
            ->where('ps.series_id', $gameData->series_id)
            ->where('ps.status', 2)
            ->select(
                'ps.*',
                'home_team.name as home_team_name',
                'home_conf.name as home_conference',
                'away_team.name as away_team_name',
                'away_conf.name as away_conference'
            )
            ->first();


        if (!$series) {
            return; // Series not found
        }

        // Determine the conference based on home or away conference name from the series table
        // Assuming the conference relevant to this update is the home team's conference
        $conferenceName = $series->home_conference;
        $winnerId = $series->winner_team_id;
        // Determine winner's name from the series table based on winnerId
        $winnerName = null;
        if ($series->home_team_id === $winnerId) {
            $winnerName = $series->home_team_name;
        } elseif ($series->away_team_id === $winnerId) {
            $winnerName = $series->away_team_name;
        }

        // Prepare columns to update in the seasons table based on conference
        $columnsToUpdate = [];

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

        // Update the seasons table with the champion info for the conference
        if (!empty($columnsToUpdate)) {
            DB::table('seasons')
                ->where('id', $gameData->season_id)
                ->update($columnsToUpdate);
        }
    }

    private function getTeamChemistry($seasonId, $teamId)
    {
        return DB::table('team_season_info')
            ->where('season_id', $seasonId)
            ->where('team_id', $teamId)
            ->value('chemistry');
    }
    // Method to handle finals logic
    private function updateFinalsWinner($gameData, $winnerId, $homeScore, $awayScore)
    {
        // 1. Get all finals game IDs for this season
        $finalsGameIds = DB::table('schedules')
            ->where('season_id', $gameData->season_id)
            ->where('round', 'finals') // adjust if your finals round name is different
            ->pluck('id');

        // 2. Calculate averages for all players in the winning team across finals
        $mvpPlayer = PlayerGameStats::join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->whereIn('player_game_stats.game_id', $finalsGameIds)
            ->where('player_game_stats.team_id', $winnerId)
            ->select(
                'player_game_stats.player_id',
                'players.name as mvp_name',
                DB::raw('AVG(player_game_stats.eff) as avg_eff'),
                DB::raw('AVG(player_game_stats.points) as avg_points'),
                DB::raw('AVG(player_game_stats.assists) as avg_assists'),
                DB::raw('AVG(player_game_stats.rebounds) as avg_rebounds')
            )
            ->groupBy('player_game_stats.player_id', 'players.name')
            ->orderByDesc('avg_eff')
            ->first();

        // 3. Get MVP details
        $finalsMVP = $mvpPlayer ? $mvpPlayer->mvp_name : '';
        $finalsMVPId = $mvpPlayer ? $mvpPlayer->player_id : '';
        $homeTeamWins = $gameData->home_team_id === $winnerId;

        // 4. Update season record with finals results
        DB::table('seasons')
            ->where('id', $gameData->season_id)
            ->update([
                'finals_winner_id'    => $winnerId,
                'finals_loser_id'     => $homeTeamWins ? $gameData->away_team_id : $gameData->home_team_id,
                'finals_winner_name'  => $homeTeamWins ? $gameData->home_team_name : $gameData->away_team_name,
                'finals_loser_name'   => $homeTeamWins ? $gameData->away_team_name : $gameData->home_team_name,
                'finals_winner_score' => $homeTeamWins ? $homeScore : $awayScore,
                'finals_loser_score'  => $homeTeamWins ? $awayScore : $homeScore,
                'finals_mvp'          => $finalsMVP,
                'finals_mvp_id'       => $finalsMVPId,
            ]);
    }

    private function updateSeriesFinalsWinner($gameData)
    {
        // Get series info including home and away team names and wins
        $series = DB::table('playoff_series as ps')
            ->join('teams as home_team', 'ps.home_team_id', '=', 'home_team.id')
            ->join('teams as away_team', 'ps.away_team_id', '=', 'away_team.id')
            ->where('ps.series_id', $gameData->series_id)
            ->select(
                'ps.*',
                'home_team.name as home_team_name',
                'away_team.name as away_team_name'
            )
            ->where('ps.status', 2)
            ->first();

        if (!$series) {
            return; // Series not found
        }

        // Finals game IDs in this season involving the two teams
        $finalsGameIds = DB::table('schedules')
            ->where('round', 'finals')
            ->where('season_id', $gameData->season_id)
            ->where(function ($query) use ($series) {
                $query->where('home_id', $series->home_team_id)
                    ->orWhere('away_id', $series->away_team_id);
            })
            ->pluck('game_id')
            ->toArray();

        // Calculate MVP based on eff average for winner team in finals
        $mvpPlayer = PlayerGameStats::join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->whereIn('player_game_stats.game_id', $finalsGameIds)
            ->where('player_game_stats.team_id', $series->winner_team_id)
            ->select(
                'player_game_stats.player_id',
                'players.name as mvp_name',
                DB::raw('AVG(player_game_stats.eff) as avg_eff')
            )
            ->groupBy('player_game_stats.player_id', 'players.name')
            ->orderByDesc('avg_eff')
            ->first();

        $finalsMVP = $mvpPlayer ? $mvpPlayer->mvp_name : null;
        $finalsMVPId = $mvpPlayer ? $mvpPlayer->player_id : null;

        // Determine if winner is home or away to assign names correctly
        $homeTeamWins = $series->winner_team_id == $series->home_team_id;

        // Update seasons table with finals results using winner_team_id, loser_team_id, and wins
        DB::table('seasons')
            ->where('id', $gameData->season_id)
            ->update([
                'finals_winner_id'    => $series->winner_team_id,
                'finals_loser_id'     => $series->loser_team_id,
                'finals_winner_name'  => $homeTeamWins ? $series->home_team_name : $series->away_team_name,
                'finals_loser_name'   => $homeTeamWins ? $series->away_team_name : $series->home_team_name,
                'finals_winner_score' => $homeTeamWins ? $series->home_wins : $series->away_wins,
                'finals_loser_score'  => $homeTeamWins ? $series->away_wins : $series->home_wins,
                'finals_mvp'          => $finalsMVP,
                'finals_mvp_id'       => $finalsMVPId,
            ]);
    }

    public function fixTeamPositionBalance($teamId)
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

        if (!$counts) {
            return response()->json(['error' => 'Team not found in view.'], 404);
        }

        $minimumPlayersPerPosition = 1;
        $posCounts = collect($counts)->only($positions)->map(fn($val) => (int) $val)->toArray();
        $positionsNeeding = collect($posCounts)->filter(fn($count) => $count < $minimumPlayersPerPosition);
        $positionsOverfilled = collect($posCounts)->filter(fn($count) => $count > $minimumPlayersPerPosition);

        // =============== CASE 1: Roster < 15 ====================
        if ($rosterCount < 15) {
            while ($rosterCount < 15) {
                $lowestPosition = collect($posCounts)->sort()->keys()->first();

                // Sign free agent
                $agent = $this->getBestFreeAgentAvailable($lowestPosition);
                if (!$agent) break;

                $contractYears = $this->getContractYearsBasedOnRole($agent->role);
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

                $posCounts[$lowestPosition]++;
                $rosterCount++;
            }

            return response()->json(['message' => 'Signed free agents to reach 15-man roster.']);
        }

        // =============== CASE 2: Roster == 15 && underfilled positions ================
        if ($rosterCount == 15 && $positionsNeeding->isNotEmpty()) {
            foreach ($positionsNeeding as $position => $missing) {
                for ($i = 0; $i < $missing; $i++) {
                    $overflow = $positionsOverfilled->sortDesc()->keys()->first();

                    if (!$overflow || $posCounts[$overflow] <= $minimumPlayersPerPosition) break;

                    // Try to trade first
                    $tradeData = $this->findTradePlayer($teamId, $position, $seasonId, $posCounts);

                    if ($tradeData) {
                        // Execute two-way trade
                        // Update player from other team to current team
                        DB::table('players')->where('id', $tradeData['incomingPlayer']->player_id)->update([
                            'team_id' => $teamId,
                        ]);

                        // Update player from current team to other team
                        DB::table('players')->where('id', $tradeData['outgoingPlayer']->player_id)->update([
                            'team_id' => $tradeData['otherTeamId'],
                        ]);

                        // Record transaction for incoming player
                        DB::table('transactions')->insert([
                            'player_id' => $tradeData['incomingPlayer']->player_id,
                            'season_id' => $seasonId,
                            'details' => "Traded to fill underfilled position {$position}",
                            'from_team_id' => $tradeData['otherTeamId'],
                            'to_team_id' => $teamId,
                            'status' => 'in-season trade',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        (new AwardsController)->storePlayerCurrentSeasonStats($teamId, $tradeData['incomingPlayer']->player_id);
                        // Record transaction for outgoing player
                        DB::table('transactions')->insert([
                            'player_id' => $tradeData['outgoingPlayer']->player_id,
                            'season_id' => $seasonId,
                            'details' => "Traded to balance position {$tradeData['outgoingPosition']}",
                            'from_team_id' => $teamId,
                            'to_team_id' => $tradeData['otherTeamId'],
                            'status' => 'in-season trade',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        (new AwardsController)->storePlayerCurrentSeasonStats($tradeData['otherTeamId'], $tradeData['outgoingPlayer']->player_id);
                        // Update position counts
                        $posCounts[$overflow]--;
                        $posCounts[$position]++;
                    } else {
                        // Fall back to waiving and signing free agent
                        $playerToWaive = DB::table('players')
                            ->where('players.team_id', $teamId)
                            ->where('players.is_active', true)
                            ->where('players.contract_years', '<=', 2)
                            ->where(function ($query) use ($overflow) {
                                $query->where('players.position', $overflow)
                                    ->orWhere('players.position', 'like', $overflow . '/%')
                                    ->orWhere('players.position', 'like', '%/' . $overflow)
                                    ->orWhere('players.position', 'like', '%/' . $overflow . '/%');
                            })
                            ->select('players.*')
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

                        // Sign free agent
                        $replacement = $this->getBestFreeAgentAvailable($position);
                        if (!$replacement) continue;

                        $contractYears = $this->getContractYearsBasedOnRole($replacement->role);
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

    /**
     * Find a player to trade from a team with an overfilled position and an underfilled position matching the current team's overfilled position
     */
    private function findTradePlayer($teamId, $neededPosition, $seasonId, $currentTeamPosCounts)
    {
        // Find teams with overfilled needed position and underfilled position matching current team's overfilled position
        $overfilledPosition = collect($currentTeamPosCounts)->filter(fn($count) => $count > 3)->sortDesc()->keys()->first();

        if (!$overfilledPosition) {
            return null;
        }

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

    private function updateFinalsMVPBonusContract($winnerId, $seasonId, $finalsMVPId)
    {
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

    private function updateTeamStreaks($gameid)
    {
        // Fetch all completed games up to the given game ID
        $games = \DB::table('schedule_view')
            ->where('id', '<=', $gameid)
            ->where('status', 2)
            ->orderBy('id', 'asc') // Ensure chronological order
            ->get();

        if ($games->isEmpty()) {
            \Log::warning("No completed games found up to gameid $gameid");
            return;
        }

        $teamStreaks = [];

        foreach ($games as $game) {
            // Validate game data
            if (empty($game->winner_id)) {
                \Log::warning("Skipping game $game->id: No valid winner_id");
                continue;
            }

            $this->processGameStreak($teamStreaks, $game->home_id, $game->winner_id, $game->id);
            $this->processGameStreak($teamStreaks, $game->away_id, $game->winner_id, $game->id);
        }

        foreach ($teamStreaks as $teamId => $streak) {
            $streakRecord = \DB::table('streak')->where('team_id', $teamId)->first();

            $data = [
                'best_winning_streak' => $streak['best_winning_streak'],
                'best_winning_streak_start_id' => $streak['best_winning_streak_start_id'],
                'best_winning_streak_end_id' => $streak['best_winning_streak_end_id'],
                'best_losing_streak' => $streak['best_losing_streak'],
                'best_losing_streak_start_id' => $streak['best_losing_streak_start_id'],
                'best_losing_streak_end_id' => $streak['best_losing_streak_end_id'],
            ];

            try {
                if ($streakRecord) {
                    \DB::table('streak')->where('team_id', $teamId)->update($data);
                } else {
                    \DB::table('streak')->insert(array_merge(['team_id' => $teamId], $data));
                }
            } catch (\Exception $e) {
                \Log::error("Failed to update streak for team $teamId: " . $e->getMessage());
            }
        }
    }

    private function processGameStreak(&$teamStreaks, $teamId, $winnerId, $gameId)
    {
        if (!isset($teamStreacks[$teamId])) {
            $teamStreaks[$teamId] = [
                'current_streak' => 0,
                'is_winning_streak' => null,
                'best_winning_streak' => 0,
                'best_losing_streak' => 0,
                'best_winning_streak_start_id' => null,
                'best_winning_streak_end_id' => null,
                'best_losing_streak_start_id' => null,
                'best_losing_streak_end_id' => null,
            ];
        }

        $streak = &$teamStreaks[$teamId];

        $isWin = $teamId == $winnerId;

        if ($isWin) {
            if ($streak['is_winning_streak'] === false || $streak['is_winning_streak'] === null) {
                $streak['current_streak'] = 1;
                $streak['best_winning_streak_start_id'] = $gameId;
            } else {
                $streak['current_streak']++;
            }
            $streak['is_winning_streak'] = true;
            if ($streak['current_streak'] > $streak['best_winning_streak']) {
                $streak['best_winning_streak'] = $streak['current_streak'];
                $streak['best_winning_streak_end_id'] = $gameId;
            }
        } else {
            if ($streak['is_winning_streak'] === true || $streak['is_winning_streak'] === null) {
                $streak['current_streak'] = 1;
                $streak['best_losing_streak_start_id'] = $gameId;
            } else {
                $streak['current_streak']++;
            }
            $streak['is_winning_streak'] = false;
            if ($streak['current_streak'] > $streak['best_losing_streak']) {
                $streak['best_losing_streak'] = $streak['current_streak'];
                $streak['best_losing_streak_end_id'] = $gameId;
            }
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

    public function updatePlayoffAppearancesForGame($gameData)
    {
        $homeId = $gameData->home_team_id;
        $awayId = $gameData->away_team_id;
        $seasonId = $gameData->season_id;
        $round = $gameData->round;

        // Generate a unique series identifier (sorted to ensure consistency)
        $teamIds = [$homeId, $awayId];
        sort($teamIds); // Sort to ensure consistent series ID regardless of home/away order
        $seriesIdentifier = implode('_', [$seasonId, $round, $teamIds[0], $teamIds[1]]);

        // Get unique player IDs from both teams
        $playerIds = DB::table('players')
            ->whereIn('team_id', [$homeId, $awayId])
            ->pluck('id')
            ->unique();

        foreach ($playerIds as $playerId) {
            $this->updatePlayerPlayoffAppearance($playerId, $gameData, $seriesIdentifier);
        }
    }

    public function updatePlayerPlayoffAppearance($playerId, $gameData, $seriesIdentifier)
    {
        if (!$playerId || !$gameData || !$seriesIdentifier) {
            \Log::error("Invalid input: playerId=$playerId, gameData=" . json_encode($gameData) . ", seriesIdentifier=$seriesIdentifier");
            return;
        }

        $seasonId = $gameData->season_id;
        $round = $gameData->round;

        // ✅ Winner team comes directly from schedules (single-elims assumption)
        $winnerTeamId = $gameData->winner_id;

        // Fetch player's team_id
        $playerTeamId = DB::table('players')->where('id', $playerId)->value('team_id');
        if (!$playerTeamId) {
            \Log::error("No team_id found for player_id: $playerId");
            return;
        }

        $roundColumnMap = [
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

        if (!isset($roundColumnMap[$round])) {
            \Log::warning("Invalid playoff round: $round");
            return; // Not a tracked playoff round
        }

        $columnToIncrement = $roundColumnMap[$round];

        // Use a transaction for database consistency
        DB::transaction(function () use ($playerId, $columnToIncrement, $round, $playerTeamId, $winnerTeamId, $seriesIdentifier, $gameData) {

            // Check if the player has already been credited for this series
            $existingAppearance = DB::table('player_series_appearances')
                ->where('player_id', $playerId)
                ->where('series_identifier', $seriesIdentifier)
                ->exists();

            if ($existingAppearance) {
                \Log::info("Player $playerId already credited for series $seriesIdentifier");
                return; // Skip if appearance already recorded
            }

            // Record the series appearance
            DB::table('player_series_appearances')->insert([
                'player_id' => $playerId,
                'series_identifier' => $seriesIdentifier,
                'season_id' => $gameData->season_id,
                'round' => $round,
                'created_at' => now(),
            ]);

            // Ensure player record exists in player_playoff_appearances
            DB::table('player_playoff_appearances')->updateOrInsert(
                ['player_id' => $playerId],
                []
            );

            // Increment specific round appearance
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->increment($columnToIncrement);

            // Increment total playoff appearances
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->increment('total_playoff_appearances');

            // ✅ Handle championship win in finals (single-elims assumption)
            if ($round === 'finals' && $winnerTeamId && $playerTeamId == $winnerTeamId) {
                \Log::info("Incrementing championships_won for player $playerId, team $playerTeamId won");
                DB::table('player_playoff_appearances')
                    ->where('player_id', $playerId)
                    ->increment('championships_won');
            } else {
                \Log::info("Championship not incremented: round=$round, playerTeamId=$playerTeamId, winnerTeamId=$winnerTeamId");
            }
        });
    }

    public function updatePlayoffSeriesAppearancesForGame($gameData)
    {
        $homeId = $gameData->home_team_id;
        $awayId = $gameData->away_team_id;
        $seasonId = $gameData->season_id;
        $round = $gameData->round;

        // Generate a unique series identifier (sorted to ensure consistency)
        $teamIds = [$homeId, $awayId];
        sort($teamIds); // Sort to ensure consistent series ID regardless of home/away order
        $seriesIdentifier = implode('_', [$seasonId, $round, $teamIds[0], $teamIds[1]]);

        // Get unique player IDs from both teams
        $playerIds = DB::table('players')
            ->whereIn('team_id', [$homeId, $awayId])
            ->pluck('id')
            ->unique();

        foreach ($playerIds as $playerId) {
            $this->updatePlayerPlayoffSeriesAppearance($playerId, $gameData, $seriesIdentifier);
        }
    }

    public function updatePlayerPlayoffSeriesAppearance($playerId, $gameData, $seriesIdentifier)
    {
        if (!$playerId || !$gameData || !$seriesIdentifier) {
            return;
        }

        $seasonId = $gameData->season_id;
        $round = $gameData->round;
        $winnerId = $gameData->winner_id;

        // Fetch player's team_id
        $playerTeamId = DB::table('players')->where('id', $playerId)->value('team_id');
        if (!$playerTeamId) {
            return;
        }

        $roundColumnMap = [
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

        if (!isset($roundColumnMap[$round])) {
            return; // Not a tracked playoff round
        }

        $columnToIncrement = $roundColumnMap[$round];

        DB::transaction(function () use ($playerId, $columnToIncrement, $round, $playerTeamId, $winnerId, $seriesIdentifier, $gameData) {
            // Check if already recorded
            $existingAppearance = DB::table('player_series_appearances')
                ->where('player_id', $playerId)
                ->where('series_identifier', $seriesIdentifier)
                ->exists();

            // Championship win condition: finals + winner + completed series
            if ($round === 'finals' && $playerTeamId) {
                // Get the series data
                $series = DB::table('playoff_series')
                    ->where('series_id', $gameData->series_id)
                    ->where('status', 2) // finished
                    ->first();

                // Check if the player's team is the series winner
                if ($series && $series->winner_team_id == $playerTeamId) {
                    DB::table('player_playoff_appearances')
                        ->where('player_id', $playerId)
                        ->increment('championships_won');
                }
            }

            if ($existingAppearance) {
                return;
            }

            // Record the series appearance
            DB::table('player_series_appearances')->insert([
                'player_id' => $playerId,
                'series_identifier' => $seriesIdentifier,
                'season_id' => $gameData->season_id,
                'round' => $round,
                'created_at' => now(),
            ]);

            // Ensure record exists in player_playoff_appearances
            DB::table('player_playoff_appearances')->updateOrInsert(
                ['player_id' => $playerId],
                []
            );

            // Increment round appearance
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->increment($columnToIncrement);

            // Increment total playoff appearances
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->increment('total_playoff_appearances');
        });
    }


    private function updateTeamRolesBasedOnStats($teamId, $round)
    {
        if (!$teamId) return false;

        DB::beginTransaction();
        try {
            $seasonId = get_current_season_id();
            $previousSeasonId = $seasonId - 1;

            $playersRaw = DB::table('players')
                ->where('contract_years', '>', 0)
                ->where('is_injured', false)
                ->where('team_id', $teamId)
                ->select('id', 'role', 'position')
                ->get();

            $playerEfficiencies = [];

            foreach ($playersRaw as $player) {
                $playerId = $player->id;

                $yearsPro = DB::table('player_season_stats')
                    ->where('player_id', $playerId)
                    ->distinct('season_id')
                    ->count('season_id');

                // Current season totals
                $currentEff = DB::table('player_season_stats')
                    ->where('season_id', $seasonId)
                    ->where('player_id', $playerId)
                    ->sum('eff');

                $gamesPlayed = DB::table('player_game_stats')
                    ->where('season_id', $seasonId)
                    ->where('player_id', $playerId)
                    ->count();

                // PER approximation (EFF per game)
                $per = $gamesPlayed > 0 ? ($currentEff / $gamesPlayed) : 0;

                // Total EFF including last 5 games of previous season (early season buffer)
                $totalEff = $currentEff;
                if ($round <= 5) {
                    $lastFiveGames = DB::table('player_game_stats')
                        ->where('season_id', $previousSeasonId)
                        ->where('player_id', $playerId)
                        ->orderByDesc('id')
                        ->limit(5)
                        ->pluck('eff');
                    $totalEff += $lastFiveGames->sum();
                }

                // Draft info
                $draft = DB::table('drafts')
                    ->where('player_id', $playerId)
                    ->where('season_id', $seasonId)
                    ->first();

                // Hybrid role score: PER weighted higher than EFF/game
                $roleScore = ($per * 0.6) + (($totalEff / max(1, $gamesPlayed)) * 0.4);

                // Positional weights
                $positionWeights = [
                    'PG' => fn($score) => $score * 1.05,
                    'SG' => fn($score) => $score * 1.03,
                    'SF' => fn($score) => $score,
                    'PF' => fn($score) => $score * 1.02,
                    'C'  => fn($score) => $score * 1.06,
                ];

                $mainPosition = explode('/', $player->position)[0] ?? 'SF';
                $weightedScore = isset($positionWeights[$mainPosition])
                    ? $positionWeights[$mainPosition]($roleScore)
                    : $roleScore;

                $playerEfficiencies[] = [
                    'player_id' => $playerId,
                    'position' => $player->position,
                    'role' => $player->role,
                    'role_score' => $weightedScore,
                    'years_pro' => $yearsPro,
                    'is_rookie' => $draft ? true : false,
                    'draft_round' => $draft->round ?? null,
                    'draft_pick' => $draft->pick_number ?? null,
                ];
            }

            // Sort by score first, then years pro
            $players = collect($playerEfficiencies)->sort(function ($a, $b) {
                return $b['role_score'] <=> $a['role_score'] ?: $b['years_pro'] <=> $a['years_pro'];
            })->values();

            // Build starting five
            $positions = ['PG', 'SG', 'SF', 'PF', 'C'];
            $startingFive = [];
            $usedPlayerIds = [];

            foreach ($positions as $neededPosition) {
                foreach ($players as $player) {
                    if (in_array($player['player_id'], $usedPlayerIds)) continue;
                    $playerPositions = explode('/', strtoupper($player['position']));
                    if (in_array($neededPosition, $playerPositions)) {
                        $startingFive[] = $player;
                        $usedPlayerIds[] = $player['player_id'];
                        break;
                    }
                }
            }

            // Add high-performing rookie to starting five if missing
            foreach ($players as $player) {
                if (count($startingFive) >= 5) break;
                if (!in_array($player['player_id'], $usedPlayerIds)) {
                    if ($player['is_rookie'] && $player['draft_pick'] && $player['draft_pick'] <= 5) {
                        $startingFive[] = $player;
                        $usedPlayerIds[] = $player['player_id'];
                    }
                }
            }

            // Fill to 5 if still lacking
            foreach ($players as $player) {
                if (count($startingFive) >= 5) break;
                if (!in_array($player['player_id'], $usedPlayerIds)) {
                    $startingFive[] = $player;
                    $usedPlayerIds[] = $player['player_id'];
                }
            }

            // Assign roles
            usort($startingFive, function ($a, $b) {
                return $b['role_score'] <=> $a['role_score'] ?: $b['years_pro'] <=> $a['years_pro'];
            });

            $newRoles = [];
            $newRoles[$startingFive[0]['player_id']] = 'star player';
            $roleOrder = ['all star', 'all star', 'starter', 'starter'];
            foreach (array_slice($startingFive, 1) as $i => $player) {
                $newRoles[$player['player_id']] = $roleOrder[$i];
            }

            // Role players
            $rolePlayerCount = 0;
            foreach ($players as $player) {
                if (in_array($player['player_id'], $usedPlayerIds)) continue;
                if ($rolePlayerCount < 5 || ($player['is_rookie'] && $player['draft_pick'] && $player['draft_pick'] <= 10)) {
                    $newRoles[$player['player_id']] = 'role player';
                    $usedPlayerIds[] = $player['player_id'];
                    $rolePlayerCount++;
                }
            }

            // Remaining bench
            foreach ($players as $player) {
                if (!in_array($player['player_id'], $usedPlayerIds)) {
                    $newRoles[$player['player_id']] = 'bench';
                }
            }

            // Save role changes
            foreach ($newRoles as $playerId => $newRole) {
                $currentRole = collect($playersRaw)->firstWhere('id', $playerId)->role;
                if ($currentRole !== $newRole) {
                    $status = $newRole === 'star player' ? 'star player change' : 'role change';
                    $roundName = is_numeric($round) ? "Round $round" : "Playoffs";

                    DB::table('transactions')->insert([
                        'player_id' => $playerId,
                        'season_id' => $seasonId,
                        'details' => "Has moved from $currentRole to $newRole for the upcoming games. $roundName",
                        'from_team_id' => $teamId,
                        'to_team_id' => $teamId,
                        'status' => $status,
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
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updating team $teamId roles: " . $e->getMessage());
            return false;
        }
    }

    private function updateSeasonStats($playerGameStats, $gameData, $isPlayoff)
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
                    $this->handleHardshipContract($player, $stats);
                }
            }
        } catch (Exception $e) {
            // Log error for debugging
            // Log::error("Error updating season stats: " . $e->getMessage());

            // Optionally, throw the error again to stop execution
            throw new Exception("Failed to update season stats. Please check logs." . $e->getMessage());
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

        $coachIQ = $chemistryRow->coach_iq;

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

        $injuredCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_injured', true) // assuming you track this
            ->count();

        if (!is_null($injuredCount)) {
            if ($injuredCount >= 3) $chemistry -= 3;
            elseif ($injuredCount === 1) $chemistry -= 1;
        }

        // 🧼 Clamp
        $chemistry = max(10, min(100, round($chemistry)));

        // ✅ Update
        DB::table('team_season_info')
            ->updateOrInsert(
                ['team_id' => $teamId, 'season_id' => $seasonId],
                ['chemistry' => $chemistry]
            );
    }
    private function updateInjuryAndWaiving($teamId)
    {
        $seasonId = get_current_season_id();
        $seasonStatus = DB::table('seasons')->where('id', $seasonId)->value('status');

        if (!$teamId || !$seasonId || !$seasonStatus) {
            return; // Invalid parameters
        }

        $players = DB::table('players')->where('team_id', $teamId)->get();
        foreach ($players as $player) {
            $this->handleInjuredPlayer($player, $seasonId, $seasonStatus);
            // Evaluate whether player should be waived based on injury duration and season status
            $this->playerWaiverEvaluator($player, $seasonId, $seasonStatus);
        }
    }
    private function updatePlayerMoraleBasedOnStats($teamId, $winnerId)
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
                ->first();

            if (!$gameStats) continue;

            // Calculate initial morale
            $morale = $player->morale ?? 75;
            $role = $player->role ?? 'bench';

            // 🎯 1. Game result impact
            $morale += $wonGame ? 2 : -2;

            // 🎯 2. Performance-based morale adjustment
            $efficiency = $gameStats->eff ?? 0;
            if ($efficiency > 20) {
                $morale += 2;
            } elseif ($efficiency < 5) {
                $morale -= 2;
            }

            // 🎯 3. Minutes played vs role expectation
            $expectedMin = match ($role) {
                'star' => 32,
                'all star' => 28,
                'starter' => 24,
                'bench' => 10,
                default => 5,
            };

            if ($gameStats->minutes < $expectedMin - 5) {
                $morale -= 2;
            } elseif ($gameStats->minutes > $expectedMin + 5) {
                $morale += 1;
            }

            // 🎯 4. Chemistry impact
            if ($chemistry < 60) {
                $morale -= 1;
            } elseif ($chemistry >= 85) {
                $morale += 1;
            }

            // 🎯 5. Clamp morale between 50 and 100
            $morale = max(50, min(100, round($morale)));

            // ✅ Update player morale
            DB::table('players')
                ->where('id', $player->id)
                ->update(['morale' => $morale]);
        }

        // ✅ Update coach career wins or losses
        $coachId = DB::table('teams')->where('id', $teamId)->value('coach_id');

        if ($coachId) {
            DB::table('coaches')
                ->where('id', $coachId)
                ->increment($wonGame ? 'career_wins' : 'career_losses');
        }
    }

    private function handleHardshipContract($player, $stats)
    {
        $newHardshipGames = $player->hardship_contract - 1;
        if ($newHardshipGames > 0) {
            // Reduce remaining hardship games
            DB::table('players')->where('id', $player->id)->update([
                'hardship_contract' => $newHardshipGames
            ]);
        } else {
            // Check if player performance is good (e.g., points or eff >= 10)
            $performanceGood = false;
            $seasonId = $stats['season_id'] ?? get_current_season_id();
            $playerSeasonStats = DB::table('player_season_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $seasonId)
                ->first();

            $effPerGame = $playerSeasonStats->eff ?? 0;
            $minutesPerGame = $playerSeasonStats->avg_minutes_per_game ?? 0;

            $performanceGood = $effPerGame >= 10 || ($effPerGame >= 7 && $minutesPerGame <= 15); // efficient in small minutes
            if ($performanceGood) {
                // Sign as regular: assign contract years (e.g., 1 or based on role)
                $contractYears = $this->getContractYearsBasedOnRole($player->role);
                DB::table('players')->where('id', $player->id)->update([
                    'hardship_contract' => 0,
                    'contract_years' => $contractYears,
                ]);
                // Waive the injured player at the same position with the longest injury recovery time
                $longestInjured = DB::table('players')
                    ->where('team_id', $player->team_id)
                    ->where('is_injured', 1)
                    ->where('position', $player->position)
                    ->orderByDesc('injury_recovery_games')
                    ->first();

                if (!$longestInjured) {
                    // If none at the same position, get any injured player with the longest recovery time
                    $longestInjured = DB::table('players')
                        ->where('team_id', $player->team_id)
                        ->where('is_injured', 1)
                        ->orderByDesc('injury_recovery_games')
                        ->first();
                }

                if ($longestInjured) {
                    DB::table('players')->where('id', $longestInjured->id)->update([
                        'team_id' => 0,
                        'contract_years' => 0,
                    ]);
                    // Log transaction for waived player
                    DB::table('transactions')->insert([
                        'player_id' => $longestInjured->id,
                        'season_id' => $stats['season_id'],
                        'details' => 'Waived due to hardship player being signed as regular.',
                        'from_team_id' => $stats['team_id'],
                        'to_team_id' => 0,
                        'status' => 'waived',
                    ]);
                }
                // Log transaction for hardship player
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $stats['season_id'],
                    'details' => 'Signed a regular contract for the rest of the season.',
                    'from_team_id' => $stats['team_id'],
                    'to_team_id' => $stats['team_id'],
                    'status' => 'signed',
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

    private function shouldWaivePlayer($player, int $seasonId, int $seasonStatus): array
    {
        // Waivers only allowed in first half of season
        if ($seasonStatus > 1) {
            return ['waived' => false, 'reason' => 'Season too late to waive'];
        }

        // Protect key players
        $protectedRoles = ['star player', 'all star', 'starter'];
        if (in_array(strtolower($player->role), $protectedRoles) && $player->contract_years >= 3) {
            return ['waived' => false, 'reason' => 'Protected star/all-star with long contract'];
        }

        if ($player->is_rookie && $this->isHighPickRookie($player->id)) {
            return ['waived' => false, 'reason' => 'Protected high-pick rookie'];
        }

        if ($this->isDevelopmentalPlayer($player) || $this->wasRecentlyDrafted($player->id, $seasonId)) {
            return ['waived' => false, 'reason' => 'Protected developmental or recently drafted player'];
        }

        if ($this->calculatePotentialScore($player) >= 75) {
            return ['waived' => false, 'reason' => 'High potential player'];
        }

        // Get season stats and team games
        $seasonStats = $this->getPlayerSeasonStats($player->id, $player->team_id, $seasonId);
        if (!$seasonStats) {
            return ['waived' => false, 'reason' => 'Missing season stats'];
        }

        $totalGames = $this->totalTeamGames($seasonId, $player->team_id);
        $minGamesPlayed = max(3, floor($totalGames * 0.20)); // 20% of team games
        $hasPlayedMinimumGames = ($seasonStats->total_games_played ?? 0) >= $minGamesPlayed;

        // Role-based usage threshold
        $role = strtolower($player->role);
        $usageMinutesThreshold = 7;
        if (in_array($role, ['bench', 'role player'])) {
            $usageMinutesThreshold = 5; // More tolerance for bench/role players
        }

        // Injury-based waiver (aligned with handleInjuredPlayer and injuries.php)
        $rolePctMap = [
            'star player' => 0.80,
            'all star'    => 0.70,
            'starter'     => 0.60,
            'role player' => 0.50,
            'bench'       => 0.40,
        ];
        $defaultPct = 0.30;
        $pct = $rolePctMap[$role] ?? $defaultPct;

        $totalContractGames = $totalGames * max($player->contract_years, 1);
        $baseRecoveryGames = ceil($totalContractGames * $pct);
        $maxRecoveryGames = 30;
        $requiredRecoveryGames = min($baseRecoveryGames, $maxRecoveryGames);

        if ($player->overall_rating >= 90) {
            $requiredRecoveryGames += 5;
        } elseif ($player->overall_rating >= 80) {
            $requiredRecoveryGames += 2;
        } elseif ($player->overall_rating <= 70) {
            $requiredRecoveryGames -= 2;
        } elseif ($player->overall_rating <= 60) {
            $requiredRecoveryGames -= 4;
        }
        $requiredRecoveryGames = max(2, min($requiredRecoveryGames, $totalContractGames));

        if ($player->injury_recovery_games > $requiredRecoveryGames) {
            return ['waived' => true, 'reason' => 'Injured too long'];
        }

        // Combined criteria for efficiency and improvement (stricter to reduce waivers)
        $isRebuilding = $this->isRebuildingTeam($player->team_id);
        $hasNotImproved = $this->hasNotImproved($player->id, $player->team_id, $seasonId);

        // Adjusted efficiency threshold (from eff < 4 to eff < 6)
        if ($seasonStats->eff !== null && $seasonStats->eff < 6 && $hasNotImproved && $hasPlayedMinimumGames && !$isRebuilding) {
            return ['waived' => true, 'reason' => 'Low efficiency and no improvement'];
        }

        // Adjusted composite score (from < 5 to < 7)
        $usageScore = ($seasonStats->avg_minutes_per_game * 0.5) +
            ($seasonStats->avg_points_per_game * 0.3) +
            ($seasonStats->avg_rebounds_per_game * 0.2);
        $compositeScore = $usageScore * ($seasonStats->eff / 10);

        if (
            $compositeScore < 7 && $seasonStats->avg_minutes_per_game < $usageMinutesThreshold &&
            $seasonStats->total_games_played <= ($totalGames * 0.30) && $hasNotImproved && $hasPlayedMinimumGames
        ) {
            return ['waived' => true, 'reason' => 'Low composite efficiency and no improvement'];
        }

        // Rebuilding team: waive veterans with moderate performance
        if ($isRebuilding && $player->age >= 32 && $seasonStats->eff < 12 && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'Veteran waived by rebuilding team'];
        }

        // High fatigue or morale issues (require multiple conditions)
        if ($player->fatigue >= 85 && $seasonStats->eff < 8 && $hasNotImproved && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'High fatigue and underperforming with no improvement'];
        }

        if ($player->morale !== null && $player->morale < 40 && $seasonStats->eff < 8 && $hasNotImproved && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'Low morale and underperforming with no improvement'];
        }

        // Aging players (stricter criteria)
        if ($player->age >= 34 && $seasonStats->eff < 10 && $hasNotImproved && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'Aging player with poor impact and no improvement'];
        }

        // Bad value contract (require no improvement)
        if ($player->contract_years > 2 && $seasonStats->eff < 8 && $hasNotImproved && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'Bad value contract with no improvement'];
        }

        return ['waived' => false, 'reason' => null];
    }

    private array $playerYearsProCache = [];
    private array $playerYearsProWithTeamCache = [];

    private function getYearsPro(int $playerId): int
    {
        if (isset($this->playerYearsProCache[$playerId])) {
            return $this->playerYearsProCache[$playerId];
        }

        $yearsPro = DB::table('player_season_stats')
            ->where('player_id', $playerId)
            ->distinct()
            ->count('season_id');

        return $this->playerYearsProCache[$playerId] = $yearsPro;
    }

    private function getYearsProWithTeam(int $playerId, int $teamId): int
    {
        if (isset($this->playerYearsProCache[$playerId])) {
            return $this->playerYearsProCache[$playerId];
        }

        $yearsPro = DB::table('player_season_stats')
            ->where('player_id', $playerId)
            ->where('team_id', $teamId)
            ->distinct()
            ->count('season_id');

        return $this->playerYearsProWithTeamCache[$playerId] = $yearsPro;
    }

    private function isDevelopmentalPlayer($player): bool
    {
        $yearsPro = $this->getYearsPro($player->id);
        return ($player->age <= 23 || $yearsPro <= 2 || $player->is_rookie);
    }

    private function wasRecentlyDrafted(int $playerId, int $seasonId, int $roundLimit = 2): bool
    {
        $draft = DB::table('drafts')
            ->where('player_id', $playerId)
            ->where('season_id', $seasonId)
            ->first();

        return $draft && $draft->round <= $roundLimit;
    }

    private function calculatePotentialScore($player): int
    {
        $score = 0;

        if ($player->age <= 23) $score += 30;
        if ($this->getYearsPro($player->id) <= 2) $score += 20;
        if ($player->work_ethic_rating >= 75) $score += 20;
        if ($player->basketball_iq_rating >= 70) $score += 15;
        if (isset($player->draft_pick_number) && $player->draft_pick_number <= 15) $score += 15;

        return $score; // Max score: 100
    }

    private function isHighPickRookie($playerId): bool
    {
        $draft = DB::table('drafts')
            ->where('player_id', $playerId)
            ->first();

        if (!$draft) return false;

        return $draft->round == 1 && $draft->pick_number <= 10;
    }

    private function hasNotImproved(int $playerId, int $teamId, int $currentSeasonId): bool
    {
        // Get the earliest season ID
        $firstSeasonId = DB::table('seasons')->min('id');

        if ($currentSeasonId == $firstSeasonId) {
            return false; // No past data
        }

        // Calculate composite improvement index
        $improvementIndex = $this->calculateImprovementIndex($playerId, $teamId, $currentSeasonId);

        if (is_null($improvementIndex)) {
            // Handle players with no recent prior season data
            $seasonStats = $this->getPlayerSeasonStats($playerId, $teamId, $currentSeasonId);
            if (!$seasonStats || $seasonStats->total_games_played < max(3, floor($seasonStats->total_games * 0.15))) {
                return false; // Not enough current season data
            }

            // Role-based efficiency threshold, adjusted for injuries
            $role = strtolower($seasonStats->role ?? 'bench');
            $effThresholds = [
                'star player' => 18,
                'all star' => 15,
                'starter' => 12,
                'role player' => 10,
                'bench' => 8,
            ];
            $effThreshold = $effThresholds[$role] ?? 8;

            // Check injury history for adjustment
            $injuryCount = DB::table('injury_histories')
                ->where('player_id', $playerId)
                ->where('season_id', '<', $currentSeasonId)
                ->whereNull('recovery_date')
                ->count();
            $effThreshold += $injuryCount * 1; // Increase threshold for each unrecovered injury

            // Also check per-minute efficiency
            $perPerMinute = $seasonStats->per / max($seasonStats->avg_minutes_per_game, 1);
            $perThresholds = [
                'star player' => 0.8,
                'all star' => 0.7,
                'starter' => 0.6,
                'role player' => 0.5,
                'bench' => 0.4,
            ];
            $perThreshold = $perThresholds[$role] ?? 0.4;

            return $seasonStats->eff < $effThreshold && $perPerMinute < $perThreshold;
        }

        // Dynamic decline threshold based on role
        $seasonStats = $this->getPlayerSeasonStats($playerId, $teamId, $currentSeasonId);
        $role = strtolower($seasonStats->role ?? 'bench');
        $declineThreshold = in_array($role, ['bench', 'role player']) ? -0.10 : -0.15;

        return $improvementIndex <= $declineThreshold;
    }

    private function calculateImprovementIndex(int $playerId, int $teamId, int $currentSeasonId): ?float
    {
        $firstSeasonId = DB::table('seasons')->min('id');
        if ($currentSeasonId == $firstSeasonId) {
            return null; // No prior data
        }

        $yearsPro = $this->getYearsPro($playerId);

        // Fetch stats from last played season and one prior
        $pastSeasons = DB::table('player_season_stats as pss')
            ->join(DB::raw('(
            SELECT season_id, player_id, role
            FROM player_season_stats
            WHERE id IN (
                SELECT MAX(id)
                FROM player_season_stats
                GROUP BY season_id, player_id
            )
        ) as latest_stats'), function ($join) {
                $join->on('pss.season_id', '=', 'latest_stats.season_id')
                    ->on('pss.player_id', '=', 'latest_stats.player_id');
            })
            ->join('players as p', 'pss.player_id', '=', 'p.id')
            ->leftJoin('injury_histories as ih', function ($join) use ($currentSeasonId) {
                $join->on('pss.player_id', '=', 'ih.player_id')
                    ->where('ih.season_id', '<', $currentSeasonId)
                    ->whereNull('ih.recovery_date');
            })
            ->select(
                'pss.season_id',
                DB::raw('AVG(pss.per / NULLIF(pss.avg_minutes_per_game, 0)) as per_per_minute'),
                DB::raw('LOWER(latest_stats.role) as role'),
                DB::raw('AVG(pss.avg_minutes_per_game) as avg_mpg'),
                DB::raw('MAX(pss.total_games_played) as games_played'),
                DB::raw('MAX(pss.total_games) as total_games'),
                'p.age',
                DB::raw('COUNT(ih.id) as injury_count'),
                DB::raw('AVG(pss.eff) as avg_eff')
            )
            ->where('pss.player_id', $playerId)
            ->where('pss.team_id', $teamId)
            ->where('pss.season_id', '<', $currentSeasonId)
            ->groupBy('pss.season_id', 'latest_stats.role', 'p.age')
            ->orderByDesc('pss.season_id')
            ->limit(2)
            ->get()
            ->toArray();

        // Check for no prior data
        if (count($pastSeasons) < 1) {
            return null;
        }

        $latestSeason = $pastSeasons[0];
        $olderSeason = count($pastSeasons) > 1 ? $pastSeasons[1] : null;

        // Dynamic minimum games
        $minGamesPlayed = max(3, floor($latestSeason->total_games * 0.15));
        if ($latestSeason->games_played < $minGamesPlayed) {
            return null;
        }

        // Handle non-consecutive seasons
        if ($latestSeason->season_id < $currentSeasonId - 1) {
            return null; // Trigger current season eff/per check in hasNotImproved
        }

        // If only one prior season, return null
        if (!$olderSeason) {
            return null;
        }

        // Require similar minutes (within 25%) and minimum games for older season
        $olderMinGamesPlayed = max(3, floor($olderSeason->total_games * 0.15));
        if (
            $olderSeason->games_played < $olderMinGamesPlayed ||
            ($olderSeason->avg_mpg > 0 && abs($latestSeason->avg_mpg - $olderSeason->avg_mpg) / $olderSeason->avg_mpg > 0.25)
        ) {
            return null;
        }

        // Role scores
        $roleScores = [
            'star player' => 5,
            'all star' => 4,
            'starter' => 3,
            'role player' => 2,
            'bench' => 1,
        ];

        $latestRoleScore = $roleScores[$latestSeason->role] ?? 1;
        $olderRoleScore = $roleScores[$olderSeason->role] ?? 1;

        // Per-minute efficiency difference
        $perDiffPct = $olderSeason->per_per_minute > 0 ? ($latestSeason->per_per_minute - $olderSeason->per_per_minute) / $olderSeason->per_per_minute : 0;
        $roleDiff = $latestRoleScore - $olderRoleScore;
        $mpgDiffPct = $olderSeason->avg_mpg > 0 ? ($latestSeason->avg_mpg - $olderSeason->avg_mpg) / $olderSeason->avg_mpg : 0;

        // Injury adjustment (using injuries.php performance_impact approximation)
        $injuryPenalty = $latestSeason->injury_count > 0 ? -0.05 * $latestSeason->injury_count : 0;

        // Age penalty
        $age = $latestSeason->age ?? 25;
        if ($age < 27) {
            $agePenalty = 0;
        } elseif ($age <= 30) {
            $agePenalty = -0.03 * ($age - 27);
        } else {
            $agePenalty = -0.12 - 0.05 * ($age - 30);
        }

        // Calculate improvement index with reduced role weight
        $improvementIndex = ($perDiffPct * 0.65) + ($roleDiff * 0.05) + ($mpgDiffPct * 0.2) + $agePenalty + $injuryPenalty;

        if ($yearsPro < 2 && $improvementIndex < 0) {
            $improvementIndex *= 0.5; // Leniency for young players
        }

        return $improvementIndex;
    }

    private function isRebuildingTeam(int $teamId): bool
    {
        $seasonCount = DB::table('seasons')->count();

        $standings = DB::table('standings_view')
            ->where('team_id', $teamId)
            ->first();

        if (!$standings) return false;

        $wins = (int) ($standings->wins ?? 0);
        $losses = (int) ($standings->losses ?? 0);
        $totalGames = $wins + $losses;

        // Avoid calling a team "rebuilding" too early
        if ($totalGames < 5) return false;

        $scoreDiff = $standings->score_difference ?? 0;
        $chemistry = $standings->chemistry ?? 100;
        $last5 = strtolower($standings->last_5_games ?? '');
        $recentWins = substr_count($last5, 'w');

        // Flags that apply in all seasons
        $flags = 0;
        $flags += $wins < 10 ? 1 : 0;
        $flags += $scoreDiff < -5 ? 1 : 0;
        $flags += $chemistry < 50 ? 1 : 0;
        $flags += $recentWins <= 1 ? 1 : 0;

        // If league has history, add legacy-based flags
        if ($seasonCount > 1) {
            $flags += ($standings->championships ?? 0) == 0 ? 1 : 0;
            $flags += ($standings->playoff_appearances ?? 0) < 2 ? 1 : 0;
        }

        // You can adjust how many flags are needed (3 is safe)
        return $flags >= 3;
    }

    private function getRegularSeasonGameCount(int $seasonId, int $playerId): int
    {
        return (int) (
            DB::table('player_season_stats')
            ->where('season_id', $seasonId)
            ->where('player_id', $playerId)
            ->orderByDesc('id') // get the latest record
            ->value('total_games') ?? 19
        );
    }

    // Add this helper method to get the last round number
    private function getLastRoundNumber()
    {
        return DB::table('schedules')
            ->where('season_id', get_current_season_id())
            ->whereNotIn('round', config('playoffs'))
            ->where(function ($query) {
                $query->whereRaw('round REGEXP \'^[0-9]+$\'')  // Only get numeric rounds
                    ->orWhereRaw('CAST(round AS UNSIGNED) > 0');  // Ensure it can be cast to number
            })
            ->max(DB::raw('CAST(round AS UNSIGNED)'));  // Convert to number before finding max
    }

    private function getPlayerSeasonStats(int $playerId, int $teamId, int $seasonId)
    {
        $stats = DB::table('player_season_stats')
            ->where('player_id', $playerId)
            ->where('season_id', $seasonId)
            ->where('team_id', $teamId)
            ->first();

        return $stats;
    }

    private function totalTeamGames($seasonId, $teamId)
    {

        $gamesPlayedCount = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->count();

        return $gamesPlayedCount;
    }

    /**
     * Check if all rounds have been simulated for the given season.
     *
     * @param int $seasonId
     * @return bool
     */
    private function allRoundsSimulatedForSeason(int $seasonId): bool
    {
        return !Schedules::where('season_id', $seasonId)
            ->where('status', 1)
            ->exists();
    }

    /**
     * Check if a specific round has been simulated for the given season.
     *
     * @param int $seasonId
     * @param int $round
     * @return bool
     */
    private function isRoundSimulated(int $seasonId, $round): bool
    {
        return !Schedules::where('season_id', $seasonId)
            ->where('round', $round)
            ->where('status', 1)
            ->exists();
    }

    private function isRoundSeriesSimulated($seasonId, $round)
    {

        return !DB::table('playoff_series')
            ->where('season_id', $seasonId)
            ->where('round', $round) // Fetch previous round + current round in one query
            ->where('status', 1)
            ->exists();
    }

    private function createGameNewsFromGame($gameId)
    {
        // Fetch game info with winner_id and team details
        $game = DB::table('schedule_view as sv')
            ->select(
                'sv.game_id',
                'sv.season_id',
                'sv.round',
                'sv.home_team_name as home_team',
                'sv.home_id as home_team_id',
                'sv.away_team_name as away_team',
                'sv.away_id as away_team_id',
                'sv.winner_id',
                'sv.winning_name as winner_team',
                'sv.home_score',
                'sv.away_score',
                'sv.conference_id'
            )
            ->where('sv.id', $gameId)
            ->first();

        if (!$game) {
            return;
        }

        // Check if this is the final round of the regular season
        $season = DB::table('seasons')
            ->select('total_regular_games')
            ->where('id', $game->season_id)
            ->first();

        $isLast3Rounds = $season && $game->round >= ($season->total_regular_games - 3);

        // Handle tie case (no winner_id)
        if (!$game->winner_id) {
            $title = "{$game->home_team} and {$game->away_team} Battle to a {$game->home_score}-{$game->away_score} Draw in Round {$game->round}";
            $content = "In a thrilling Round {$game->round} showdown, {$game->home_team} and {$game->away_team} fought to a hard-earned {$game->home_score}-{$game->away_score} tie. Both teams showcased relentless determination, with neither side giving an inch in a match filled with heart-stopping moments. This deadlock keeps both squads hungry for their next chance to claim victory.";

            DB::table('game_news')->insert([
                'game_id'    => $game->game_id,
                'season_id'  => $game->season_id,
                'round'      => $game->round,
                'winner_id'  => null,
                'title'      => $title,
                'content'    => $content,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        // Determine loser and scores
        $loser = $game->winner_id === $game->home_team_id ? $game->away_team : $game->home_team;
        $loserId = $game->winner_id === $game->home_team_id ? $game->away_team_id : $game->home_team_id;
        $winnerScore = $game->winner_id === $game->home_team_id ? $game->home_score : $game->away_score;
        $loserScore = $game->winner_id === $game->home_team_id ? $game->away_score : $game->home_score;
        $scoreMargin = abs($winnerScore - $loserScore);

        // Fetch draft pick for the winning team
        $draftPick = DB::table('drafts as d')
            ->join('players as p', 'd.player_id', '=', 'p.id')
            ->select('p.name as player_name', 'd.round', 'd.pick_number', 'd.season_id as draft_season')
            ->where('d.team_id', $game->winner_id)
            ->where('d.season_id', $game->season_id)
            ->first();

        // Fetch team stats from standings_view for both teams
        $winnerStats = DB::table('standings_view as s')
            ->select('s.wins', 's.losses', 's.home_ppg', 's.score_difference', 's.conference_rank', 's.overall_rank', 's.streak_status', 's.is_defending_champion', 's.next_opponent_name', 's.championships', 's.conference_championships', 's.playoff_appearances')
            ->where('s.team_id', $game->winner_id)
            ->where('s.season_id', $game->season_id)
            ->first();

        $loserStats = DB::table('standings_view as s')
            ->select('s.wins', 's.losses', 's.home_ppg', 's.score_difference', 's.conference_rank', 's.overall_rank', 's.streak_status', 's.is_defending_champion', 's.next_opponent_name', 's.championships', 's.conference_championships', 's.playoff_appearances')
            ->where('s.team_id', $loserId)
            ->where('s.season_id', $game->season_id)
            ->first();

        // Fetch top performer for the winning team
        $topPerformer = DB::table('player_game_stats as pgs')
            ->join('players as p', 'pgs.player_id', '=', 'p.id')
            ->leftJoin('drafts as d', function ($join) use ($game) {
                $join->on('p.id', '=', 'd.player_id')
                    ->where('d.season_id', '=', $game->season_id);
            })
            ->select(
                'p.name as player_name',
                'pgs.points',
                'pgs.rebounds',
                'pgs.assists',
                'pgs.steals',
                'pgs.blocks',
                'd.season_id as draft_season'
            )
            ->where('pgs.game_id', $game->game_id)
            ->where('pgs.team_id', $game->winner_id)
            ->orderBy('pgs.points', 'desc')
            ->orderBy('pgs.rebounds', 'desc')
            ->orderBy('pgs.assists', 'desc')
            ->first();

        // Determine if top performer is a rookie and has a "good" stat line
        $isRookie = $topPerformer && $topPerformer->draft_season == $game->season_id;
        $isGoodStatLine = $topPerformer && (
            ($topPerformer->points >= 20) || // 20+ points
            ($topPerformer->rebounds >= 10) || // 10+ rebounds
            ($topPerformer->assists >= 10) || // 10+ assists
            ($topPerformer->points >= 15 && $topPerformer->rebounds >= 10) || // Double-double
            ($topPerformer->points >= 15 && $topPerformer->assists >= 10)
        );
        $isRareStatLine = $topPerformer && (
            ($topPerformer->points >= 40) || // 40+ points
            ($topPerformer->points >= 10 && $topPerformer->rebounds >= 10 && $topPerformer->assists >= 10) || // Triple-double
            ($topPerformer->rebounds >= 20) || // 20+ rebounds
            ($topPerformer->assists >= 20) || // 20+ assists
            ($topPerformer->steals >= 7) || // 7+ steals
            ($topPerformer->blocks >= 7) // 7+ blocks
        );

        // Determine if this is a top-vs-worst matchup
        $isTopVsWorst = false;
        $maxRank = null;
        if ($winnerStats && $loserStats && $game->conference_id) {
            $maxRank = DB::table('standings_view')
                ->where('conference_id', $game->conference_id)
                ->where('season_id', $game->season_id)
                ->max('conference_rank');
            $isTopVsWorst = ($winnerStats->conference_rank == 1 && $loserStats->conference_rank == $maxRank) ||
                ($loserStats->conference_rank == 1 && $winnerStats->conference_rank == $maxRank);
        }

        // Fetch head-to-head stats
        $headToHead = DB::table('head_to_head as h2h')
            ->select('h2h.win_percentage', 'h2h.points_for')
            ->where('h2h.team_id', $game->winner_id)
            ->where('h2h.opponent_id', $loserId)
            ->first();

        // Fetch team chemistry
        $teamInfo = DB::table('team_season_info as tsi')
            ->select('tsi.chemistry')
            ->where('tsi.team_id', $game->winner_id)
            ->where('tsi.season_id', $game->season_id)
            ->first();

        // Fetch injured players for the game
        $injuredPlayers = DB::table('player_game_stats as pgs')
            ->join('players as p', 'pgs.player_id', '=', 'p.id')
            ->select('p.name as player_name', 'p.injury_type', 'p.injury_recovery_games', 'pgs.team_id')
            ->where('pgs.game_id', $game->game_id)
            ->where('pgs.is_injured', true)
            ->get();

        // Random headline templates
        $headlineTemplates = [];
        if ($winnerStats && preg_match('/W5/', $winnerStats->streak_status)) {
            $headlineTemplates = [
                "{winner} Stuns {loser} {home_score}-{away_score} in Round {round} Thriller, Extends W5 Streak",
                "{winner} Dominates {loser} {home_score}-{away_score} in Round {round} Showdown, Keeps W5 Streak",
            ];
        } elseif ($winnerStats && preg_match('/L5/', $winnerStats->streak_status)) {
            $headlineTemplates = [
                "{winner} Stuns {loser} {home_score}-{away_score} in Round {round} Thriller, Snaps L5 Skid",
                "{winner} Edges Out {loser} {home_score}-{away_score} in Round {round}, Ends L5 Streak",
            ];
        } elseif ($isTopVsWorst) {
            $headlineTemplates = [
                "Conference #1 {winner} Stuns {loser} {home_score}-{away_score} in Round {round} Thriller",
                "{winner} (#1) Dominates Worst-Ranked {loser} {home_score}-{away_score} in Round {round} Showdown",
            ];
        } else {
            // Check if the match impacts the #1 conference spot
            $isNumberOneMatch = ($winnerStats->conference_rank == 1 && $loserStats->conference_rank == 2) || ($winnerStats->conference_rank == 2 && $loserStats->conference_rank == 1);

            if ($winnerStats->is_defending_champion) {
                if ($isNumberOneMatch) {
                    $headlineTemplates = [
                        "Defending Champs {winner} Hold Onto #1 Spot by Edging {loser} {home_score}-{away_score}",
                        "{winner} Secures Top Conference Position with {home_score}-{away_score} Win Over {loser}",
                        "Champion {winner} Maintains #1 Rank After Narrow Victory Against {loser} {home_score}-{away_score}",
                    ];
                } else if ($scoreMargin <= 3) {
                    // Close match, defending champion wins
                    $headlineTemplates = [
                        "Defending Champs {winner} Edge Out {loser} {home_score}-{away_score} in Round {round} Thriller",
                        "Reigning Champs {winner} Squeak Past {loser} {home_score}-{away_score} in Tight Round {round}",
                        "Champion {winner} Survives {loser} {home_score}-{away_score} in Nail-Biting Round {round}",
                        "Defending Champs {winner} Hold Off {loser} {home_score}-{away_score} in Close Round {round}",
                    ];
                } elseif ($scoreMargin <= 7) {
                    // Moderate win, defending champion wins
                    $headlineTemplates = [
                        "Defending Champs {winner} Defeat {loser} {home_score}-{away_score} in Round {round} Victory",
                        "Reigning Champs {winner} Outplay {loser} {home_score}-{away_score} in Round {round} Showdown",
                        "Champion {winner} Secures {home_score}-{away_score} Win Over {loser} in Round {round}",
                        "Defending Champs {winner} Prevail Over {loser} {home_score}-{away_score} in Round {round}",
                    ];
                } else {
                    // Dominant win, defending champion wins
                    $headlineTemplates = [
                        "Defending Champs {winner} Crush {loser} {home_score}-{away_score} in Round {round} Rout",
                        "Reigning Champs {winner} Overwhelm {loser} {home_score}-{away_score} in Round {round} Triumph",
                        "Champion {winner} Demolish {loser} {home_score}-{away_score} in Round {round} Blowout",
                        "Defending Champs {winner} Annihilate {loser} {home_score}-{away_score} in Round {round}",
                    ];
                }
            } elseif ($loserStats->is_defending_champion) {
                if ($isNumberOneMatch) {
                    $headlineTemplates = [
                        "{winner} Shakes Up #1 Spot by Defeating Defending Champs {loser} {home_score}-{away_score}",
                        "Upset Alert: {winner} Tops Defending Champion {loser} {home_score}-{away_score} in Round {round}",
                    ];
                } elseif ($scoreMargin <= 3) {
                    // Close match, defending champion loses
                    $headlineTemplates = [
                        "{winner} Stuns Defending Champs {loser} {home_score}-{away_score} in Round {round} Thriller",
                        "{winner} Upsets Reigning Champs {loser} {home_score}-{away_score} in Tight Round {round}",
                        "{winner} Shocks Champion {loser} {home_score}-{away_score} in Nail-Biting Round {round}",
                        "{winner} Edges Out Defending Champs {loser} {home_score}-{away_score} in Close Round {round}",
                    ];
                } elseif ($scoreMargin <= 7) {
                    // Moderate win, defending champion loses
                    $headlineTemplates = [
                        "{winner} Defeats Reigning Champs {loser} {home_score}-{away_score} in Round {round} Upset",
                        "{winner} Outplays Defending Champs {loser} {home_score}-{away_score} in Round {round} Showdown",
                        "{winner} Secures {home_score}-{away_score} Win Over Champion {loser} in Round {round}",
                        "{winner} Overcomes Defending Champs {loser} {home_score}-{away_score} in Round {round}",
                    ];
                } else {
                    // Dominant win, defending champion loses
                    $headlineTemplates = [
                        "{winner} Crushes Defending Champs {loser} {home_score}-{away_score} in Round {round} Shocker",
                        "{winner} Overwhelms Reigning Champs {loser} {home_score}-{away_score} in Round {round} Rout",
                        "{winner} Demolishes Champion {loser} {home_score}-{away_score} in Round {round} Blowout",
                        "{winner} Annihilates Defending Champs {loser} {home_score}-{away_score} in Round {round} Upset",
                    ];
                }
            } else {
                if ($isNumberOneMatch) {
                    $headlineTemplates = [
                        "{winner} Battles {loser} for #1 Conference Spot, Wins {home_score}-{away_score}",
                        "{winner} Climbs to Top Conference Rank with {home_score}-{away_score} Win Over {loser}",
                    ];
                } elseif ($scoreMargin <= 3) {
                    // Close match, no defending champion
                    $headlineTemplates = [
                        "{winner} Edges Out {loser} {home_score}-{away_score} in Nail-Biting Round {round}",
                        "{winner} Squeaks Past {loser} {home_score}-{away_score} in Tight Round {round} Finish",
                        "{winner} Holds Off {loser} {home_score}-{away_score} in Thrilling Round {round}",
                        "{winner} Survives {loser} {home_score}-{away_score} in Close Round {round} Battle",
                    ];
                } elseif ($scoreMargin <= 7) {
                    // Moderate win, no defending champion
                    $headlineTemplates = [
                        "{winner} Defeats {loser} {home_score}-{away_score} in Solid Round {round} Victory",
                        "{winner} Outplays {loser} {home_score}-{away_score} in Round {round} Showdown",
                        "{winner} Secures {home_score}-{away_score} Win Over {loser} in Round {round}",
                        "{winner} Prevails Over {loser} {home_score}-{away_score} in Round {round} Clash",
                    ];
                } else {
                    // Dominant win, no defending champion
                    $headlineTemplates = [
                        "{winner} Crushes {loser} {home_score}-{away_score} in Round {round} Rout",
                        "{winner} Overwhelms {loser} {home_score}-{away_score} in Dominant Round {round}",
                        "{winner} Demolishes {loser} {home_score}-{away_score} in Round {round} Blowout",
                        "{winner} Annihilates {loser} {home_score}-{away_score} in Round {round} Triumph",
                    ];
                }
            }
        }

        // Conditional content starters
        $contentStarters = [];
        if ($scoreMargin > 15) {
            if ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "Rookie sensation {player} erupted for {points} points, {rebounds} rebounds, and {assists} assists, leading {winner} to a {home_score}-{away_score} rout of {loser} in Round {round}.",
                    "First-year star {player}’s {points}-point, {rebounds}-rebound performance powered {winner} to a {home_score}-{away_score} blowout over {loser} in Round {round}.",
                    "Rookie {player} dazzled with {points} points and {assists} assists, fueling {winner}’s {home_score}-{away_score} domination of {loser} in Round {round}.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "Led by {player}’s {points} points, {rebounds} rebounds, and {assists} assists, {winner} obliterated {loser} {home_score}-{away_score} in a Round {round} beatdown.",
                    "{player}’s {points}-point explosion fueled {winner}’s {home_score}-{away_score} rout of {loser} in Round {round}.",
                    "With {player} dropping {points} points and {assists} assists, {winner} dominated {loser} {home_score}-{away_score} in Round {round}.",
                ];
            } else {
                $contentStarters = [
                    "In a Round {round} beatdown, {winner} obliterated {loser} with a commanding {home_score}-{away_score} scoreline.",
                    "{winner} unleashed a Round {round} onslaught, crushing {loser} {home_score}-{away_score} in a one-sided affair.",
                    "Round {round} saw {winner} dominate {loser} {home_score}-{away_score}, leaving no doubt about their superiority.",
                ];
            }
        } elseif ($scoreMargin <= 5) {
            if ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "Rookie {player}’s {points} points, {rebounds} rebounds, and {assists} assists lifted {winner} to a thrilling {home_score}-{away_score} win over {loser} in Round {round}.",
                    "First-year standout {player} delivered {points} points and {assists} assists, guiding {winner} to a {home_score}-{away_score} nail-biter over {loser} in Round {round}.",
                    "Rookie sensation {player} shone with {points} points and {rebounds} rebounds, pushing {winner} past {loser} {home_score}-{away_score} in Round {round}.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "{player}’s {points} points, {rebounds} rebounds, and {assists} assists propelled {winner} to a thrilling {home_score}-{away_score} win over {loser} in Round {round}.",
                    "In a heart-stopping Round {round} clash, {winner}, led by {player}’s {points} points and {assists} assists, edged out {loser} {home_score}-{away_score}.",
                    "{player}’s {points}-point, {rebounds}-rebound performance powered {winner} past {loser} {home_score}-{away_score} in Round {round}.",
                ];
            } else {
                $contentStarters = [
                    "In a heart-stopping Round {round} clash, {winner} edged out {loser} {home_score}-{away_score} in a nail-biting finish.",
                    "{winner} survived a Round {round} thriller, squeaking past {loser} {home_score}-{away_score} in a photo finish.",
                    "Round {round} delivered drama as {winner} narrowly defeated {loser} {home_score}-{away_score} in a tense battle.",
                ];
            }
        } else {
            if ($isRookie && $isGoodStatLine && $topPerformer) {
                $contentStarters = [
                    "Rookie {player} led with {points} points, {rebounds} rebounds, and {assists} assists, driving {winner} to a {home_score}-{away_score} victory over {loser} in Round {round}.",
                    "First-year star {player}’s {points} points and {assists} assists sparked {winner} to a {home_score}-{away_score} win over {loser} in Round {round}.",
                    "Rookie sensation {player} delivered {points} points and {rebounds} rebounds, powering {winner} to a {home_score}-{away_score} triumph over {loser} in Round {round}.",
                ];
            } elseif ($isRareStatLine && $topPerformer) {
                $contentStarters = [
                    "{player}’s {points} points, {rebounds} rebounds, and {assists} assists led {winner} to a {home_score}-{away_score} victory over {loser} in Round {round}.",
                    "With {player} posting {points} points and {assists} assists, {winner} secured a {home_score}-{away_score} win over {loser} in Round {round}.",
                    "{player}’s {points}-point, {rebounds}-rebound effort powered {winner} to a {home_score}-{away_score} triumph over {loser} in Round {round}.",
                ];
            } else {
                $contentStarters = [
                    "In an electrifying Round {round} matchup, {winner} showcased their prowess, securing a {home_score}-{away_score} win over {loser}.",
                    "Round {round} delivered a spectacle as {winner} clinched a {home_score}-{away_score} victory against {loser}.",
                    "Fans were treated to a Round {round} thriller as {winner} powered past {loser} with a {home_score}-{away_score} scoreline.",
                ];
            }
        }

        // Conditional content middles, prioritizing streak_status
        $contentMiddles = [];
        if ($winnerStats && preg_match('/W(\d+)/', $winnerStats->streak_status, $matches) && $matches[1] >= 3) {
            $streakCount = $matches[1];
            $contentMiddles = [
                "{winner} extended their {$streakCount}-game winning streak, overpowering {loser} with relentless precision.",
                "Riding a {$streakCount}-game win streak, {winner} showcased their unstoppable form against {loser}.",
            ];
        } elseif ($winnerStats && preg_match('/L(\d+)/', $winnerStats->streak_status, $matches) && $matches[1] >= 3) {
            $streakCount = $matches[1];
            $contentMiddles = [
                "Snapping a {$streakCount}-game losing streak, {winner} roared back to form with a commanding performance against {loser}.",
                "{winner} ended a {$streakCount}-game skid, outmuscling {loser} in a much-needed victory.",
            ];
        } elseif ($winnerStats && $winnerStats->score_difference > 50) {
            $contentMiddles = [
                "With a {score_difference}-point score differential this season, {winner}’s dominance was on full display, overwhelming {loser}’s defense.",
                "{winner}’s season-long {score_difference}-point edge proved too much for {loser}, who struggled to keep pace.",
            ];
        } elseif ($headToHead && $headToHead->win_percentage > 0.6) {
            $contentMiddles = [
                "Boasting a {win_percentage}% win rate against {loser} in head-to-head matchups, {winner} capitalized on their historical edge.",
                "{winner} continued their {win_percentage}% head-to-head dominance over {loser}, dictating the game’s tempo.",
            ];
        } elseif ($teamInfo && $teamInfo->chemistry > 80) {
            $contentMiddles = [
                "{winner}’s team chemistry, rated at {chemistry}, fueled seamless coordination that left {loser} scrambling.",
                "With {chemistry} chemistry, {winner} executed with precision, outmaneuvering {loser} at every turn.",
            ];
        } else {
            if ($isLast3Rounds) {
                // Upset scenario: lower-ranked team beats a higher-ranked team
                if ($winnerStats->conference_rank > $loserStats->conference_rank) {
                    $contentMiddles[] = "In a stunning upset, #{$winnerStats->conference_rank} {$game->winner_team} toppled #{$loserStats->conference_rank} {$loser} in Round {$game->round}, shaking up the playoff picture!";
                }

                // Winner beats a defending champion
                if ($loserStats->is_defending_champion) {
                    $contentMiddles[] = "{$game->winner_team} pulled off a monumental win over the defending champions, {$loser}, potentially altering postseason hopes!";
                }

                // Winner is defending champion themselves
                if ($winnerStats->is_defending_champion) {
                    $contentMiddles[] = "{$game->winner_team}, the defending champions, showed they can still dominate, holding a #{$winnerStats->conference_rank} conference rank.";
                }

                // Playoff contention for non-defending champions
                if (!$winnerStats->is_defending_champion) {
                    if ($winnerStats->conference_rank <= 4) {
                        $contentMiddles[] = "{$game->winner_team} strengthens their playoff position with a #{$winnerStats->conference_rank} conference rank.";
                    } elseif ($winnerStats->conference_rank <= 10) {
                        $contentMiddles[] = "{$game->winner_team} is battling for a play-in spot, currently sitting at #{$winnerStats->conference_rank} in the conference.";
                    } else {
                        $contentMiddles[] = "{$game->winner_team} continues fighting, but with a #{$winnerStats->conference_rank} rank, postseason hopes are slim.";
                    }
                }
            } else {
                // Early/mid-season news
                $contentMiddles[] = "{$game->winner_team} improved their standing with a Round {$game->round} victory, now holding #{$winnerStats->conference_rank} in the conference.";
            }
        }

        // Content enders
        // Base statements
        // Conditional enhancements
        $contentEnders = [];
        if ($winnerStats->is_defending_champion) {
            $contentEnders[] = "{$game->winner_team} proves they can defend their crown with another solid win.";
        }

        if ($loserStats->is_defending_champion) {
            $contentEnders[] = "{$game->winner_team} pulls off a statement win over the defending champion, shaking up the standings.";
        }

        if ($winnerStats->streak_status && preg_match('/W(\d+)/', $winnerStats->streak_status, $matches) && $matches[1] >= 3) {
            $streak = $matches[1];
            $contentEnders[] = "{$game->winner_team} extends their {$streak}-game winning streak, gaining momentum for the postseason.";
        }

        if ($isLast3Rounds) {
            // Winner is a top contender in the conference
            if ($winnerStats->conference_rank <= 6) {
                $contentEnders[] = "{$game->winner_team} keeps their playoff hopes alive, defeating {$loser} as the regular season nears its end.";
            }
            // Winner is a low-ranked team
            else {
                $contentEnders[] = "Despite being lower in the conference standings, {$game->winner_team} earns a crucial win against {$loser}, keeping their faint playoff hopes alive.";
            }

            // Loser is a top contender — now in danger
            if ($loserStats->conference_rank <= 6 && $loserStats->conference_rank > $winnerStats->conference_rank) {
                $contentEnders[] = "The loss puts {$loser} in danger of dropping out of playoff contention, while {$game->winner_team} climbs in the conference race.";
            }

            // Upset vs defending champion
            if ($loserStats->is_defending_champion) {
                $contentEnders[] = "{$game->winner_team} shocks the defending champion {$loser}, potentially altering the playoff picture in their conference!";
            }
        } else {
            // Case 2: Not last 3 rounds — regular season news
            // Winner is defending champion
            if ($winnerStats->is_defending_champion) {
                $contentEnders[] = "{$game->winner_team}, the defending champion, continues to assert their dominance in the conference.";
            }
            // Winner is top-ranked
            elseif ($winnerStats->conference_rank == 1) {
                $contentEnders[] = "{$game->winner_team} remains atop their conference, next facing {$winnerStats->next_opponent_name}.";
            }
            // Winner is mid-ranked (playoff contender)
            elseif ($winnerStats->conference_rank >= 2 && $winnerStats->conference_rank <= 6) {
                $contentEnders[] = "The win keeps {$game->winner_team} in strong playoff contention, aiming for a higher seed.";
            }
            // Winner is low-ranked (struggling)
            else {
                $contentEnders[] = "Despite their lower ranking, {$game->winner_team} secures a vital victory to stay competitive in the conference.";
            }

            // Upset scenarios
            if ($loserStats->is_defending_champion && $winnerStats->conference_rank > $loserStats->conference_rank) {
                $contentEnders[] = "{$game->winner_team} pulls off an upset over defending champion {$loser}, shaking up the playoff picture.";
            }
            // Non-champion upset: low-rank team beats higher-rank team
            elseif ($winnerStats->conference_rank > $loserStats->conference_rank) {
                $contentEnders[] = "In a surprising result, lower-ranked {$game->winner_team} defeats higher-ranked {$loser}, making waves in the conference standings.";
            }
        }
        // Highlight experienced teams
        if ($winnerStats->playoff_appearances > 5) {
            $contentEnders[] = "Veteran experience shines as {$game->winner_team} adds another regular season win to their impressive track record.";
        }

        if ($winnerStats->championships > 0) {
            $contentEnders[] = "With past championships fueling their confidence, {$game->winner_team} remains a team to watch this season.";
        }

        // Select random phrases
        $title = str_replace(
            ['{winner}', '{loser}', '{home_score}', '{away_score}', '{round}', '{player}'],
            [$game->winner_team, $loser, $game->home_score, $game->away_score, $game->round, $draftPick ? $draftPick->player_name : ''],
            $headlineTemplates[array_rand($headlineTemplates)]
        );

        // Build content with random segments
        $content = str_replace(
            ['{round}', '{winner}', '{loser}', '{home_score}', '{away_score}', '{player}', '{points}', '{rebounds}', '{assists}'],
            [
                $game->round,
                $game->winner_team,
                $loser,
                $game->home_score,
                $game->away_score,
                $topPerformer ? $topPerformer->player_name : ($draftPick ? $draftPick->player_name : ''),
                $topPerformer ? $topPerformer->points : 'N/A',
                $topPerformer ? $topPerformer->rebounds : 'N/A',
                $topPerformer ? $topPerformer->assists : 'N/A',
            ],
            $contentStarters[array_rand($contentStarters)]
        ) . ' ' . str_replace(
            ['{winner}', '{loser}', '{ppg}', '{win_percentage}', '{chemistry}', '{score_difference}', '{wins}', '{losses}', '{conference_rank}'],
            [
                $game->winner_team,
                $loser,
                $winnerStats ? number_format($winnerStats->home_ppg, 1) : 'N/A',
                $headToHead ? number_format($headToHead->win_percentage * 100, 1) : 'N/A',
                $teamInfo ? $teamInfo->chemistry : 'N/A',
                $winnerStats ? $winnerStats->score_difference : 'N/A',
                $winnerStats ? $winnerStats->wins : 'N/A',
                $winnerStats ? $winnerStats->losses : 'N/A',
                $winnerStats ? $winnerStats->conference_rank : 'N/A',
            ],
            $contentMiddles[array_rand($contentMiddles)]
        ) . ' ' . str_replace(
            ['{winner}', '{loser}', '{player}'],
            [$game->winner_team, $loser, $draftPick ? $draftPick->player_name : ''],
            $contentEnders[array_rand($contentEnders)]
        );

        // Build injury content
        $injuryContent = '';
        $winnerInjured = $injuredPlayers->where('team_id', $game->winner_id);
        $loserInjured = $injuredPlayers->where('team_id', $loserId);

        if ($winnerInjured->count() > 0) {
            $winnerInjuryList = $winnerInjured->map(function ($player) {
                $injuryType = $player->injury_type
                    ? ucwords(str_replace('_', ' ', $player->injury_type))
                    : 'Unknown Injury';
                $recoveryStatus = ($player->injury_recovery_games == 0 || is_null($player->injury_type))
                    ? 'expected to return next game'
                    : 'out for ' . $player->injury_recovery_games . ' games';
                return $player->player_name . ' (' . $injuryType . ', ' . $recoveryStatus . ')';
            })->implode(', ');
            $injuryContent .= " Despite injuries to $winnerInjuryList, {$game->winner_team} prevailed.";
        }

        if ($loserInjured->count() > 0) {
            $loserInjuryList = $loserInjured->map(function ($player) {
                $injuryType = $player->injury_type
                    ? ucwords(str_replace('_', ' ', $player->injury_type))
                    : 'Unknown Injury';
                $recoveryStatus = ($player->injury_recovery_games == 0 || is_null($player->injury_type))
                    ? 'expected to return next game'
                    : 'out for ' . $player->injury_recovery_games . ' games';
                return $player->player_name . ' (' . $injuryType . ', ' . $recoveryStatus . ')';
            })->implode(', ');
            $injuryContent .= " {$loser} was hampered by injuries to $loserInjuryList.";
        }

        // Append injury content to main content
        $content .= $injuryContent;

        // Insert into game_news with winner_id
        DB::table('game_news')->insert([
            'game_id'    => $game->game_id,
            'season_id'  => $game->season_id,
            'round'      => $game->round,
            'winner_id'  => $game->winner_id,
            'title'      => $title,
            'content'    => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
