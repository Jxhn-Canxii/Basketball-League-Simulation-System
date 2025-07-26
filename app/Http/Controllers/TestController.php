<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teams;
use App\Models\Player; // <-- Add this if not yet imported
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function waiveTeam(Request $request, $teamId)
    {
        $seasonStatus = (int) $request->input('season_status', 2);
        $requiredRecoveryGames = (int) $request->input('required_games', 8);
        $seed = $request->input('seed');

        if (!is_null($seed)) {
            mt_srand((int) $seed);
        }

        // Find the team
        $team = Teams::findOrFail($teamId);

        // Fetch players for this team
        $players = Player::where('team_id', $teamId)->get();
        $seasonId = get_current_season_id() ?? 1;

        $waivablePlayers = [];
        foreach ($players as $player) {
            if ($this->shouldWaivePlayer($player, $seasonId, $seasonStatus)) {
                $waivablePlayers[] = [
                    'player_id' => $player->id,
                    'name' => $player->name ?? $player->full_name ?? null,
                    'role' => $player->role,
                    'overall_rating' => $player->overall_rating,
                    'age' => $player->age,
                    'injury_recovery_games' => $player->injury_recovery_games,
                    'injury_prone_percentage' => $player->injury_prone_percentage,
                    'morale' => $player->morale,
                    'stamina_rating' => $player->stamina_rating,
                    'work_ethic_rating' => $player->work_ethic_rating,
                    'leadership_rating' => $player->leadership_rating,
                    'contract_years' => $player->contract_years,
                ];
            }
        }

        return response()->json([
            'team_id' => $team->id,
            'team_name' => $team->name ?? null,
            'season_status' => $seasonStatus,
            'required_recovery_games' => $requiredRecoveryGames,
            'has_waived' => count($waivablePlayers) > 0,
            'waivable_players' => $waivablePlayers,
        ]);
    }

    // Keep waiveScan as-is or adjust similarly
    public function waiveScan(Request $request)
    {
        $seasonStatus = (int) $request->input('season_status', 2);
        $requiredRecoveryGames = (int) $request->input('required_games', 6);
        $seed = $request->input('seed');

        if (!is_null($seed)) {
            mt_srand((int) $seed);
        }

        $teams = Teams::all();
        $seasonId = get_current_season_id() ?? 1;

        $report = [];
        foreach ($teams as $team) {
            $players = Player::where('team_id', $team->id)->get();
            $waivablePlayers = [];

            foreach ($players as $player) {
                if ($this->shouldWaivePlayer($player, $seasonId, $seasonStatus)) {
                    $waivablePlayers[] = [
                        'player_id' => $player->id,
                        'name' => $player->name ?? $player->full_name ?? null,
                        'role' => $player->role,
                        'overall_rating' => $player->overall_rating,
                        'age' => $player->age,
                        'injury_recovery_games' => $player->injury_recovery_games,
                        'injury_prone_percentage' => $player->injury_prone_percentage,
                        'morale' => $player->morale,
                        'stamina_rating' => $player->stamina_rating,
                        'work_ethic_rating' => $player->work_ethic_rating,
                        'leadership_rating' => $player->leadership_rating,
                        'contract_years' => $player->contract_years,
                    ];
                }
            }

            $report[] = [
                'team_id' => $team->id,
                'team_name' => $team->name ?? null,
                'has_waived' => count($waivablePlayers) > 0,
                'waivable_players' => $waivablePlayers,
            ];
        }

        return response()->json([
            'season_status' => $seasonStatus,
            'required_recovery_games' => $requiredRecoveryGames,
            'total_teams' => $teams->count(),
            'results' => $report,
        ]);
    }

    private function shouldWaivePlayer($player, int $seasonId, int $seasonStatus): bool
    {
        // Only consider waiving during first half of season (e.g., before trade deadline)
        if ($seasonStatus > 2) return false;

        // How many regular-season games this specific team will play (dynamic)
        $totalTeamGames = $this->getRegularSeasonGameCount($seasonId, $player->id);

        // % of the season a player must be out before we even consider waiving
        $rolePctMap = [
            'star player' => 0.90, // ~90% of the season
            'all star'    => 0.85,
            'starter'     => 0.70,
            'role player' => 0.55,
            'bench'       => 0.40, // bench guys can be waived earlier
        ];

        // Fallback % if role is missing/unknown
        $defaultPct = 0.55;

        $pct = $rolePctMap[strtolower($player->role)] ?? $defaultPct;

        // Convert % to games, clamp to [min,max]
        $minReq = 4;                                // never below 4 games
        $maxReq = $totalTeamGames;                  // never above full season
        $requiredRecoveryGames = (int) ceil($totalTeamGames * $pct);
        $requiredRecoveryGames = max($minReq, min($requiredRecoveryGames, $maxReq));

        // Must be a serious injury (has missed at least the role-based threshold)
        if ($player->injury_recovery_games < $requiredRecoveryGames) return false;

        // Never waive your top talents (franchise anchors)
        if (
            in_array($player->role, ['star player', 'all star']) ||
            $player->overall_rating >= 80 ||
            ($player->is_rookie && $player->overall_rating >= 70) ||
            $player->work_ethic_rating >= 85 ||
            $player->leadership_rating >= 85
        ) {
            return false;
        }

        // Waive protection for players with long-term deals unless clearly declining
        if ($player->contract_years > 2 && $player->overall_rating > 72) return false;

        // Risk categories
        $isOldDeclining = $player->age >= 30 && $player->overall_rating <= 72;
        $isInjuryProne = $player->injury_prone_percentage >= 70 || count(json_decode($player->injury_history ?? '[]')) >= 3;
        $lowMorale = $player->morale !== null && $player->morale <= 40;
        $lowStamina = $player->stamina_rating <= 60;
        $poorWorkEthic = $player->work_ethic_rating <= 60;

        // Role-based likelihood
        $waivableRoles = ['bench', 'role player'];
        $isExpendableRole = in_array($player->role, $waivableRoles);

        // High likelihood waiver condition
        if (
            ($isOldDeclining || $isInjuryProne || $lowMorale || $lowStamina || $poorWorkEthic) &&
            $isExpendableRole &&
            rand(1, 100) <= 50
        ) {
            return true;
        }

        // Medium likelihood waiver condition (e.g., injured starter)
        if (
            $player->role === 'starter' &&
            ($isInjuryProne || $lowMorale || $poorWorkEthic) &&
            rand(1, 100) <= 25
        ) {
            return true;
        }

        // Rare chance to simulate tough or chaotic front office decisions
        if (rand(1, 100) <= 10) {
            return true;
        }

        return false;
    }

    private function getRegularSeasonGameCount(int $seasonId, int $playerId): int
    {
        return (int) (
            DB::table('player_season_stats')
                ->where('season_id', $seasonId)
                ->where('player_id', $playerId)
                ->orderByDesc('id') // get the latest record
                ->value('total_games_played') ?? 19
        );
    }

}
