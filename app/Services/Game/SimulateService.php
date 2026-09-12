<?php

namespace App\Services\Game;

ini_set('max_execution_time', 0); // Unlimited execution time

use Illuminate\Http\Request;
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
use App\Services\Team\TeamStatsService;
use App\Services\Team\TeamStreakService;
use Illuminate\Support\Facades\DB;

class SimulateService
{
    protected $storeStats;
    protected $contract;
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
    protected $engine;
    protected $gameResultService;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        // $this->storeStats = new AwardsController();
        $this->contract = new ContractService();
        $this->teamManagement = new TeamManagementService();
        $this->playOffStats = new PlayoffStatsService();
        $this->playerStats = new PlayerStatsService();
        $this->teamStats = new TeamStatsService();
        $this->teamStreak = new TeamStreakService();
        $this->freeAgent = new FreeAgencyService();
        $this->news = new NewsService();
        $this->archive = new ArchiveService();
        $this->career = new PlayerCareerStatsService();
        $this->engine = new GameEngineService();
        $this->gameResultService = new GameResultService();
        $this->helper = new HelperService();
    }

    public function simulateRegular(Request $request)
    {
        // try{
        //     DB::beginTransaction(); // Start transaction
    
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

            $data = collect($this->engine->startRegularGame($request->schedule_id,240));

            $gameData = $data['game_info'];

            // Calculate scores based on player stats
            $homeScore = DB::table('game_quarter_breakdown')->where('team_id', $gameData->home_team_id)
                ->where('game_id', $gameData->game_id)
                ->value('total');

            $awayScore = DB::table('game_quarter_breakdown')->where('team_id', $gameData->away_team_id)
                ->where('game_id', $gameData->game_id)
                ->value('total');

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

            $this->teamManagement->evaluatePlayerInjury($gameData->home_team_id);
            $this->teamManagement->evaluatePlayerInjury($gameData->away_team_id);

            $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->home_team_id, $gameData->winner_id,  $gameData->round);
            $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->away_team_id, $gameData->winner_id,  $gameData->round);

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

                    $this->archive->archiveStandingViewTable($currentSeasonId);
                    $this->playOffStats->updatePlayoffQualifiedFlags();
                }
            }

            // Commit the transaction
            // DB::commit();

            // Return the simulation result
            return response()->json([
                'message' => 'Game simulated successfully',
                'game_id' => $gameData->game_id,
                'season_status' => $season->status,
                'round' => $gameData->round,
                'transaction_count' => $transactionCount,
                'conference_id' => $gameData->conference_id,
                // 'data' => $gameResult,
                // 'playerGameStats' => $playerGameStats,
            ]);
        // }
        // catch(\Exception $e){
        //     DB::rollBack();

        //     return response()->json([
        //         'message' => 'An error occurred while storing the season awards.',
        //         'error' => $e->getMessage(),
        //     ], 500);

        // }

    }

    public function simulatePlayoffSeries(Request $request)
    {
        try{
            DB::beginTransaction(); // Start transaction

            $currentSeasonId = get_current_season_id();

            // $season = Seasons::find($currentSeasonId);
            $this->seriesAudit($request->schedule_id);

            $data = collect($this->engine->startPlayoffSeriesGame($request->schedule_id,240));

            $gameData = $data ? $data['game_info'] : [];

            $series = DB::table('playoff_series')
                ->where('series_id', $gameData->series_id)
                ->first();

            if ($gameData && !$series) {
                return response()->json([
                    'message' => 'Series #'.$gameData->series_id.' not found for the given schedule!',
                ], 404);
            }

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

            // Calculate scores based on player stats
            $homeScore = DB::table('game_quarter_breakdown')->where('team_id', $gameData->home_team_id)
                ->where('game_id', $gameData->game_id)
                ->value('total');

            $awayScore = DB::table('game_quarter_breakdown')->where('team_id', $gameData->away_team_id)
                ->where('game_id', $gameData->game_id)
                ->value('total');

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
                    'playoff_series.race_to',
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
            $series->home_wins = ($gameData->winner_id ==  $series->home_team_id) ? $series->home_wins + 1 : $series->home_wins;
            $series->away_wins = ($gameData->winner_id ==  $series->away_team_id) ? $series->away_wins + 1 : $series->away_wins;

            if ($series->completed) {
                $winnerName = $series->winner_team_id == $series->home_team_id ? $homeTeamName : $awayTeamName;
                $seriesLead = "{$winnerName} Wins {$series->home_wins}-{$series->away_wins}";
            } else {
    
                if ($series->home_wins == $series->race_to || $series->away_wins == $series->race_to) {
                    $winnerName = $series->winner_team_id == $series->home_team_id ? $homeTeamName : $awayTeamName;
                    $seriesLead = "{$winnerName} Wins {$series->home_wins}-{$series->away_wins}";
                }
                if ($series->home_wins == $series->away_wins) {
                    $seriesLead = "Series Tied {$series->home_wins}-{$series->away_wins}";
                } 
                else {
                    $leaderName = $series->home_wins > $series->away_wins ? $homeTeamName : $awayTeamName;
                    $leadWins = max($series->home_wins, $series->away_wins);
                    $trailWins = min($series->home_wins, $series->away_wins);
                    $seriesLead = "{$leaderName} Leads {$leadWins}-{$trailWins}";
                }
            }

            // Save game data and update other tables in a transaction
            $winnerId = $gameData->winner_id;
            DB::transaction(function () use ($gameData, $currentSeasonId, $winnerId) {

                $this->teamManagement->evaluatePlayerInjury($gameData->home_team_id);
                $this->teamManagement->evaluatePlayerInjury($gameData->away_team_id);

                $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->home_team_id, $winnerId,  $gameData->round);
                $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->away_team_id, $winnerId,  $gameData->round);
                
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

            DB::commit();

            $gameNews = DB::table('game_news')
                ->select('id', 'game_id', 'season_id', 'round', 'title', 'content', 'created_at', 'updated_at')
                ->where('game_id', $gameData->game_id)
                ->first();

            $quarterBreakDown = $this->gameResultService->getQuarterBreakDown($gameData->game_id,$currentSeasonId);

            $isOT = DB::table('schedules')
                ->where('game_id', $gameData->game_id)
                ->value('is_overtime');

            $pastResults = DB::table('schedules')
                ->join('teams as w','w.id','=','schedules.winner_id')
                ->join('teams as h','h.id','=','schedules.home_id')
                ->join('teams as a','a.id','=','schedules.home_id')
                ->select('schedules.game_number','schedules.id','w.primary_color','w.acronym as winner_team_name','h.acronym as home_team_name','a.acronym as away_team_name','schedules.home_score','schedules.away_score','schedules.winner_id','schedules.game_number')
                ->where('schedules.series_id', $gameData->series_id)
                ->where('schedules.status',2)
                ->orderBy('schedules.id','desc')
                ->get();

            // Format series response
            $seriesResponse = [
                'id' => $series->id,
                'game_id' => $gameData->game_id,
                'series_id' => $series->series_id,
                'season_id' => $series->season_id,
                'conference' => $series->conference ?? 'Interconference',
                'round' => $series->round,
                'best_of' => $series->best_of,
                'race_to' => $series->race_to,
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
                'game_winner_id' => $winnerId,
                'winner_id' => $series->winner_team_id,
                'loser_id' => $series->loser_team_id,
                'past_results' => $pastResults,
                'news' => $gameNews,
                'break_down' => $quarterBreakDown,
                'is_overtime' => $isOT,
                'created_at' => $series->created_at,
                'updated_at' => $series->updated_at,
            ];

            // Return the simulation result
            return response()->json([
                'message' => 'Game simulated successfully',
                'series' => $seriesResponse,
            ]);
        }catch(\Exception $e){
            DB::rollBack();

            return response()->json([
                'message' => 'An error occurred while storing the season awards.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //no priority

    public function simulatePlayoff(Request $request)
    {
        $currentSeasonId = get_current_season_id();


        $isGameFinished = DB::table('schedules')
            ->where('id', $request->schedule_id)
            ->where('status', 2)  // Fetch previous round and current round in one query
            ->exists(); // Use exists() for a boolean result

        if ($isGameFinished) {
            return response()->json([
                'message' => 'Game already simulated!',
            ], 400); // 400 - Bad Request is more appropriate for this scenario
        }

        $data = collect($this->engine->startPlayoffNonSeriesGame($request->schedule_id,240));

        // dd($data);

        $gameData = $data['game_info'];
    
        // Calculate scores based on player stats
        $homeScore = DB::table('game_quarter_breakdown')->where('team_id', $gameData->home_team_id)
            ->where('game_id', $gameData->game_id)
            ->value('total');

        $awayScore = DB::table('game_quarter_breakdown')->where('team_id', $gameData->away_team_id)
            ->where('game_id', $gameData->game_id)
            ->value('total');

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
        // $transactionCount = $this->helper->getTransferTransactionCount();

        $this->teamManagement->evaluatePlayerInjury($gameData->home_team_id);
        $this->teamManagement->evaluatePlayerInjury($gameData->away_team_id);

        $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->home_team_id, $winnerId,  $gameData->round);
        $this->playerStats->updatePlayerMoraleBasedOnStats($gameData->away_team_id, $winnerId,  $gameData->round);
        
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
            'transaction_count' => 0,
        ]);
    }

    private function seriesAudit($id){

        $seriesId = DB::table('schedules')
            ->where('id', $id)
            ->where('status', 2)  // Fetch previous round and current round in one query
            ->value('series_id'); // Use exists() for a boolean result

        $seriesInfo = DB::table('playoff_series')
            ->where('series_id', $seriesId)
            ->first(); // Use exists() for a boolean result
    
        $finishedSeriesGames = DB::table('schedules')
            ->where('series_id', $seriesId)
            ->where('status', 2)  // Fetch previous round and current round in one query
            ->count(); // Use exists() for a boolean result

        $isGameFinished = DB::table('schedules')
            ->where('id', $id)
            ->where('status', 2)  // Fetch previous round and current round in one query
            ->exists(); // Use exists() for a boolean result


        if ($isGameFinished) {
            return response()->json([
                'message' => 'Game already simulated!',
            ], 400); // 400 - Bad Request is more appropriate for this scenario
        }

        if($seriesInfo){
            $seriesGamesCombined = $seriesInfo->home_wins + $seriesInfo->away_wins;
            $seriesReachedMaxGame = $seriesInfo->home_wins == $seriesInfo->race_to || $seriesInfo->away_wins == $seriesInfo->race_to;
            if ($seriesGamesCombined != $finishedSeriesGames) {

                return response()->json([
                    'message' => 'Series Audit Warning: Finished series game schedule doesnt match playoff series records!',
                ], 400);

            }

            if ($seriesReachedMaxGame) {

                return response()->json([
                    'message' => 'Series Already Finished: Cant proceed to simulate this game!',
                ], 400);
            }
        }
    }
}
