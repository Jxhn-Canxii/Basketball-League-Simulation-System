<?php

namespace App\Services\Game;

ini_set('max_execution_time', 1048); // Unlimited execution time

use App\Models\Schedules;
use App\Services\Contract\ContractService;
use App\Services\Helper\HelperService;
use App\Services\League\NewsService;
use App\Services\Player\FreeAgencyService;
use App\Services\Stats\PlayerCareerStatsService;
use App\Services\Stats\PlayoffStatsService;
use App\Services\Game\PlayerStatsService;
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
    protected $career;
    protected $teamChemistry;
    protected $minutes;
    protected $playByPlayEngine;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        // $this->storeStats = new AwardsController();
        $this->playByPlayEngine = new PlayByPlayService();
        $this->contract = new ContractService();
        $this->teamChemistry = new TeamChemistryService();
        $this->teamManagement = new TeamManagementService();
        $this->playOffStats = new PlayoffStatsService();
        $this->playerStats = new PlayerStatsService();
        $this->teamStreak = new TeamStreakService();
        $this->freeAgent = new FreeAgencyService();
        $this->news = new NewsService();
        $this->career = new PlayerCareerStatsService();
        $this->minutes = new MinuteDistributionService();
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

        if(!is_numeric($gameData->round)){
            return response()->json([
                'message' => 'Game round not valid!',
            ], 400);
        }

        $this->freeAgent->auditTeamRosterSpot($gameData->home_team_id,$gameData->round);
        $this->freeAgent->auditTeamRosterSpot($gameData->away_team_id,$gameData->round);
        
        $gameResults = $this->runGame($scheduleId, $gameData,$totalMinutes);
    
        $this->playerStats->updateSeasonStats($gameResults['game_stats'], false);
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

    public function startAllStarGame(string $scheduleId, $totalMinutes = 240)
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

        $gameResults = $this->runAllStarGame($scheduleId, $gameData,$totalMinutes);
    
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

    private function runGame($scheduleId, $gameData, $totalMinutes = 240){

        $this->insertGameQuarterBreakDown($gameData->game_id,$gameData->home_team_id,$gameData->season_id);
        $this->insertGameQuarterBreakDown($gameData->game_id,$gameData->away_team_id,$gameData->season_id);

        $homeTeamRosterReport = $this->teamManagement->prepareFinalRoster($gameData->home_team_id,$gameData->round);
        $awayTeamRosterReport = $this->teamManagement->prepareFinalRoster($gameData->away_team_id,$gameData->round);

        $totalPossessions = rand(450,600);
        $totalMinutesPlayed = 0;
        for ($quarterNumber=1; $quarterNumber <= 4; $quarterNumber++) { 
            //core of the game
            $quarterMinutes = $totalMinutes / 4;
            $quarterPossessions = $totalPossessions / 4;

            $totalMinutesPlayed += $quarterMinutes;

            $quarter = 'Q'.$quarterNumber;

            $playerQuarterStats = $this->gameEngine($gameData,$quarter,$quarterMinutes, $quarterPossessions);

            $this->playerStats->updateQuarterStats($playerQuarterStats,$gameData,$quarter);
            
            $this->updateGameScore($gameData,$quarter);
        }

        $isTied = $this->isGameTied($gameData->game_id,$gameData->home_team_id,$gameData->away_team_id);
        
        if ($isTied) {

            $maxOvertimes = 3;

            for ($OTNumber = 1; $OTNumber <= $maxOvertimes; $OTNumber++) {
                //core of the game
                $otMinutes = $totalMinutes / 8;
                $otPossessions = $totalPossessions / 8;

                $totalMinutesPlayed += $otMinutes;

                $overtimeQuarter = 'OT' . $OTNumber;

                $playerQuarterStats = $this->gameEngine($gameData,$overtimeQuarter,$otMinutes, $otPossessions);

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

        $players =  DB::table('player_per_quarter_stats_temp as pps')
                ->join('players','players.id','=','pps.player_id')
                ->select('players.id','players.is_reserved','pps.role')
                ->whereIn('pps.team_id', [$gameData->home_team_id,$gameData->away_team_id])
                ->where('players.is_active',1)
                ->where('pps.quarter','Q1')
                ->get();

        foreach ($players as $player) {
            $isStarter = in_array($player->role,['all star','starter','star player']);

            $this->playerStats->updateGameStats($player->id,$gameData->game_id,$gameData->season_id,$player->is_reserved,$isStarter, $totalMinutesPlayed);
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
                'offensive_rebounds' => $playerStats->offensive_rebounds,
                'defensive_rebounds' => $playerStats->defensive_rebounds,
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

    private function runAllStarGame($scheduleId, $gameData, $totalMinutes = 240){

        $this->insertGameQuarterBreakDown($gameData->game_id,$gameData->home_team_id,$gameData->season_id);
        $this->insertGameQuarterBreakDown($gameData->game_id,$gameData->away_team_id,$gameData->season_id);

        $homeTeamRosterReport = $this->teamManagement->prepareAllStarFinalRoster($gameData->home_team_id,$gameData->round);
        $awayTeamRosterReport = $this->teamManagement->prepareAllStarFinalRoster($gameData->away_team_id,$gameData->round);

        $totalPossessions = rand(450,600);
        $totalMinutesPlayed = 0;
        for ($quarterNumber=1; $quarterNumber <= 4; $quarterNumber++) { 
            // $totalPossessions = rand(160,310);
            //core of the game
            $quarterMinutes = $totalMinutes / 4;
            $quarterPossessions = $totalPossessions / 4;

            $totalMinutesPlayed += $quarterMinutes;

            $quarter = 'Q'.$quarterNumber;

            $playerQuarterStats = $this->allstarGameEngine($gameData,$quarter,$quarterMinutes, $quarterPossessions);

            $this->playerStats->updateQuarterStats($playerQuarterStats,$gameData,$quarter);
            
            $this->updateGameScore($gameData,$quarter);
        }

        $isTied = $this->isGameTied($gameData->game_id,$gameData->home_team_id,$gameData->away_team_id);
        
        if ($isTied) {

            $maxOvertimes = 3;

            for ($OTNumber = 1; $OTNumber <= $maxOvertimes; $OTNumber++) {
                // $totalPossessions = rand(160,310);

                $otMinutes = $totalMinutes / 8;
                $otPossessions = $totalPossessions / 8;

                $totalMinutesPlayed += $otMinutes;

                $overtimeQuarter = 'OT' . $OTNumber;

                $playerQuarterStats = $this->allstarGameEngine($gameData,$overtimeQuarter,$otMinutes, $otPossessions);

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

        $players =  DB::table('season_awards')
                    ->select('players.id','players.is_reserved','players.role')
                    ->join('players','players.id','=','season_awards.player_id')
                    ->where('season_awards.award_name',$gameData->round)
                    ->where('season_awards.season_id',$gameData->season_id)
                    ->where('players.is_active',1)
                    ->get();

        foreach ($players as $player) {
            $isStarter = in_array($player->role,['all star','starter','star player']);
            $this->playerStats->updateGameStats($player->id,$gameData->game_id,$gameData->season_id,$player->is_reserved, $isStarter, $totalMinutesPlayed);
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
                'offensive_rebounds' => $playerStats->offensive_rebounds,
                'defensive_rebounds' => $playerStats->defensive_rebounds,
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

    private function gameEngine($gameData,$quarter,$totalMinutes,$totalPossessions)
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
        $homeMinutes = $this->minutes->distributeMinutes($homeTeamPlayers, $totalMinutes, $gameData->game_id, $gameData->home_coaching_style, $gameData->home_preferred_team_composition);
        $awayMinutes = $this->minutes->distributeMinutes($awayTeamPlayers, $totalMinutes, $gameData->game_id, $gameData->away_coaching_style, $gameData->away_preferred_team_composition);

        $homeChemistry = $gameData->home_team_chemistry ?? 75;
        $awayChemistry = $gameData->away_team_chemistry ?? 75;
        // Simulate home team player stats with detailed shooting metrics

        $data = $this->playByPlayEngine->simulateQuarterPossessions(
            $currentSeasonId,
            $gameData,
            $homeTeamPlayers,
            $awayTeamPlayers,
            $homeMinutes,
            $awayMinutes,
            $quarter,
            (float) $homeChemistry,
            (float) $awayChemistry,
            $totalPossessions,
            $totalMinutes
        );

        $playerGameStats = $data['stats'];
        $gamePlayByPlay = $data['events'];
    
        $this->playByPlayEngine->recordGameEvents($gamePlayByPlay);

        return $playerGameStats ?? [];

    }

    private function allstarGameEngine($gameData,$quarter,$totalMinutes,$totalPossessions)
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
        $homeTeamPlayers = $this->teamManagement->getActiveAllstarPlayersSorted($gameData->home_team_id,$gameData->game_id, $rolePriority, $gameData->round);
        $awayTeamPlayers = $this->teamManagement->getActiveAllstarPlayersSorted($gameData->away_team_id,$gameData->game_id, $rolePriority, $gameData->round);

        $playerGameStats = [];
        $homeMinutes = $this->minutes->distributeMinutes($homeTeamPlayers, $totalMinutes, $gameData->game_id);
        $awayMinutes = $this->minutes->distributeMinutes($awayTeamPlayers, $totalMinutes, $gameData->game_id);

        $homeChemistry = $gameData->home_team_chemistry ?? 75;
        $awayChemistry = $gameData->away_team_chemistry ?? 75;
        // Simulate home team player stats with detailed shooting metrics

        $data = $this->playByPlayEngine->simulateQuarterPossessions(
            $currentSeasonId,
            $gameData,
            $homeTeamPlayers,
            $awayTeamPlayers,
            $homeMinutes,
            $awayMinutes,
            $quarter,
            (float) $homeChemistry,
            (float) $awayChemistry,
            $totalPossessions,
            $totalMinutes
        );

        $playerGameStats = $data['stats'];
        $gamePlayByPlay = $data['events'];
    
        $this->playByPlayEngine->recordGameEvents($gamePlayByPlay);

        return $playerGameStats ?? [];

    }

    private function gameDataInfo($scheduleId){

        $gameData = Schedules::join('teams as home', 'schedules.home_id', '=', 'home.id')
            ->join('teams as away', 'schedules.away_id', '=', 'away.id')
            ->join('team_season_info as home_info', 'home_info.team_id', '=', 'schedules.home_id')
            ->join('team_season_info as away_info', 'away_info.team_id', '=', 'schedules.away_id')
            ->join('coaches as home_coach', 'home_coach.id', '=', 'home_info.coach_id')
            ->join('coaches as away_coach', 'away_coach.id', '=', 'away_info.coach_id')
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
                'away_coach.coaching_style as away_coaching_style',
                'home_coach.coaching_style as home_coaching_style',
                'away_coach.preferred_team_composition as away_preferred_team_composition',
                'home_coach.preferred_team_composition as home_preferred_team_composition',
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