<?php

namespace App\Services\Game;

ini_set('max_execution_time', 0); // Unlimited execution time

use Illuminate\Http\Request;
use App\Models\PlayerGameStats;
use App\Models\Schedules;
use App\Models\Seasons;
use App\Services\Archive\ArchiveService;
use App\Services\Contract\ContractService;
use App\Services\Helper\HelperService;
use App\Services\League\NewsService;
use App\Services\Player\FreeAgencyService;
use App\Services\Stats\PlayerCareerStatsService;
use App\Services\Stats\PlayoffStatsService;
use App\Services\Stats\PlayerStatsService;
use App\Services\Team\TeamManagementService;
use App\Services\Team\TeamRoleService;
use App\Services\Team\TeamStatsService;
use App\Services\Team\TeamStreakService;
use Illuminate\Support\Facades\DB;

class GameEngineService
{
    protected $storeStats;
    protected $contract;
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
    protected $career;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        // $this->storeStats = new AwardsController();
        $this->contract = new ContractService();
        $this->teamRole = new TeamRoleService();
        $this->teamManagement = new TeamManagementService();
        $this->playOffStats = new PlayoffStatsService();
        $this->playerStats = new PlayerStatsService();
        $this->teamStats = new TeamStatsService();
        $this->teamStreak = new TeamStreakService();
        $this->freeAgent = new FreeAgencyService();
        $this->news = new NewsService();
        $this->archive = new ArchiveService();
        $this->career = new PlayerCareerStatsService();
        $this->helper = new HelperService();
    }

    public function startRegularGame(string $scheduleId, $totalMinutes = 240)
    {

        $currentSeasonId = get_current_season_id();
        
        $gameData = $this->gameDataInfo($scheduleId);

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

        $formattedPlayerGameStats = $this->runGame($scheduleId, $gameData,$totalMinutes);
      
        $this->playerStats->updateSeasonStats($formattedPlayerGameStats, false);
        $this->career->recordPlayerCareerHigh($formattedPlayerGameStats,$gameData);

        $this->teamRole->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
        $this->teamRole->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

        return [
            'game_info' => $gameData,
        ];

    }

    public function startPlayoffNonSeriesGame(string $scheduleId, $totalMinutes = 240)
    {

        $currentSeasonId = get_current_season_id();
        
        $gameData = $this->gameDataInfo($scheduleId);

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
 
        $formattedPlayerGameStats = $this->runGame($scheduleId, $gameData,$totalMinutes);

        $this->playerStats->updateSeasonStats($formattedPlayerGameStats, true);
        $this->career->recordPlayerCareerHigh($formattedPlayerGameStats,$gameData);

        $this->teamRole->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
        $this->teamRole->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);
        
        return [
            'game_info' => $gameData
        ];

    }

    public function startPlayoffSeriesGame(string $scheduleId, $totalMinutes = 240)
    {

        $currentSeasonId = get_current_season_id();
        
        $gameData = $this->gameDataInfo($scheduleId);

         // Fetch game data
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

        //core of the game
        $formattedPlayerGameStats = $this->runGame($scheduleId, $gameData,$totalMinutes);

        $this->playerStats->updateSeasonStats($formattedPlayerGameStats, true);
        $this->career->recordPlayerCareerHigh($formattedPlayerGameStats,$gameData);

        $this->teamRole->updateTeamRolesBasedOnStats($gameData->home_team_id, $gameData->round);
        $this->teamRole->updateTeamRolesBasedOnStats($gameData->away_team_id, $gameData->round);

        return [
            'game_info' => $gameData
        ];

    }


    private function runGame($scheduleId, $gameData, $totalMinutes = 240){

        $this->insertGameQuarterBreakDown($gameData->game_id,$gameData->home_team_id,$gameData->season_id);
        $this->insertGameQuarterBreakDown($gameData->game_id,$gameData->away_team_id,$gameData->season_id);

        $this->teamManagement->resetFatigue($gameData->home_team_id);
        $this->teamManagement->resetFatigue($gameData->away_team_id);
            
         //core of the game
        $quarterMinutes = $totalMinutes / 4;

        $quarterNumber = 0;
        
        while ($quarterNumber <= 3) {
            $quarterNumber++;

            $quarter = 'Q'.$quarterNumber;

            $playerQuarterStats = $this->gameEngine($scheduleId,$gameData,$quarterMinutes);

            $this->playerStats->updateQuarterStats($playerQuarterStats,$gameData,$quarter);
            $this->updateGameScore($gameData,$quarter);
        }

        $isTied = $this->isGameTied($gameData->game_id,$gameData->home_team_id,$gameData->away_team_id);
        
        if($isTied && $quarterNumber >= 4){
            $otMinutes = $totalMinutes / 8;

            $OT = true;
            $OTNumber = 0;
            while ($OT) {
                $quarterNumber++;
                $OTNumber++;

                $overtimeQuarter = 'OT'.$OTNumber;

                $playerQuarterStats = $this->gameEngine($scheduleId,$gameData,$otMinutes);

                $this->playerStats->updateQuarterStats($playerQuarterStats,$gameData,$overtimeQuarter);
                $this->updateGameScore($gameData,$overtimeQuarter);

                $isTied = $this->isGameTied($gameData->game_id,$gameData->home_team_id,$gameData->away_team_id);
        
                if(!$isTied || $OTNumber > 3){
                    $OT = false;

                    DB::table('schedules')
                        ->where('game_id', $gameData->game_id)
                        ->update(['is_overtime' => $OTNumber ]);
                }
            }
        }

        $playerOverallQuarterStats =  DB::table('player_per_quarter_stats')
                ->select('player_id','game_id','season_id')
                ->where('game_id', $gameData->game_id)
                ->distinct('player_id')
                ->get();

        foreach ($playerOverallQuarterStats as $playerStats) {
            $this->playerStats->updateGameStats($playerStats->player_id,$playerStats->game_id,$playerStats->season_id);
        }

        $playerGameStats =  DB::table('player_game_stats')
                ->where('game_id', $gameData->game_id)
                ->get();
        
        $formattedGameStats = [];
        foreach ($playerGameStats as $playerStats) {
    
            $formattedGameStats[] = [
                'game_id' => $playerStats->game_id,
                'team_id' => $playerStats->team_id,
                'player_id' => $playerStats->player_id,
                'season_id' => $playerStats->season_id,
                'role' => $playerStats->role,
                'minutes' => $playerStats->minutes,
                'points' => $playerStats->points,
                'rebounds' => $playerStats->rebounds,
                'assists' => $playerStats->assists,
                'steals' => $playerStats->steals,
                'blocks' => $playerStats->blocks,
                'turnovers' => $playerStats->turnovers,
                'fouls' => $playerStats->fouls,
                'field_goals_made' => $playerStats->field_goals_made,
                'field_goal_attempts' => $playerStats->field_goal_attempts,
                'two_pointers_made' => $playerStats->two_pointers_made,
                'two_point_attempts' => $playerStats->two_point_attempts,
                'three_pointers_made' => $playerStats->three_pointers_made,
                'three_point_attempts' => $playerStats->three_point_attempts,
                'free_throws_made' => $playerStats->free_throws_made,
                'free_throw_attempts' => $playerStats->free_throw_attempts,
            ];
        }

        return $formattedGameStats;
    }

    private function gameEngine($scheduleId,$gameData,$totalMinutes)
    {

        $currentSeasonId = get_current_season_id();
        
        $this->teamManagement->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);
        $this->teamManagement->updateSeasonTeamChemistryBeforeGame($gameData->away_team_id);
        //check first to balance team positions

        $rolePriority = [
            'star player' => 1,
            'all star' => 2,
            'starter' => 2,
            'role player' => 5,
            'bench' => 5,
        ];

        // Fetching sorted active players for both teams
        $homeTeamPlayers = $this->teamStats->getActivePlayersSorted($gameData->home_team_id,$gameData->game_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->teamStats->getActivePlayersSorted($gameData->away_team_id,$gameData->game_id, $rolePriority, $gameData->round);


        $playerGameStats = [];
        $homeMinutes = $this->playerStats->distributeMinutes($homeTeamPlayers, $totalMinutes, $scheduleId);
        $awayMinutes = $this->playerStats->distributeMinutes($awayTeamPlayers, $totalMinutes, $scheduleId);

        
        $homeChemistry =  $this->teamStats->getTeamChemistry($currentSeasonId, $gameData->home_team_id);
        $awayChemistry =  $this->teamStats->getTeamChemistry($currentSeasonId, $gameData->away_team_id);
        // Simulate home team player stats with detailed shooting metrics

         // Simulate home team player stats with detailed shooting metrics
        foreach ($homeTeamPlayers as $player) {
            $minutes = (float) $homeMinutes[$player->id];
            if ($minutes === 0 || $player->is_injured == 1 || $player->is_fouled_out == 1) {
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
            if ($minutes === 0 || $player->is_injured == 1 || $player->is_fouled_out == 1) {
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
                'is_fouled_out' => $player->is_fouled_out
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

         // Distribute assists for the home team
        $this->playerStats->distributeAssists($playerGameStats, $gameData->home_team_id, $maxHomeAssists, $homeAssistsAssigned);

        // Distribute assists for the away team
        $this->playerStats->distributeAssists($playerGameStats, $gameData->away_team_id, $maxAwayAssists, $awayAssistsAssigned);
        
        return $playerGameStats ?? [];

    }

    private function gameDataInfo($scheduleId){

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
                'schedules.series_id',
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
            ->findOrFail($scheduleId);

        return $gameData;
    }

    private function insertGameQuarterBreakDown($gameId, $teamId, $seasonId){

            DB::table('game_quarter_breakdown')->updateOrInsert(
                [
                    'game_id' => $gameId,
                    'team_id' => $teamId,
                    'season_id' => $seasonId,
                ],
                [
                    'Q1' => 0,
                    'Q2' => 0,
                    'Q3' => 0,
                    'Q4' => 0,
                    'OT1' => 0,
                    'OT2' => 0,
                    'OT3' => 0
                ]
            );
    }

    private function isGameTied($gameId,$homeTeamId,$awayTeamId){

        $results = DB::table('game_quarter_breakdown')->whereIn('team_id', [$homeTeamId,$awayTeamId])
                    ->where('game_id', $gameId)
                    ->pluck('total')
                    ->toArray();
        
        return $results[0] == $results[1];
    }

    private function getScore($gameId,$teamId,$seasonId){

        return DB::table('game_quarter_breakdown')->where('team_id', $teamId)
                    ->where('season_id', $seasonId)
                    ->where('game_id', $gameId)
                    ->value('total');
    }

    private function updateGameScore($gameData,$quarter){

        $homeQuarterScore = DB::table('player_per_quarter_stats')
                ->where('team_id', $gameData->home_team_id)
                ->where('game_id', $gameData->game_id)
                ->where('quarter', $quarter)
                ->sum('points');

            $awayQuarterScore = DB::table('player_per_quarter_stats')
                ->where('team_id', $gameData->away_team_id)
                ->where('game_id', $gameData->game_id)
                ->where('quarter', $quarter)
                ->sum('points');

            DB::table('game_quarter_breakdown')
                ->where('team_id', $gameData->home_team_id)
                ->where('game_id', $gameData->game_id)
                ->update([
                    $quarter => $homeQuarterScore,
                ]);

            DB::table('game_quarter_breakdown')
                ->where('team_id', $gameData->away_team_id)
                ->where('game_id', $gameData->game_id)
                ->update([
                    $quarter => $awayQuarterScore,
                ]);
    }
}