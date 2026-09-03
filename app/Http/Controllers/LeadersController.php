<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\Analytics\LeadersService;

class LeadersController extends Controller
{
    protected $leaderService;

    public function __construct(){
        $this->leaderService = new LeadersService();
    }
    public function index()
    {
        return Inertia::render('Leaders/Index', [
            'status' => session('status'),
        ]);
    }
    public function getAverageStatsLeaders(){
        return $this->leaderService->getAverageStatsLeaders();
    }

    public function getTotalStatsLeaders(){
        return $this->leaderService->getTotalStatsLeaders();
    }
 
    public function getSingleStatsLeaders(){
        return $this->leaderService->getSingleStatsLeaders();
    }

    public function updateAllTimeTopStats(){
        return $this->leaderService->updateAllTimeTopStats();
    }

    public function updateAllTimeTopStatsPerSeason(Request $request){
        return $this->leaderService->updateAllTimeTopStats($request);
    }
    
    public function getTop15MVPLeaders(){
        return $this->leaderService->getTop15MVPLeaders();
    }

}
