<?php

namespace App\Services\Game;

use App\Services\Stats\PlayerSeasonStatsService;
use App\Services\Helper\HelperService;
use App\Services\Team\TeamInjuryService;
use Illuminate\Support\Facades\DB;

class PlayByPlayStatsService
{
    protected $storeStats;
    protected $teamInjury;
    protected $helper;
    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->storeStats = new PlayerSeasonStatsService();
        $this->teamInjury = new TeamInjuryService();
        $this->helper = new HelperService();
    }

    ////
    /**
     * Lightweight possession-level turnover probability.
     *
     * IMPORTANT:
     * This does NOT use DB queries.
     */
    public function possessionTurnoverProbability(
        $offensivePlayer,
        $defensivePlayer,
        float $defensiveImpact,
        float $performanceFactor
    ): float {
        $position = strtoupper(trim(explode('/', $offensivePlayer->position ?? 'PG')[0]));

        $positionRate = [
            'PG' => 0.095,
            'SG' => 0.075,
            'SF' => 0.060,
            'PF' => 0.055,
            'C'  => 0.060,
        ][$position] ?? 0.065;

        $passing = $this->rating($offensivePlayer, 'passing_rating', 60);
        $iq = $this->rating($offensivePlayer, 'basketball_iq_rating', 60);
        $usage = $this->usageMultiplier($offensivePlayer);

        $defense = $this->rating($defensivePlayer, 'defense_rating', 60);
        $defenseIq = $this->rating(
            $defensivePlayer,
            'basketball_iq_rating',
            60
        );

        /*
     * Better passing + IQ = fewer turnovers.
     */
        $ballHandlingSkill =
            ($passing * 0.55) +
            ($iq * 0.45);

        $skillFactor = 1.18 - (($ballHandlingSkill / 100) * 0.30);

        /*
     * Strong individual defender creates more pressure.
     */
        $defenderFactor =
            0.90 +
            (($defense * 0.65 + $defenseIq * 0.35) / 100) * 0.22;

        $usageFactor = 0.90 + (($usage - 0.65) * 0.65);

        $fatigue = $this->rating(
            $offensivePlayer,
            'fatigue',
            0
        );

        $fatigueFactor = 1.0 + ($fatigue / 450);

        $probability =
            $positionRate *
            $skillFactor *
            $defenderFactor *
            $usageFactor *
            $fatigueFactor;

        $probability *= (0.94 + ($performanceFactor * 0.06));

        /*
     * Team defensive impact is deliberately a small modifier.
     */
        $probability *= 1.0 + ($defensiveImpact * 1.35);

        return $this->clamp(
            $probability,
            0.025,
            0.145
        );
    }


    /**
     * Lightweight possession foul probability.
     *
     * This is for a defensive player committing a foul on the possession.
     */
    public function possessionFoulProbability(
        $defensivePlayer,
        $offensivePlayer,
        float $defensiveImpact,
        float $performanceFactor
    ): float {
        $position = strtoupper(trim(explode('/', $defensivePlayer->position ?? 'SF')[0]));

        $positionRate = [
            'PG' => 0.040,
            'SG' => 0.045,
            'SF' => 0.052,
            'PF' => 0.062,
            'C'  => 0.072,
        ][$position] ?? 0.050;

        $iq = $this->rating(
            $defensivePlayer,
            'basketball_iq_rating',
            60
        );

        $workEthic = $this->rating(
            $defensivePlayer,
            'work_ethic_rating',
            60
        );

        $stamina = $this->rating(
            $defensivePlayer,
            'stamina_rating',
            70
        );

        $bashing = $this->rating(
            $defensivePlayer,
            'bashing_factor',
            50
        );

        $defense = $this->rating(
            $defensivePlayer,
            'defense_rating',
            60
        );

        $offenseTwo = $this->rating(
            $offensivePlayer,
            'two_point_rating',
            60
        );

        $athleticism = $this->rating(
            $offensivePlayer,
            'athleticism_rating',
            60
        );

        $strength = $this->rating(
            $offensivePlayer,
            'strength_rating',
            60
        );

        /*
     * Rim pressure determines how likely this possession
     * is to create contact.
     */
        $rimPressure =
            ($offenseTwo * 0.40) +
            ($athleticism * 0.30) +
            ($strength * 0.20) +
            ($this->rating($offensivePlayer, 'basketball_iq_rating', 60) * 0.10);

        $rimPressure /= 100;

        /*
     * Defensive discipline.
     */
        $discipline =
            1.12 -
            (
                (
                    $iq * 0.45 +
                    $workEthic * 0.35 +
                    $stamina * 0.20
                ) / 100
            ) * 0.20;

        $aggression =
            0.95 +
            ($bashing / 100) * 0.18 +
            ($defense / 100) * 0.06;

        $fatigue =
            $this->rating($defensivePlayer, 'fatigue', 0);

        $fatigueFactor = 1.0 + ($fatigue / 300);

        $probability =
            $positionRate *
            $discipline *
            $aggression *
            (0.90 + ($rimPressure * 0.35)) *
            $fatigueFactor;

        $probability *=
            1.0 + ($defensiveImpact * 0.55);

        $probability *=
            0.95 + ($performanceFactor * 0.05);

        return $this->clamp(
            $probability,
            0.025,
            0.135
        );
    }


    /**
     * Probability that a field goal attempt is blocked.
     */
    public function possessionBlockProbability(
        $defensivePlayer,
        $offensivePlayer,
        float $defensiveImpact
    ): float {
        $defense = $this->rating(
            $defensivePlayer,
            'defense_rating',
            60
        );

        $athleticism = $this->rating(
            $defensivePlayer,
            'athleticism_rating',
            60
        );

        $strength = $this->rating(
            $defensivePlayer,
            'strength_rating',
            60
        );

        $iq = $this->rating(
            $defensivePlayer,
            'basketball_iq_rating',
            60
        );

        $rimAttack =
            $this->rating(
                $offensivePlayer,
                'two_point_rating',
                60
            ) * 0.45;

        $rimAttack +=
            $this->rating(
                $offensivePlayer,
                'athleticism_rating',
                60
            ) * 0.30;

        $rimAttack +=
            $this->rating(
                $offensivePlayer,
                'strength_rating',
                60
            ) * 0.25;

        $defensiveSkill =
            $defense * 0.45 +
            $athleticism * 0.25 +
            $strength * 0.15 +
            $iq * 0.15;

        /*
     * Centers/PFs naturally get more block opportunities.
     */
        $position = strtoupper(
            trim(explode('/', $defensivePlayer->position ?? 'SF')[0])
        );

        $positionMultiplier = [
            'PG' => 0.35,
            'SG' => 0.50,
            'SF' => 0.75,
            'PF' => 1.15,
            'C'  => 1.40,
        ][$position] ?? 0.75;

        $probability =
            0.012 +
            (($defensiveSkill - 50) / 1000);

        $probability *= $positionMultiplier;

        /*
     * Better rim attackers are harder to block.
     */
        $probability *=
            1.10 - (($rimAttack - 60) / 500);

        $probability *=
            1.0 + ($defensiveImpact * 0.60);

        return $this->clamp(
            $probability,
            0.003,
            0.085
        );
    }


    /**
     * Determines whether the player attempts a 3PT shot.
     */
    public function possessionThreePointProbability(
        $player,
        float $defensiveImpact,
        float $performanceFactor,
        float $chemistry,
        bool $isHomeAdvantage = false
    ): float {
        $position = strtoupper(
            trim(explode('/', $player->position ?? 'SF')[0])
        );

        $positionShare = [
            'PG' => 0.48,
            'SG' => 0.52,
            'SF' => 0.40,
            'PF' => 0.30,
            'C'  => 0.16,
        ][$position] ?? 0.35;

        $threePoint = $this->rating(
            $player,
            'three_point_rating',
            60
        );

        $shooting = $this->rating(
            $player,
            'shooting_rating',
            60
        );

        $iq = $this->rating(
            $player,
            'basketball_iq_rating',
            60
        );

        $morale = $this->rating(
            $player,
            'morale',
            75
        );

        $fatigue = $this->rating(
            $player,
            'fatigue',
            0
        );

        $role = strtolower(
            trim($player->role ?? 'starter')
        );

        $roleFactor = [
            'star player' => 1.05,
            'all star'    => 1.04,
            'starter'     => 1.00,
            'role player' => 0.98,
            'bench'       => 0.92,
        ][$role] ?? 1.00;

        $skillFactor =
            0.82 +
            ($threePoint - 60) / 300;

        $shootingFactor =
            0.95 +
            ($shooting - 60) / 700;

        $iqFactor =
            0.97 +
            ($iq - 60) / 1500;

        $chemistryFactor =
            0.97 +
            ($chemistry / 3000);

        $moraleFactor =
            0.97 +
            ($morale / 3000);

        $fatigueFactor =
            max(0.88, 1.0 - ($fatigue / 500));

        $defenseFactor =
            max(0.90, 1.0 - ($defensiveImpact * 0.35));

        $probability =
            $positionShare *
            $skillFactor *
            $shootingFactor *
            $iqFactor *
            $chemistryFactor *
            $moraleFactor *
            $fatigueFactor *
            $defenseFactor *
            $roleFactor *
            $performanceFactor;

        if ($isHomeAdvantage) {
            $probability *= 1.015;
        }

        return $this->clamp(
            $probability,
            0.08,
            0.65
        );
    }


    /**
     * Field-goal percentage for a single possession.
     */
    public function possessionShotPercentage(
        $player,
        bool $isThree,
        float $defensiveImpact,
        float $performanceFactor,
        bool $isHomeAdvantage = false
    ): float {
        $shooting = $this->rating(
            $player,
            'shooting_rating',
            60
        );

        $specificRating = $this->rating(
            $player,
            $isThree
                ? 'three_point_rating'
                : 'two_point_rating',
            60
        );

        $iq = $this->rating(
            $player,
            'basketball_iq_rating',
            60
        );

        $morale = $this->rating(
            $player,
            'morale',
            75
        );

        $fatigue = $this->rating(
            $player,
            'fatigue',
            0
        );

        if ($isThree) {
            $percentage =
                0.25 +
                ($specificRating / 100) * 0.22 +
                ($shooting / 100) * 0.04;

            $percentage +=
                ($iq - 70) / 1500;

            $percentage *= $performanceFactor;

            $percentage *=
                max(0.90, 1.0 - ($defensiveImpact * 0.50));

            $percentage *=
                max(0.90, 1.0 - ($fatigue / 700));

            if ($isHomeAdvantage) {
                $percentage *= 1.01;
            }

            return $this->clamp(
                $percentage,
                0.20,
                0.48
            );
        }

        $percentage =
            0.38 +
            ($specificRating / 100) * 0.22 +
            ($shooting / 100) * 0.05;

        $percentage +=
            ($iq - 70) / 1000;

        $percentage +=
            ($morale - 75) / 1500;

        $percentage *= $performanceFactor;

        $percentage *=
            max(0.88, 1.0 - ($defensiveImpact * 0.70));

        $percentage *=
            max(0.88, 1.0 - ($fatigue / 600));

        if ($isHomeAdvantage) {
            $percentage *= 1.01;
        }

        return $this->clamp(
            $percentage,
            0.30,
            0.70
        );
    }


    /**
     * Free throw percentage.
     */
    public function possessionFreeThrowPercentage(
        $player,
        float $performanceFactor
    ): float {
        $ft = $this->rating(
            $player,
            'free_throw_rating',
            60
        );

        $percentage =
            0.68 +
            ($ft / 100) * 0.25;

        $percentage *= $performanceFactor;

        return $this->clamp(
            $percentage,
            0.55,
            0.97
        );
    }


    /**
     * Select a weighted player without using Collection operations
     * inside the possession loop.
     */
    public function weightedPossessionPlayer(
        $players,
        $weights
    ) {
        $total = 0.0;

        foreach ($players as $player) {
            $id = $player->id;
            // dd($player);

            if (($weights[$id] ?? 0) <= 0) {
                continue;
            }

            $total += $weights[$id];
        }

        if ($total <= 0) {
            return null;
        }

        $random = (mt_rand() / mt_getrandmax()) * $total;

        foreach ($players as $player) {
            $id = $player->id;
            $weight = $weights[$id] ?? 0;

            if ($weight <= 0) {
                continue;
            }

            $random -= $weight;

            if ($random <= 0) {
                return $player;
            }
        }

        return end($players) ?: null;
    }


    /**
     * Fast probability check.
     */
    public function possessionChance(float $probability): bool
    {
        return (mt_rand() / mt_getrandmax()) < $probability;
    }


    /**
     * Fast binomial approximation for small possession events.
     *
     * Used primarily for free throws.
     */
    public function possessionBinomial(
        int $attempts,
        float $probability
    ): int {
        if ($attempts <= 0 || $probability <= 0) {
            return 0;
        }

        if ($probability >= 1) {
            return $attempts;
        }

        $made = 0;

        for ($i = 0; $i < $attempts; $i++) {
            if (
                (mt_rand() / mt_getrandmax()) <
                $probability
            ) {
                $made++;
            }
        }

        return $made;
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
    
}
