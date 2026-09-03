<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use App\Services\Analytics\AnalyticsService;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct()
    {
        $this->analyticsService = new AnalyticsService;
    }
    public function index()
    {
        return Inertia::render('Analytics/Index', [
            'status' => session('status'),
        ]);
    }

    public function getAllStandings(Request $request)
    {
        $request->validate([
            'conference_id' => 'required|integer',
            'team_id' => 'required|integer',
        ]);
        
        return $this->analyticsService->getAllStandings($request);
    }

    public function countPlayers()
    {
        return $this->analyticsService->countPlayers(); 
    }

    public function getSeasonLeaders(Request $request)
    {
        return $this->analyticsService->getSeasonLeaders($request); 
    }

    public function getAllStatistics()
    {
       return $this->analyticsService->getAllStatistics();
    }

    public function getDraftPlayerStatistics()
    {
       return $this->analyticsService->getDraftPlayerStatistics();
    }
}
