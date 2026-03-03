<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Player;

class FreeAgentController extends Controller
{

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
        $positions = explode('/', strtoupper($position)); // Normalize casing

        // Flexible position filter: match any part of multi-position fields
        $positionFilter = function ($query) use ($positions) {
            $query->where(function ($q) use ($positions) {
                foreach ($positions as $pos) {
                    $q->orWhere('players.position', 'LIKE', '%' . $pos . '%');
                }
            });
        };

        // Get latest season id (adjust if your season logic is different)
        $latestSeasonId = get_current_season_id();

        // Top 10 by overall_rating
        $byOverall = DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(10)
            ->get();

        // Top 10 by awards count
        $byAwards = DB::table('players')
            ->leftJoin('season_awards', 'players.id', '=', 'season_awards.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
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
            ->orderByDesc('awards_count')
            ->limit(10)
            ->get();

        // Top 10 by EFF in latest season
        $byEff = DB::table('players')
            ->leftJoin('player_season_stats', 'players.id', '=', 'player_season_stats.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where('player_season_stats.season_id', $latestSeasonId)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                'player_season_stats.eff'
            )
            ->orderByDesc('player_season_stats.eff')
            ->limit(10)
            ->get();

        // Merge all and deduplicate by player_id
        $merged = $byOverall->merge($byAwards)->merge($byEff)->unique('player_id')->values();

        // Return a random player from the merged top candidates
        if ($merged->isNotEmpty()) {
            return $merged->random();
        }

        // Fallback: any available player at the position
        return DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(1)
            ->first();
    }

    public function getBestFreeAgentOffWaiver()
    {

        // Get latest season id (adjust if your season logic is different)
        $latestSeasonId = get_current_season_id();

        // Top 10 by overall_rating
        $byOverall = DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where('players.overall_rating', '>=', 80)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(3)
            ->get();

        // Top 10 by awards count
        $byAwards = DB::table('players')
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
            ->orderByDesc('awards_count')
            ->limit(5)
            ->get();

        // Merge all and deduplicate by player_id
        $merged = $byOverall->merge($byAwards)->unique('player_id')->values();

        // Return a random player from the merged top candidates
        if ($merged->isNotEmpty()) {
            return $merged->random();
        }

        // Fallback: any available player at the position
        return DB::table('players')
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
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(1)
            ->first();
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
