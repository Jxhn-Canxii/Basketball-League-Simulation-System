<?php

namespace App\Services\Player;

use App\Services\Coach\CoachDecisionService;
use Illuminate\Support\Facades\DB;

class PlayerRetentionService
{
    protected $coachDecisionService;

    public function __construct()
    {
        $this->coachDecisionService = new CoachDecisionService();
    }

    /**
     * Evaluate how valuable a player is to a specific team.
     *
     * Score:
     * 90+  = Franchise / Untouchable
     * 80-89 = Must Keep
     * 70-79 = Keep unless replacement is better
     * 60-69 = Evaluate / Shop
     * 50-59 = Likely waive
     * <50  = Waive
     */
    public function evaluate($player, int $teamId, int $seasonId): array
    {
        $coach = $this->coachDecisionService->getTeamCoach($teamId);

        $abilityScore      = $this->getAbilityScore($player);
        $performanceScore  = $this->getPerformanceScore($player, $seasonId);
        $futureScore       = $this->getFutureValueScore($player);
        $ageScore           = $this->getAgeScore($player);
        $positionScore      = $this->getPositionNeedScore($player, $teamId);
        $salaryScore        = $this->getSalaryValueScore($player, $seasonId);
        $injuryScore        = $this->getInjuryScore($player, $seasonId);
        $teamDirectionScore = $this->getTeamDirectionScore(
            $player,
            $teamId,
            $seasonId
        );

        $baseScore =
            $abilityScore +
            $performanceScore +
            $futureScore +
            $ageScore +
            $positionScore +
            $salaryScore +
            $injuryScore +
            $teamDirectionScore;

        $coachModifier = $this->coachDecisionService
            ->getRetentionModifier($coach, $player);

        $replacement = $this->getBestReplacement(
            $player,
            $teamId,
            $seasonId
        );

        $replacementModifier = 0;

        if ($replacement) {
            $difference = $replacement['score'] - $baseScore;

            if ($difference >= 10) {
                $replacementModifier = -12;
            } elseif ($difference >= 5) {
                $replacementModifier = -7;
            } elseif ($difference >= 2) {
                $replacementModifier = -3;
            } elseif ($difference <= -10) {
                $replacementModifier = 6;
            }
        }

        $finalScore = max(
            0,
            min(
                100,
                $baseScore +
                $coachModifier +
                $replacementModifier
            )
        );

        $action = $this->getAction(
            $finalScore,
            $player,
            $replacement
        );

        return [
            'player_id' => $player->id,
            'player_name' => $player->name ?? '',
            'score' => round($finalScore, 2),
            'base_score' => round($baseScore, 2),
            'coach_modifier' => round($coachModifier, 2),
            'replacement_modifier' => round($replacementModifier, 2),

            'ability' => round($abilityScore, 2),
            'performance' => round($performanceScore, 2),
            'future_value' => round($futureScore, 2),
            'age' => round($ageScore, 2),
            'position_need' => round($positionScore, 2),
            'salary_value' => round($salaryScore, 2),
            'injury' => round($injuryScore, 2),
            'team_direction' => round($teamDirectionScore, 2),

            'replacement' => $replacement,
            'action' => $action,
        ];
    }

    /**
     * 22 points.
     */
    private function getAbilityScore($player): float
    {
        $overall = (float) ($player->overall_rating ?? 50);

        return min(22, max(0, $overall * 0.22));
    }

    /**
     * 18 points.
     */
    private function getPerformanceScore($player, int $seasonId): float
    {
        $stats = DB::table('player_season_stats')
            ->where('player_id', $player->id)
            ->where('season_id', $seasonId)
            ->first();

        if (!$stats) {
            $stats = DB::table('player_season_stats_archives')
                ->where('player_id', $player->id)
                ->orderByDesc('season_id')
                ->first();
        }

        if (!$stats) {
            return 9;
        }

        $eff = (float) ($stats->eff ?? 0);

        /*
         * EFF is the primary performance indicator.
         *
         * 25 EFF = full 18 points.
         */
        $effScore = min(18, max(0, ($eff / 25) * 18));

        /*
         * Small production bonus.
         */
        $ppg = (float) ($stats->avg_points_per_game ?? 0);
        $apg = (float) ($stats->avg_assists_per_game ?? 0);
        $rpg = (float) ($stats->avg_rebounds_per_game ?? 0);

        $productionBonus = 0;

        if ($ppg >= 20) {
            $productionBonus += 1.5;
        } elseif ($ppg >= 15) {
            $productionBonus += 1;
        }

        if ($apg >= 7) {
            $productionBonus += 0.75;
        } elseif ($apg >= 5) {
            $productionBonus += 0.4;
        }

        if ($rpg >= 10) {
            $productionBonus += 0.75;
        } elseif ($rpg >= 7) {
            $productionBonus += 0.4;
        }

        return min(18, $effScore + min(2, $productionBonus));
    }

