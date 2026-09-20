<?php

namespace App\Services\Team;

use App\Models\Player;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class TeamInjuryService
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }

    public function handleInjuredPlayer($player, $seasonId, $seasonStatus)
    {
        try {
            // Check if player is already retired or inactive
            if (!$player->is_active) {
                return;
            }

            // Track if retirement age was adjusted due to severe injury
            $retirementReason = 'reached retirement age';
            $injuryHistoryCount = 0;

            // Handle injury if player is injured
            if ($player->is_injured) {
                // Process injury recovery
                $deductionPerGame = 1;

                if ($player->injury_recovery_games > 0) {
                    // Decrement injury recovery games
                    DB::table('players')->where('id', $player->id)->decrement('injury_recovery_games', $deductionPerGame);
                    $updatedRecoveryGames = DB::table('players')->where('id', $player->id)->value('injury_recovery_games');
                } else {
                    $updatedRecoveryGames = $player->injury_recovery_games;
                }

                // Load injury config
                $injuries = config('injuries');
                $currentInjury = $player->injury_type;

                // Define severe injury criteria
                $severeInjuryThreshold = [
                    'recovery_games' => 15, // Severe if recovery takes 15+ games
                    'performance_impact' => 0.3, // Severe if performance impact is 30% or less
                ];
                $injuryHistoryThreshold = 5; // Threshold for "too many" injuries
                $retirementAgeReduction = 2; // Years to reduce retirement age
                $minimumRetirementAge = max($player->age, 30); // Minimum retirement age

                // Non-injury factors that shouldn't affect retirement age
                $nonInjuryFactors = [
                    'resting',
                    'suspension',
                    'personal_reason',
                    'logistics_issue',
                    'family_emergency',
                    'contract_dispute',
                    'mental_health',
                    'player_protest',
                    'travel_fatigue'
                ];

                // Check if the current injury is severe and not a non-injury factor
                $isSevereInjury = false;
                if (array_key_exists($currentInjury, $injuries) && !in_array($currentInjury, $nonInjuryFactors)) {
                    $injuryDetails = $injuries[$currentInjury];
                    if (
                        $injuryDetails['recovery_games'] >= $severeInjuryThreshold['recovery_games'] ||
                        $injuryDetails['performance_impact'] <= $severeInjuryThreshold['performance_impact']
                    ) {
                        $isSevereInjury = true;
                    }
                }

                // Get injury history count
                $injuryHistoryCount = DB::table('injury_histories')
                    ->where('player_id', $player->id)
                    ->count();

                // Adjust retirement age if injury is severe and injury history is high
                if ($isSevereInjury && $injuryHistoryCount > $injuryHistoryThreshold) {
                    $newRetirementAge = max($player->retirement_age - $retirementAgeReduction, $minimumRetirementAge);
                    if ($newRetirementAge < $player->retirement_age) {
                        DB::table('players')->where('id', $player->id)->update([
                            'retirement_age' => $newRetirementAge,
                            'updated_at' => now(),
                        ]);
                        // Update player object and retirement reason
                        $player->retirement_age = $newRetirementAge;
                        $retirementReason = "severe injury history ({$injuryHistoryCount} injuries)";
                    }
                }

                // If player fully recovered
                if ($updatedRecoveryGames <= 0) {
                    DB::table('players')->where('id', $player->id)->update(['injury_type' => null]);

                    // Update injury history recovery date
                    DB::table('injury_histories')
                        ->where('player_id', $player->id)
                        ->whereNull('recovery_date')
                        ->latest()
                        ->update([
                            'recovery_date' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            // Check for forced retirement
            if ($player->age >= $player->retirement_age) {
                // Get team name for transaction log
                $teamName = $player->team_id
                    ? $this->helper->getTeamName($player->team_id)
                    : 'No Team';

                // Create detailed transaction message
                $details = "{$player->name} retired from the league at age {$player->age} (retirement age: {$player->retirement_age}) due to {$retirementReason}. Last team: {$teamName}";
                if ($retirementReason === 'severe injury history') {
                    $details .= " (injury count: {$injuryHistoryCount})";
                }

                // Log retirement in transactions table
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => $details,
                    'from_team_id' => $player->team_id ?? 0,
                    'to_team_id' => 0,
                    'status' => 'retired',
                ]);

                DB::table('player_contracts')
                    ->where('player_id', $player->id)
                    ->where('status', 'signed')
                    ->update(['status' => 'terminated']);

                DB::table('players')
                    ->where('id', $player->id)
                    ->update([
                        'team_id' => 0,
                        'contract_years' => 0,
                        'salary' => 0,
                        'contract_type' => 0,
                        'player_option' => 0,
                        'team_option' => 0,
                        'no_trade_clause' => 0,
                        'is_active' => false
                    ]);

                return;
            }
        } catch (\Exception $e) {
        }
    }

    public function resetFatigue($teamId)
    {

        DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_injured', 0)
            ->where('is_active', 1)
            ->update([
                'fatigue' => 0,
            ]);
    }

    public function fatigueAdjustment($morale, $fatigue)
    {

        $moraleFactor = $this->moraleFactor($morale);

        $newFatigue = min(0, ($fatigue - ($fatigue * $moraleFactor)));

        return $newFatigue;
    }

    public function fatigueRate($player, $minutes, $gameId)
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

        } catch (\Exception $e) {
            // Log::error("Error updating fatigue for player {$player->id}: " . $e->getMessage());
        }
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

    public function causeInjury($player, $gameId, $seasonId)
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

    private function moraleFactor(int $morale)
    {

        switch ($morale) {
            case $morale > 90:
                return 0.50;
                break;
            case $morale > 80 && $morale < 90:
                return 0.40;
                break;
            case $morale > 70 && $morale < 80:
                return 0.30;
                break;
            case $morale > 60 && $morale < 70:
                return 0.20;
                break;
            case $morale < 50:
                return 0.10;
                break;
            default:
                return 0.05;
                break;
        }
    }

}
