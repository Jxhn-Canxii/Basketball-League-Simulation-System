<?php

namespace App\Services\Trade;

ini_set('max_execution_time', 0);

use App\Services\Archive\ArchiveService;
use Illuminate\Support\Facades\DB;
use App\Services\Player\PlayerValuationService;
use App\Services\Coach\CoachDecisionService;
use App\Services\Helper\HelperService;
use App\Services\League\StoryLineService;

class TradeValuationService
{
    protected $storyLineService;
    protected $helper;
    protected $coachDecisionService;
    protected $valuationService;
    protected $archive;

    protected float $maxPackageDifference = 15.0;

    public function __construct()
    {
        $this->helper = new HelperService();

        $this->valuationService = new PlayerValuationService();

        $this->coachDecisionService = new CoachDecisionService();

        $this->storyLineService = new StoryLineService();

        $this->archive = new ArchiveService();
    }


    public function evaluateTeamPackage(
        int $teamId,
        $outgoing,
        $incoming
    ): array {

        $coach =
            $this->coachDecisionService
            ->getTeamCoach($teamId);

        $outgoingValue = 0;
        $incomingValue = 0;

        $outgoingSalary = 0;
        $incomingSalary = 0;

        /*
        |--------------------------------------------------------------------------
        | Outgoing
        |--------------------------------------------------------------------------
        */

        foreach ($outgoing as $asset) {

            if (!empty($asset->player_id)) {

                $player =
                    DB::table('players')
                    ->where('id', $asset->player_id)
                    ->first();

                if (!$player) {
                    continue;
                }

                $baseValue =
                    $this->calculatePlayerValue(
                        $player
                    );

                $adjustedValue =
                    $this->coachDecisionService
                    ->getTradeValue(
                        $coach,
                        $player,
                        $baseValue
                    );

                $outgoingValue +=
                    $adjustedValue;

                $outgoingSalary +=
                    (float) ($player->salary ?? 0);
            } elseif (!empty($asset->draft_pick_right_id)) {

                $pick =
                    DB::table('draft_pick_rights')
                    ->where(
                        'id',
                        $asset->draft_pick_right_id
                    )
                    ->first();

                if ($pick) {

                    $outgoingValue +=
                        $this->calculateDraftPickValue(
                            $pick
                        );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Incoming
        |--------------------------------------------------------------------------
        */

        foreach ($incoming as $asset) {

            if (!empty($asset->player_id)) {

                $player =
                    DB::table('players')
                    ->where('id', $asset->player_id)
                    ->first();

                if (!$player) {
                    continue;
                }

                $baseValue =
                    $this->calculatePlayerValue(
                        $player
                    );

                $adjustedValue =
                    $this->coachDecisionService
                    ->getTradeValue(
                        $coach,
                        $player,
                        $baseValue
                    );

                $incomingValue +=
                    $adjustedValue;

                $incomingSalary +=
                    (float) ($player->salary ?? 0);
            } elseif (!empty($asset->draft_pick_right_id)) {

                $pick =
                    DB::table('draft_pick_rights')
                    ->where(
                        'id',
                        $asset->draft_pick_right_id
                    )
                    ->first();

                if ($pick) {

                    $incomingValue +=
                        $this->calculateDraftPickValue(
                            $pick
                        );
                }
            }
        }

        $netBenefit =
            $incomingValue -
            $outgoingValue;

        /*
        |--------------------------------------------------------------------------
        | Salary difference
        |--------------------------------------------------------------------------
        */

        $salaryDifference =
            $incomingSalary -
            $outgoingSalary;

        /*
        |--------------------------------------------------------------------------
        | Coach approval
        |--------------------------------------------------------------------------
        */

        $approvalChance =
            $this->coachDecisionService
            ->getTradeApprovalChance(
                $coach,
                $netBenefit
            );

        /*
        |--------------------------------------------------------------------------
        | Package balance modifier
        |--------------------------------------------------------------------------
        */

        if ($outgoingValue > 0) {

            $differencePercentage =
                (
                    ($incomingValue - $outgoingValue)
                    /
                    $outgoingValue
                ) * 100;

            /*
            | Very bad value for the team.
            */

            if ($differencePercentage <= -20) {
                $approvalChance -= 25;
            }

            /*
            | Excellent value.
            */

            if ($differencePercentage >= 15) {
                $approvalChance += 15;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Salary penalty
        |--------------------------------------------------------------------------
        */

        if (
            $outgoingSalary > 0 &&
            $incomingSalary >
            ($outgoingSalary * 1.25) + 100000
        ) {

            $approvalChance -= 15;
        }

        $approvalChance =
            max(
                5,
                min(
                    95,
                    $approvalChance
                )
            );

        return [
            'team_id' => $teamId,
            'coach_id' => $coach?->id,

            'outgoing_value' => round(
                $outgoingValue,
                2
            ),

            'incoming_value' => round(
                $incomingValue,
                2
            ),

            'net_benefit' => round(
                $netBenefit,
                2
            ),

            'outgoing_salary' => round(
                $outgoingSalary,
                2
            ),

            'incoming_salary' => round(
                $incomingSalary,
                2
            ),

            'salary_difference' => round(
                $salaryDifference,
                2
            ),

            'approval_chance' => round(
                $approvalChance,
                2
            ),
        ];
    }

    public function calculateDraftPickValue($pick): float {

        $round = (int) ($pick->round ?? 2);

        $season = (int) ($pick->season_id ?? 0);

        $currentSeason = get_current_season_id();

        $yearsAway = max(0,$season - $currentSeason);

        /*
        |--------------------------------------------------------------------------
        | Base value
        |--------------------------------------------------------------------------
        */

        if ($round === 1) {

            $value = 80;
        } elseif ($round === 2) {

            $value = 40;
        } else {

            $value = 20;
        }

        /*
        |--------------------------------------------------------------------------
        | Future pick discount
        |--------------------------------------------------------------------------
        */

        if ($yearsAway > 0) {

            $value *= pow(0.90, $yearsAway);
        }

        /*
        |--------------------------------------------------------------------------
        | Protection
        |--------------------------------------------------------------------------
        */

        $protection = strtolower(trim((string) ($pick->protections?? '')));

        if ($protection !== '' && $protection !== 'none') {

            $value *= 0.90;
        }

        return round($value,2);
    }

    /*
    |--------------------------------------------------------------------------
    | PACKAGE VALUE
    |--------------------------------------------------------------------------
    */

    public function getPackageValue(array $assets): float 
    {

        $value = 0;

        foreach ($assets as $asset) {

            $value += (float) ($asset['value'] ?? 0);
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | PACKAGE BALANCE
    |--------------------------------------------------------------------------
    */

    public function isPackageBalanced(array $outgoing,array $incoming): bool
    {

        if (empty($outgoing) || empty($incoming)) {
            return false;
        }

        $outgoingValue = $this->getPackageValue($outgoing);

        $incomingValue = $this->getPackageValue($incoming);

        if ($outgoingValue <= 0 || $incomingValue <= 0) {
            return false;
        }

        $difference = abs($outgoingValue - $incomingValue);

        $largest = max($outgoingValue,$incomingValue);

        $differencePercentage = ($difference / $largest) * 100;

        return $differencePercentage <= $this->maxPackageDifference;
    }

    /*
    |--------------------------------------------------------------------------
    | PLAYER VALUE
    |--------------------------------------------------------------------------
    */

    public function calculatePlayerValue($player): float
    {

        if (!$player) {
            return 0;
        }

        return (float) $this->valuationService->calculatePlayerValue($player);
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY PERFORMANCE SCORE
    |--------------------------------------------------------------------------
    */

    public function calculatePerformanceScore($player): float 
    {

        return $this->calculatePlayerValue($player);
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY TRADE BALANCE
    |--------------------------------------------------------------------------
    */

    public function validateTradeBalance(array $players): bool 
    {

        if (count($players) < 2) {
            return false;
        }

        $scores =
            array_map(
                function ($player) {
                    return (float) (
                        $player->composite_score
                        ?? 0
                    );
                },
                $players
            );

        $maxScore = max($scores);

        $minScore = min($scores);

        if(($maxScore - $minScore) > 15) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | COACH ADJUSTED TRADE VALUE
    |--------------------------------------------------------------------------
    */

    public function calculateCoachAdjustedTradeValue(int $teamId,$player,float $baseValue): float 
    {

        $coach = $this->coachDecisionService->getTeamCoach($teamId);

        return (float) $this->coachDecisionService->getTradeValue($coach,$player,$baseValue);
    }

    /*
    |--------------------------------------------------------------------------
    | SINGLE PLAYER TRADE EVALUATION
    |--------------------------------------------------------------------------
    */

    public function evaluateTradeForTeam(int $teamId,$outgoingPlayer,$incomingPlayer,float $outgoingBaseValue,float $incomingBaseValue): array 
    {

        $coach = $this->coachDecisionService->getTeamCoach($teamId);

        $outgoingValue = $this->coachDecisionService->getTradeValue($coach,$outgoingPlayer,$outgoingBaseValue);

        $incomingValue = $this->coachDecisionService->getTradeValue($coach,$incomingPlayer,$incomingBaseValue);

        $netBenefit = $incomingValue - $outgoingValue;

        $approvalChance =
            $this->coachDecisionService
            ->getTradeApprovalChance(
                $coach,
                $netBenefit
            );

        return [
            'team_id' => $teamId,
            'coach_id' => $coach?->id,
            'outgoing_value' => $outgoingValue,
            'incoming_value' => $incomingValue,
            'net_benefit' => $netBenefit,
            'approval_chance' => $approvalChance,
        ];
    }
}
