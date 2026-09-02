<?php

namespace App\Services\Coach;

use Illuminate\Support\Facades\DB;

class CoachDecisionService
{
    /**
     * Get the current active coach of a team.
     */
    public function getTeamCoach(int $teamId)
    {
        return DB::table('coaches')
            ->where('team_id', $teamId)
            ->where('is_active', 1)
            ->orderByDesc('coach_iq')
            ->first();
    }

    /**
     * Get overall coach quality.
     *
     * Used to determine how strongly the coach's
     * preferences should affect AI decisions.
     */
    public function getCoachQuality($coach): float
    {
        if (!$coach) {
            return 50;
        }

        $ratings = [
            (float) ($coach->coach_iq ?? 50),
            (float) ($coach->strategy_rating ?? 50),
            (float) ($coach->development_rating ?? 50),
            (float) ($coach->leadership_rating ?? 50),
        ];

        return array_sum($ratings) / count($ratings);
    }

    /**
     * Get how influential the coach is.
     *
     * 50 quality = 1.00
     * 70 quality = 1.20
     * 90 quality = 1.40
     */
    public function getCoachInfluence($coach): float
    {
        if (!$coach) {
            return 1.0;
        }

        $quality = $this->getCoachQuality($coach);

        return 0.5 + ($quality / 100);
    }

    /**
     * Normalize a string.
     *
     * Converts:
     * fast-paced
     * fast_paced
     * Fast Paced
     *
     * into:
     * fast paced
     */
    private function normalizeString(?string $value): string
    {
        return strtolower(
            str_replace(
                ['-', '_'],
                ' ',
                trim($value ?? '')
            )
        );
    }

    /**
     * Get normalized player type/archetype.
     */
    private function getPlayerType($player): string
    {
        return $this->normalizeString(
            $player->type
                ?? $player->archetype
                ?? $player->player_type
                ?? ''
        );
    }

    /**
     * Get normalized player role.
     */
    private function getPlayerRole($player): string
    {
        return $this->normalizeString(
            $player->role ?? ''
        );
    }

    /**
     * Get normalized coaching style.
     */
    private function getCoachingStyle($coach): string
    {
        return $this->normalizeString(
            $coach->coaching_style ?? 'balanced'
        );
    }

    /**
     * Get normalized preferred team composition.
     */
    private function getTeamComposition($coach): string
    {
        return $this->normalizeString(
            $coach->preferred_team_composition ?? 'any'
        );
    }

    /**
     * Get player age.
     */
    private function getPlayerAge($player): int
    {
        return (int) (
            $player->age
            ?? $player->player_age
            ?? 25
        );
    }

