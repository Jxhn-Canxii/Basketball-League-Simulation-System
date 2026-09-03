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
            } 
            else {

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
