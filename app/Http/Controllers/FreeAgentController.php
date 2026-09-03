<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Player;
use App\Services\Player\FreeAgentService;
class FreeAgentController extends Controller
{
   
    protected $freeAgentService;

    public function __construct()
    {
        $this->freeAgentService = new FreeAgentService();
    }

    public function getBestFreeAgent(Request $request){

        return $this->freeAgentService->getBestFreeAgent($request);
    }

    public function getBestFreeAgentAvailable($position){

        return $this->freeAgentService->getBestFreeAgentAvailable($position);
    }


    public function getBestFreeAgentScouted($teamId, $position)
    {
        return $this->freeAgentService->rankFreeAgentsForPosition($position, $teamId)->first();
    }

    public function runFreeAgencyPeriod(Request $request)
    {
        $request->validate([
            'season_id' => 'nullable|integer|min:1',
        ]);

        return  $this->freeAgentService->runFreeAgencyPeriod($request);

    }

    public function getBestFreeAgentOffWaiver()
    {
        return  $this->freeAgentService->getBestFreeAgentOffWaiver();
    }

    public function updateInjuryFreeAgents()
    {
        return  $this->freeAgentService->updateInjuryFreeAgents();
    }

    public function getBestAvailableFreeAgent()
    {
        return  $this->freeAgentService->getBestAvailableFreeAgent();
    }
}
