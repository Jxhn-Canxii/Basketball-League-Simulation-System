<?php

namespace App\Services\Player;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class RoleService
{
   protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }

    public function getRoleChangeHistory($request)
    {
        $player_id = $request->input('player_id');

        if (!$player_id) {
            return response()->json(['error' => 'Player ID is required'], 400);
        }

        $transactions = DB::table('transactions')
            ->join('players', 'transactions.player_id', '=', 'players.id')
            ->leftJoin('teams as from_team', 'transactions.from_team_id', '=', 'from_team.id')
            ->leftJoin('teams as to_team', 'transactions.to_team_id', '=', 'to_team.id')
            ->leftJoinSub(
                DB::table('player_season_stats as pss')
                    ->select('pss.player_id', 'pss.season_id', 'pss.role')
                    ->whereRaw('pss.id = (SELECT id FROM player_season_stats WHERE player_id = pss.player_id AND season_id = pss.season_id ORDER BY id DESC LIMIT 1)'), // Get latest role
                'latest_stats',
                function ($join) {
                    $join->on('transactions.player_id', '=', 'latest_stats.player_id')
                        ->on('transactions.season_id', '=', 'latest_stats.season_id');
                }
            )
            ->where('transactions.player_id', $player_id)
            ->whereIn('transactions.status', ['transfer', 'star player change', 'role change'])
            ->select(
                'transactions.id',
                'transactions.season_id',
                'transactions.from_team_id',
                'from_team.name as from_team_name',
                'transactions.to_team_id',
                'to_team.name as to_team_name',
                'transactions.status',
                'players.name as player_name',
                DB::raw('COALESCE(latest_stats.role, "Unknown") as latest_role'), // Get latest role per season
                DB::raw('GROUP_CONCAT(DISTINCT transactions.details ORDER BY transactions.details SEPARATOR ", ") as merged_details') // Merge duplicate transactions
            )
            ->groupBy(
                'transactions.id',
                'transactions.season_id',
                'transactions.from_team_id',
                'from_team.name',
                'transactions.to_team_id',
                'to_team.name',
                'transactions.status',
                'players.name',
                'latest_stats.role'
            )
            ->orderByDesc('transactions.id')
            ->get();

        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'No transactions found for this player.'], 404);
        }

        return response()->json($transactions);
    }
   
}
