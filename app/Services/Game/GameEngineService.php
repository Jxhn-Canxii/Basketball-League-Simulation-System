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
use App\Services\Team\TeamChemistryService;
use App\Services\Team\TeamManagementService;
use App\Services\Team\TeamStreakService;
use Illuminate\Support\Facades\DB;
class GameEngineService
{
    protected $storeStats;
    protected $contract;
    protected $teamManagement;
    protected $playerStats;
    protected $teamStreak;
    protected $freeAgent;
    protected $playOffStats;
    protected $helper;
    protected $news;
    protected $archive;
    protected $career;
    protected $teamChemistry;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        // $this->storeStats = new AwardsController();
        $this->contract = new ContractService();
        $this->teamChemistry = new TeamChemistryService();
        $this->teamManagement = new TeamManagementService();
        $this->playOffStats = new PlayoffStatsService();
        $this->playerStats = new PlayerStatsService();
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

        //dd($gameData);
        
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

        $this->freeAgent->auditTeamRosterSpot($gameData->home_team_id);
        $this->freeAgent->auditTeamRosterSpot($gameData->away_team_id);

        $gameResults = $this->runGame($scheduleId, $gameData,$totalMinutes);
    
        $this->playerStats->updateSeasonStats($gameResults['game_stats'], false);
        $this->career->recordPlayerCareerHigh($gameResults['game_stats'],$gameData);