    /**
     * Calculate how well a player fits a coach.
     *
     * Returns approximately 0-100.
     */
    public function getPlayerFitScore($coach, $player): float
    {
        if (!$coach || !$player) {
            return 50;
        }

        $score = 50;

        $style = $this->getCoachingStyle($coach);
        $composition = $this->getTeamComposition($coach);
        $type = $this->getPlayerType($player);
        $role = $this->getPlayerRole($player);
        $age = $this->getPlayerAge($player);

        /*
        |--------------------------------------------------------------------------
        | Coaching style
        |--------------------------------------------------------------------------
        */

        switch ($style) {

            case 'defensive':

                if (
                    str_contains($type, 'defender') ||
                    str_contains($type, 'rim protector') ||
                    str_contains($type, 'two way') ||
                    str_contains($type, 'paint beast')
                ) {
                    $score += 12;
                }

                if (
                    str_contains($type, 'sharpshooter') ||
                    str_contains($type, 'scorer')
                ) {
                    $score -= 3;
                }

                break;

            case 'offensive':

                if (
                    str_contains($type, 'scorer') ||
                    str_contains($type, 'sharpshooter') ||
                    str_contains($type, 'playmaker') ||
                    str_contains($type, 'point forward') ||
                    str_contains($type, 'floor general')
                ) {
                    $score += 12;
                }

                if (
                    str_contains($type, 'defender') &&
                    !str_contains($type, 'two way')
                ) {
                    $score -= 2;
                }

                break;

            case 'fast paced':

                if (
                    str_contains($type, 'athletic') ||
                    str_contains($type, 'athletic finisher') ||
                    str_contains($type, 'slasher') ||
                    str_contains($type, 'playmaker') ||
                    str_contains($type, 'floor general')
                ) {
                    $score += 10;
                }

                if (
                    str_contains($type, 'post scorer') ||
                    str_contains($type, 'paint beast')
                ) {
                    $score -= 4;
                }

                break;

            case 'slow tempo':

                if (
                    str_contains($type, 'post') ||
                    str_contains($type, 'paint') ||
                    str_contains($type, 'rim protector') ||
                    str_contains($type, 'stretch four') ||
                    str_contains($type, 'versatile big')
                ) {
                    $score += 8;
                }

                if (
                    str_contains($type, 'athletic finisher') ||
                    str_contains($type, 'slasher')
                ) {
                    $score -= 3;
                }

                break;

            case 'balanced':
            default:

                if (
                    str_contains($type, 'two way') ||
                    str_contains($type, 'versatile')
                ) {
                    $score += 8;
                }

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Preferred team composition
        |--------------------------------------------------------------------------
        */

        switch ($composition) {

            case 'offensive':

                if (
                    str_contains($type, 'scorer') ||
                    str_contains($type, 'sharpshooter') ||
                    str_contains($type, 'playmaker') ||
                    str_contains($type, 'point forward')
                ) {
                    $score += 12;
                }

                break;

            case 'defensive':

                if (
                    str_contains($type, 'defender') ||
                    str_contains($type, 'rim protector') ||
                    str_contains($type, 'two way') ||
                    str_contains($type, 'paint beast')
                ) {
                    $score += 12;
                }

                break;

            case 'contenders':

                if (in_array($role, [
                    'star player',
                    'all star',
                    'starter',
                ], true)) {
                    $score += 10;
                }

                break;

            case 'rebuilding':

                if ($age <= 24) {
                    $score += 10;
                }

                break;

            case 'balanced':
            case 'any':
            default:
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Development rating
        |--------------------------------------------------------------------------
        */

        $development = (int) (
            $coach->development_rating ?? 50
        );

        if ($age <= 22) {

            $score +=
                ($development - 50) * 0.20;

        } elseif ($age <= 24) {

            $score +=
                ($development - 50) * 0.12;
        }

        /*
        |--------------------------------------------------------------------------
        | Coach IQ
        |--------------------------------------------------------------------------
        */

        $iq = (int) (
            $coach->coach_iq ?? 50
        );

        $score += ($iq - 50) * 0.10;

        /*
        |--------------------------------------------------------------------------
        | Keep within 0-100
        |--------------------------------------------------------------------------
        */

        return max(
            0,
            min(100, $score)
        );
    }

    /**
     * Calculate coach-adjusted signing score.
     *
     * Base score comes from ContractService / player valuation.
     */
    public function getSigningScore(
        $coach,
        $player,
        float $baseScore
    ): float {
        if (!$coach) {
            return $baseScore;
        }

        $fit = $this->getPlayerFitScore(
            $coach,
            $player
        );

        $influence = $this->getCoachInfluence(
            $coach
        );

        /*
        |--------------------------------------------------------------------------
        | Coach fit
        |--------------------------------------------------------------------------
        |
        | Fit 0   = roughly -10
        | Fit 50  = 0
        | Fit 100 = roughly +10
        |
        */

        $coachInfluence =
            ($fit - 50) *
            0.20 *
            $influence;

        /*
        |--------------------------------------------------------------------------
        | Development coach
        |--------------------------------------------------------------------------
        */

        $development = (int) (
            $coach->development_rating ?? 50
        );

        $age = $this->getPlayerAge($player);

        if ($age <= 24) {

            $coachInfluence +=
                (($development - 50) / 100) * 5;
        }

        return max(
            0,
            $baseScore + $coachInfluence
        );
    }

    /**
     * Calculate coach-adjusted trade value.
     *
     * The same player can have different values
     * for different teams.
     */
    public function getTradeValue(
        $coach,
        $player,
        float $baseValue
    ): float {
        if (!$coach) {
            return $baseValue;
        }

        $fit = $this->getPlayerFitScore(
            $coach,
            $player
        );

        $influence = $this->getCoachInfluence(
            $coach
        );

        /*
        |--------------------------------------------------------------------------
        | Coach system fit
        |--------------------------------------------------------------------------
        */

        $fitModifier =
            ($fit - 50) *
            0.30 *
            $influence;

        /*
        |--------------------------------------------------------------------------
        | Coach IQ
        |--------------------------------------------------------------------------
        */

        $iqModifier =
            (
                (int) ($coach->coach_iq ?? 50)
                - 50
            ) * 0.10;

        return max(
            0,
            $baseValue +
            $fitModifier +
            $iqModifier
        );
    }

    /**
     * Legacy-compatible trade score.
     *
     * This can be used if your existing controller
     * already calls getTradeScore().
     */
    public function getTradeScore(
        $coach,
        $player,
        float $baseValue
    ): float {
        return $this->getTradeValue(
            $coach,
            $player,
            $baseValue
        );
    }

    /**
     * Calculate trade pressure.
     *
     * Higher score = coach is more willing to trade
     * the player away.
     */
    public function getTradePressure(
        $coach,
        $player,
        float $performanceDecline = 0,
        bool $superDecline = false,
        bool $expiringContract = false
    ): float {
        $pressure = 0;

        /*
        |--------------------------------------------------------------------------
        | Performance decline
        |--------------------------------------------------------------------------
        */

        $pressure += min(
            30,
            max(0, $performanceDecline)
        );

        /*
        |--------------------------------------------------------------------------
        | Major decline
        |--------------------------------------------------------------------------
        */

        if ($superDecline) {
            $pressure += 15;
        }

        /*
        |--------------------------------------------------------------------------
        | Expiring contract
        |--------------------------------------------------------------------------
        */

        if ($expiringContract) {
            $pressure += 8;
        }

        /*
        |--------------------------------------------------------------------------
        | Coach opinion
        |--------------------------------------------------------------------------
        */

        if ($coach) {

            $fit = $this->getPlayerFitScore(
                $coach,
                $player
            );

            /*
            | Coach dislikes player.
            */

            if ($fit < 35) {

                $pressure += 18;

            } elseif ($fit < 45) {

                $pressure += 10;
            }

            /*
            | Coach strongly likes player.
            */

            if ($fit >= 80) {

                $pressure -= 15;

            } elseif ($fit >= 70) {

                $pressure -= 8;
            }

            /*
            |--------------------------------------------------------------------------
            | Protect important players.
            |--------------------------------------------------------------------------
            */

            $role = $this->getPlayerRole($player);

            if (in_array($role, [
                'star player',
                'all star',
            ], true)) {
                $pressure -= 15;
            }
        }

        return max(
            0,
            min(100, $pressure)
        );
    }

    /**
     * Draft score.
     *
     * Position needs can optionally be supplied.
     */
    public function getDraftScore(
        $coach,
        $player,
        float $baseDraftScore,
        array $positionNeeds = []
    ): float {
        if (!$coach) {

            /*
            | Still allow positional need
            | even without a coach.
            */

            $score = $baseDraftScore;

            $score += $this->getPositionNeedBonus(
                $player,
                $positionNeeds
            );

            return $score;
        }

        $fit = $this->getPlayerFitScore(
            $coach,
            $player
        );

        $influence = $this->getCoachInfluence(
            $coach
        );

        $score = $baseDraftScore;

        /*
        |--------------------------------------------------------------------------
        | Position need
        |--------------------------------------------------------------------------
        */

        $score += $this->getPositionNeedBonus(
            $player,
            $positionNeeds
        );

        /*
        |--------------------------------------------------------------------------
        | Coach fit
        |--------------------------------------------------------------------------
        */

        $score +=
            ($fit - 50) *
            0.35 *
            $influence;

        /*
        |--------------------------------------------------------------------------
        | Development coach
        |--------------------------------------------------------------------------
        */

        $development = (int) (
            $coach->development_rating ?? 50
        );

        $age = $this->getPlayerAge($player);

        if ($age <= 22) {

            $score +=
                (($development - 50) / 50) * 4;
        }

        /*
        |--------------------------------------------------------------------------
        | Strategy coach
        |--------------------------------------------------------------------------
        */

        $strategy = (int) (
            $coach->strategy_rating ?? 50
        );

        if ($strategy >= 85) {

            $score +=
                ($fit - 50) * 0.15;
        }

        return $score;
    }

    /**
     * Calculate positional need bonus for drafting.
     */
    private function getPositionNeedBonus(
        $player,
        array $positionNeeds
    ): float {
        if (empty($positionNeeds)) {
            return 0;
        }

        $positionString = strtoupper(
            str_replace(
                ['-', ','],
                '/',
                trim($player->position ?? '')
            )
        );

        $positions = preg_split(
            '/[\/\s]+/',
            $positionString
        );

        $highestNeed = 0;

        foreach ($positions as $position) {

            $position = trim($position);

            if (
                $position !== '' &&
                isset($positionNeeds[$position])
            ) {
                $highestNeed = max(
                    $highestNeed,
                    (int) $positionNeeds[$position]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Maximum positional bonus = 10
        |--------------------------------------------------------------------------
        */

        return min(
            10,
            $highestNeed * 2
        );
    }

    /**
     * Get probability of approving a signing.
     */
    public function getSigningApprovalChance(
        $coach,
        float $signingScore
    ): float {
        if (!$coach) {

            return max(
                10,
                min(95, $signingScore)
            );
        }

        $quality = $this->getCoachQuality(
            $coach
        );

        /*
        |--------------------------------------------------------------------------
        | Convert signing score to probability.
        |--------------------------------------------------------------------------
        */

        $chance =
            25 +
            ($signingScore * 0.55);

        /*
        |--------------------------------------------------------------------------
        | Better coaches make more confident decisions.
        |--------------------------------------------------------------------------
        */

        $chance +=
            ($quality - 50) *
            0.10;

        return max(
            10,
            min(95, $chance)
        );
    }

    /**
     * Get probability of approving a trade.
     *
     * Positive net benefit = incoming player is more
     * valuable to this coach than outgoing player.
     */
    public function getTradeApprovalChance(
        $coach,
        float $netBenefit
    ): float {
        if (!$coach) {

            return max(
                10,
                min(
                    90,
                    50 + ($netBenefit * 2)
                )
            );
        }

        $quality = $this->getCoachQuality(
            $coach
        );

        $chance =
            50 +
            ($netBenefit * 2);

        /*
        |--------------------------------------------------------------------------
        | Coach quality.
        |--------------------------------------------------------------------------
        */

        $chance +=
            ($quality - 50) *
            0.20;

        return max(
            10,
            min(95, $chance)
        );
    }

    /**
     * Get probability of selecting a draft player.
     */
    public function getDraftSelectionChance(
        $coach,
        float $scoreDifference
    ): float {
        if (!$coach) {

            return max(
                10,
                min(
                    95,
                    50 + ($scoreDifference * 2)
                )
            );
        }

        $quality = $this->getCoachQuality(
            $coach
        );

        $chance =
            50 +
            ($scoreDifference * 2);

        $chance +=
            ($quality - 50) *
            0.15;

        return max(
            10,
            min(95, $chance)
        );
    }

    /**
     * Make a random decision based on percentage.
     *
     * Example:
     *
     * randomDecision(75)
     *
     * = approximately 75% chance of true.
     */
    public function randomDecision(
        float $chance
    ): bool {
        $chance = max(
            0,
            min(100, $chance)
        );

        return mt_rand(
            1,
            10000
        ) <= ($chance * 100);
    }
}