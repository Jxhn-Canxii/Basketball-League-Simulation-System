<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seasons;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\TeamChemistryController;
use App\Http\Controllers\HelperController;
use Inertia\Inertia;

class ArchiveController extends Controller
{
    protected $chemistry;
    protected $helper;

    public function __construct(){
        $this->chemistry = new TeamChemistryController();
        $this->helper = new HelperController();
    }

    public static function archiveDecadeStats()
    {
        $currentSeasonId = get_current_season_id();
        $MODULO = config('archive.DECADE_MODULO');
        $tableBatch = $currentSeasonId / $MODULO;

        $archiveTable = "player_game_stats_batch_" . $tableBatch;

        // 1) Only proceed if season is finished
        $season = DB::table('seasons')->where('id', $currentSeasonId)->first();
        if (!$season || $season->status < 14) return;

        // 2) Must be a modulo season
        if ($currentSeasonId % $MODULO !== 0) return;

        // 3) IF ARCHIVE TABLE EXISTS → STOP (no transaction)
        if (Schema::hasTable($archiveTable)) {
            return;
        }

        DB::beginTransaction();
        try {

            DB::statement("CREATE TABLE $archiveTable LIKE player_game_stats");
            DB::statement("INSERT INTO $archiveTable SELECT * FROM player_game_stats");
            DB::statement("DELETE FROM player_game_stats WHERE season_id < $currentSeasonId");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function storeTeamSeasonInfo()
    {
        $latestSeasonId = get_current_season_id();
        $previousSeasonId = $latestSeasonId - 1;

        $teamsCoach = DB::table('teams')->get();

        // Get the previous season's champion team_id
        $prevChampion = $this->helper->getNationalChampionId($previousSeasonId);

        foreach ($teamsCoach as $team) {
            try {
                // Ensure necessary fields are present
                if (!$team->id || !$latestSeasonId) {
                    throw new \Exception("Missing team ID or season ID.");
                }

                $coach = DB::table('coaches')->where('id', $team->coach_id)->first();

                $coachIq = $coach ? $coach->coach_iq : 0;
                $chemistry = $this->chemistry->getChemistryCalculation($team->id,$latestSeasonId,$previousSeasonId) ?? 0;
                $conferenceId = $team->conference_id ?? 0;
                $isDefendingChampion = $team->id == $prevChampion ? 1 : 0;
                // Perform the insert/update
                DB::table('team_season_info')->updateOrInsert(
                    [
                        'team_id' => $team->id,
                        'season_id' => $latestSeasonId,
                    ],
                    [
                        'coach_id' => $team->coach_id,
                        'coach_iq' => $coachIq,
                        'chemistry' => $chemistry['chemistry_score'],
                        'conference_id' => $conferenceId,
                        'is_defending_champion' => $isDefendingChampion,
                        'updated_at' => now(),
                    ]
                );
            } catch (\Exception $e) {
                // Optional: log individual team errors
                throw new \Exception("Failed to store team season info for team ID {$team->id}: " . $e->getMessage());
            }
        }
    }

}