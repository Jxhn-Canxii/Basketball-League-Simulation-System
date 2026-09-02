<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Contract\ContractService;
use App\Services\Coach\CoachDecisionService;


class ContractController extends Controller
{
    protected  $contractService;
    protected  $coachDecisionService;

    public function __construct()
    {
        $this->contractService = new ContractService();
        $this->coachDecisionService = new CoachDecisionService();
    }

    public function handleHardshipContract($player, $stats)
    {
        $newHardshipGames = $player->hardship_contract - 1;
        if ($newHardshipGames > 0) {
            // Reduce remaining hardship games
            DB::table('players')->where('id', $player->id)->update([
                'hardship_contract' => $newHardshipGames
            ]);
        } else {
            // Check if player performance is good (e.g., points or eff >= 10)
            $performanceGood = false;
            $seasonId = $stats['season_id'] ?? get_current_season_id();
            $playerSeasonStats = DB::table('player_season_stats') // good
                ->where('player_id', $player->id)
                ->where('season_id', $seasonId)
                ->first();

            $effPerGame = $playerSeasonStats->eff ?? 0;
            $minutesPerGame = $playerSeasonStats->avg_minutes_per_game ?? 0;

            $performanceGood = $effPerGame >= 10 || ($effPerGame >= 7 && $minutesPerGame <= 15); // efficient in small minutes
            if ($performanceGood) {
                // Sign as regular: assign contract years (e.g., 1 or based on role)
                $contractYears = $this->getContractYearsBasedOnRole($player->role);
                DB::table('players')->where('id', $player->id)->update([
                    'hardship_contract' => 0,
                    'contract_years' => $contractYears,
                ]);
                // Waive the injured player at the same position with the longest injury recovery time
                $longestInjured = DB::table('players')
                    ->where('team_id', $player->team_id)
                    ->where('is_injured', 1)
                    ->where('position', $player->position)
                    ->orderByDesc('injury_recovery_games')
                    ->first();

                if (!$longestInjured) {
                    // If none at the same position, get any injured player with the longest recovery time
                    $longestInjured = DB::table('players')
                        ->where('team_id', $player->team_id)
                        ->where('is_injured', 1)
                        ->orderByDesc('injury_recovery_games')
                        ->first();
                }

                if ($longestInjured) {
                    DB::table('players')->where('id', $longestInjured->id)->update([
                        'team_id' => 0,
                        'contract_years' => 0,
                    ]);
                    // Log transaction for waived player
                    DB::table('transactions')->insert([
                        'player_id' => $longestInjured->id,
                        'season_id' => $stats['season_id'],
                        'details' => 'Waived due to hardship player being signed as regular.',
                        'from_team_id' => $stats['team_id'],
                        'to_team_id' => 0,
                        'status' => 'waived',
                    ]);
                }
                // Log transaction for hardship player
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $stats['season_id'],
                    'details' => 'Signed a regular contract for the rest of the season.',
                    'from_team_id' => $stats['team_id'],
                    'to_team_id' => $stats['team_id'],
                    'status' => 'signed',
                ]);
            } else {
                // Hardship contract expired -> Release player back to free agency
                DB::table('players')->where('id', $player->id)->update([
                    'team_id' => 0, // Free agent pool
                    'contract_years' => 0, // Reset contract
                    'hardship_contract' => 0 // Clear hardship flag
                ]);
                // Log transaction
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $stats['season_id'],
                    'details' => 'Released after hardship contract expired.',
                    'from_team_id' => $stats['team_id'],
                    'to_team_id' => 0, // Free agent pool
                    'status' => 'released-hardship'
                ]);
            }
        }
    }
    
    public function getContractYearsBasedOnRole($role)
    {
        return $this->contractService->getContractYearsBasedOnRole($role);
    }

   public function endOfSeasonContractDecisions()
{
    $seasonId = get_current_season_id();

    $players = DB::table('players')
        ->where('team_id', '>', 0)
        ->where('is_active', 1)
        ->where('contract_years', '<=', 0)
        ->get();

    $results = [];

    foreach ($players as $player) {

        $teamId = (int) $player->team_id;

        /*
        |--------------------------------------------------------------------------
        | Get coach
        |--------------------------------------------------------------------------
        */

        $coach = $this->coachDecisionService
            ->getTeamCoach($teamId);

        /*
        |--------------------------------------------------------------------------
        | Get normal contract offer
        |--------------------------------------------------------------------------
        */

        $offer = $this->contractService
            ->getContractOffer(
                $player,
                $teamId
            );

        if (!$offer) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Base valuation
        |--------------------------------------------------------------------------
        */

        $baseScore = (float) (
            $offer['valuation']
            ?? $offer['salary']
            ?? 50
        );

        /*
        |--------------------------------------------------------------------------
        | Coach adjusts valuation
        |--------------------------------------------------------------------------
        */

        $signingScore = $this->coachDecisionService
            ->getSigningScore(
                $coach,
                $player,
                $baseScore
            );

        /*
        |--------------------------------------------------------------------------
        | Coach approval chance
        |--------------------------------------------------------------------------
        */

        $approvalChance = $this->coachDecisionService
            ->getSigningApprovalChance(
                $coach,
                $signingScore
            );

        /*
        |--------------------------------------------------------------------------
        | Existing automatic rules
        |--------------------------------------------------------------------------
        */

        $isMaxContract = (
            ($offer['contract_type'] ?? null) === 'max'
        );

        $salary = (float) (
            $offer['salary']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Max contracts should almost always be retained.
        |--------------------------------------------------------------------------
        */

        if ($isMaxContract) {
            $shouldSign = true;
        } else {

            /*
            | Existing MLE rule becomes the base decision,
            | then coach can influence it.
            */

            $baseDecision = (
                $salary >= $this->contractService->getMLE()
            );

            if ($baseDecision) {

                /*
                | Coach can still reject a player he strongly dislikes.
                */

                $shouldSign =
                    $this->coachDecisionService
                        ->randomDecision(
                            max(45, $approvalChance)
                        );

            } else {

                /*
                | Lower-valued players require stronger coach support.
                */

                $shouldSign =
                    $this->coachDecisionService
                        ->randomDecision(
                            $approvalChance * 0.70
                        );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Sign player
        |--------------------------------------------------------------------------
        */

        if ($shouldSign) {

            $years = (int) (
                $offer['years']
                ?? $offer['contract_years']
                ?? 1
            );

            DB::table('players')
                ->where('id', $player->id)
                ->update([
                    'contract_years' => $years,
                    'updated_at' => now(),
                ]);

            $results[] = [
                'player_id' => $player->id,
                'team_id' => $teamId,
                'coach_id' => $coach?->id,
                'decision' => 'signed',
                'signing_score' => round($signingScore, 2),
                'approval_chance' => round($approvalChance, 2),
            ];

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Release player
        |--------------------------------------------------------------------------
        */

        DB::table('players')
            ->where('id', $player->id)
            ->update([
                'team_id' => 0,
                'contract_years' => 0,
                'updated_at' => now(),
            ]);

        $results[] = [
            'player_id' => $player->id,
            'team_id' => $teamId,
            'coach_id' => $coach?->id,
            'decision' => 'released',
            'signing_score' => round($signingScore, 2),
            'approval_chance' => round($approvalChance, 2),
        ];
    }

    return $results;
}
}
