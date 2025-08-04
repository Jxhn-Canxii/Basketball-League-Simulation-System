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
                // $waivablePlayers[] = $this->shouldWaivePlayer($player, $seasonId, $seasonStatus);
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

    private function shouldWaivePlayer($player, int $seasonId, int $seasonStatus)
    {
        // Only consider waiving during first half of season (e.g., before trade deadline)
        if ($seasonStatus > 2) return false;

        // How many regular-season games this specific team will play (dynamic)
        $totalTeamGames = $this->getRegularSeasonGameCount($seasonId, $player->id);

        // % of the season a player must be out before we even consider waiving
        $rolePctMap = [
            'star player' => 0.80,
            'all star'    => 0.75,
            'starter'     => 0.60,
            'role player' => 0.45,
            'bench'       => 0.30,
        ];

        $defaultPct = 0.45;
        $pct = $rolePctMap[strtolower($player->role)] ?? $defaultPct;

        $minReq = 2;
        $maxReq = $totalTeamGames;
        $requiredRecoveryGames = (int) ceil($totalTeamGames * $pct);
        $requiredRecoveryGames = max($minReq, min($requiredRecoveryGames, $maxReq));

        // Must be a serious injury (has missed at least the role-based threshold)
        if ($player->injury_recovery_games < $requiredRecoveryGames) return false;

        $highLevelRoles = ['star player', 'all star'];
        // Never waive top-tier players
        if (
            in_array($player->role, $highLevelRoles) ||
            $player->overall_rating >= 90
        ) {
            return false;
        }
        
        // star player or all star that is less than 85 ratings is expandable
        if ($player->injury_recovery_games >= (1.5 * $totalTeamGames) && in_array($player->role, $highLevelRoles)) {
            return true;
        }

        // High chance to waive expendable roles
        $waivableRoles = ['bench', 'role player'];

        // Force waive if player is out for more than 1 full season
        if ($player->injury_recovery_games >= $totalTeamGames && in_array($player->role, $waivableRoles) &&  $player->overall_rating < 80) {
            return true;
        }

        // Moderate chance to waive injured starter
        if ($player->role === 'starter' &&  $player->overall_rating < 80 && rand(1, 100) <= 35) {
            return true;
        }

        // Rare chaos/GM decision
        if (rand(1, 100) <= 30) {
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
                ->value('total_games') ?? 19
        );
    }

}
