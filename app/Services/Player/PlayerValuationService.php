<?php

namespace App\Services\Player;

use Illuminate\Support\Facades\DB;

class PlayerValuationService
{
    public function calculatePlayerValue($player, array $context = []): float
    {
        if (!$player) {
            return 0.0;
        }

        $production = $this->calculateProductionScore($player);
        $ageMultiplier = $this->getAgeMultiplier((int) $this->value($player, 'age', 0));
        $overallBonus = min(10, max(0, ((float) $this->value($player, 'overall_rating', 0) / 100) * 10));
        $awardBonus = $this->getAwardsBonus($player);
        $injuryDeduction = ((float) $this->value($player, 'injury_prone_percentage', 0)) / 10;
        $roleBonus = $this->getRoleBonus((string) $this->value($player, 'role', ''));
        $contractModifier = $this->getContractModifier(
            (int) ($context['contract_years_remaining'] ?? $this->value($player, 'contract_years', 0))
        );

        $value = (($production * $ageMultiplier) + $overallBonus + $awardBonus + $roleBonus - $injuryDeduction);
        $value *= $contractModifier;

        return round(max(0, $value), 2);
    }

    public function calculateProductionScore($player): float
    {
        if (!$player) {
            return 0.0;
        }

        $pts = $this->statValue($player, ['avg_points_per_game', 'avg_points', 'pts', 'points_per_game']);
        $reb = $this->statValue($player, ['avg_rebounds_per_game', 'avg_rebounds', 'reb', 'rebounds_per_game']);
        $ast = $this->statValue($player, ['avg_assists_per_game', 'avg_assists', 'ast', 'assists_per_game']);
        $stl = $this->statValue($player, ['avg_steals_per_game', 'avg_steals', 'stl', 'steals_per_game']);
        $blk = $this->statValue($player, ['avg_blocks_per_game', 'avg_blocks', 'blk', 'blocks_per_game']);
        $tov = $this->statValue($player, ['avg_turnovers_per_game', 'avg_turnovers', 'tov', 'turnovers_per_game']);

        return (float) (($pts * 2) + ($reb * 1.5) + ($ast * 1.5) + ($stl * 2) + ($blk * 2) - ($tov * 1.5));
    }

    private function getAgeMultiplier(int $age): float
    {
        if ($age <= 25) {
            return 0.85;
        }

        if ($age <= 30) {
            return 1.0;
        }

        if ($age <= 34) {
            return 0.75;
        }

        return 0.6;
    }

    private function getRoleBonus(string $role): float
    {
        return match (strtolower(trim($role))) {
            'star player', 'star', 'all star', 'all-star' => 8.0,
            'starter' => 5.0,
            'role player' => 2.0,
            default => 0.0,
        };
    }

    private function getContractModifier(int $yearsRemaining): float
    {
        return match (true) {
            $yearsRemaining <= 0 => 0.80,
            $yearsRemaining === 1 => 0.88,
            $yearsRemaining === 2 => 0.95,
            $yearsRemaining === 3 => 1.00,
            default => 1.05,
        };
    }

    private function getAwardsBonus($player): float
    {
        $playerId = (int) $this->value($player, 'id', 0);

        if ($playerId <= 0) {
            return 0.0;
        }

        $awardCount = (int) DB::table('season_awards')
            ->where('player_id', $playerId)
            ->count();

        $finalsMvpBonus = DB::table('seasons')
            ->where('finals_mvp_id', $playerId)
            ->exists() ? 5.0 : 0.0;

        return ($awardCount * 3.0) + $finalsMvpBonus;
    }

    private function statValue($player, array $keys): float
    {
        foreach ($keys as $key) {
            $value = $this->value($player, $key, null);

            if ($value !== null && $value !== '') {
                return (float) $value;
            }
        }

        return 0.0;
    }

    private function value($player, string $key, $default = null)
    {
        if (is_array($player)) {
            return $player[$key] ?? $default;
        }

        if (is_object($player)) {
            return $player->{$key} ?? $default;
        }

        return $default;
    }
}
