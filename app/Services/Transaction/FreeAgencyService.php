<?php

namespace App\Services\Transaction;

use App\Services\Contract\ContractService;
use App\Services\Player\PlayerValuationService;
use Illuminate\Support\Facades\DB;

class FreeAgencyService
{
    protected ContractService $contractService;
    protected PlayerValuationService $valuationService;

    public function __construct()
    {
        $this->contractService = new ContractService();
        $this->valuationService = new PlayerValuationService();
    }

    public function generateFreeAgencyOffers($seasonId = null): array
    {
        $seasonId = $seasonId ?? get_current_season_id();
        $teams = DB::table('teams')->select('id', 'name')->get();
        $freeAgents = DB::table('players')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->where('age', '<=', 65)
            ->get();

        $offers = [];

        foreach ($freeAgents as $player) {
            $playerOffers = [];

            foreach ($teams as $team) {
                $offer = $this->contractService->getContractOffer($player, $team);
                $playerOffers[] = [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'salary' => $offer['salary'],
                    'years' => $offer['years'],
                    'contract_type' => $offer['contract_type'],
                    'score' => $this->scoreOffer($player, $team, $offer, $seasonId),
                ];
            }

            $offers[] = [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'best_offer' => $this->playerEvaluateOffers($player, $playerOffers),
                'offers' => $playerOffers,
            ];
        }

        return $offers;
    }

    public function playerEvaluateOffers($player, array $offers)
    {
        if (empty($offers)) {
            return null;
        }

        usort($offers, function ($left, $right) {
            return ($right['score'] ?? 0) <=> ($left['score'] ?? 0);
        });

        return $offers[0];
    }

    public function handleRestrictedFA($player, $offer)
    {
        $currentTeamId = (int) ($player->team_id ?? 0);

        if ($currentTeamId <= 0) {
            return $offer;
        }

        $currentOffer = $this->contractService->getContractOffer($player, $currentTeamId);

        if (($currentOffer['salary'] ?? 0) >= (($offer['salary'] ?? 0) * 0.95)) {
            $offer['team_id'] = $currentTeamId;
            $offer['team_name'] = DB::table('teams')->where('id', $currentTeamId)->value('name');
            $offer['salary'] = $currentOffer['salary'];
            $offer['years'] = $currentOffer['years'];
            $offer['contract_type'] = $currentOffer['contract_type'];
        }

        return $offer;
    }

    public function signVeteranMinimum($teamId, $seasonId = null)
    {
        $seasonId = $seasonId ?? get_current_season_id();
        $player = DB::table('players')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->where('age', '>=', 30)
            ->orderByDesc('overall_rating')
            ->first();

        if (!$player) {
            return null;
        }

        $offer = $this->contractService->getContractOffer($player, $teamId);
        $cap = $this->contractService->getSalaryCapValues($seasonId);
        $offer['salary'] = $cap['vet_min_value'];
        $offer['years'] = 1;
        $offer['contract_type'] = 'vet_min';

        return $this->signPlayer($player, $teamId, $offer, $seasonId);
    }

    public function runFreeAgencyPeriod($seasonId = null): array
    {
        $seasonId = $seasonId ?? get_current_season_id();
        $offers = $this->generateFreeAgencyOffers($seasonId);
        $signed = [];

        foreach ($offers as $bundle) {
            $player = DB::table('players')->where('id', $bundle['player_id'])->first();
            if (!$player) {
                continue;
            }

            $bestOffer = $bundle['best_offer'];
            if (!$bestOffer) {
                continue;
            }

            $bestOffer = $this->handleRestrictedFA($player, $bestOffer);
            $signedRecord = $this->signPlayer($player, (int) $bestOffer['team_id'], $bestOffer, $seasonId);

            if ($signedRecord) {
                $signed[] = $signedRecord;
            }
        }

        return [
            'season_id' => $seasonId,
            'signed' => $signed,
            'signed_count' => count($signed),
        ];
    }

    private function signPlayer($player, int $teamId, array $offer, int $seasonId)
    {
        if ($teamId <= 0) {
            return null;
        }

        if (!$this->contractService->canSignPlayer($teamId, $offer['salary'], $seasonId)) {
            return null;
        }

        DB::transaction(function () use ($player, $teamId, $offer, $seasonId) {
            DB::table('players')
                ->where('id', $player->id)
                ->update([
                    'team_id' => $teamId,
                    'contract_years' => $offer['years'],
                    'salary' => $offer['salary'],
                    'contract_type' => $offer['contract_type'],
                    'player_option' => $offer['player_option'] ?? false,
                    'team_option' => $offer['team_option'] ?? false,
                    'no_trade_clause' => $offer['no_trade_clause'] ?? false,
                ]);

            DB::table('transactions')->insert([
                'player_id' => $player->id,
                'season_id' => $seasonId,
                'details' => $player->name . ' signed with team ' . $teamId . ' for ₱' . number_format((float) $offer['salary'], 2) . ' on a ' . $offer['contract_type'] . ' contract.',
                'from_team_id' => 0,
                'to_team_id' => $teamId,
                'status' => 'signed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return [
            'player_id' => $player->id,
            'team_id' => $teamId,
            'salary' => $offer['salary'],
            'years' => $offer['years'],
            'contract_type' => $offer['contract_type'],
        ];
    }

    private function scoreOffer($player, $team, array $offer, int $seasonId): float
    {
        $valuation = max(1.0, $this->valuationService->calculatePlayerValue($player));
        $salaryScore = (($offer['salary'] ?? 0) / $valuation) * 10;

        $teamWinRate = 0.5;
        $standing = DB::table('standings_view')
            ->where('season_id', $seasonId)
            ->where('team_id', $team->id)
            ->first();

        if ($standing) {
            $games = (int) (($standing->wins ?? 0) + ($standing->losses ?? 0));
            if ($games > 0) {
                $teamWinRate = ((int) ($standing->wins ?? 0)) / $games;
            }
        }

        $loyalty = (float) ($player->loyalty_rating ?? 50) / 10;
        $ambition = (float) ($player->ambition_rating ?? 50) / 10;

        return round(($salaryScore * 0.5) + ($teamWinRate * 10) + ($loyalty * 0.2) + ($ambition * 0.3), 2);
    }
}
