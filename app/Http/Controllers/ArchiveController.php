<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seasons;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\TeamChemistryController;
use App\Http\Controllers\TeamStreakController;
use App\Http\Controllers\HelperController;
use Inertia\Inertia;

class ArchiveController extends Controller
{
    protected $chemistry;
    protected $helper;
    protected $streak;

    public function __construct(){
        $this->chemistry = new TeamChemistryController();
        $this->streak = new TeamStreakController();
        $this->helper = new HelperController();
    }

    public static function archiveGameStats()
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

    public static function archivePlayerSeasonStats()
    {
        $currentSeasonId = get_current_season_id();
        $MODULO = config('archive.DECADE_MODULO');
        // $tableBatch = $currentSeasonId / $MODULO;

        $archiveSeasonStatsTable = "player_season_stats_archives" ;
        $archiveSeasonPlayoffStatsTable = "player_season_playoff_stats_archives" ;

        // 1) Only proceed if season is finished
        $season = DB::table('seasons')->where('id', $currentSeasonId)->first();
        if (!$season || $season->status < 14) return;

        DB::beginTransaction();
        try {

            // 3) IF ARCHIVE TABLE EXISTS → STOP (no transaction)
            if (!Schema::hasTable($archiveSeasonStatsTable)) {
                DB::statement("CREATE TABLE $archiveSeasonStatsTable LIKE player_season_stats");
            }

            if (!Schema::hasTable($archiveSeasonPlayoffStatsTable)) {
                DB::statement("CREATE TABLE $archiveSeasonPlayoffStatsTable LIKE player_season_playoff_stats");
            }
            
            DB::statement("INSERT INTO $archiveSeasonStatsTable SELECT CONCAT(`id`,'P-',`player_id`,'S-',`player_id`,'T-',`season_id`) as id, `player_id`, `team_id`, `season_id`, `role`, `avg_minutes_per_game`, `avg_points_per_game`, `avg_rebounds_per_game`, `avg_assists_per_game`, `avg_steals_per_game`, `avg_blocks_per_game`, `avg_turnovers_per_game`, `avg_fouls_per_game`, `total_field_goals_made`, `total_field_goal_attempts`, `total_two_pointers_made`, `total_two_point_attempts`, `total_three_pointers_made`, `total_three_point_attempts`, `total_free_throws_made`, `total_free_throw_attempts`, `total_points`, `total_rebounds`, `total_assists`, `total_steals`, `total_blocks`, `total_turnovers`, `total_fouls`, `total_minutes_played`, `total_games_played`, `total_games`, `bpg_game_leader`, `points_game_leader`, `rebounds_game_leader`, `assists_game_leader`, `steals_game_leader`, `blocks_game_leader`, `per`, `ts_percent`, `eff`, `field_goal_percentage`, `two_point_percentage`, `three_point_percentage`, `free_throw_percentage`, `performance_points`, `created_at`, `updated_at` FROM player_season_stats");
            DB::statement("DELETE FROM player_season_stats");

            DB::statement("INSERT INTO $archiveSeasonPlayoffStatsTable SELECT CONCAT(`id`,'P-',`player_id`,'S-',`player_id`,'T-',`season_id`) as id, `player_id`, `team_id`, `season_id`, `role`, `avg_minutes_per_game`, `avg_points_per_game`, `avg_rebounds_per_game`, `avg_assists_per_game`, `avg_steals_per_game`, `avg_blocks_per_game`, `avg_turnovers_per_game`, `avg_fouls_per_game`, `total_field_goals_made`, `total_field_goal_attempts`, `total_two_pointers_made`, `total_two_point_attempts`, `total_three_pointers_made`, `total_three_point_attempts`, `total_free_throws_made`, `total_free_throw_attempts`, `total_points`, `total_rebounds`, `total_assists`, `total_steals`, `total_blocks`, `total_turnovers`, `total_fouls`, `total_minutes_played`, `total_games_played`, `total_games`, `bpg_game_leader`, `points_game_leader`, `rebounds_game_leader`, `assists_game_leader`, `steals_game_leader`, `blocks_game_leader`, `per`, `ts_percent`, `eff`, `field_goal_percentage`, `two_point_percentage`, `three_point_percentage`, `free_throw_percentage`, `performance_points`, `created_at`, `updated_at` FROM player_season_playoff_stats");
            DB::statement("DELETE FROM player_season_playoff_stats");

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

                $this->streak->newTeamStreak($team->id);
                
            } catch (\Exception $e) {
                // Optional: log individual team errors
                throw new \Exception("Failed to store team season info for team ID {$team->id}: " . $e->getMessage());
            }
        }
    }

    public function saveStandingsSnapshot()
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
                    'total_points_for',
                    'total_points_against',
                    'home_ppg',
                    'away_ppg',
                    'total_points_for_avg',
                    'total_points_against_avg',
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

}