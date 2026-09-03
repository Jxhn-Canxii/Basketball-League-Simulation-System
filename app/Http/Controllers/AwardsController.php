<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use App\Services\League\AwardsService;

class AwardsController extends Controller
{

    protected $awardsService;

    public function __construct()
    {
        $this->awardsService = new AwardsService();
    }
    public function index()
    {
        return Inertia::render('Awards/Index', [
            'status' => session('status'),
        ]);
    }
   
    public function getSeasonAwards(Request $request)
    {

        // Validate the incoming request
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
        ]);
        // Fetch awards along with player, team, and season names for the latest season
       
        return $this->awardsService->getSeasonAwards($request);
    }

    public function getawardnamesdropdown()
    {
        // Fetch distinct award names from the season_awards table
        return $this->awardsService->getawardnamesdropdown();
    }

    public function filterawardsperseason(Request $request)
    {
        return $this->awardsService->filterawardsperseason($request);
    }

    public function storeSeasonAwards()
    {
        return $this->awardsService->storeSeasonAwards();
    }

    public function storeSeasonAwardsAuto(Request $request)
    {
        return $this->awardsService->storeSeasonAwardsAuto($request);

    }

    public function getFinalsMVPList()
    {
       return $this->awardsService->getFinalsMVPList();
    }
    
}
