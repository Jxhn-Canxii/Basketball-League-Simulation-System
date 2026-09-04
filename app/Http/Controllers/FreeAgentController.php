<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Player;
use App\Services\Player\FreeAgencyService;
use App\Services\Trade\TradeService;

class FreeAgentController extends Controller
{
   
    protected $freeAgencyService;
    protected $tradeService;

    public function __construct()
    {
        $this->freeAgencyService = new FreeAgencyService();
        $this->tradeService = new TradeService();
    }

    public function runFreeAgencyPeriod(Request $request)
    {
        $request->validate([
            'season_id' => 'nullable|integer|min:1',
        ]);

        return  $this->freeAgencyService->runFreeAgencyPeriod($request);

    }

    public function underPerformedPlayersPerTeam(){
        
        $activeTeams = DB::table('teams')
            ->select('teams.id', 'teams.name')
            ->groupBy('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();
        
        $players = [];

        foreach($activeTeams as $team){
            $players[$team->name]['players'] = $this->tradeService->findUnderperformingPlayers($team->id);
            $players[$team->name]['team_name'] = $team->name;
        }

        return response()->json($players,200);
    }
}