    /**
     * 14 points.
     */
    private function getFutureValueScore($player): float
    {
        $overall = (float) ($player->overall_rating ?? 50);
        $potential = (float) (
            $player->potential_rating ?? $overall
        );

        $age = (int) ($player->age ?? 25);

        $current = $overall * 0.08;
        $potentialPart = $potential * 0.06;

        $ageBonus = 0;

        if ($age <= 21) {
            $ageBonus = 3;
        } elseif ($age <= 24) {
            $ageBonus = 2;
        } elseif ($age <= 27) {
            $ageBonus = 1;
        }

        return min(
            14,
            max(
                0,
                $current + $potentialPart + $ageBonus
            )
        );
    }

    /**
     * 8 points.
     *
     * Younger players are more valuable to rebuilding teams.
     */
    private function getAgeScore($player): float
    {
        $age = (int) ($player->age ?? 25);

        if ($age <= 22) return 8;
        if ($age <= 24) return 7;
        if ($age <= 27) return 6;
        if ($age <= 30) return 5;
        if ($age <= 32) return 3.5;
        if ($age <= 34) return 2;
        if ($age <= 36) return 1;

        return 0;
    }

    /**
     * 9 points.
     *
     * Teams with a positional shortage value a player more.
     */
    private function getPositionNeedScore($player, int $teamId): float
    {
        $position = strtolower(
            trim($player->position ?? '')
        );

        if ($position === '') {
            return 3;
        }

        $roster = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', 1)
            ->where('id', '!=', $player->id)
            ->get([
                'position',
                'overall_rating',
                'role'
            ]);

        $samePosition = 0;

        foreach ($roster as $teammate) {
            $teammatePositions = $this->splitPositions(
                $teammate->position
            );

            if (count(
                array_intersect(
                    $this->splitPositions($position),
                    $teammatePositions
                )
            ) > 0) {
                $samePosition++;
            }
        }

        if ($samePosition === 0) {
            return 9;
        }

        if ($samePosition === 1) {
            return 8;
        }

        if ($samePosition === 2) {
            return 6;
        }

        if ($samePosition === 3) {
            return 4;
        }

        return 2;
    }

    /**
     * 8 points.
     *
     * Rewards players who provide good production/value
     * relative to their salary.
     */
    private function getSalaryValueScore(
        $player,
        int $seasonId
    ): float {
        $salary = (float) ($player->salary ?? 0);
        $overall = (float) ($player->overall_rating ?? 50);

        /*
         * Free/very cheap players should not automatically
         * receive maximum value.
         */
        if ($salary <= 0) {
            return min(8, 3 + ($overall / 100) * 5);
        }

        $stats = DB::table('player_season_stats')
            ->where('player_id', $player->id)
            ->where('season_id', $seasonId)
            ->first();

        $eff = (float) ($stats->eff ?? 0);

        /*
         * Expected salary based loosely on ability and production.
         */
        $expectedValue =
            ($overall * 100000) +
            ($eff * 50000);

        if ($expectedValue <= 0) {
            return 4;
        }

        $ratio = $expectedValue / $salary;

        if ($ratio >= 2.5) return 8;
        if ($ratio >= 2.0) return 7;
        if ($ratio >= 1.5) return 6;
        if ($ratio >= 1.1) return 5;
        if ($ratio >= 0.8) return 3;
        if ($ratio >= 0.5) return 2;

        return 1;
    }

    /**
     * 4 points.
     *
     * Injury should affect retention, but should NOT permanently
     * destroy a player's ratings.
     */
    private function getInjuryScore($player, int $seasonId): float
    {
        $injuryRisk = (float) (
            $player->injury_prone_percentage ?? 0
        );

        $score = 4;

        if ($injuryRisk >= 80) {
            $score -= 2.5;
        } elseif ($injuryRisk >= 60) {
            $score -= 1.5;
        } elseif ($injuryRisk >= 40) {
            $score -= 0.75;
        }

        $activeInjury = DB::table('injured_players_view')
            ->where('player_id', $player->id)
            ->where('season_id', $seasonId)
            ->where('status', 'Injured')
            ->exists();

        if ($activeInjury) {
            $score -= 0.75;
        }

        return max(0, $score);
    }