        return [
            'game_info' => $gameData,
            'home_team_report' => $gameResults['home_team_roster'],
            'away_team_report' => $gameResults['home_team_roster'],
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

        $gameResults = $this->runGame($scheduleId, $gameData,$totalMinutes);

        $this->playerStats->updateSeasonStats($gameResults['game_stats'], true);
        $this->career->recordPlayerCareerHigh($gameResults['game_stats'],$gameData);

        return [
            'game_info' => $gameData,
            'home_team_report' => $gameResults['home_team_roster'],
            'away_team_report' => $gameResults['home_team_roster'],
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
        $gameResults = $this->runGame($scheduleId, $gameData,$totalMinutes);

        $this->playerStats->updateSeasonStats($gameResults['game_stats'], true);
        $this->career->recordPlayerCareerHigh($gameResults['game_stats'],$gameData);

        return [
            'game_info' => $gameData,
            'home_team_report' => $gameResults['home_team_roster'],
            'away_team_report' => $gameResults['home_team_roster'],
        ];

    }


    private function runGame($scheduleId, $gameData, $totalMinutes = 240){

        $this->insertGameQuarterBreakDown($gameData->game_id,$gameData->home_team_id,$gameData->season_id);
        $this->insertGameQuarterBreakDown($gameData->game_id,$gameData->away_team_id,$gameData->season_id);

        $homeTeamRosterReport = $this->teamManagement->prepareFinalRoster($gameData->home_team_id,$gameData->round);
        $awayTeamRosterReport = $this->teamManagement->prepareFinalRoster($gameData->away_team_id,$gameData->round);

         //core of the game
        $quarterMinutes = $totalMinutes / 4;


        for ($quarterNumber=1; $quarterNumber <= 4; $quarterNumber++) { 

            $quarter = 'Q'.$quarterNumber;

            $playerQuarterStats = $this->gameEngine($gameData,$quarter,$quarterMinutes);

            $this->playerStats->updateQuarterStats($playerQuarterStats,$gameData,$quarter);
            
            $this->updateGameScore($gameData,$quarter);
        }

        $isTied = $this->isGameTied($gameData->game_id,$gameData->home_team_id,$gameData->away_team_id);
        
        if ($isTied) {

            $otMinutes = $totalMinutes / 8;

            $maxOvertimes = 3;

            for ($OTNumber = 1; $OTNumber <= $maxOvertimes; $OTNumber++) {

                $overtimeQuarter = 'OT' . $OTNumber;

                $playerQuarterStats = $this->gameEngine($gameData,$overtimeQuarter,$otMinutes);

                $this->playerStats->updateQuarterStats($playerQuarterStats,$gameData,$overtimeQuarter);

                $this->updateGameScore($gameData,$overtimeQuarter);

                // Check score after this overtime
                $isTied = $this->isGameTied($gameData->game_id,$gameData->home_team_id,$gameData->away_team_id);

                // Game has a winner, stop overtime
                if (!$isTied) {

                    DB::table('schedules')
                        ->where('game_id', $gameData->game_id)
                        ->update([
                            'is_overtime' => $OTNumber
                        ]);

                    break;
                }
            }
        }

        $players =  DB::table('players')
                ->select('id')
                ->whereIn('team_id', [$gameData->home_team_id,$gameData->away_team_id])
                ->where('is_active',1)
                ->get();

        foreach ($players as $player) {
            $this->playerStats->updateGameStats($player->id,$gameData->game_id,$gameData->season_id,$player->is_reserved);
        }

        $playerGameStats =  DB::table('player_game_stats')
                ->where('game_id', $gameData->game_id)
                ->get();

        $this->playerStats->removeQuarterStats();
        
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
                'assists' => max(0,$playerStats->assists),
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

        return [
            'game_stats' => $formattedGameStats,
            'home_team_roster' => $homeTeamRosterReport,
            'away_team_roster' => $awayTeamRosterReport
        ];
    }

    private function gameEngine($gameData,$quarter,$totalMinutes)
    {

        $currentSeasonId = get_current_season_id();
        
        $this->teamChemistry->updateSeasonTeamChemistryBeforeGame($gameData->home_team_id);
        $this->teamChemistry->updateSeasonTeamChemistryBeforeGame($gameData->away_team_id);
        //check first to balance team positions

        $rolePriority = [
            'star player' => 1,
            'all star' => 2,
            'starter' => 2,
            'role player' => 5,
            'bench' => 5,
        ];

        // Fetching sorted active players for both teams
        $homeTeamPlayers = $this->teamManagement->getActivePlayersSorted($gameData->home_team_id,$gameData->game_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->teamManagement->getActivePlayersSorted($gameData->away_team_id,$gameData->game_id, $rolePriority, $gameData->round);


        $playerGameStats = [];
        $homeMinutes = $this->playerStats->distributeMinutes($homeTeamPlayers, $totalMinutes, $gameData->game_id);
        $awayMinutes = $this->playerStats->distributeMinutes($awayTeamPlayers, $totalMinutes, $gameData->game_id);

        
        $homeChemistry = $gameData->home_team_chemistry ?? 75;
        $awayChemistry = $gameData->away_team_chemistry ?? 75;
        // Simulate home team player stats with detailed shooting metrics

        // Repeat similar simulation for away team players...
        foreach ($homeTeamPlayers as $player) {
            $playerGameStats[] = $this->statsEngine($currentSeasonId, $gameData, $player, $homeMinutes, $homeChemistry);
        }

        foreach ($awayTeamPlayers as $player) {
            $playerGameStats[] = $this->statsEngine($currentSeasonId, $gameData, $player, $awayMinutes,  $awayChemistry);
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

    private function statsEngine($currentSeasonId, $gameData, $player, $playerMinutes, $chemistry)
    {
        $minutes = (int) $playerMinutes[$player->id];

        $performanceFactor = $this->playerStats->calculatePerformanceFactor($player);
        $defensiveImpact =  $this->playerStats->calculateDefensiveImpact($gameData->away_team_id);

        $turnovers =  $this->playerStats->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
        $fouls =  $this->playerStats->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);

        $totalFouls = $player->last_quarter_fouls + $fouls;
        $isPlayerOut = $totalFouls > 5 ? 1 : 0;

        //set is foul out to true
        $player->is_fouled_out = $isPlayerOut;

        if ($isPlayerOut == 1 || $minutes == 0 || $player->is_injured == 1 || $player->is_fouled_out == 1 || $player->is_reserved == 1) {
            $playerGameStats = $this->playerStats->createInactivePlayerStats($player, $gameData, $currentSeasonId);
        }
        else{
            $shotStats =  $this->playerStats->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $chemistry, true, false);

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

        
            $playerGameStats = [
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

        $this->teamManagement->fatigueRate($player, $minutes, $gameData->game_id);

        return $playerGameStats;

    }

    private function gameDataInfo($scheduleId){

        $gameData = Schedules::join('teams as home', 'schedules.home_id', '=', 'home.id')
            ->join('teams as away', 'schedules.away_id', '=', 'away.id')
            ->join('team_season_info as home_info', 'home_info.team_id', '=', 'schedules.home_id')
            ->join('team_season_info as away_info', 'away_info.team_id', '=', 'schedules.away_id')
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
                'home_info.chemistry as home_team_chemistry',
                'away_info.chemistry as away_team_chemistry',
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
        
        if($results[0] !=0 && $results[1] != 0)
        {
            return $results[0] == $results[1];
        }

        return true;
    }

    private function getScore($gameId,$teamId,$seasonId){

        return DB::table('game_quarter_breakdown')->where('team_id', $teamId)
                    ->where('season_id', $seasonId)
                    ->where('game_id', $gameId)
                    ->value('total');
    }

    private function updateGameScore($gameData,$quarter){

        try{
            $homeQuarterScore = DB::table('player_per_quarter_stats_temp')
                ->where('team_id', $gameData->home_team_id)
                ->where('game_id', $gameData->game_id)
                ->where('quarter', $quarter)
                ->sum('points');

            $awayQuarterScore = DB::table('player_per_quarter_stats_temp')
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

            return true;
        }catch(\Exception $e){
            return false;
        }
    }
}