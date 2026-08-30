<?php

namespace App\Http\Controllers;

use App\Models\Schedules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HelperController extends Controller
{
    protected $excludedRounds = 0;

    public function __construct(){

        $this->excludedRounds = config('playoffs');
    }

    public function simulatedRounds($seasonId){

        return DB::table('schedules')
            ->where('season_id', $seasonId)
            ->whereNotIn('round', $this->excludedRounds)
            ->where('status', '=', 2) // Check if any game is not yet simulated
            ->distinct('round')
            ->count();
    }

    public function seasonStatus($seasonId){

        return DB::table('seasons')
            ->where('id', $seasonId)
            ->value('status'); // Get the 'status' of the current season

    }

    public function currentSeasonConferenceRank($teamId){
        
        return DB::table('standings_view')
            ->where('team_id', $teamId)
            ->value('conference_rank'); // Get the 'conference_rank' of the current season standings
    }

    public function totalRounds($seasonId){

        return DB::table('schedules')
            ->where('season_id', $seasonId)
            ->whereNotIn('round', $this->excludedRounds)
            ->distinct('round')
            ->count();
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
    
    /**
     * Check if all rounds have been simulated for the given season.
     *
     * @param int $seasonId
     * @return bool
     */
    public function allRoundsSimulatedForSeason(int $seasonId): bool
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
    public function isRoundSimulated(int $seasonId, $round): bool
    {
        return !Schedules::where('season_id', $seasonId)
            ->where('round', $round)
            ->where('status', 1)
            ->exists();
    }

    public function isRoundSeriesSimulated($seasonId, $round)
    {

        return !DB::table('playoff_series')
            ->where('season_id', $seasonId)
            ->where('round', $round) // Fetch previous round + current round in one query
            ->where('status', 1)
            ->exists();
    }

    public function getPlayerStatsDatabaseName($seasonId)
    {

        $MODULO = config('archive.DECADE_MODULO');
        $tableBatch = ceil($seasonId / $MODULO);

        $archiveTable = "player_game_stats_batch_" . $tableBatch;
        if (!Schema::hasTable($archiveTable)) {
            $archiveTable = "player_game_stats";
        }

        return $archiveTable;
    }

    public function getSeasonStatsDBName($seasonId)
    {

        $latestSeasonId = get_current_season_id() ?? 1;

        $archiveTable = 'player_season_stats';

        if($latestSeasonId != $seasonId) {
            $archiveTable = "player_season_stats_archives";
        }

        return $archiveTable;
    }

    public function getPlayoffStatsDBName($seasonId)
    {

        $latestSeasonId = get_current_season_id() ?? 1;

        $archiveTable = 'player_season_playoff_stats';

        if($latestSeasonId != $seasonId) {
            $archiveTable = "player_season_playoff_stats_archives";
        }

        return $archiveTable;
    }

    public function getTeamName($teamId){

        return DB::table('teams')->where('id', $teamId)->value('name') ?? 'Unknown Team';
    }

    public function getNationalChampionId($seasonId) {
        
        $championId = DB::table('seasons')
            ->where('id', $seasonId)
            ->value('finals_winner_id');
        
        return $championId;
    }

    public function totalRegularSeasonGames($seasonId, $teamId)
    {
        $gamesPlayedCount = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->where('game_number',0)
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->count();

        return $gamesPlayedCount;
    }

    public function getTransferTransactionCount(){
        $latestSeasonId = get_current_season_id() ?? 1;

         // Get the total number of records
        $totalItems = DB::table('transactions')
            ->where('season_id', $latestSeasonId)
            ->whereNotIn('status', ['star player change', 'role change'])
            ->count();

        return $totalItems;
    }

    public function hasImproved($latest,$previous){
        $hasImproved = 0;
        if($latest > $previous){
            $hasImproved = 1;
        }

        if($latest == $previous){
            $hasImproved = 2;
        }
        
        return $hasImproved;

    }
}
