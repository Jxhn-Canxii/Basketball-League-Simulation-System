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
            $role = $player->role ?? 'bench';

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
                'star' => 32,
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
            $staminaFactor  = $player->stamina_rating / 100;
            $strengthFactor = $player->strength_rating / 100;
            $currentFatigue = $player->fatigue;
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
                $fatigueIncrease = $minutes * (1 - $staminaFactor * 0.5);
                $newFatigue = min(20, $currentFatigue + round($fatigueIncrease)); // Cap at 20
            }

            // STEP 4: Injury chance check using injury_prone_percentage
            if ($newFatigue >= 20) {
                $triggerInjuryChance = rand(1, 100);

                if ($triggerInjuryChance <= 30) { // 30% chance to trigger injury logic
                    $injuryRoll = rand(1, 100);
                    if ($injuryRoll <= $player->injury_prone_percentage) {
                        $this->causeInjury($player, $gameId, $seasonId);
                        return;
                    }
                }

                // If not injured, reset fatigue
                $newFatigue = 0;
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


    public function calculatePerformanceFactor($player)
    {
        try {
            // Base performance factor with a random value between 100 and 120
            $basePerformanceFactor = rand(100, 120) / 100;

            // Adjust the factor based on player fatigue
            $fatigueFactor = (100 - $player->fatigue) / 100;
            $performanceFactor = $basePerformanceFactor * $fatigueFactor;

            // Further adjustments based on player injury status
            if ($player->is_injured) {
                // If injured, reduce performance factor by a percentage (e.g., 50% less)
                $performanceFactor *= 0.5;
            }

            // Optionally adjust further based on player ratings, like leadership or basketball IQ
            if ($player->leadership_rating > 70) {
                // If the player has a high leadership rating, boost performance slightly
                $performanceFactor *= 1.05;
            }

            // Return the final performance factor
            return round($performanceFactor, 2);
        } catch (\Exception $e) {

            return 1.0; // Default performance factor in case of error
        }
    }

    public function calculateDefensiveImpact($opponentTeamId)
    {
        $seasonId = get_current_season_id();
        // Step 1: Get average defense_rating, rebounding_rating, and morale
        $playerAverages = DB::table('players')
            ->where('team_id', $opponentTeamId)
            ->where('is_active', 1)
            ->selectRaw('AVG(defense_rating) as defense_rating, AVG(rebounding_rating) as rebounding_rating')
            ->first();

        $defenseRating = $playerAverages->defense_rating ?? 0;
        $reboundingRating = $playerAverages->rebounding_rating ?? 0;
        $morale = $playerAverages->morale ?? 0;

        // Step 3: Calculate the overall defensive score
        $overallDefensiveRating = ($defenseRating + $reboundingRating) / 2;

        // Step 4: Combine skill, morale, and chemistry
        $combinedImpact = (
            ($overallDefensiveRating * 0.6) + ($morale * 0.2)
        );

        // Step 5: Normalize
        return floor($combinedImpact / 30);
    }

    public function calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact)
    {
        if ($minutes === 0) return 0;

        $baseRates = [
            'PG' => 0.07,
            'SG' => 0.06,
            'SF' => 0.05,
            'PF' => 0.04,
            'C'  => 0.03,
        ];

        $positions = explode('/', $player->position ?? 'SF');
        $baseRate = collect($positions)
            ->map(fn($pos) => $baseRates[trim($pos)] ?? 0.05)
            ->average();

        $iqPassFactor = (200 - ($player->passing_rating + $player->basketball_iq_rating)) / 200;
        $adjustedRate = ($baseRate + ($defensiveImpact / 250)) * (1 + $iqPassFactor / 2);

        $turnovers = round($minutes * $adjustedRate * $performanceFactor);
        return min($turnovers, 8);
    }

    public function calculateFoul(Player $player, int $minutes, float $performanceFactor, float $defensiveImpact): int
    {
        if ($minutes === 0) return 0;

        // 1. BASE FOUL RATE BY POSITION/ROLE (Primary driver)
        $positionRates = [
            'PG' => 0.03,  // Point guards foul least
            'SG' => 0.04,
            'SF' => 0.05,
            'PF' => 0.07,
            'C'  => 0.09   // Big men foul most
        ];

        // Handle multi-position roles (e.g., "PG/SG")
        $positions = explode('/', $player->role ?? $player->position ?? 'SF');
        $baseRate = collect($positions)
            ->map(fn($pos) => $positionRates[trim($pos)] ?? 0.05)
            ->average();

        // 2. PLAYER ATTRIBUTE MODIFIERS (Weighted contributions)
        $controlFactors = [
            'positive' => [
                'basketball_iq_rating' => 0.25,  // Smart players foul less
                'work_ethic_rating'    => 0.20,  // Disciplined players
                'stamina_rating'       => 0.15,  // Better conditioning
                'morale'               => 0.15,  // Happy players
                'leadership_rating'    => 0.10   // On-court decision making
            ],
            'negative' => [
                'bashing_factor'      => 0.30,  // Aggressive players foul more
                'fatigue'             => 0.25,  // Tired players
                'injury_prone_percentage' => 0.10 // Injury-prone players
            ]
        ];

        // Calculate control score (0-100 scale)
        $positiveScore = array_reduce(
            array_keys($controlFactors['positive']),
            fn($carry, $attr) => $carry + ($player->$attr * $controlFactors['positive'][$attr]),
            0
        );

        $negativeScore = array_reduce(
            array_keys($controlFactors['negative']),
            fn($carry, $attr) => $carry + ($player->$attr * $controlFactors['negative'][$attr]),
            0
        );

        $controlScore = ($positiveScore - $negativeScore) / 100;

        // 3. DYNAMIC MODIFIERS
        $defensiveAggression = ($player->defense_rating / 100) * ($defensiveImpact / 100);
        $fatiguePenalty = 1 + ($player->fatigue / 100); // 1.0-2.0 multiplier
        $rookiePenalty = $player->is_rookie ? 1.15 : 1.0; // Rookies foul 15% more

        // 4. FINAL FOUL CALCULATION
        $adjustedRate = $baseRate
            * (1 + $defensiveAggression)  // Aggressive defense
            * (1.5 - $controlScore)       // Control score impact
            * $fatiguePenalty             // Fatigue effect
            * $rookiePenalty;             // Rookie adjustment

        // 5. APPLY PERFORMANCE FACTOR & RANDOMNESS
        $fouls = round($minutes * $adjustedRate * $performanceFactor) + rand(-1, 1);

        // 6. SPECIAL CASES
        // Injured players foul more carelessly
        if ($player->is_injured) {
            $fouls += rand(0, 1);
        }

        // "Hack-a-Shaq" rule: Poor FT shooters get targeted
        if ($player->free_throw_rating < 50 && rand(1, 100) > 70) {
            $fouls += 1;
        }

        return min(max($fouls, 0), 6); // Clamp to 0-6 fouls
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

    public function calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowsMade, $fouls)
    {
        // Base points
        $points = ($twoPointMade * 2) + ($threePointMade * 3) + $freeThrowsMade;

        // Foul trouble lowers aggressiveness (less shot attempts)
        $foulPenalty = max(0, 1 - ($fouls * 0.05));

        // Work ethic + stamina help players maintain scoring despite fouls
        $resilience = ($player->work_ethic_rating + $player->stamina_rating) / 200; // 0 - 1

        return max(round($points * ($foulPenalty + ($resilience * 0.1))), 0);
    }

    public function calculateRebounds(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        // Position weights tuned to NBA averages
        $positionWeights = [
            'C' => 0.35,
            'PF' => 0.30,
            'SF' => 0.20,
            'SG' => 0.15,
            'PG' => 0.10
        ];
        $positionFactor = $positionWeights[$player->position] ?? 0.20;

        // Base per-minute expected rebounds (ceiling keeps averages realistic)
        $reboundPerMinute = min(
            0.70,
            $positionFactor * (
                ($player->rebounding_rating * 0.65 +
                    $player->athleticism_rating * 0.25 +
                    $player->strength_rating * 0.10) / 100.0
            )
        );

        // Softer foul penalty (players still rebound with fouls)
        $foulPenalty = max(0.5, 1 - ($fouls * 0.08));

        // Expected (lambda) rebounds
        $expected = $reboundPerMinute * $minutes * $performanceFactor * $foulPenalty;

        // --- Monster spike gating (ultra-rare) ---
        // Example: base 0.0005 ~ 1 in 2000. Adjust as needed.
        $monsterChance = 0.0005;
        $spike = 0;
        if (mt_rand() / mt_getrandmax() < $monsterChance && $player->rebounding_rating >= 88 && in_array($player->position, ['C', 'PF'])) {
            // Add spike to expected before sampling (so stochastic)
            $expected += rand(10, 18);
        }

        //actual rebounds from Poisson(lambda = expected)
        $actual = $this->poissonRandomizer($expected);

        return (int) $actual;
    }


    public function calculateBlocks(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        // Position-based caps
        $positionCaps = ['C' => 0.40, 'PF' => 0.35, 'SF' => 0.25, 'SG' => 0.15, 'PG' => 0.10];

        $blocksPerMinute = min(
            $positionCaps[$player->position] ?? 0.20,
            ($player->blocks_rating * 0.6 +
                $player->athleticism_rating * 0.25 +
                $player->defense_rating * 0.15) / 250
        );

        // Stronger foul impact
        $foulPenalty = max(0.2, 1 - ($fouls * 0.15));

        $expected = round($blocksPerMinute * $minutes * $performanceFactor * $foulPenalty);

        //actual blocks from Poisson(lambda = expected)
        $actual = $this->poissonRandomizer($expected);

        return (int) $actual;
    }

    public function calculateSteals(Player $player, int $minutes, float $performanceFactor, int $fouls): int
    {
        // More conservative base rate
        $stealsPerMinute = min(
            0.30, // Absolute max
            ($player->steals_rating * 0.5 +
                $player->basketball_iq_rating * 0.3 +
                $player->athleticism_rating * 0.2) / 300
        );

        // Fouls hurt steals more
        $foulPenalty = max(0.4, 1 - ($fouls * 0.08));

        // Leadership reduces reckless steals
        $discipline = 1 - ($player->leadership_rating / 500);

        $expected = round($stealsPerMinute * $minutes * $performanceFactor * $foulPenalty * $discipline);

        //actual steals from Poisson(lambda = expected)
        $actual = $this->poissonRandomizer($expected);

        return (int) $actual;
    }

    // $this->calculateShotAttempts($player, $minutes, $defensiveImpact,$fouls, $turnovers,$homeChemistry, true, true);
    public function calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $chemistry = 50, $isClutchTime = false, $isHomeAdvantage)
    {
        $positionWeights = [
            'PG' => ['two_point' => 0.5, 'three_point' => 0.5, 'free_throw' => 0.6],
            'SG' => ['two_point' => 0.4, 'three_point' => 0.6, 'free_throw' => 0.5],
            'SF' => ['two_point' => 0.5, 'three_point' => 0.5, 'free_throw' => 0.5],
            'PF' => ['two_point' => 0.7, 'three_point' => 0.3, 'free_throw' => 0.5],
            'C'  => ['two_point' => 0.8, 'three_point' => 0.2, 'free_throw' => 0.4],
        ];

        $roleMultipliers = [
            'star player' => 1.1,
            'all star'    => 1.05,
            'starter'     => 1.0,
            'role player' => 0.85,
            'bench'       => 0.7,
        ];

        $positions = explode('/', $player->position ?? 'SF');
        $positionCount = count($positions);

        $positionFactor = ['two_point' => 0, 'three_point' => 0, 'free_throw' => 0];
        foreach ($positions as $pos) {
            $pos = trim($pos);
            $weights = $positionWeights[$pos] ?? $positionWeights['SF'];
            $positionFactor['two_point'] += $weights['two_point'] / $positionCount;
            $positionFactor['three_point'] += $weights['three_point'] / $positionCount;
            $positionFactor['free_throw'] += $weights['free_throw'] / $positionCount;
        }

        $roleFactor = $roleMultipliers[strtolower($player->role)] ?? 1.0;
        $fatigueFactor = max(0.5, (100 - ($player->fatigue ?? 0)) / 100);
        $injuryFactor = $player->is_injured ? 0.3 : 1.0;
        $clutchBoost = ($isClutchTime && ($player->clutch_rating ?? 50) > 80) ? 1.2 : 1.0;

        // 🆕 New: Chemistry and Morale factors
        $morale = $player->morale ?? 50;
        $moraleFactor = 0.9 + ($morale / 1000);     // 0.9 ~ 1.4 range (at morale 40 ~ 100)
        $chemistryFactor = 0.9 + ($chemistry / 1000); // 0.9 ~ 1.4 range (at chemistry 40 ~ 100)
        $homeAdvantageFactor = $isHomeAdvantage ? 1.05 : 1.0; // 5% boost if at home

        $baseAttempts = max(1, round($minutes * 0.8));
        $foulImpact = $fouls * 0.05;
        $turnoverImpact = $turnovers * 0.1;
        $adjustedBaseAttempts = max(0, $baseAttempts - ($foulImpact + $turnoverImpact));

        $attemptBias = rand(85, 115) / 100;
        $threePointWeight = $positionFactor['three_point'] * $attemptBias;
        $twoPointWeight = 1 - $threePointWeight;

        $totalFactor = $roleFactor * $fatigueFactor * $injuryFactor * $clutchBoost * $moraleFactor * $chemistryFactor * $homeAdvantageFactor;

        $rawAdjustedAttempts = $adjustedBaseAttempts * $totalFactor;

        $maxPointsPerMinute = 3.0;
        $maxAttempts = ($player->role === 'star player') ? 40 : 35;
        $adjustedAttempts = min($rawAdjustedAttempts, $maxAttempts);

        $threePointAttempts = round($adjustedAttempts * $threePointWeight);
        $twoPointAttempts = round($adjustedAttempts * $twoPointWeight);

        $freeThrowAttempts = round(
            ($twoPointAttempts * 0.3 + $threePointAttempts * 0.1) * (($player->strength_rating ?? 70) / 100)
        );

        // Defense impact
        $defenseScaling = 1 + ($adjustedAttempts / 50);
        $adjustedTwoPointAttempts = max(0, $twoPointAttempts - ($defensiveImpact * $defenseScaling));
        $adjustedThreePointAttempts = max(0, $threePointAttempts - ($defensiveImpact * $defenseScaling));
        $adjustedFreeThrowAttempts = max(0, $freeThrowAttempts - ($defensiveImpact * 0.5));

        // Efficiency drop from high volume
        $volumePenalty = 1 - min(0.15, max(0, $adjustedAttempts - 25) * 0.01);

        $twoPointAccuracy = (
            ($player->two_point_rating ?? 60) / 100 *
            ($player->basketball_iq_rating ?? 60) / 100 *
            $fatigueFactor * $injuryFactor * $volumePenalty * $moraleFactor * $chemistryFactor
        );

        $threePointAccuracy = (
            ($player->three_point_rating ?? 60) / 100 *
            ($player->basketball_iq_rating ?? 60) / 100 *
            $fatigueFactor * $injuryFactor * $volumePenalty * $moraleFactor * $chemistryFactor
        );

        $freeThrowAccuracy = (
            ($player->free_throw_rating ?? 60) / 100 *
            ($player->work_ethic_rating ?? 60) / 100 *
            $fatigueFactor * $injuryFactor * $moraleFactor
        );

        $twoPointMade = min(rand(0, round($adjustedTwoPointAttempts * $twoPointAccuracy)), $adjustedTwoPointAttempts);
        $threePointMade = min(rand(0, round($adjustedThreePointAttempts * $threePointAccuracy)), $adjustedThreePointAttempts);
        $freeThrowMade = min(rand(0, round($adjustedFreeThrowAttempts * $freeThrowAccuracy)), $adjustedFreeThrowAttempts);

        // $twoPointMade = $this->binomialRandomizer($adjustedTwoPointAttempts, min(0.75, $twoPointAccuracy));
        // $threePointMade = $this->binomialRandomizer($adjustedThreePointAttempts, min(0.55, $threePointAccuracy));
        // $freeThrowMade = $this->binomialRandomizer($adjustedFreeThrowAttempts, min(0.95, $freeThrowAccuracy));

        // Cap scoring to a realistic points per minute
        $estimatedPoints = ($twoPointMade * 2) + ($threePointMade * 3) + $freeThrowMade;
        $maxPointsPerMinute = 3.0;
        $maxPoints = round($minutes * $maxPointsPerMinute);

        if ($estimatedPoints > $maxPoints) {
            $scalingFactor = $maxPoints / $estimatedPoints;

            $twoPointMade = round($twoPointMade * $scalingFactor);
            $threePointMade = round($threePointMade * $scalingFactor);
            $freeThrowMade = round($freeThrowMade * $scalingFactor);
        }

        return [
            'two_point_attempts'     => $adjustedTwoPointAttempts,
            'two_point_made'         => $twoPointMade,
            'three_point_attempts'   => $adjustedThreePointAttempts,
            'three_point_made'       => $threePointMade,
            'free_throw_attempts'    => $adjustedFreeThrowAttempts,
            'free_throw_made'        => $freeThrowMade,
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

                if($isPlayoff){
                    $this->storeStats->storePlayerSeasonPlayoffStats($stats['team_id'], $stats['player_id']);
                }else{
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

    public function handleHardshipContract($player,$stats){
        
            $updatedContract = $player->hardship_contract - 1;
            $teamId = $updatedContract > 0 ? $player->team_id : 0;

            if($updatedContract == 0){
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
