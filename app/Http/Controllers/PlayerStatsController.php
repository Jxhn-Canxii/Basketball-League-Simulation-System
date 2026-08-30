<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PlayerSeasonStatsController;
use App\Models\PlayerGameStats;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class PlayerStatsController extends Controller
{
    protected $storeStats;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->storeStats = new PlayerSeasonStatsController();
    }

    public function createInactivePlayerStats($player, $gameData, $seasonId)
    {
        return [
            'player_id' => $player->id,
            'game_id' => $gameData->game_id,
            'season_id' => $seasonId,
            'team_id' => $player->team_id,
            'is_injured' => $player->is_injured,
            'role' => $player->role,
            'points' => 0,
            'rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'fouls' => 0,
            'minutes' => 0,
            'field_goal_attempts' => 0,
            'field_goals_made' => 0,
            'three_point_attempts' => 0,
            'three_pointers_made' => 0,
            'free_throw_attempts' => 0,
            'free_throws_made' => 0,
        ];
    }

    public function updatePlayerMoraleBasedOnStats($teamId, $winnerId)
    {
        $seasonId = get_current_season_id();
        $wonGame = ($teamId == $winnerId);

        $players = DB::table('players')->where('team_id', $teamId)->get();
        $chemistry = DB::table('team_season_info')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->value('chemistry') ?? 75;

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

    public function distributeMinutes($playersArray, $totalMinutes, $gameId)
    {
        $rolePriority = [
            'star player' => 1,
            'all star'    => 2,
            'starter'     => 3,
            'role player' => 4,
            'bench'       => 5,
        ];

        $roleMinuteRanges = [
            'star player' => [36, 42],
            'all star'    => [32, 38],
            'starter'     => [28, 34],
            'role player' => [16, 24],
            'bench'       => [0, 20],
        ];

        $positionTargets = [
            'PG' => 48,
            'SG' => 48,
            'SF' => 48,
            'PF' => 48,
            'C'  => 48,
        ];

        $sorted = collect($playersArray)
            ->sortBy(fn($p) => $rolePriority[$p['role']] ?? 5)
            ->values();

        // Step 1: Sit injured players
        $dnpPlayers = $sorted->filter(fn($p) => $p['is_injured']);

        $maxDNPs = 3;
        // Step 2: Fill remaining DNP slots, but protect star players and all-stars
        if ($dnpPlayers->count() < $maxDNPs) {
            $remainingSlots = $maxDNPs - $dnpPlayers->count();
            $additionalDNP = $sorted
                ->reject(
                    fn($p) =>
                    $dnpPlayers->contains('id', $p['id']) ||
                        $p['is_injured'] ||
                        $p['role'] === 'star player' ||
                        $p['role'] === 'all star'
                )
                ->sortBy([
                    ['per', 'asc'],
                    ['eff', 'asc'],
                ])
                ->take($remainingSlots);

            $dnpPlayers = $dnpPlayers->merge($additionalDNP);
        }

        // Ensure minimum of 8 players with minutes
        $rotation = $sorted->reject(fn($p) => $dnpPlayers->contains('id', $p['id']));

        if ($rotation->count() < 6) {
            $needed = 8 - $rotation->count();

            $reAddCandidates = $dnpPlayers
                ->filter(fn($p) => !$p['is_injured'])
                ->sortBy([
                    ['per', 'desc'],
                    ['eff', 'desc'],
                ])
                ->take($needed);

            $dnpPlayers = $dnpPlayers->reject(fn($p) => $reAddCandidates->contains('id', $p['id']));
            $rotation = $rotation->merge($reAddCandidates);
        }

        $minutes = [];
        foreach ($dnpPlayers as $p) {
            $minutes[$p['id']] = 0;
        }

        // Step 3: Assign minutes based on role and position
        $assignedTotal = 0;

        // First pass: Assign minimum minutes to star players and all-stars
        foreach ($rotation as $player) {
            if ($player['role'] === 'star player' || $player['role'] === 'all star') {
                $role = $player['role'];
                $range = $roleMinuteRanges[$role];
                $baseMinutes = rand($range[0], $range[1]);

                $positions = explode('/', $player['position']);
                foreach ($positions as $pos) {
                    if (($positionTargets[$pos] ?? 0) > 0) {
                        $assigned = min($baseMinutes, $positionTargets[$pos]);
                        $minutes[$player['id']] = $assigned;
                        $positionTargets[$pos] -= $assigned;
                        $assignedTotal += $assigned;
                        break;
                    }
                }
            }
        }

        // Second pass: Assign minutes to remaining players
        foreach ($rotation as $player) {
            if (!isset($minutes[$player['id']])) {
                $role = $player['role'];
                $range = $roleMinuteRanges[$role] ?? [5, 15];
                $baseMinutes = rand($range[0], $range[1]);

                $positions = explode('/', $player['position']);
                $positionAssigned = false;

                foreach ($positions as $pos) {
                    if (($positionTargets[$pos] ?? 0) > 0) {
                        $assigned = min($baseMinutes, $positionTargets[$pos]);
                        $minutes[$player['id']] = $assigned;
                        $positionTargets[$pos] -= $assigned;
                        $assignedTotal += $assigned;
                        $positionAssigned = true;
                        break;
                    }
                }

                if (!$positionAssigned) {
                    foreach ($positions as $pos) {
                        if (isset($positionTargets[$pos])) {
                            $assigned = min($baseMinutes, $positionTargets[$pos]);
                            $minutes[$player['id']] = $assigned;
                            $positionTargets[$pos] -= $assigned;
                            $assignedTotal += $assigned;
                            break;
                        }
                    }
                }

                if (!isset($minutes[$player['id']])) {
                    $minutes[$player['id']] = 0;
                }
            }

            $this->fatigueRate($player, $minutes[$player['id']], $gameId);
        }

        // Step 4: Normalize to total minutes (usually 240)
        $difference = $totalMinutes - array_sum($minutes);

        if (abs($difference) > 0) {
            // Prioritize star players and all-stars when distributing remaining minutes
            $eligible = $rotation->sortBy(function ($p) use ($rolePriority) {
                $role = $p['role'];
                if ($role === 'star player' || $role === 'all star') {
                    return 0; // Highest priority
                }
                return $rolePriority[$role] ?? 5;
            })->values();

            $i = 0;
            while ($difference !== 0 && $eligible->isNotEmpty()) {
                $player = $eligible[$i % $eligible->count()];
                $id = $player['id'];

                if (!isset($minutes[$id])) {
                    $minutes[$id] = 0;
                }

                if ($difference > 0 && $minutes[$id] < 48) {
                    $minutes[$id]++;
                    $difference--;
                } elseif ($difference < 0 && $minutes[$id] > 0) {
                    $minutes[$id]--;
                    $difference++;
                }

                $i++;
            }
        }

        return $minutes;
    }

    private function fatigueRate($player, $minutes, $gameId)
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
                $fatigueIncrease = $minutes * (1.0 - $staminaFactor * 0.55);
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
            \Log::error("Error updating fatigue for player {$player->id}: " . $e->getMessage());
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
            \Log::error("Injury types configuration is missing.");
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
            \Log::error("Error calculating performance factor for player {$player->id}: " . $e->getMessage());
            return 1.00;
        }
    }

    public function calculateDefensiveImpact($opponentTeamId)
    {
        $players = DB::table('players')
            ->where('team_id', $opponentTeamId)
            ->where('is_active', 1)
            ->where('is_injured', 0)
            ->select([
                'defense_rating',
                'basketball_iq_rating',
                'athleticism_rating',
                'strength_rating',
                'morale',
                'stamina_rating'
            ])
            ->get();

        if ($players->isEmpty()) {
            return 0.08;
        }

        // Use the better half of the active roster more heavily because the
        // opponent's best defenders spend more possessions on the floor.
        $ratings = $players->map(function ($p) {
            $skill =
                ($this->rating($p, 'defense_rating', 50) * 0.55) +
                ($this->rating($p, 'basketball_iq_rating', 50) * 0.15) +
                ($this->rating($p, 'athleticism_rating', 50) * 0.15) +
                ($this->rating($p, 'strength_rating', 50) * 0.05) +
                ($this->rating($p, 'stamina_rating', 50) * 0.10);
            return $skill * (0.90 + ($this->rating($p, 'morale', 75) / 1000));
        })->sortDesc()->values();

        $topCount = max(5, (int) ceil($ratings->count() / 2));
        $defenseRating = $ratings->take($topCount)->avg();

        // Return a small fraction used by shot-volume formulas, not a raw
        // 0-100 rating. Around .05 is weak and .12-.15 is elite.
        return round($this->clamp(0.035 + (($defenseRating - 50) / 1000), 0.02, 0.15), 3);
    }

    public function calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact)
    {
        if ($minutes <= 0) return 0;

        $positions = array_map('trim', explode('/', $player->position ?? 'SF'));
        $positionRates = [
            'PG' => 0.105,
            'SG' => 0.085,
            'SF' => 0.070,
            'PF' => 0.065,
            'C'  => 0.070,
        ];
        $baseRate = collect($positions)->map(fn($p) => $positionRates[$p] ?? 0.07)->average();

        $passing = $this->rating($player, 'passing_rating', 60);
        $iq = $this->rating($player, 'basketball_iq_rating', 60);
        $usage = $this->usageMultiplier($player);

        // Better passers/IQ players lose fewer possessions. High usage creates
        // more turnover opportunities.
        $skillFactor = 1.15 - (($passing * 0.55 + $iq * 0.45) / 100) * 0.30;
        $defenseFactor = 1.0 + $defensiveImpact * 1.25;
        $fatigueFactor = 1.0 + (($this->rating($player, 'fatigue', 0)) / 500);
        $expected = $minutes * $baseRate * $usage * $skillFactor * $defenseFactor * $fatigueFactor;
        $expected *= (0.92 + $performanceFactor * 0.08);

        return min(10, $this->poissonRandomizer(max(0.01, $expected)));
    }

    public function calculateFoul(Player $player, int $minutes, float $performanceFactor, float $defensiveImpact): int
    {
        if ($minutes <= 0) return 0;

        $positions = array_map('trim', explode('/', $player->position ?? 'SF'));
        $positionRates = [
            'PG' => 0.045,
            'SG' => 0.050,
            'SF' => 0.058,
            'PF' => 0.070,
            'C'  => 0.078,
        ];
        $baseRate = collect($positions)->map(fn($p) => $positionRates[$p] ?? 0.055)->average();

        $iq = $this->rating($player, 'basketball_iq_rating', 60);
        $workEthic = $this->rating($player, 'work_ethic_rating', 60);
        $stamina = $this->rating($player, 'stamina_rating', 70);
        $bashing = $this->rating($player, 'bashing_factor', 50);
        $fatigue = $this->rating($player, 'fatigue', 0);
        $defense = $this->rating($player, 'defense_rating', 60);
        $rookie = !empty($player->is_rookie) ? 1.04 : 1.0;

        $discipline = 1.12 - (($iq * 0.45 + $workEthic * 0.35 + $stamina * 0.20) / 100) * 0.22;
        $aggression = 0.94 + ($bashing / 100) * 0.20 + ($defense / 100) * 0.08;
        $fatigueFactor = 1.0 + ($fatigue / 250);
        $defensiveEnvironment = 1.0 + ($defensiveImpact * 0.60);

        $expected = $minutes * $baseRate * $discipline * $aggression * $fatigueFactor * $rookie * $defensiveEnvironment;
        $expected *= (0.95 + $performanceFactor * 0.05);

        // Occasional shooting/loose-ball foul variation.
        if (mt_rand(1, 100) <= 12) {
            $expected += 0.15;
        }

        return min(6, $this->poissonRandomizer(max(0.01, $expected)));
    }

    private function poissonRandomizer(float $lambda): int
    {
        // For small lambda (<30) use direct Knuth method
        if ($lambda <= 0) {
            return 0;
        }
        if ($lambda < 30.0) {
            $L = exp(-$lambda);
            $p = 1.0;
            $k = 0;
            while ($p > $L) {
                $k++;
                // mt_rand returns int — scale to (0,1]
                $p *= mt_rand() / mt_getrandmax();
            }
            return $k - 1;
        }

        // For large lambda use normal approximation (mu=lambda, sigma=sqrt(lambda))
        // sample standard normal using Box-Muller
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
        $sample = (int) round($lambda + sqrt($lambda) * $z);
        return max(0, $sample);
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

    private function usageMultiplier($player): float
    {
        $role = strtolower(trim(is_array($player) ? ($player['role'] ?? 'starter') : ($player->role ?? 'starter')));
        return [
            'star player' => 1.30,
            'all star' => 1.15,
            'starter' => 1.00,
            'role player' => 0.82,
            'bench' => 0.65,
        ][$role] ?? 0.90;
    }

    private function binomialRandomizer(int $trials, float $probability): int
    {
        if ($trials <= 0 || $probability <= 0) return 0;
        if ($probability >= 1) return $trials;

        $successes = 0;
        for ($i = 0; $i < $trials; $i++) {
            if ((mt_rand() / mt_getrandmax()) < $probability) {
                $successes++;
            }
        }
        return $successes;
    }

    public function calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowsMade, $fouls)
    {
        // Points must equal made shots. Do not multiply them again with
        // morale/work-ethic/foul factors after the makes have been generated.
        return max(
            0,
            ((int) $twoPointMade * 2) +
                ((int) $threePointMade * 3) +
                (int) $freeThrowsMade
        );
    }

    public function calculateRebounds(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        if ($minutes <= 0) return 0;

        $positions = array_map('trim', explode('/', $player->position ?? 'SF'));
        $positionRates = [
            'PG' => 0.105,
            'SG' => 0.125,
            'SF' => 0.175,
            'PF' => 0.245,
            'C'  => 0.305,
        ];
        $baseRate = collect($positions)->map(fn($p) => $positionRates[$p] ?? 0.17)->average();

        $rebounding = $this->rating($player, 'rebounding_rating', 60);
        $athleticism = $this->rating($player, 'athleticism_rating', 60);
        $strength = $this->rating($player, 'strength_rating', 60);
        $iq = $this->rating($player, 'basketball_iq_rating', 60);

        $skill = (
            $rebounding * 0.55 +
            $athleticism * 0.20 +
            $strength * 0.15 +
            $iq * 0.10
        ) / 75;

        $foulPenalty = max(0.72, 1 - ($fouls * 0.055));
        $expected = $minutes * $baseRate * $skill * $performanceFactor * $foulPenalty;

        // Rare ceiling games, gated by actual rebounding ability.
        if ($rebounding >= 88 && in_array($positions[0], ['PF', 'C'], true) && mt_rand(1, 1000) === 1) {
            $expected += mt_rand(5, 9);
        }

        return min(25, $this->poissonRandomizer(max(0.01, $expected)));
    }


    public function calculateBlocks(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        if ($minutes <= 0) return 0;

        $positions = array_map('trim', explode('/', $player->position ?? 'SF'));
        $positionRates = [
            'PG' => 0.0015,
            'SG' => 0.0025,
            'SF' => 0.0045,
            'PF' => 0.0085,
            'C'  => 0.0115,
        ];
        $baseRate = collect($positions)->map(fn($p) => $positionRates[$p] ?? 0.0045)->average();

        $defense = $this->rating($player, 'defense_rating', 60);
        $athleticism = $this->rating($player, 'athleticism_rating', 60);
        $strength = $this->rating($player, 'strength_rating', 60);
        $iq = $this->rating($player, 'basketball_iq_rating', 60);

        $skill = (
            $defense * 0.45 +
            $athleticism * 0.25 +
            $strength * 0.15 +
            $iq * 0.15
        ) / 70;

        $foulPenalty = max(0.65, 1 - ($fouls * 0.07));
        $expected = $minutes * $baseRate * $skill * $performanceFactor * $foulPenalty;

        return min(8, $this->poissonRandomizer(max(0.001, $expected)));
    }

    public function calculateSteals(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        if ($minutes <= 0) return 0;

        $positions = array_map('trim', explode('/', $player->position ?? 'SF'));
        $positionRates = [
            'PG' => 0.028,
            'SG' => 0.025,
            'SF' => 0.020,
            'PF' => 0.015,
            'C'  => 0.012,
        ];
        $baseRate = collect($positions)->map(fn($p) => $positionRates[$p] ?? 0.020)->average();

        $defense = $this->rating($player, 'defense_rating', 60);
        $iq = $this->rating($player, 'basketball_iq_rating', 60);
        $athleticism = $this->rating($player, 'athleticism_rating', 60);
        $bashing = $this->rating($player, 'bashing_factor', 50);

        $skill = (
            $defense * 0.45 +
            $iq * 0.30 +
            $athleticism * 0.20 +
            $bashing * 0.05
        ) / 70;

        $foulPenalty = max(0.70, 1 - ($fouls * 0.05));
        $expected = $minutes * $baseRate * $skill * $performanceFactor * $foulPenalty;

        return min(7, $this->poissonRandomizer(max(0.001, $expected)));
    }

    // $this->calculateShotAttempts($player, $minutes, $defensiveImpact,$fouls, $turnovers,$homeChemistry, true, true);
    public function calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $chemistry = 50, $isClutchTime = false, $isHomeAdvantage = false)
    {
        if ($minutes <= 0) {
            return [
                'two_point_attempts' => 0,
                'two_point_made' => 0,
                'three_point_attempts' => 0,
                'three_point_made' => 0,
                'free_throw_attempts' => 0,
                'free_throw_made' => 0,
            ];
        }

        $positions = array_map('trim', explode('/', $player->position ?? 'SF'));
        $position = $positions[0] ?? 'SF';

        $roleMultipliers = [
            'star player' => 1.25,
            'all star'    => 1.12,
            'starter'     => 1.00,
            'role player' => 0.78,
            'bench'       => 0.60,
        ];
        $role = strtolower(trim($player->role ?? 'starter'));
        $roleFactor = $roleMultipliers[$role] ?? 0.85;

        $shooting = $this->rating($player, 'shooting_rating', 60);
        $twoPoint = $this->rating($player, 'two_point_rating', 60);
        $threePoint = $this->rating($player, 'three_point_rating', 60);
        $ft = $this->rating($player, 'free_throw_rating', 60);
        $iq = $this->rating($player, 'basketball_iq_rating', 60);
        $athleticism = $this->rating($player, 'athleticism_rating', 60);
        $strength = $this->rating($player, 'strength_rating', 60);
        $morale = $this->rating($player, 'morale', 75);
        $stamina = $this->rating($player, 'stamina_rating', 70);
        $fatigue = $this->rating($player, 'fatigue', 0);

        $performanceFactor = $this->calculatePerformanceFactor($player, $isClutchTime);

        // Shot volume is based on role + minutes + offensive skill, not simply
        // minutes * 0.8. This produces realistic FGA ranges.
        $baseFgaPer36 = [
            'star player' => 20.0,
            'all star'    => 17.5,
            'starter'     => 14.0,
            'role player' => 10.5,
            'bench'       => 8.0,
        ][$role] ?? 12.0;

        $scoringAbility = (
            $shooting * 0.25 +
            $twoPoint * 0.25 +
            $threePoint * 0.25 +
            $iq * 0.15 +
            $athleticism * 0.10
        ) / 75;

        $usageFactor = $roleFactor * $scoringAbility;
        $usageFactor *= 0.94 + ($morale / 1000);
        $usageFactor *= 0.96 + ($chemistry / 2500);
        $usageFactor *= max(0.78, 1 - ($fatigue / 350));
        $usageFactor *= $performanceFactor;

        if ($isHomeAdvantage) $usageFactor *= 1.015;
        if (!empty($player->is_injured)) $usageFactor *= 0.65;

        $fga = ($minutes / 36) * $baseFgaPer36 * $usageFactor;
        $fga -= $turnovers * 0.20;
        $fga -= $fouls * 0.08;
        $fga *= mt_rand(94, 106) / 100;
        $fga = (int) round($this->clamp($fga, 0, min(30, $minutes * 0.90)));

        // Position provides a tendency, while actual 3PT rating strongly
        // influences whether the player takes those shots.
        $positionThree = [
            'PG' => 0.48,
            'SG' => 0.52,
            'SF' => 0.40,
            'PF' => 0.30,
            'C' => 0.16,
        ][$position] ?? 0.35;
        $threeSkill = 0.65 + (($threePoint - 60) / 300);
        $threeShare = $this->clamp($positionThree * $threeSkill, 0.08, 0.62);

        // High IQ players take fewer low-value shots, but good shooters are
        // more willing to shoot from three.
        $threeShare += (($threePoint - 70) / 1000);
        $threeShare = $this->clamp($threeShare, 0.08, 0.65);

        $threeAttempts = $this->binomialRandomizer($fga, $threeShare);
        $twoAttempts = max(0, $fga - $threeAttempts);

        // Free throws come from rim pressure, strength, athleticism and usage.
        $rimPressure = (
            $twoPoint * 0.35 +
            $athleticism * 0.30 +
            $strength * 0.20 +
            $iq * 0.15
        ) / 100;
        $ftaRate = $this->clamp(0.10 + ($rimPressure * 0.14), 0.08, 0.25);
        $ftaRate *= $role === 'star player' ? 1.08 : 1.0;
        $ftaRate *= 1 + ($defensiveImpact * 0.15);
        $freeThrowAttempts = $this->binomialRandomizer($fga, $ftaRate);

        // Shooting efficiency: ratings are the anchor; performance factors
        // only move the result modestly. This avoids 90% shooting games.
        $twoPct = 0.38 + ($twoPoint / 100) * 0.22 + ($shooting / 100) * 0.05;
        $twoPct += (($iq - 70) / 1000);
        $twoPct += (($morale - 75) / 1500);
        $twoPct *= $performanceFactor;
        $twoPct *= max(0.88, 1 - ($defensiveImpact * 0.70));
        $twoPct *= max(0.88, 1 - ($fatigue / 600));
        $twoPct = $this->clamp($twoPct, 0.30, 0.70);

        $threePct = 0.25 + ($threePoint / 100) * 0.22 + ($shooting / 100) * 0.04;
        $threePct += (($iq - 70) / 1500);
        $threePct *= $performanceFactor;
        $threePct *= max(0.90, 1 - ($defensiveImpact * 0.50));
        $threePct *= max(0.90, 1 - ($fatigue / 700));
        $threePct = $this->clamp($threePct, 0.20, 0.48);

        $ftPct = 0.68 + ($ft / 100) * 0.25;
        $ftPct *= $performanceFactor;
        $ftPct = $this->clamp($ftPct, 0.55, 0.97);

        $twoMade = $this->binomialRandomizer($twoAttempts, $twoPct);
        $threeMade = $this->binomialRandomizer($threeAttempts, $threePct);
        $ftMade = $this->binomialRandomizer($freeThrowAttempts, $ftPct);

        return [
            'two_point_attempts'   => $twoAttempts,
            'two_point_made'       => min($twoMade, $twoAttempts),
            'three_point_attempts' => $threeAttempts,
            'three_point_made'     => min($threeMade, $threeAttempts),
            'free_throw_attempts'  => $freeThrowAttempts,
            'free_throw_made'      => min($ftMade, $freeThrowAttempts),
        ];
    }

    public function updateSeasonStats($playerGameStats, $gameData, $isPlayoff)
    {
        if (empty($playerGameStats)) {
            throw new Exception("Player game stats are empty. Cannot update season stats.");
        }

        try {
            // Find max values for each category
            $maxPoints = max(array_column($playerGameStats, 'points'));
            $maxRebounds = max(array_column($playerGameStats, 'rebounds'));
            $maxAssists = max(array_column($playerGameStats, 'assists'));
            $maxSteals = max(array_column($playerGameStats, 'steals'));
            $maxBlocks = max(array_column($playerGameStats, 'blocks'));

            // Determine Best Player of the Game (BPG) using Efficiency Formula
            $bestPlayerId = null;
            $bestEfficiency = -INF;


            foreach ($playerGameStats as &$stats) {
                if (isset($stats['passing_rating'])) {
                    unset($stats['passing_rating']);
                }

                // Update Player Game Stats
                PlayerGameStats::updateOrCreate(
                    [
                        'player_id' => $stats['player_id'],
                        'game_id' => $stats['game_id'],
                        'season_id' => $stats['season_id'],
                        'team_id' => $stats['team_id'],
                    ],
                    $stats
                );

                // Calculate efficiency (EFF) for Best Player of the Game
                $efficiency = ($stats['points'] + $stats['rebounds'] + $stats['assists'] + $stats['steals'] + $stats['blocks'])
                    - (($stats['fouls'] ?? 0) + ($stats['turnovers'] ?? 0)); // Assuming fg_missed exists

                if ($efficiency > $bestEfficiency) {
                    $bestEfficiency = $efficiency;
                    $bestPlayerId = $stats['player_id'];
                }

                // Assign Game Leader Titles
                $stats['points_game_leader'] = ($stats['points'] == $maxPoints) ? 1 : 0;
                $stats['rebounds_game_leader'] = ($stats['rebounds'] == $maxRebounds) ? 1 : 0;
                $stats['assists_game_leader'] = ($stats['assists'] == $maxAssists) ? 1 : 0;
                $stats['steals_game_leader'] = ($stats['steals'] == $maxSteals) ? 1 : 0;
                $stats['blocks_game_leader'] = ($stats['blocks'] == $maxBlocks) ? 1 : 0;
            }

            // Mark the Best Player of the Game (BPG)
            foreach ($playerGameStats as &$stats) {
                $stats['bpg_game_leader'] = ($stats['player_id'] == $bestPlayerId) ? 1 : 0;

                if ($isPlayoff) {
                    $this->storeStats->storePlayerSeasonPlayoffStats($stats['team_id'], $stats['player_id']);
                } else {
                    $this->storeStats->storePlayerSeasonStats($stats['team_id'], $stats['player_id']);
                }

                $seasonStatsTable = $isPlayoff ? 'player_season_playoff_stats' : 'player_season_stats';

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

                // Reduce hardship contract games for players on temporary contracts
                $player = DB::table('players')->where('id', $stats['player_id'])->first();

                if ($player && $player->hardship_contract > 0) {
                    $this->handleHardshipContract($player, $stats);
                }
            }
        } catch (Exception $e) {
            // Log error for debugging
            // Log::error("Error updating season stats: " . $e->getMessage());

            // Optionally, throw the error again to stop execution
            throw new Exception("Failed to update season stats. Please check logs." . $e->getMessage());
        }
    }

    public function handleHardshipContract($player, $stats)
    {

        $updatedContract = $player->hardship_contract - 1;
        $teamId = $updatedContract > 0 ? $player->team_id : 0;

        if ($updatedContract == 0) {
            DB::table('transactions')->insert([
                'player_id' => $stats['player_id'],
                'season_id' => $stats['season_id'],
                'details' => 'Has ended his 10-game hardship contract.',
                'from_team_id' => $player->team_id,
                'to_team_id' => 0,
                'status' => 'waived',
            ]);
        }

        DB::table('players')->updateOrInsert(
            ['id' => $stats['player_id']],
            [
                'hardship_contract' => $updatedContract,
                'team_id' =>  $teamId,
            ]
        );

        return true;
    }
}
