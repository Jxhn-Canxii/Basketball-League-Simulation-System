<?php

namespace App\Services\League;

use App\Services\Schedule\ScheduleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlayoffResetService
{
    protected $schedule;

    public function __construct(){
        $this->schedule = new ScheduleService();
    }
    /**
     * Playoff stage status mapping.
     *
     * The numeric value represents the season.status.
     */
    protected array $playoffStages = [
        'play_offs'                    => 3,

        'play_ins_elims_round_1'       => 4,
        'play_ins_elims_round_2'       => 5,
        'play_ins_finals'              => 6,

        'round_of_16'                  => 7,
        'quarter_finals'               => 8,
        'semi_finals'                  => 9,
        'interconference_semi_finals'  => 10,
        'finals'                       => 11,
    ];

    /**
     * Reset a specific playoff round and everything after it.
     *
     * Example:
     * resetRound(13, 'quarter_finals')
     *
     * This removes:
     * - Quarter finals
     * - Semi finals
     * - Interconference semi finals
     * - Finals
     *
     * Previous rounds remain untouched.
     */
    public function resetRound(
        int $seasonId,
        string $round
    ): array {
        if (!isset($this->playoffStages[$round])) {
            throw new \InvalidArgumentException(
                "Invalid playoff round: {$round}"
            );
        }

        $stageStatus = $this->playoffStages[$round];

        return DB::transaction(function () use (
            $seasonId,
            $round,
            $stageStatus
        ) {
            // ---------------------------------------------------------
            // 1. Find all playoff games from this round onward
            // ---------------------------------------------------------
            $gameIds = $this->getPlayoffGameIdsFromStage(
                $seasonId,
                $stageStatus
            );

            // ---------------------------------------------------------
            // 2. Delete all dependent game data
            // ---------------------------------------------------------
            $deleted = $this->deleteGameData(
                $seasonId,
                $gameIds
            );

            // ---------------------------------------------------------
            // 3. Delete playoff series from this round onward
            // ---------------------------------------------------------
            $seriesDeleted = $this->deletePlayoffSeriesFromStage(
                $seasonId,
                $stageStatus
            );

            // ---------------------------------------------------------
            // 4. Recalculate playoff aggregate stats
            //    from the playoff games that remain.
            // ---------------------------------------------------------
            $this->rebuildPlayoffPlayerSeasonStats($seasonId);

            // ---------------------------------------------------------
            // 5. Reset season playoff information
            // ---------------------------------------------------------
            $this->resetSeasonAfterRound(
                $seasonId,
                $stageStatus
            );

            Log::warning(
                "Playoff round reset",
                [
                    'season_id' => $seasonId,
                    'round'     => $round,
                    'status'    => $stageStatus,
                    'games'     => count($gameIds),
                    'series'    => $seriesDeleted,
                ]
            );

            return [
                'success' => true,
                'type' => 'round',
                'season_id' => $seasonId,
                'round' => $round,
                'deleted_games' => count($gameIds),
                'deleted_series' => $seriesDeleted,
                'deleted_game_data' => $deleted,
            ];
        });
    }

    /**
     * Reset the entire playoff tournament.
     *
     * Regular-season games remain untouched.
     */
    public function resetPlayoffs(int $seasonId): array
    {
        return DB::transaction(function () use ($seasonId) {

            // ---------------------------------------------------------
            // Get ALL playoff game IDs.
            //
            // Playoff games are identified through playoff series /
            // playoff round information.
            // ---------------------------------------------------------
            $gameIds = DB::table('schedules')
                ->where('season_id', $seasonId)
                ->where(function ($query) {
                    $query
                        ->whereNotNull('series_id')
                        ->orWhereIn('round', array_keys($this->playoffStages));
                })
                ->pluck('game_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            // ---------------------------------------------------------
            // Delete dependent data
            // ---------------------------------------------------------
            $deleted = $this->deleteGameData(
                $seasonId,
                $gameIds
            );

            // ---------------------------------------------------------
            // Delete all playoff schedules
            // ---------------------------------------------------------
            $deletedSchedules = DB::table('schedules')
                ->where('season_id', $seasonId)
                ->where(function ($query) {
                    $query
                        ->whereNotNull('series_id')
                        ->orWhereIn('round', array_keys($this->playoffStages));
                })
                ->delete();

            // ---------------------------------------------------------
            // Delete all playoff series
            // ---------------------------------------------------------
            $deletedSeries = DB::table('playoff_series')
                ->where('season_id', $seasonId)
                ->delete();

            // ---------------------------------------------------------
            // Delete playoff aggregate stats
            // ---------------------------------------------------------
            $deletedPlayoffStats = DB::table(
                'player_season_playoff_stats'
            )
                ->where('season_id', $seasonId)
                ->delete();

            // ---------------------------------------------------------
            // Reset playoff-related season information
            // ---------------------------------------------------------
            $this->resetSeasonToPlayoffStart($seasonId);

            Log::warning(
                "Entire playoffs reset",
                [
                    'season_id' => $seasonId,
                    'games' => count($gameIds),
                    'schedules' => $deletedSchedules,
                    'series' => $deletedSeries,
                    'playoff_stats' => $deletedPlayoffStats,
                ]
            );

            return [
                'success' => true,
                'type' => 'playoffs',
                'season_id' => $seasonId,
                'deleted_games' => count($gameIds),
                'deleted_schedules' => $deletedSchedules,
                'deleted_series' => $deletedSeries,
                'deleted_playoff_stats' => $deletedPlayoffStats,
                'deleted_game_data' => $deleted,
            ];
        });
    }

    private function redoSeasonSchedule($type,$nextSeasonId,$leagueId){
         // Create schedule based on type
            switch ((int)$type) {
                case 2:
                    $this->schedule->createSingleRoundRobinScheduleByConference($nextSeasonId, $leagueId);
                    break;
                case 3:
                    $this->schedule->createDoubleRoundRobinScheduleByConference($nextSeasonId, $leagueId);
                    break;
                case 4:
                    $this->schedule->createHybridRoundRobinScheduleByConference($nextSeasonId, $leagueId);
                    break;
                case 5:
                    $this->schedule->createCustomRoundRobinScheduleByConference($nextSeasonId, $leagueId, 5);
                    break;
                case 6:
                    $this->schedule->createCustomRoundRobinScheduleByConference($nextSeasonId, $leagueId, 10);
                    break;
                case 7:
                    $this->schedule->createRoundRobinSchedule($nextSeasonId, $leagueId);
                    break;
                case 1:
                    throw new \Exception('Single Elimination not available for this season type.');
                default:
                    throw new \Exception('Invalid season type.');
            }
    }
    /**
     * Reset the ENTIRE season.
     *
     * WARNING:
     * This removes regular-season and playoff game data.
     */
    public function resetSeason(int $seasonId): array
    {
        return DB::transaction(function () use ($seasonId) {

            // ---------------------------------------------------------
            // Get every game belonging to the season.
            // ---------------------------------------------------------
            $gameIds = DB::table('schedules')
                ->where('season_id', $seasonId)
                ->pluck('game_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            // ---------------------------------------------------------
            // Delete game-related data.
            // ---------------------------------------------------------
            $deleted = $this->deleteGameData(
                $seasonId,
                $gameIds
            );

            $seasonInfo =  DB::table('seasons')
                ->select('league_id','type')
                ->where('id', $seasonId)
                ->first();

            $schedulesExists = DB::table('schedules')
                ->where('season_id', $seasonId)
                ->exists();

            $resetSchedules = [];
            // status:
            //   1 = unplayed
            //   2 = finished
            // ---------------------------------------------------------
            if($schedulesExists){
                $resetSchedules = DB::table('schedules')
                    ->where('season_id', $seasonId)
                    ->update([
                        'home_score'  => 0,
                        'away_score'  => 0,
                        'winner_id'   => 0,
                        'is_overtime' => 0,
                        'status'      => 1,
                        'updated_at'  => now(),
                    ]);
            }
            else{
                $this->redoSeasonSchedule($seasonInfo->type,$seasonId,$seasonInfo->league_id);
            }
            // ---------------------------------------------------------
            // Delete playoff series.
            // ---------------------------------------------------------
            $deletedSeries = DB::table('playoff_series')
                ->where('season_id', $seasonId)
                ->delete();

            $deletedTradeLogs = DB::table('trade_logs')
                ->where('season_id', $seasonId)
                ->delete();

            $undoInSeasonTradeProposals = DB::table('trade_proposals')
                ->where('season_id', $seasonId)
                ->where('status','!=','approved')
                ->delete();
            // ---------------------------------------------------------
            // Delete player playoff stats.
            // ---------------------------------------------------------
            $deletedPlayoffStats = DB::table(
                'player_season_playoff_stats'
            )
                ->where('season_id', $seasonId)
                ->delete();

            // ---------------------------------------------------------
            // Delete regular season player stats.
            // ---------------------------------------------------------
            $deletedSeasonStats = DB::table(
                'player_season_stats'
            )
                ->where('season_id', $seasonId)
                ->delete();

            // ---------------------------------------------------------
            // Reset season record.
            // ---------------------------------------------------------
            $this->resetEntireSeasonRecord($seasonId);

            Log::critical(
                "ENTIRE SEASON RESET",
                [
                    'season_id' => $seasonId,
                    'games' => count($gameIds),
                    'schedules' => $resetSchedules,
                    'series' => $deletedSeries,
                ]
            );

            return [
                'success' => true,
                'type' => 'season',
                'season_id' => $seasonId,
                'deleted_games' => count($gameIds),
                'deleted_schedules' => $resetSchedules,
                'deleted_series' => $deletedSeries,
                'deleted_playoff_stats' => $deletedPlayoffStats,
                'deleted_season_stats' => $deletedSeasonStats,
                'deleted_game_data' => $deleted,
            ];
        });
    }

    protected function getPlayoffGameIdsFromStage(
        int $seasonId,
        int $stageStatus
    ): array {
        $allowedStages = array_keys(
            array_filter(
                $this->playoffStages,
                fn($status) => $status >= $stageStatus
            )
        );

        return DB::table('schedules as s')
            ->where('s.season_id', $seasonId)
            ->where(function ($query) use (
                $allowedStages,
                $seasonId
            ) {
                $query
                    ->whereIn('s.round', $allowedStages)
                    ->orWhereIn(
                        's.series_id',
                        function ($subQuery) use (
                            $seasonId,
                            $allowedStages
                        ) {
                            $subQuery
                                ->select('id')
                                ->from('playoff_series')
                                ->where('season_id', $seasonId)
                                ->whereIn('round', $allowedStages);
                        }
                    );
            })
            ->pluck('s.game_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
    /**
     * Delete everything directly associated with the supplied games.
     */
    protected function deleteGameData(
        int $seasonId,
        array $gameIds
    ): array {
        if (empty($gameIds)) {
            return [
                'quarter_breakdown' => 0,
                'player_game_stats' => 0,
                'player_per_quarter_stats' => 0,
                'career_highlights' => 0,
            ];
        }

        $quarterBreakdown = DB::table(
            'game_quarter_breakdown'
        )
            ->whereIn('game_id', $gameIds)
            ->delete();

        $playerGameStats = DB::table(
            'player_game_stats'
        )
            ->whereIn('game_id', $gameIds)
            ->delete();

        $playerQuarterStats = DB::table(
            'player_per_quarter_stats'
        )
            ->whereIn('game_id', $gameIds)
            ->delete();

        $careerHighlights = DB::table(
            'career_highlights'
        )
            ->whereIn('game_id', $gameIds)
            ->delete();

        return [
            'quarter_breakdown' => $quarterBreakdown,
            'player_game_stats' => $playerGameStats,
            'player_per_quarter_stats' => $playerQuarterStats,
            'career_highlights' => $careerHighlights,
        ];
    }

    /**
     * Delete playoff series from a stage onward.
     */
    protected function deletePlayoffSeriesFromStage(
        int $seasonId,
        int $stageStatus
    ): int {
        /*
         * If your playoff_series.round stores numeric stage values,
         * this works directly.
         *
         * If it stores strings such as "quarter_finals",
         * the method below maps them first.
         */

        $rounds = array_keys(
            array_filter(
                $this->playoffStages,
                fn($status) => $status >= $stageStatus
            )
        );

        return DB::table('playoff_series')
            ->where('season_id', $seasonId)
            ->whereIn('round', $rounds)
            ->delete();
    }

    /**
     * Rebuild player playoff season aggregates using the
     * player_game_stats rows that remain.
     *
     * This prevents resetRound() from leaving stale aggregate stats.
     */
    protected function rebuildPlayoffPlayerSeasonStats(
        int $seasonId
    ): void {
        // Remove current aggregate playoff stats.
        DB::table('player_season_playoff_stats')
            ->where('season_id', $seasonId)
            ->delete();

        /*
         * Nothing to rebuild if there are no remaining playoff games.
         */
        $hasGames = DB::table('player_game_stats')
            ->where('season_id', $seasonId)
            ->where('is_playoff', 1)
            ->exists();

        if (!$hasGames) {
            return;
        }

        /*
         * Insert a fresh aggregate.
         *
         * This intentionally calculates the core statistics from
         * player_game_stats.
         *
         * Add your existing performance_points / valuation logic
         * afterward if those are generated by a separate service.
         */
        $stats = DB::table('player_game_stats')
            ->select(
                'player_id',
                'team_id',
                'season_id',
                'role',

                DB::raw('AVG(minutes) as avg_minutes_per_game'),
                DB::raw('AVG(points) as avg_points_per_game'),
                DB::raw('AVG(rebounds) as avg_rebounds_per_game'),
                DB::raw('AVG(assists) as avg_assists_per_game'),
                DB::raw('AVG(steals) as avg_steals_per_game'),
                DB::raw('AVG(blocks) as avg_blocks_per_game'),
                DB::raw('AVG(turnovers) as avg_turnovers_per_game'),
                DB::raw('AVG(fouls) as avg_fouls_per_game'),

                DB::raw('SUM(field_goals_made) as total_field_goals_made'),
                DB::raw('SUM(field_goal_attempts) as total_field_goal_attempts'),

                DB::raw('SUM(two_pointers_made) as total_two_pointers_made'),
                DB::raw('SUM(two_point_attempts) as total_two_point_attempts'),

                DB::raw('SUM(three_pointers_made) as total_three_pointers_made'),
                DB::raw('SUM(three_point_attempts) as total_three_point_attempts'),

                DB::raw('SUM(free_throws_made) as total_free_throws_made'),
                DB::raw('SUM(free_throw_attempts) as total_free_throw_attempts'),

                DB::raw('SUM(points) as total_points'),
                DB::raw('SUM(rebounds) as total_rebounds'),
                DB::raw('SUM(assists) as total_assists'),
                DB::raw('SUM(steals) as total_steals'),
                DB::raw('SUM(blocks) as total_blocks'),
                DB::raw('SUM(turnovers) as total_turnovers'),
                DB::raw('SUM(fouls) as total_fouls'),

                DB::raw(
                    'SUM(CASE WHEN is_fouled_out = 1 THEN 1 ELSE 0 END) as total_fouled_out'
                ),

                DB::raw('SUM(minutes) as total_minutes_played'),
                DB::raw('COUNT(DISTINCT game_id) as total_games_played'),
                DB::raw('COUNT(DISTINCT game_id) as total_games')
            )
            ->where('season_id', $seasonId)
            ->where('is_playoff', 1)
            ->groupBy(
                'player_id',
                'team_id',
                'season_id',
                'role'
            )
            ->get();

        foreach ($stats as $stat) {

            $fgPct = $stat->total_field_goal_attempts > 0
                ? $stat->total_field_goals_made /
                $stat->total_field_goal_attempts
                : 0;

            $twoPct = $stat->total_two_point_attempts > 0
                ? $stat->total_two_pointers_made /
                $stat->total_two_point_attempts
                : 0;

            $threePct = $stat->total_three_point_attempts > 0
                ? $stat->total_three_pointers_made /
                $stat->total_three_point_attempts
                : 0;

            $ftPct = $stat->total_free_throw_attempts > 0
                ? $stat->total_free_throws_made /
                $stat->total_free_throw_attempts
                : 0;

            DB::table('player_season_playoff_stats')->insert([
                'player_id' => $stat->player_id,
                'team_id' => $stat->team_id,
                'season_id' => $stat->season_id,
                'role' => $stat->role,

                'avg_minutes_per_game' => $stat->avg_minutes_per_game,
                'avg_points_per_game' => $stat->avg_points_per_game,
                'avg_rebounds_per_game' => $stat->avg_rebounds_per_game,
                'avg_assists_per_game' => $stat->avg_assists_per_game,
                'avg_steals_per_game' => $stat->avg_steals_per_game,
                'avg_blocks_per_game' => $stat->avg_blocks_per_game,
                'avg_turnovers_per_game' => $stat->avg_turnovers_per_game,
                'avg_fouls_per_game' => $stat->avg_fouls_per_game,

                'total_field_goals_made' => $stat->total_field_goals_made,
                'total_field_goal_attempts' => $stat->total_field_goal_attempts,

                'total_two_pointers_made' => $stat->total_two_pointers_made,
                'total_two_point_attempts' => $stat->total_two_point_attempts,

                'total_three_pointers_made' => $stat->total_three_pointers_made,
                'total_three_point_attempts' => $stat->total_three_point_attempts,

                'total_free_throws_made' => $stat->total_free_throws_made,
                'total_free_throw_attempts' => $stat->total_free_throw_attempts,

                'total_points' => $stat->total_points,
                'total_rebounds' => $stat->total_rebounds,
                'total_assists' => $stat->total_assists,
                'total_steals' => $stat->total_steals,
                'total_blocks' => $stat->total_blocks,
                'total_turnovers' => $stat->total_turnovers,
                'total_fouls' => $stat->total_fouls,

                'total_fouled_out' => $stat->total_fouled_out,

                'total_minutes_played' => $stat->total_minutes_played,
                'total_games_played' => $stat->total_games_played,
                'total_games' => $stat->total_games,

                'field_goal_percentage' => $fgPct,
                'two_point_percentage' => $twoPct,
                'three_point_percentage' => $threePct,
                'free_throw_percentage' => $ftPct,

                'per' => 0,
                'ts_percent' => 0,
                'eff' => 0,
                'performance_points' => 0,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reset season status after a round reset.
     */
    protected function resetSeasonAfterRound(
        int $seasonId,
        int $stageStatus
    ): void {
        /*
         * If we reset a round, the season should be placed immediately
         * before that round.
         *
         * Example:
         *
         * Reset quarter_finals (8)
         * => season status becomes 7
         *
         * Reset finals (11)
         * => season status becomes 10
         */

        $newStatus = max(3, $stageStatus - 1);

        DB::table('seasons')
            ->where('id', $seasonId)
            ->update([
                'status' => $newStatus,
                'finals_mvp_id' => null,
                'finals_mvp' => null,
                'finals_winner_id' => null,
                'finals_winner_name' => null,
                'finals_winner_score' => null,
                'finals_loser_id' => null,
                'finals_loser_name' => null,
                'finals_loser_score' => null,
                'champion_id' => null,
                'champion_name' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reset the entire playoff tournament to playoff start.
     *
     * Regular-season data is preserved.
     */
    protected function resetSeasonToPlayoffStart(
        int $seasonId
    ): void {
        DB::table('seasons')
            ->where('id', $seasonId)
            ->update([
                'status' => 2,

                'finals_mvp_id' => null,
                'finals_mvp' => null,

                'finals_winner_id' => null,
                'finals_winner_name' => null,
                'finals_winner_score' => null,

                'finals_loser_id' => null,
                'finals_loser_name' => null,
                'finals_loser_score' => null,

                'west_champion_id' => null,
                'west_champion_name' => null,

                'east_champion_id' => null,
                'east_champion_name' => null,

                'north_champion_id' => null,
                'north_champion_name' => null,

                'south_champion_id' => null,
                'south_champion_name' => null,

                'champion_id' => null,
                'champion_name' => null,

                'updated_at' => now(),
            ]);
    }

    /**
     * Reset entire season metadata.
     *
     * This does NOT delete the seasons row.
     */
    protected function resetEntireSeasonRecord(
        int $seasonId
    ): void {
        DB::table('seasons')
            ->where('id', $seasonId)
            ->update([
                'status' => 1,
                'finals_mvp_id' => null,
                'finals_mvp' => null,

                'finals_winner_id' => null,
                'finals_winner_name' => null,
                'finals_winner_score' => null,

                'finals_loser_id' => null,
                'finals_loser_name' => null,
                'finals_loser_score' => null,

                'west_champion_id' => null,
                'west_champion_name' => null,

                'east_champion_id' => null,
                'east_champion_name' => null,

                'north_champion_id' => null,
                'north_champion_name' => null,

                'south_champion_id' => null,
                'south_champion_name' => null,

                'champion_id' => null,
                'champion_name' => null,

                'weakest_id' => null,
                'weakest_name' => null,

                'updated_at' => now(),
            ]);
    }
}
