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
                    DB::table('players')->where('id', $player->id)->update([
                        'is_injured' => false,
                        'injury_type' => null,
                    ]);

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
