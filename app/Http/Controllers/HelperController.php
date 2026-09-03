<?php

namespace App\Http\Controllers;

use App\Services\Helper\HelperService;

class HelperController extends Controller
{
    protected $excludedRounds = 0;
    protected $helperService;

    public function __construct(){

        $this->excludedRounds = config('playoffs');
        $this->helperService = new HelperService();
    }

    public function simulatedRounds($seasonId){

        return $this->helperService->simulatedRounds($seasonId);
    }

    public function seasonStatus($seasonId){

        return $this->helperService->seasonStatus($seasonId);

    }

    public function currentSeasonConferenceRank($teamId){
        
        return $this->helperService->currentSeasonConferenceRank($teamId);
    }

    public function totalRounds($seasonId){

        return $this->helperService->totalRounds($seasonId);
    }

    public function calculateInjuryChance($fatigue)
    {
        return $this->helperService->calculateInjuryChance($fatigue);
    }
    
    /**
     * Check if all rounds have been simulated for the given season.
     *
     * @param int $seasonId
     * @return bool
     */
    public function allRoundsSimulatedForSeason(int $seasonId): bool
    {
        return $this->helperService->allRoundsSimulatedForSeason($seasonId);
    }

    /**
     * Check if a specific round has been simulated for the given season.
     *
     * @param int $seasonId
     * @param int $round
     * @return bool
     */
    public function isRoundSimulated(int $seasonId, $round): bool
    {
        return $this->helperService->isRoundSimulated($seasonId, $round);
    }

    public function isRoundSeriesSimulated(int $seasonId, string $round)
    {

        return $this->helperService->isRoundSeriesSimulated($seasonId, $round);
    }

    public function getPlayerStatsDatabaseName(int $seasonId)
    {
        return $this->helperService->getPlayerStatsDatabaseName($seasonId);
    }

    public function getSeasonStatsDBName(int $seasonId)
    {
        return $this->helperService->getSeasonStatsDBName($seasonId);
    }

    public function getPlayoffStatsDBName(int $seasonId)
    {

        return $this->helperService->getPlayoffStatsDBName($seasonId);
    }

    public function getScheduleViewDBName(int $seasonId)
    {

        return $this->helperService->getScheduleViewDBName($seasonId);
    }

    public function getScheduleDBName(int $seasonId)
    {

        return $this->helperService->getScheduleDBName($seasonId);
    }

    public function getTeamName($teamId){

        return $this->helperService->getTeamName($teamId);
    }

    public function getNationalChampionId($seasonId) {
        
        return $this->helperService->getNationalChampionId($seasonId);
    }

    public function totalRegularSeasonGames($seasonId, $teamId)
    {
        return $this->helperService->totalRegularSeasonGames($seasonId, $teamId);
    }

    public function getTransferTransactionCount(){

        return $this->helperService->getTransferTransactionCount();
    }

    public function hasImproved($latest,$previous){
        return $this->helperService->getTransferTransactionCount();
    }

    public function roundFormatter($round){
        
        return $this->helperService->roundFormatter($round);

    }
}
