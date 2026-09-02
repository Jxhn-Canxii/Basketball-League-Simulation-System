<?php

namespace App\Services\Contract;

use App\Services\Player\PlayerValuationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContractService
{
    protected PlayerValuationService $valuationService;

    public function __construct()
    {
        $this->valuationService = new PlayerValuationService();
    }

    public function getContractOffer($player, $team = null): array
    {
        $seasonId = get_current_season_id() ?? DB::table('seasons')->max('id');
        $cap = $this->getSalaryCapValues($seasonId);
        $teamId = $this->teamId($team);
        $valuation = $this->valuationService->calculatePlayerValue($player);
        $role = strtolower(trim((string) $this->value($player, 'role', 'bench')));
        $years = $this->getContractYearsBasedOnRole($role);

        $overall = (float) $this->value($player, 'overall_rating', 0);
        $age = (int) $this->value($player, 'age', 0);
        $playerId = (int) $this->value($player, 'id', 0);
        $awards = $playerId > 0
            ? (int) DB::table('season_awards')->where('player_id', $playerId)->count()
            : 0;

        $salary = match (true) {
            $role === 'star player' || $overall >= 90 => max($cap['mle_value'] * 2.5, 18000000 + ($valuation * 180000)),
            $role === 'starter' || $overall >= 80 => max($cap['mle_value'], 9000000 + ($valuation * 90000)),
            $role === 'role player' || $overall >= 70 => max($cap['vet_min_value'], 3500000 + ($valuation * 50000)),
            default => max($cap['vet_min_value'], 1200000 + ($valuation * 25000)),
        };

        if ($overall >= 92 || $awards >= 2) {
            $years = max($years, 4);
            $salary = max($salary, $cap['salary_cap'] * 0.28);
        } elseif ($role === 'star player' && $age <= 30) {
            $years = max($years, 4);
            $salary = max($salary, $cap['salary_cap'] * 0.24);
        }

        $contractType = $this->determineContractType($salary, $cap, $role);

        if ($teamId > 0 && !$this->canSignPlayer($teamId, $salary, $seasonId)) {
            if ($salary > $cap['mle_value'] && $this->canSignPlayer($teamId, $cap['mle_value'], $seasonId)) {
                $salary = $cap['mle_value'];
                $contractType = 'mle';
                $years = min($years, 3);
            } elseif ($this->canSignPlayer($teamId, $cap['vet_min_value'], $seasonId)) {
                $salary = $cap['vet_min_value'];
                $contractType = 'vet_min';
                $years = min($years, 1);
            } elseif ($this->canSignPlayer($teamId, $cap['two_way_value'], $seasonId)) {
                $salary = $cap['two_way_value'];
                $contractType = 'two_way';
                $years = 1;
            }
        }

        return [
            'salary' => round((float) $salary, 2),
            'years' => max(1, (int) $years),
            'contract_type' => $contractType,
            'valuation' => $valuation,
            'player_option' => $overall >= 88,
            'team_option' => $overall < 75,
            'no_trade_clause' => $overall >= 95 && $awards >= 3,
        ];
    }

    public function assignRookieContract($player, $pickNumber): array
    {
        $pickNumber = max(1, (int) $pickNumber);

        $salary = match (true) {
            $pickNumber <= 5 => 12000000,
            $pickNumber <= 14 => 8000000,
            $pickNumber <= 30 => 4500000,
            default => 1500000,
        };

        return [
            'salary' => (float) $salary,
            'years' => $pickNumber <= 30 ? 4 : 2,
            'contract_type' => 'rookie',
            'player_option' => false,
            'team_option' => true,
            'no_trade_clause' => false,
        ];
    }

    public function assignMLE($player, $team): array
    {
        $offer = $this->getContractOffer($player, $team);
        $cap = $this->getSalaryCapValues(get_current_season_id());

        $offer['salary'] = min($cap['mle_value'], max($offer['salary'], $cap['mle_value']));
        $offer['contract_type'] = 'mle';
        $offer['years'] = min(3, max(1, $offer['years']));

        return $offer;
    }

    public function canSignPlayer($teamId, $salary, $seasonId = null): bool
    {
        $salary = (float) $salary;

        if ($salary <= 0) {
            return true;
        }

        $seasonId = $seasonId ?? get_current_season_id();
        $cap = $this->getSalaryCapValues($seasonId);
        $remainingCap = $this->getRemainingCapSpace($teamId, $seasonId);

        if ($remainingCap >= $salary) {
            return true;
        }

        return in_array($salary, [
            $cap['mle_value'],
            $cap['vet_min_value'],
            $cap['two_way_value'],
        ], true);
    }

    public function getRemainingCapSpace($teamId, $seasonId = null): float
    {
        $seasonId = $seasonId ?? get_current_season_id();
        $cap = $this->getSalaryCapValues($seasonId);

        $payroll = (float) DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', 1)
            ->sum(DB::raw('COALESCE(salary, 0)'));

        return max(0, $cap['salary_cap'] - $payroll);
    }

    public function getSalaryCapValues($seasonId = null): array
    {
        $defaults = [
            'salary_cap' => (float) config('salary_cap.salary_cap.salary_cap', 136000000),
            'luxury_tax_line' => (float) config('salary_cap.salary_cap.luxury_tax_line', 165600000),
            'mle_value' => (float) config('salary_cap.salary_cap.mle_value', 12400000),
            'vet_min_value' => (float) config('salary_cap.salary_cap.vet_min_value', 1165720),
            'two_way_value' => (float) config('salary_cap.salary_cap.two_way_value', 578710),
        ];

        if (!Schema::hasTable('salary_caps')) {
            return $defaults;
        }

        $query = DB::table('salary_caps');

        if ($seasonId !== null) {
            $record = $query->where('season_id', $seasonId)->first();
            if ($record) {
                return $this->normalizeCapRecord($record, $defaults);
            }
        }

        $record = $query->orderByDesc('season_id')->first();

        return $record ? $this->normalizeCapRecord($record, $defaults) : $defaults;
    }


    /**
     * Get the current Mid-Level Exception (MLE).
     */
    public function getMLE(): float
    {
        $seasonId = get_current_season_id();

        /*
    |--------------------------------------------------------------------------
    | Base MLE
    |--------------------------------------------------------------------------
    */

        $baseMLE = 5_000_000;

        /*
    |--------------------------------------------------------------------------
    | Optional salary inflation
    |--------------------------------------------------------------------------
    |
    | Increase the MLE gradually as the league evolves.
    |
    */

        $seasonOffset = max(0, $seasonId - 1);

        $inflationRate = 0.03; // 3% per season

        return round(
            $baseMLE * pow(
                1 + $inflationRate,
                $seasonOffset
            ),
            2
        );
    }

    public function getContractYearsBasedOnRole($role): int
    {
        return match (strtolower(trim((string) $role))) {
            'star player', 'star' => mt_rand(3, 5),
            'starter' => mt_rand(2, 4),
            'role player' => mt_rand(1, 3),
            default => mt_rand(1, 2),
        };
    }

    private function determineContractType(float $salary, array $cap, string $role): string
    {
        if ($salary <= $cap['two_way_value']) {
            return 'two_way';
        }

        if ($salary <= $cap['vet_min_value']) {
            return 'vet_min';
        }

        if ($salary <= $cap['mle_value']) {
            return 'mle';
        }

        if (in_array($role, ['star player', 'star'], true)) {
            return 'max';
        }

        return 'standard';
    }

    private function normalizeCapRecord($record, array $defaults): array
    {
        return [
            'salary_cap' => (float) ($record->salary_cap ?? $defaults['salary_cap']),
            'luxury_tax_line' => (float) ($record->luxury_tax_line ?? $defaults['luxury_tax_line']),
            'mle_value' => (float) ($record->mle_value ?? $defaults['mle_value']),
            'vet_min_value' => (float) ($record->vet_min_value ?? $defaults['vet_min_value']),
            'two_way_value' => (float) ($record->two_way_value ?? $defaults['two_way_value']),
        ];
    }

    private function teamId($team): int
    {
        if (is_numeric($team)) {
            return (int) $team;
        }

        if (is_array($team)) {
            return (int) ($team['id'] ?? 0);
        }

        if (is_object($team)) {
            return (int) ($team->id ?? 0);
        }

        return 0;
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
