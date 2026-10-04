<?php

namespace App\Services\Game;

use App\Services\Stats\PlayerSeasonStatsService;
use App\Models\Player;
use App\Services\Helper\HelperService;
use Illuminate\Support\Facades\DB;

class PlayerStatsService
{
    protected $storeStats;
    protected $helper;
    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->storeStats = new PlayerSeasonStatsService();
        $this->helper = new HelperService();
    }

    public function updatePlayerMoraleBasedOnStats($teamId, $winnerId, $round)
    {
        $seasonId = get_current_season_id();
        $wonGame = ($teamId == $winnerId);

        $totalRounds = $this->helper->totalRounds($seasonId);
        $tradeDeadlineThreshold = CEIL($totalRounds / 2);

        $players = DB::table('players')->where('team_id', $teamId)->get();

        $chemistry = DB::table('team_season_info')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->value('chemistry') ?? 75;

        
        $currentConferenceRank = DB::table('standings_view')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->value('conference_rank') ?? 0;

        $resetMoral = ($round == $tradeDeadlineThreshold) && ( $currentConferenceRank > 6);

        foreach ($players as $player) {
            // Fetch the most recent game stats for the player
            $gameStats = DB::table('player_game_stats')
                ->where('player_id', $player->id)
                ->where('season_id', $seasonId)
                ->orderByDesc('created_at')
                ->first();

            if (!$gameStats) continue;

            // Calculate initial morale
            $morale = $player->morale ?? 75;
            $role = strtolower(trim($player->role ?? 'bench'));

            // 🎯 1. Game result impact
            $morale += $wonGame ? 2 : -2;

            // 🎯 2. Performance-based morale adjustment
            $efficiency = $gameStats->eff ?? 0;
            if ($efficiency > 20) {
                $morale += 2;
            } elseif ($efficiency < 5) {
                $morale -= 2;
            }

            // 🎯 3. Minutes played vs role expectation
            $expectedMin = match ($role) {
                'star player' => 32,
                'all star' => 28,
                'starter' => 24,
                'bench' => 10,
                default => 5,
            };

            if ($gameStats->minutes < $expectedMin - 5) {
                $morale -= 2;
            } elseif ($gameStats->minutes > $expectedMin + 5) {
                $morale += 1;
            }

            // 🎯 4. Chemistry impact
            if ($chemistry < 60) {
                $morale -= 1;
            } elseif ($chemistry >= 85) {
                $morale += 1;
            }

            if($resetMoral && $currentConferenceRank > 6 && $currentConferenceRank <= 10){
                $morale += 50;
            }

            if($resetMoral && $currentConferenceRank > 10 && $currentConferenceRank <= 14){
                $morale += 40;
            }

            if($resetMoral && $currentConferenceRank > 14){
                $morale += 30;
            }

            // 🎯 5. Clamp morale between 50 and 100
            $morale = max(0, min(100, round($morale)));

            // ✅ Update player morale
            DB::table('players')
                ->where('id', $player->id)
                ->update(['morale' => $morale]);
        }

        // ✅ Update coach career wins or losses
        $coachId = DB::table('teams')->where('id', $teamId)->value('coach_id');

        if ($coachId) {
            DB::table('coaches')
                ->where('id', $coachId)
                ->increment($wonGame ? 'career_wins' : 'career_losses');
        }
    }

    public function applyFatigue($player, $minutes, $gameId)
    {
        try {
            if (is_array($player)) {
                $player = (object) $player;
            }

            $seasonId = get_current_season_id() ?? 1;
            $staminaFactor  = $this->rating($player, 'stamina_rating', 70) / 100;
            $strengthFactor = $this->rating($player, 'strength_rating', 70) / 100;
            $currentFatigue = $this->rating($player, 'fatigue', 0);
            $retirementAge = $player->retirement_age ?? 36;
            $age = $player->age;

            // STEP 1: Calculate recovery rate
            $baseRecoveryRate = ($staminaFactor + $strengthFactor) * 0.1;

            // Age-based slowdown
            $ageGap = $retirementAge - $age;
            if ($ageGap <= 0) {
                $recoverySlowdown = 0.5;
            } elseif ($ageGap <= 5) {
                $recoverySlowdown = 1 - (0.1 * (5 - $ageGap));
            } else {
                $recoverySlowdown = 1;
            }

            $recoveryRate = $baseRecoveryRate * $recoverySlowdown;

            // STEP 2: Apply Recovery
            if (!$player->is_injured && $currentFatigue > 0) {
                $currentFatigue = max(0, $currentFatigue - $recoveryRate);
            }

            // STEP 3: Add fatigue from this game
            if ($minutes == 0) {
                $newFatigue = max(0, $currentFatigue - 20); // Auto-recovery for DNP
            } else {
                $fatigueIncrease = $minutes * (1.8 - $staminaFactor * 0.55);
                $newFatigue = min(100, $currentFatigue + round($fatigueIncrease));
            }

            // STEP 4: Injury chance check using injury_prone_percentage
            if ($newFatigue >= 85) {
                $triggerInjuryChance = rand(1, 100);

                if ($triggerInjuryChance <= 30) { // 30% chance to trigger injury logic
                    $injuryRoll = rand(1, 100);
                    if ($injuryRoll <= $player->injury_prone_percentage) {
                        $this->causeInjury($player, $gameId, $seasonId);
                        return;
                    }
                }

                // Heavy fatigue is reduced after the injury check instead of being
                // reset to zero, preserving the player's accumulated fatigue.
                $newFatigue = 35;
            }


            // STEP 5: Save fatigue
            DB::table('players')->where('id', $player->id)->update([
                'fatigue' => $newFatigue,
            ]);

            return true;
        } catch (\Exception $e) {
            // \Log::error("Error updating fatigue for player {$player->id}: " . $e->getMessage());
        }
    }

    private function causeInjury($player, $gameId, $seasonId)
    {
        // **Injury Logic**
        $injuryTypes = config('injuries');
        if (!empty($injuryTypes)) {
            $injuryTypeName = array_rand($injuryTypes);
            $recoveryGames = $injuryTypes[$injuryTypeName]['recovery_games'];

            // **Update Injury in Database**
            DB::table('players')->where('id', $player->id)->update([
                'fatigue' => 100,
                'is_injured' => true,
                'injury_type' => $injuryTypeName,
                'injury_recovery_games' => $recoveryGames,
            ]);

            DB::table('players')->where('id', $player->id)->increment('injury_history', 1);

            // Insert injury history
            DB::table('injury_histories')->insert([
                'player_id' => $player->id,
                'game_id' => $gameId,
                'team_id' => $player->team_id,
                'season_id' => $seasonId,
                'injury_type' => $injuryTypeName,
                'recovery_games' => $recoveryGames,
                'performance_impact' => $injuryTypes[$injuryTypeName]['performance_impact'],
                'injury_date' => now(),
                'recovery_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // \Log::error("Injury types configuration is missing.");
        }
    }


    public function calculatePerformanceFactor($player, bool $isClutchTime = false)
    {
        try {
            // Keep this factor close to 1.00. It is a modifier, not another
            // rating system. Player attributes should drive individual stats.
            $overall = $this->clamp((float) ($player->overall_rating ?? 70), 40, 99);
            $iq = $this->clamp((float) ($player->basketball_iq_rating ?? 60), 1, 99);
            $workEthic = $this->clamp((float) ($player->work_ethic_rating ?? 60), 1, 99);
            $morale = $this->clamp((float) ($player->morale ?? 75), 0, 100);
            $stamina = $this->clamp((float) ($player->stamina_rating ?? 70), 1, 99);
            $fatigue = $this->clamp((float) ($player->fatigue ?? 0), 0, 100);
            $age = (int) ($player->age ?? 25);
            $yearsPro = max(0, (int) ($player->years_pro ?? 0));

            // Overall is the largest influence. The other attributes only
            // move the player's nightly form a little.
            $factor = 0.78 + ($overall / 100) * 0.20; // 0.86 at 40, 0.978 at 99
            $factor += (($iq - 70) / 1000);
            $factor += (($workEthic - 70) / 1400);
            $factor += (($morale - 75) / 1500);

            // Experience improves consistency, but does not make old players
            // permanently better than their actual ratings.
            $factor += min(0.025, $yearsPro * 0.0025);

            // Prime-age curve.
            if ($age >= 24 && $age <= 30) {
                $factor += 0.015;
            } elseif ($age >= 31 && $age <= 33) {
                $factor += 0.005;
            } elseif ($age >= 34) {
                $factor -= min(0.08, ($age - 33) * 0.012);
            } elseif ($age <= 21) {
                $factor -= 0.01;
            }

            // Fatigue is important, but stamina softens its effect.
            $fatiguePenalty = ($fatigue / 100) * (0.20 + ((100 - $stamina) / 100) * 0.30);
            $factor -= $fatiguePenalty;

            if (!empty($player->is_injured)) {
                $factor *= 0.70;
            }

            if ($isClutchTime) {
                $clutch = $this->clamp((float) ($player->clutch_rating ?? 50), 1, 99);
                $factor += (($clutch - 50) / 2500); // roughly +/- 2%
            }

            // Small nightly variance: roughly +/- 6%.
            $factor *= mt_rand(94, 106) / 100;

            return round($this->clamp($factor, 0.72, 1.12), 3);
        } catch (\Throwable $e) {
            // \Log::error("Error calculating performance factor for player {$player->id}: " . $e->getMessage());
            return 1.00;
        }
    }

    private function rating($player, string $attribute, float $default = 50): float
    {
        $value = is_array($player) ? ($player[$attribute] ?? $default) : ($player->$attribute ?? $default);
        return $this->clamp((float) $value, 0, 100);
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    public function removeQuarterStats(){

        DB::table('player_per_quarter_stats_temp')->delete();
    }

    public function updateQuarterStats($playerGameStats, $gameData, $quarter = 'Q1')
    {
        if (empty($playerGameStats)) {
            throw new \Exception("Player per quarter game stats are empty. Cannot update season stats.");
        }

        try {
            foreach ($playerGameStats as $stats) {
                if (isset($stats['passing_rating'])) {
                    unset($stats['passing_rating']);
                }
                if (!isset($stats['is_fouled_out'])) {
                    $stats['is_fouled_out'] = 0;
                    $stats['assists'] = max(0,$stats['assists']);
                }
                
                // Update Player Game Stats
                DB::table('player_per_quarter_stats')->updateOrInsert(
                    [
                        'player_id' => $stats['player_id'],
                        'game_id' => $stats['game_id'],
                        'season_id' => $stats['season_id'],
                        'team_id' => $stats['team_id'],
                        'quarter' => $quarter,
                    ],
                    $stats
                );

                DB::table('player_per_quarter_stats_temp')->updateOrInsert(
                    [
                        'player_id' => $stats['player_id'],
                        'game_id' => $stats['game_id'],
                        'season_id' => $stats['season_id'],
                        'team_id' => $stats['team_id'],
                        'quarter' => $quarter,
                    ],
                    $stats
                );
            }
            
            return true;
        } catch (\Exception $e) {

            throw new \Exception("Failed to update quarter stats. Please check logs." . $e->getMessage());
        }
    }

    public function updateGameStats($playerId, $gameId, $seasonId, $isReserved, $isStarter)
    {
        if (empty($playerId)) {
            throw new \Exception("Player quarter stats are empty. Cannot update season stats.");
        }

        try {
            // dd($playerId, $gameId);
            // Calculate the player's aggregated stats for the latest season
            $playerStats = DB::table('player_per_quarter_stats')
                ->where('player_id', $playerId)
                // ->where('team_id', $teamId)
                ->where('game_id', $gameId)
                ->where('season_id', $seasonId)
                ->select(
                    'player_id',
                    'team_id',
                    DB::raw('MAX(role) as game_role'),
                    DB::raw('MAX(is_fouled_out) as is_fouled_out'),
                    DB::raw('SUM(minutes) as total_minutes'),
                    DB::raw('SUM(points) as total_points'),
                    DB::raw('SUM(rebounds) as total_rebounds'),
                    DB::raw('SUM(assists) as total_assists'),
                    DB::raw('SUM(steals) as total_steals'),
                    DB::raw('SUM(blocks) as total_blocks'),
                    DB::raw('SUM(turnovers) as total_turnovers'),
                    DB::raw('SUM(fouls) as total_fouls'),
                    DB::raw('SUM(field_goals_made) as total_field_goals_made'),
                    DB::raw('SUM(field_goal_attempts) as total_field_goal_attempts'),
                    DB::raw('SUM(two_pointers_made) as total_two_pointers_made'),
                    DB::raw('SUM(two_point_attempts) as total_two_point_attempts'),
                    DB::raw('SUM(three_pointers_made) as total_three_pointers_made'),
                    DB::raw('SUM(three_point_attempts) as total_three_point_attempts'),
                    DB::raw('SUM(free_throws_made) as total_free_throws_made'),
                    DB::raw('SUM(free_throw_attempts) as total_free_throw_attempts')
                )
                ->groupBy('player_id')
                ->first();

            // Insert or update the player's season stats in the player_season_stats table

            // dd($playerStats);
            if ($playerStats) {

                DB::table('player_game_stats')->updateOrInsert(
                    [
                        'player_id' => $playerId,
                        'team_id' => $playerStats->team_id,
                        'season_id' => $seasonId,
                        'game_id' => $gameId,
                    ],
                    [
                        'role' => $playerStats->game_role,
                        'minutes' => $playerStats->total_minutes,
                        'points' => $playerStats->total_points,
                        'rebounds' => $playerStats->total_rebounds,
                        'assists' => max(0,$playerStats->total_assists),
                        'steals' => $playerStats->total_steals,
                        'blocks' => $playerStats->total_blocks,
                        'turnovers' => $playerStats->total_turnovers,
                        'fouls' => $playerStats->total_fouls,
                        'field_goals_made' => $playerStats->total_field_goals_made,
                        'field_goal_attempts' => $playerStats->total_field_goal_attempts,
                        'two_pointers_made' => $playerStats->total_two_pointers_made,
                        'two_point_attempts' => $playerStats->total_two_point_attempts,
                        'three_pointers_made' => $playerStats->total_three_pointers_made,
                        'three_point_attempts' => $playerStats->total_three_point_attempts,
                        'free_throws_made' => $playerStats->total_free_throws_made,
                        'free_throw_attempts' => $playerStats->total_free_throw_attempts,
                        'is_starter' => $isStarter,
                        'is_fouled_out' => $playerStats->is_fouled_out,
                        'is_reserved' => $isReserved,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // return true;

            }

            // return false;
        } catch (\Exception $e) {
            // Log error for debugging
            // Log::error("Error updating season stats: " . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
            ],500);
            // Optionally, throw the error again to stop execution
            throw new \Exception("Failed to update season stats. Please check logs." . $e->getMessage());
        }
    }

    public function updateSeasonStats($playerGameStats, $isPlayoff)
    {
        if (empty($playerGameStats)) {
            throw new \Exception("Player game stats are empty. Cannot update season stats.");
        }

        try {
            $seasonStatsTable = $isPlayoff ? 'player_season_playoff_stats' : 'player_season_stats';

            // Find max values for each category
            $maxPoints = max(array_column($playerGameStats, 'points'));
            $maxRebounds = max(array_column($playerGameStats, 'rebounds'));
            $maxAssists = max(array_column($playerGameStats, 'assists'));
            $maxSteals = max(array_column($playerGameStats, 'steals'));
            $maxBlocks = max(array_column($playerGameStats, 'blocks'));

            // Determine Best Player of the Game (BPG) using Efficiency Formula
            $bestPlayerId = null;
            $bestPlayerStats = [];
            $bestEfficiency = -INF;


            foreach ($playerGameStats as &$stats) {
                if (isset($stats['passing_rating'])) {
                    unset($stats['passing_rating']);
                }

                $efficiency = ($stats['points'] + $stats['rebounds'] + $stats['assists'] + $stats['steals'] + $stats['blocks'])
                    - (($stats['fouls'] ?? 0) + ($stats['turnovers'] ?? 0)); // Assuming fg_missed exists

                if ($efficiency > $bestEfficiency) {
                    $bestEfficiency = $efficiency;
                    $bestPlayerId = $stats['player_id'];
                    $bestPlayerStats = $stats;
                }

                // Assign Game Leader Titles
                $stats['points_game_leader'] = ($stats['points'] == $maxPoints) ? 1 : 0;
                $stats['rebounds_game_leader'] = ($stats['rebounds'] == $maxRebounds) ? 1 : 0;
                $stats['assists_game_leader'] = ($stats['assists'] == $maxAssists) ? 1 : 0;
                $stats['steals_game_leader'] = ($stats['steals'] == $maxSteals) ? 1 : 0;
                $stats['blocks_game_leader'] = ($stats['blocks'] == $maxBlocks) ? 1 : 0;
                $stats['bpg_game_leader'] = ($stats['player_id'] == $bestPlayerId) ? 1 : 0;

                $this->storeStats->storePlayerSeasonStats($stats['team_id'], $stats['player_id'], $isPlayoff);

                // Update Player Season Stats (Incrementing Leader Fields)
                DB::table($seasonStatsTable)->updateOrInsert(
                    ['player_id' => $stats['player_id'], 'season_id' => $stats['season_id'], 'team_id' => $stats['team_id']],
                    [
                        'points_game_leader' => DB::raw("points_game_leader + {$stats['points_game_leader']}"),
                        'rebounds_game_leader' => DB::raw("rebounds_game_leader + {$stats['rebounds_game_leader']}"),
                        'assists_game_leader' => DB::raw("assists_game_leader + {$stats['assists_game_leader']}"),
                        'steals_game_leader' => DB::raw("steals_game_leader + {$stats['steals_game_leader']}"),
                        'blocks_game_leader' => DB::raw("blocks_game_leader + {$stats['blocks_game_leader']}"),
                        'bpg_game_leader' => DB::raw("bpg_game_leader + {$stats['bpg_game_leader']}"),
                    ]
                );
            }

        } catch (\Exception $e) {
            // Log error for debugging
            // Log::error("Error updating season stats: " . $e->getMessage());

            // Optionally, throw the error again to stop execution
            throw new \Exception("Failed to update season stats. Please check logs." . $e->getMessage());
        }
    }

    
}
