<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Player;
use App\Services\Contract\ContractService;
use App\Services\Player\PlayerValuationService;
use App\Services\Transaction\FreeAgencyService;

class FreeAgentController extends Controller
{
    protected ContractService $contractService;
    protected PlayerValuationService $valuationService;
    protected FreeAgencyService $freeAgencyService;

    public function __construct()
    {
        $this->contractService = new ContractService();
        $this->valuationService = new PlayerValuationService();
        $this->freeAgencyService = new FreeAgencyService();
    }

    public function getBestFreeAgent(Request $request)
    {
        // Validate the request data
        $position = $request->position;

        if (!$position) {
            return response()->json(['error' => 'Position is required'], 400);
        }

        $freeAgent = $this->getBestFreeAgentAvailable($position);

        if ($position == 'OW') {
            $freeAgent = $this->getBestFreeAgentOffWaiver();
        }

        return response()->json(['data' =>  $freeAgent]);
    }

    public function getBestFreeAgentAvailable($position)
    {
        $candidates = $this->rankFreeAgentsForPosition($position);

        if ($candidates->isNotEmpty()) {
            return $candidates->first();
        }

        return null;
    }

    public function getBestFreeAgentScouted($teamId, $position)
    {
        return $this->rankFreeAgentsForPosition($position, $teamId)->first();
    }

    public function runFreeAgencyPeriod(Request $request)
    {
        $request->validate([
            'season_id' => 'nullable|integer|min:1',
        ]);

        $seasonId = $request->input('season_id', get_current_season_id());
        $summary = $this->freeAgencyService->runFreeAgencyPeriod($seasonId);

        return response()->json([
            'message' => 'Free agency period completed successfully.',
            'summary' => $summary,
        ]);
    }

    private function rankFreeAgentsForPosition($position, $teamId = null)
    {
        $positions = explode('/', strtoupper($position));
        $latestSeasonId = get_current_season_id();

        $query = DB::table('players')
            ->leftJoin('season_awards', 'players.id', '=', 'season_awards.player_id')
            ->leftJoin('player_season_stats', function ($join) use ($latestSeasonId) {
                $join->on('players.id', '=', 'player_season_stats.player_id')
                    ->where('player_season_stats.season_id', '=', $latestSeasonId);
            })
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where(function ($subQuery) use ($positions) {
                foreach ($positions as $pos) {
                    $subQuery->orWhere('players.position', 'LIKE', '%' . $pos . '%');
                }
            })
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                DB::raw('COUNT(season_awards.id) as awards_count'),
                DB::raw('COALESCE(player_season_stats.eff, 0) as eff')
            )
            ->groupBy(
                'players.id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                'player_season_stats.eff'
            );

        $candidates = $query->get()->map(function ($player) use ($teamId) {
            $player->valuation_score = $this->valuationService->calculatePlayerValue($player);
            $player->team_fit_score = $teamId ? $this->teamFitScore($teamId, $player) : 0;
            $player->composite_score = $player->valuation_score + $player->team_fit_score;

            return $player;
        })->sortByDesc('composite_score')->values();

