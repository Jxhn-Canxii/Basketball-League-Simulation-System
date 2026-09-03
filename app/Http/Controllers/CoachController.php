<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use App\Services\Coach\CoachService;

class CoachController extends Controller
{
    protected $coachService;

    public function __construct(){
        $this->coachService = new CoachService();
    }

    // Read all coaches
    public function index()
    {
        return Inertia::render('Coaches/Index', [
            'status' => session('status'),
        ]);
    }

    public function listCoaches(Request $request)
    {
        return $this->coachService->listCoaches($request);
    }

    // Create a new coach
    public function addFreeAgentCoach(Request $request)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:100',
            'nationality' => 'required|string|max:100',
        ]);

        return $this->coachService->addFreeAgentCoach($request);
    }

    public function endCoachSignings()
    {
        return $this->coachService->endCoachSignings();
    }

    public function assignFreeAgentCoaches()
    {
        return $this->coachService->assignFreeAgentCoaches();
    }

    public function fixDuplicateCoaches()
    {
        return $this->coachService->fixDuplicateCoaches();
    }

    public function getCoachInfo(Request $request)
    {
        // Validate the request data
        $request->validate([
            'coach_id' => 'required|exists:coaches,id',
        ]);
    
        return $this->coachService->getCoachInfo($request);
    }

    public function getSeasonHistory(Request $request)
    {
        return $this->coachService->getSeasonHistory($request);
    }
}