    /**
     * 5 points.
     *
     * Determine whether the team is rebuilding or competing.
     */
    private function getTeamDirectionScore(
        $player,
        int $teamId,
        int $seasonId
    ): float {
        $age = (int) ($player->age ?? 25);
        $potential = (float) ($player->potential_rating ?? 50);

        $direction = $this->getTeamDirection(
            $teamId,
            $seasonId
        );

        if ($direction === 'rebuilding') {
            if ($age <= 24) {
                return 5;
            }

            if ($potential >= 85) {
                return 4;
            }

            if ($age >= 32) {
                return 1;
            }

            return 3;
        }

        if ($direction === 'contender') {
            $overall = (float) ($player->overall_rating ?? 50);

            if ($overall >= 85) {
                return 5;
            }

            if ($overall >= 75) {
                return 4;
            }

            if ($age <= 24 && $potential >= 85) {
                return 3;
            }

            return 2;
        }

        /*
         * Competitive / playoff team.
         */
        if ($age <= 27 || $potential >= 85) {
            return 4;
        }

        return 3;
    }

    private function getTeamDirection(
        int $teamId,
        int $seasonId
    ): string {
        $standing = DB::table('standings_view')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->first();

        if (!$standing) {
            return 'competitive';
        }

        $rank = (int) (
            $standing->overall_rank
            ?? $standing->rank
            ?? 0
        );

        $playoff = (int) (
            $standing->is_playoff_qualified
            ?? 0
        );

        /*
         * Adjust these thresholds if your league has a
         * different number of teams.
         */
        if ($playoff && $rank > 0 && $rank <= 4) {
            return 'contender';
        }

        if ($playoff) {
            return 'competitive';
        }

        if ($rank > 0) {
            return 'rebuilding';
        }

        return 'competitive';
    }

    /**
     * Find the best free-agent replacement at the same position.
     */
    private function getBestReplacement(
        $player,
        int $teamId,
        int $seasonId
    ): ?array {
        $positions = $this->splitPositions(
            $player->position ?? ''
        );

        if (empty($positions)) {
            return null;
        }

        $freeAgents = DB::table('players')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->where('id', '!=', $player->id)
            ->where('age', '<=', 40)
            ->get();

        $best = null;

        foreach ($freeAgents as $candidate) {
            $candidatePositions = $this->splitPositions(
                $candidate->position
            );

            if (empty(
                array_intersect(
                    $positions,
                    $candidatePositions
                )
            )) {
                continue;
            }

            /*
             * Avoid recursively checking replacement.
             * Calculate a simplified objective score.
             */
            $score = $this->getReplacementScore(
                $candidate,
                $seasonId
            );

            if (!$best || $score > $best['score']) {
                $best = [
                    'player_id' => $candidate->id,
                    'player_name' => $candidate->name,
                    'overall_rating' =>
                        (float) ($candidate->overall_rating ?? 0),
                    'score' => round($score, 2),
                ];
            }
        }

        return $best;
    }

    private function getReplacementScore(
        $player,
        int $seasonId
    ): float {
        $overall = (float) ($player->overall_rating ?? 50);
        $potential = (float) ($player->potential_rating ?? $overall);
        $age = (int) ($player->age ?? 25);

        $stats = DB::table('player_season_stats')
            ->where('player_id', $player->id)
            ->where('season_id', $seasonId)
            ->first();

        $eff = (float) ($stats->eff ?? 0);

        $score =
            ($overall * 0.55) +
            ($potential * 0.20) +
            (min(25, $eff) * 0.8);

        if ($age <= 24) {
            $score += 5;
        } elseif ($age <= 28) {
            $score += 3;
        }

        return min(100, $score);
    }

    private function getAction(
        float $score,
        $player,
        ?array $replacement
    ): string {
        $age = (int) ($player->age ?? 25);
        $overall = (float) ($player->overall_rating ?? 50);
        $role = strtolower($player->role ?? '');

        /*
         * Elite young stars should almost never be waived.
         */
        if (
            $overall >= 90 &&
            $age <= 30
        ) {
            return 'KEEP';
        }

        if ($role === 'star player' && $score >= 75) {
            return 'KEEP';
        }

        if ($score >= 85) {
            return 'KEEP';
        }

        if ($score >= 75) {
            if (
                $replacement &&
                $replacement['score'] > $score + 5
            ) {
                return 'REPLACE';
            }

            return 'KEEP';
        }

        if ($score >= 65) {
            if (
                $replacement &&
                $replacement['score'] > $score + 5
            ) {
                return 'REPLACE';
            }

            return 'SHOP';
        }

        if ($score >= 55) {
            return 'REVIEW';
        }

        if (
            $replacement &&
            $replacement['score'] > $score + 3
        ) {
            return 'REPLACE';
        }

        return 'WAIVE';
    }

    private function splitPositions(?string $position): array
    {
        $position = strtolower(
            str_replace(
                ['-', ',', '|', '&'],
                '/',
                trim($position ?? '')
            )
        );

        if ($position === '') {
            return [];
        }

        $parts = preg_split(
            '/[\/\s]+/',
            $position
        );

        return array_values(
            array_unique(
                array_filter(
                    array_map('trim', $parts)
                )
            )
        );
    }
}