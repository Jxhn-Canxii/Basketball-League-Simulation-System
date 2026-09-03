<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Teams;
use App\Services\Team\TeamsService;

class TeamsController extends Controller
{
    protected $teamsService;

    public function __construct()
    {

        $this->teamsService = new TeamsService();
    }
    // Display a listing of the resource.
    public function index()
    {
        return Inertia::render('Teams/Index', [
            'status' => session('status'),
        ]);
    }
    
    public function list(Request $request)
    {
        return $this->teamsService->list($request);
    }
    
    public function teamslatestseason(Request $request)
    {
        return $this->teamsService->teamslatestseason($request);
    }

    public function matchhistory(Request $request)
    {
        return $this->teamsService->matchhistory($request);
    }

    public function currentseasonstatistics(Request $request)
    {
        return $this->teamsService->currentseasonstatistics($request);
    }

    public function getLastMatchResults($seasonId, $homeId, $awayId)
    {
        return $this->teamsService->getLastMatchResults($seasonId, $homeId, $awayId);
    }

    public function getTeamSeriesResults($seasonId, $homeId, $awayId)
    {
        return $this->teamsService->getTeamSeriesResults($seasonId, $homeId, $awayId);
    }

    public function teamInfo(Request $request)
    {
        return $this->teamsService->teamInfo($request);
    }

    public function teamSeasonFinals(Request $request)
    {
        return $this->teamsService->teamSeasonFinals($request);
    }

    public function teamSeasonStandings(Request $request)
    {
        return $this->teamsService->teamSeasonStandings($request);
    }
    public function teamSeasonHistory(Request $request)
    {
        return $this->teamsService->teamSeasonHistory($request);
    }

    public function teamsTransactionHistory(Request $request)
    {
        return $this->teamsService->teamsTransactionHistory($request);
    }
    public function teamLastSeason(Request $request)
    {
        return $this->teamsService->teamLastSeason($request);
    }
    public function teamMatches(Request $request)
    {
        return $this->teamsService->teamMatches($request);
    }

    public function teamMatchesH2H(Request $request)
    {
        return $this->teamsService->teamMatchesH2H($request);
    }
    public function teamRivals(Request $request)
    {
        return $this->teamsService->teamRivals($request);
    }
    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'acronym' => 'required',
            'league_id' => 'required|exists:leagues,id', // Assuming there's a leagues table
            'conference_id' => 'required|exists:conferences,id', // Assuming there's a conferences table
        ]);
        return $this->teamsService->add($request);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required',
            'acronym' => 'required|max:3',
            'league_id' => 'required|exists:leagues,id',
            'conference_id' => 'required|exists:conferences,id' // Assuming there's a conferences table
        ]);
        return $this->teamsService->update($request);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);
        return $this->teamsService->delete($request);
    }

    // Remove the specified resource from storage.
    public function getTeamsByConference(Request $request)
    {
        $request->validate([
            'conference_id' => 'required|integer',
        ]);
        return $this->teamsService->getTeamsByConference($request);
    }
}
