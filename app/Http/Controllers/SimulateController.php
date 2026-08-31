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
// use App\Http\Controllers\AwardsController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\TeamBalanceController;
use App\Http\Controllers\TeamRoleController;
use App\Http\Controllers\TeamStreakController;
use App\Http\Controllers\TeamManagementController;
use App\Http\Controllers\PlayerStatsController;
use App\Http\Controllers\TeamStatsController;
use App\Http\Controllers\FreeAgentController;
use App\Http\Controllers\PlayoffStatsController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\HelperController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SimulateController extends Controller
{
    protected $storeStats;
    protected $contract;
    protected $teamBalance;
    protected $teamRole;
    protected $teamManagement;
    protected $playerStats;
    protected $teamStats;
    protected $teamStreak;
    protected $freeAgent;
    protected $playOffStats;
    protected $helper;
    protected $news;
    protected $archive;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        // $this->storeStats = new AwardsController();
        $this->contract = new ContractController();
        $this->teamBalance = new TeamBalanceController();
        $this->teamRole = new TeamRoleController();
        $this->teamManagement = new TeamManagementController();
        $this->playOffStats = new PlayoffStatsController();
        $this->playerStats = new PlayerStatsController();
        $this->teamStats = new TeamStatsController();
        $this->teamStreak = new TeamStreakController();
        $this->freeAgent = new FreeAgentController();
        $this->news = new NewsController();
        $this->archive = new ArchiveController();
        $this->helper = new HelperController();
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

        $this->teamManagement->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);
        $this->teamManagement->updateSeasonTeamChemistryBeforeGame($gameData->away_team_id);

        //check if home team is injury depleted
        $homeTeamInjuries = DB::table('players')
            ->where('team_id', $gameData->home_team_id)
            ->where('is_injured', true)
            ->get();

        if ($homeTeamInjuries->count() > 5) {
            //run the fire leoparad rule
            $initiateFLRule = $this->teamManagement->fireLeopardRule($gameData->home_team_id);
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
            $initiateFLRule =  $this->teamManagement->fireLeopardRule($gameData->away_team_id);
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
        $homeTeamPlayers = $this->teamStats->getActivePlayersSorted($gameData->home_team_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->teamStats->getActivePlayersSorted($gameData->away_team_id, $rolePriority, $gameData->round);

        // Initialize arrays to hold player game stats and minutes
        $playerGameStats = [];

        // Distribute minutes to players considering injury status
        $homeMinutes = $this->playerStats->distributeMinutes($homeTeamPlayers, $totalMinutes, $request->schedule_id);
        $awayMinutes = $this->playerStats->distributeMinutes($awayTeamPlayers, $totalMinutes, $request->schedule_id);

        $homeChemistry =  $this->teamStats->getTeamChemistry($currentSeasonId, $gameData->home_team_id);
        $awayChemistry =  $this->teamStats->getTeamChemistry($currentSeasonId, $gameData->away_team_id);
        // Simulate player game stats for home team
        // Simulate home team player stats with detailed shooting metrics
        foreach ($homeTeamPlayers as $player) {
            $minutes = (float) $homeMinutes[$player->id];
            if ($minutes === 0 || $player->is_injured == 1) {
                $playerGameStats[] = $this->playerStats->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->playerStats->calculatePerformanceFactor($player);
            $defensiveImpact =  $this->playerStats->calculateDefensiveImpact($gameData->away_team_id);

            $turnovers =  $this->playerStats->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls =  $this->playerStats->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats =  $this->playerStats->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $homeChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points =  $this->playerStats->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds =  $this->playerStats->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks =  $this->playerStats->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals =  $this->playerStats->calculateSteals($player, $minutes, $performanceFactor, $fouls);

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
            if ($minutes === 0 || $player->is_injured == 1) {
                $playerGameStats[] = $this->playerStats->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->playerStats->calculatePerformanceFactor($player);
            $defensiveImpact =  $this->playerStats->calculateDefensiveImpact($gameData->away_team_id);

            $turnovers =  $this->playerStats->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls =  $this->playerStats->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats =  $this->playerStats->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $awayChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points =  $this->playerStats->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds =  $this->playerStats->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks =  $this->playerStats->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals =  $this->playerStats->calculateSteals($player, $minutes, $performanceFactor, $fouls);

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
        $this->playerStats->updateSeasonStats($playerGameStats, $gameData, true);

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
            $this->playOffStats->updateConferenceChampions($gameData, $winnerId);
        }
        if ($gameData->round === 'finals') {
            // Find the MVP of the winning team
            $this->playOffStats->updateFinalsWinner($gameData, $winnerId, $homeScore, $awayScore);
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
        $isRoundsSimulatedForSeason = $this->helper->isRoundSimulated($currentSeasonId, $gameData->round);
        $transactionCount = $this->helper->getTransferTransactionCount();

        $this->teamRole->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
        $this->teamRole->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

        $this->teamManagement->updateInjuryAndWaiving($gameData->home_team_id);
        $this->teamManagement->updateInjuryAndWaiving($gameData->away_team_id);

        $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->home_team_id, $winnerId);
        $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->away_team_id, $winnerId);
        
        $this->teamStreak->updateTeamStreaks($gameData->id);
        $this->teamStats->updateHeadToHeadResults($gameData->id);
        
        $this->playOffStats->updatePlayoffAppearancesForGame($gameData);
        
        $this->news->createGameNewsFromGame($gameData->id);

        if ($isRoundsSimulatedForSeason) {
            $this->freeAgent->updateInjuryFreeAgents();
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
            'schedule' => $schedule,
            'transaction_count' => $transactionCount
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

        $currentSeasonId = get_current_season_id();

        // $season = Seasons::find($currentSeasonId);

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

        $this->teamManagement->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);
        $this->teamManagement->updateSeasonTeamChemistryBeforeGame($gameData->away_team_id);

        //check if home team is injury depleted
        $homeTeamInjuries = DB::table('players')
            ->where('team_id', $gameData->home_team_id)
            ->where('is_injured', true)
            ->get();

        if ($homeTeamInjuries->count() > 5) {
            //run the fire leoparad rule
            $initiateFLRule = $this->teamManagement->fireLeopardRule($gameData->home_team_id);
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
            $initiateFLRule =  $this->teamManagement->fireLeopardRule($gameData->away_team_id);
            // return response()->json([
            //     'error' => true,
            //     'fire' =>  $initiateFLRule,
            //     'injured' => $awayTeamInjuries->count(),
            //     'team' => $gameData->away_team_id,
            //     'message' => $gameData->away_team_name.' team is injury depleted!.Cant proceed, game postponed!',
            // ], 400);
        }

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
        $homeTeamPlayers = $this->teamStats->getActivePlayersSorted($gameData->home_team_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->teamStats->getActivePlayersSorted($gameData->away_team_id, $rolePriority, $gameData->round);

        // Initialize arrays to hold player game stats and minutes
        $playerGameStats = [];

        // Distribute minutes to players considering injury status
        $homeMinutes = $this->playerStats->distributeMinutes($homeTeamPlayers, $totalMinutes, $request->schedule_id);
        $awayMinutes = $this->playerStats->distributeMinutes($awayTeamPlayers, $totalMinutes, $request->schedule_id);

        $homeChemistry =  $this->teamStats->getTeamChemistry($currentSeasonId, $gameData->home_team_id);
        $awayChemistry =  $this->teamStats->getTeamChemistry($currentSeasonId, $gameData->away_team_id);
        // Simulate player game stats for home team
        // Simulate home team player stats with detailed shooting metrics
        foreach ($homeTeamPlayers as $player) {
            $minutes = (float) $homeMinutes[$player->id];
            if ($minutes === 0 || $player->is_injured == 1) {
                $playerGameStats[] = $this->playerStats->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->playerStats->calculatePerformanceFactor($player);
            $defensiveImpact =  $this->playerStats->calculateDefensiveImpact($gameData->away_team_id);

            $turnovers =  $this->playerStats->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls =  $this->playerStats->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats =  $this->playerStats->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $homeChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points =  $this->playerStats->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds =  $this->playerStats->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks =  $this->playerStats->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals =  $this->playerStats->calculateSteals($player, $minutes, $performanceFactor, $fouls);

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
            if ($minutes === 0 || $player->is_injured == 1) {
                $playerGameStats[] = $this->playerStats->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->playerStats->calculatePerformanceFactor($player);
            $defensiveImpact =  $this->playerStats->calculateDefensiveImpact($gameData->away_team_id);

            $turnovers =  $this->playerStats->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls =  $this->playerStats->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats =  $this->playerStats->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $awayChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points =  $this->playerStats->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds =  $this->playerStats->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks =  $this->playerStats->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals =  $this->playerStats->calculateSteals($player, $minutes, $performanceFactor, $fouls);

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
        function distributeAssistsPlayoffsSeries(&$playerGameStats, $teamId, $maxAssists, &$assistsAssigned)
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
        distributeAssistsPlayoffsSeries($playerGameStats, $gameData->home_team_id, $maxHomeAssists, $homeAssistsAssigned);

        // Distribute assists for the away team
        distributeAssistsPlayoffsSeries($playerGameStats, $gameData->away_team_id, $maxAwayAssists, $awayAssistsAssigned);

        // Clear reference
        // unset($stats);

        // Update or insert player game stats
        $this->playerStats->updateSeasonStats($playerGameStats, $gameData, true);

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
            $this->playerStats->updateSeasonStats($playerGameStats, $gameData, true);
            
            $this->teamRole->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
            $this->teamRole->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

            $this->teamManagement->updateInjuryAndWaiving($gameData->home_team_id);
            $this->teamManagement->updateInjuryAndWaiving($gameData->away_team_id);

            $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->home_team_id, $winnerId);
            $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->away_team_id, $winnerId);
            
            $this->teamStreak->updateTeamStreaks($gameData->id);

            $this->teamStats->updateHeadToHeadResults($gameData->id);

            $isRoundsSimulatedForSeason = $this->helper->isRoundSimulated($currentSeasonId,  $gameData->round);
            $isRoundSeriesSimulatedForSeason = $this->helper->isRoundSeriesSimulated($currentSeasonId,  $gameData->round);
            if (!$isRoundSeriesSimulatedForSeason) {
                $this->playOffStats->updateSeriesAndSchedule($gameData, $winnerId);
            }
            if ($isRoundsSimulatedForSeason) {
                $this->freeAgent->updateInjuryFreeAgents();
            }

            if ($gameData->round === 'semi_finals') {
                $this->playOffStats->updateSeriesConferenceChampions($gameData);
            }
            if ($gameData->round === 'finals') {
                $this->playOffStats->updateSeriesFinalsWinner($gameData);
            }

            $this->playOffStats->updatePlayoffSeriesAppearancesForGame($gameData);

            $this->news->createGameNewsFromGame($gameData->id);

        });

        $gameNews = DB::table('game_news')
            ->select('id', 'game_id', 'season_id', 'round', 'title', 'content', 'created_at', 'updated_at')
            ->where('game_id', $gameData->game_id)
            ->first();

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
            'series' => $seriesResponse,
            'news' => $gameNews,
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

        $currentSeasonId = get_current_season_id();

        $season = Seasons::find($currentSeasonId);

        $isGameFinished = DB::table('schedules')
            ->where('id', $request->schedule_id)
            ->where('status', 2)  // Fetch previous round and current round in one query
            ->exists(); // Use exists() for a boolean result

        if ($isGameFinished) {
            return response()->json([
                'message' => 'Game already simulated!',
            ], 400); // 400 - Bad Request is more appropriate for this scenario
        }

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

        $this->teamBalance->fixTeamPositionBalance($gameData->home_team_id,false);
        $this->teamBalance->fixTeamPositionBalance($gameData->away_team_id,false);

        if($season->status == 2){
            $this->teamBalance->signPlayerOffWaiver($gameData->home_team_id);
            $this->teamBalance->signPlayerOffWaiver($gameData->away_team_id);
        }

        $this->teamManagement->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);
        $this->teamManagement->updateSeasonTeamChemistryBeforeGame($gameData->away_team_id);
        //check first to balance team positions
        //check if home team is injury depleted
        $homeTeamInjuries = DB::table('players')
            ->where('team_id', $gameData->home_team_id)
            ->where('is_injured', true)
            ->get();

        if ($homeTeamInjuries->count() > 5) {
            //run the fire leoparad rule
            $initiateFLRule = $this->teamManagement->fireLeopardRule($gameData->home_team_id);
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
            $initiateFLRule =  $this->teamManagement->fireLeopardRule($gameData->away_team_id);
            // return response()->json([
            //     'error' => true,
            //     'fire' =>  $initiateFLRule,
            //     'injured' => $awayTeamInjuries->count(),
            //     'team' => $gameData->away_team_id,
            //     'message' => $gameData->away_team_name.' team is injury depleted!.Cant proceed, game postponed!',
            // ], 400);
        }

        $rolePriority = [
            'star player' => 1,
            'all star' => 2,
            'starter' => 2,
            'role player' => 5,
            'bench' => 5,
        ];
        $totalMinutes = 240;

        // Fetching sorted active players for both teams
        $homeTeamPlayers = $this->teamStats->getActivePlayersSorted($gameData->home_team_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->teamStats->getActivePlayersSorted($gameData->away_team_id, $rolePriority, $gameData->round);


        $playerGameStats = [];
        $homeMinutes = $this->playerStats->distributeMinutes($homeTeamPlayers, $totalMinutes, $request->schedule_id);
        $awayMinutes = $this->playerStats->distributeMinutes($awayTeamPlayers, $totalMinutes, $request->schedule_id);

        $homeChemistry =  $this->teamStats->getTeamChemistry($currentSeasonId, $gameData->home_team_id);
        $awayChemistry =  $this->teamStats->getTeamChemistry($currentSeasonId, $gameData->away_team_id);
        // Simulate home team player stats with detailed shooting metrics
        foreach ($homeTeamPlayers as $player) {
            $minutes = (float) $homeMinutes[$player->id];
            if ($minutes === 0 || $player->is_injured == 1) {
                $playerGameStats[] = $this->playerStats->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->playerStats->calculatePerformanceFactor($player);
            $defensiveImpact =  $this->playerStats->calculateDefensiveImpact($gameData->away_team_id);

            $turnovers =  $this->playerStats->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls =  $this->playerStats->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats =  $this->playerStats->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $homeChemistry, true, true);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];

            $points =  $this->playerStats->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds =  $this->playerStats->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks =  $this->playerStats->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals =  $this->playerStats->calculateSteals($player, $minutes, $performanceFactor, $fouls);

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
            if ($minutes === 0 || $player->is_injured == 1) {
                $playerGameStats[] = $this->playerStats->createInactivePlayerStats($player, $gameData, $currentSeasonId);
                continue;
            }

            $performanceFactor = $this->playerStats->calculatePerformanceFactor($player);
            $defensiveImpact =  $this->playerStats->calculateDefensiveImpact($gameData->away_team_id);

            $turnovers =  $this->playerStats->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
            $fouls =  $this->playerStats->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);


            $shotStats =  $this->playerStats->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $awayChemistry, true, false);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];


            $points =  $this->playerStats->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds =  $this->playerStats->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks =  $this->playerStats->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals =  $this->playerStats->calculateSteals($player, $minutes, $performanceFactor, $fouls);

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
        $this->playerStats->updateSeasonStats($playerGameStats, $gameData, false);

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
        $allRoundsSimulatedForSeason =  $this->helper->allRoundsSimulatedForSeason($currentSeasonId);

        // check if round games is simulated
        $isRoundsSimulatedForSeason = $this->helper->isRoundSimulated($currentSeasonId,  $gameData->round);

        $transactionCount = $this->helper->getTransferTransactionCount();

        $this->teamRole->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
        $this->teamRole->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

        $this->teamManagement->updateInjuryAndWaiving($gameData->home_team_id);
        $this->teamManagement->updateInjuryAndWaiving($gameData->away_team_id);

        $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->home_team_id, $gameData->winner_id);
        $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->away_team_id, $gameData->winner_id);

        $this->teamStreak->updateTeamStreaks($gameData->id);

        $this->teamStats->updateHeadToHeadResults($gameData->id);

        $this->news->createGameNewsFromGame($gameData->id);

        if ($isRoundsSimulatedForSeason) {
            $this->freeAgent->updateInjuryFreeAgents();
        }

        if ($allRoundsSimulatedForSeason) {
            // Update the season's status to 2
            if ($season) {
                $season->status = 2;
                $season->save();

                $this->archive->archiveStandingViewTable();
                $this->playOffStats->updatePlayoffQualifiedFlags();
            }
        }

        // Commit the transaction
        DB::commit();

        // $gameResult = $this->getBoxScore($gameData->game_id);

        // Return the simulation result
        return response()->json([
            'message' => 'Game simulated successfully',
            'game_id' => $gameData->game_id,
            'season_status' => $season->status,
            'round' => $gameData->round,
            'transaction_count' => $transactionCount,
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

}
