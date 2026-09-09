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

    public function waivePlayer(Request $request){

        return $this->freeAgencyService->waivePlayer($request);
    }

    public function extendContract(Request $request){

        $request->validate([
            'id' => 'required|exists:players,id',
            'additional_years' => 'required|integer|min:1|max:5',
        ]);

        return $this->freeAgencyService->extendContract($request);
    }

    public function assignPlayerToRandomTeam(Request $request){

        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->freeAgencyService->assignPlayerToRandomTeam($request);
    }

    public function assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId)
    {
        return $this->freeAgencyService->assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId);
    }

    public function signFreeAgent(Request $request)
    {
        $request->validate([
            'player_id' => 'required|exists:players,id',
            'team_id' => 'required|exists:teams,id',
        ]);

        return $this->freeAgencyService->signFreeAgent($request);
    }

    public function assignRemainingFreeAgents()
    {
        return $this->freeAgencyService->assignRemainingFreeAgents();
    }

    public function autoAssignFreeAgents()
    {
        return $this->freeAgencyService->autoAssignFreeAgents();
    }

    public function getTeamPositionCounts($teamId)
    {
        return $this->freeAgencyService->getTeamPositionCounts($teamId);
    }

    public function checkPositionAvailability()
    {
        return $this->freeAgencyService->checkPositionAvailability();
    }
}
