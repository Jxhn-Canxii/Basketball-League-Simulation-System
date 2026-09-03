<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\League\SeasonsService;

class SeasonsController extends Controller
{
    protected $seasonService;
    
    public function __construct(){
        $this->seasonService = new SeasonsService;
    }
    /**
     * Display a listing of the seasons.
     */
    public function index()
    {
        return Inertia::render('Seasons/Index', [
            'status' => session('status'),
        ]);
    }
    public function details($season_id, $playoff_type)
    {
        return Inertia::render('Seasons/Details', [
            'status' => session('status'),
            'season_id' => $season_id,  // Pass the season_id to the Vue page
            'playoff_type' => $playoff_type,  // Pass the playoff_type to the Vue page
        ]);
    }
    public function list(Request $request)
    {
        return $this->seasonService->list($request);
    }

    public function seasonsPerLeague(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'league_id' => 'required|exists:leagues,id', // Ensure the league_id exists in the leagues table
        ]);

        return $this->seasonService->seasonsPerLeague($request);
    }

    public function seasonsPerLeaguePaginate(Request $request)
    {
    
        // Validate the incoming request
        $request->validate([
            'league_id' => 'required|exists:leagues,id', // Ensure the league_id exists in the leagues table
            'page_num' => 'required|numeric|min:1',
        ]);

        return $this->seasonService->seasonsPerLeaguePaginate($request);
    }

    public function seasonInfo(Request $request)
    {
        return $this->seasonService->seasonInfo($request);
    }

    public function seasonStoryLine(Request $request)
    {
         // Validate the incoming request
        $request->validate([
            'season_id' => 'required|exists:seasons,id', // Ensure the season_id exists in the seasons table
        ]);

        return $this->seasonService->seasonStoryLine($request);
    }

    public function getTeamSeasonsDropdown(Request $request)
    {
         // Validate the incoming request
        $request->validate([
            'team_id' => 'required|exists:teams,id', // Ensure the season_id exists in the seasons table
        ]);

        return $this->seasonService->getTeamSeasonsDropdown($request);
    }

    public function getSeasonsDropdown()
    {
        return $this->seasonService->getSeasonsDropdown();
    }

}
