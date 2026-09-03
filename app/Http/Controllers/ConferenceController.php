<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Services\League\ConferenceService;

class ConferenceController extends Controller
{
    protected $conferenceService;

    public function __construct()
    {
        $this->conferenceService = new ConferenceService();
    }
    /**
     * Get the list of all conferences with associated league name.
     */
    public function list()
    {
        return $this->conferenceService->list();
    }

    /**
     * Get the list of all conferences per league_id.
     */
    public function leagueConference(Request $request)
    {
        return $this->conferenceService->leagueConference($request);
    }

    /**
     * Add a new conference.
     */
    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'league_id' => 'required|exists:leagues,id',
        ]);

        return $this->conferenceService->add($request);
    }

    /**
     * Delete a conference.
     */
    public function delete(Conference $conference)
    {
        return $this->conferenceService->delete($conference);
    }
}
