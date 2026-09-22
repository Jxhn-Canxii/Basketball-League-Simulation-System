<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\Schedule\ScheduleService;

class ScheduleController extends Controller
{
    protected $scheduleService;

    public function __construct()
    {
        $this->scheduleService = new ScheduleService();
    }
    //
    public function index()
    {
        return Inertia::render('Schedules/Index', [
            'status' => session('status'),
        ]);
    }

    public function getScheduleIds(Request $request)
    {
        // Validate the request data
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'round' => 'required',
        ]);

        return $this->scheduleService->getScheduleIds($request);

    }

    public function insertExhibitionSchedule(Request $request)
    {
        // Validate the request data
        $request->validate([
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id',
        ]);

        return $this->scheduleService->insertExhibitionSchedule($request);

    }

   

    public function list(Request $request)
    {
        // Validate the request data
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'round' => 'required',
        ]);

        return $this->scheduleService->list($request);

    }

    public function listExhibition(Request $request)
    {
        // Validate the request data
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
        ]);

        return $this->scheduleService->listExhibition($request);

    }

    public function createSeasonandSchedule(Request $request)
    {
        // Validate the request data
        $request->validate([
            'season_name' => 'required|unique:seasons,name',
            'type' => 'required|in:1,2,3,4,5,6',
            'start' => 'required',
            'league_id' => 'required|exists:leagues,id',
            'playoff_type' => 'required|in:1,2',
        ]);

        return $this->scheduleService->createSeasonandSchedule($request);

    }

    public function playOffSeriesResults(Request $request)
    {
        
        return $this->scheduleService->playOffSeriesResults($request);

    }

    public function seasonSchedules(Request $request)
    {
        
        return $this->scheduleService->seasonSchedules($request);

    }

    public function teamseasonschedules(Request $request)
    {
        
        return $this->scheduleService->teamseasonschedules($request);

    }

    public function getScheduleCount($teamId, $seasonId)
    {   
        return $this->scheduleService->getScheduleCount($teamId, $seasonId);

    }
}