        return $candidates;
    }

    private function teamFitScore($teamId, $player): float
    {
        $standing = DB::table('standings_view')
            ->where('season_id', get_current_season_id())
            ->where('team_id', $teamId)
            ->first();

        $winRate = 0.5;

        if ($standing) {
            $games = (int) (($standing->wins ?? 0) + ($standing->losses ?? 0));
            if ($games > 0) {
                $winRate = ((int) ($standing->wins ?? 0)) / $games;
            }
        }

        $roleFit = match (strtolower((string) ($player->role ?? ''))) {
            'star player' => 4.0,
            'starter' => 3.0,
            'role player' => 2.0,
            default => 1.0,
        };

        return round(($winRate * 5) + $roleFit, 2);
    }

    public function getBestFreeAgentOffWaiver()
    {

        // Get latest season id (adjust if your season logic is different)
        $latestSeasonId = get_current_season_id();

        $freeAgents = DB::table('players')
            ->leftJoin('season_awards', 'players.id', '=', 'season_awards.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                DB::raw('COUNT(season_awards.id) as awards_count')
            )
            ->groupBy(
                'players.id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->orderByDesc('awards_count')
            ->limit(5)
            ->get();

        return $freeAgents->random();
             
    }

    public function updateInjuryFreeAgents()
    {
        // Update injury recovery games for free agents and mark them as not injured if recovery games reach 0
        $deductionPerGame = 1; // Deduct 1 of a game


        $deductInjuryGames = DB::table('players')
            ->where('is_active', 1)
            ->where('is_injured', 1)
            ->where('injury_recovery_games', '>', 0)
            ->update([
                'injury_recovery_games' => DB::raw("GREATEST(injury_recovery_games - $deductionPerGame, 0)")
            ]);


        // Check if any rows were actually updated
        $affectedRows = DB::table('players')
            ->where('is_active', 1)
            ->where('is_injured', 1)
            ->where('injury_recovery_games', 0)
            ->count(); // Count players whose recovery reached 0

        if ($affectedRows > 0) {
            DB::table('players')
                ->where('is_active', 1)
                ->where('is_injured', 1)
                ->where('injury_recovery_games', '<=', 0)
                ->update(['is_injured' => 0]);
        }
    }

    //getFreeAgentsByPositionAndCompositeScore
    public function getBestAvailableFreeAgent($position, $usedPlayerIds)
    {
        $query = Player::select(
            'players.*',
            'teams.acronym as drafted_team',
            DB::raw("(
                    SELECT GROUP_CONCAT(CONCAT(award_name, ' (Season ', season_id, ')') SEPARATOR ', ')
                    FROM season_awards
                    WHERE season_awards.player_id = players.id
                ) as awards"),
            DB::raw("(
                    SELECT CONCAT('Finals MVP (Season ', seasons.id, ')')
                    FROM seasons
                    WHERE seasons.finals_mvp_id = players.id
                    ORDER BY seasons.id DESC
                    LIMIT 1
                ) as finals_mvp"),
            DB::raw("(
                    CASE WHEN EXISTS (
                        SELECT 1 FROM seasons WHERE seasons.finals_mvp_id = players.id
                    ) THEN 1 ELSE 0 END
                ) as is_finals_mvp"),
            DB::raw("(
                    SELECT GROUP_CONCAT(seasons.name SEPARATOR ', ')
                    FROM seasons
                    WHERE seasons.finals_mvp_id = players.id
                ) as finals_mvp_seasons")
        )
            ->where('players.contract_years', 0) // Only free agents
            ->where('players.is_active', 1) // Only active players
            ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id');

        if ($position) {
            $query->where('players.position', 'LIKE', "%$position%");
        }

        if (!empty($usedPlayerIds)) {
            $query->whereNotIn('players.id', $usedPlayerIds);
        }

        $query->orderByRaw("
            LENGTH(awards) DESC,
            is_finals_mvp DESC,
            FIELD(role, 'star player', 'all star', 'starter', 'role player', 'bench')
        ");

        return $query->first();
    }

    private function getFreeAgentsByCompositeScore($currentSeasonId)
    {
        $freeAgents = Player::select(
            'players.*',
            'teams.acronym as drafted_team',
            DB::raw("(SELECT GROUP_CONCAT(CONCAT(award_name, ' (Season ', season_id, ')') SEPARATOR ', ') FROM season_awards WHERE season_awards.player_id = players.id) as awards"),
            DB::raw("(SELECT  CONCAT('Finals MVP (Season ', seasons.id, ')')  FROM seasons WHERE seasons.finals_mvp_id = players.id LIMIT 1) as finals_mvp"),
            DB::raw("CASE WHEN players.id = (SELECT finals_mvp_id FROM seasons WHERE seasons.finals_mvp_id = players.id) THEN 1 ELSE 0 END as is_finals_mvp"),
            DB::raw("(SELECT GROUP_CONCAT(seasons.name SEPARATOR ', ') FROM seasons WHERE seasons.finals_mvp_id = players.id) as finals_mvp_seasons")
        )
            ->where('players.contract_years', 0)
            ->where('players.is_active', 1)
            ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id'); // Join teams on players.drafted_team_id

        $freeAgents->orderByRaw("
            LENGTH(awards) DESC,
            is_finals_mvp DESC,
            FIELD(role, 'star player','all star', 'starter', 'role player', 'bench')
        ");

        return  $freeAgents->get();
    }
}
