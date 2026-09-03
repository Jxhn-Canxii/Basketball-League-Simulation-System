<?php

namespace App\Services\League;

use App\Models\Conference;
use Illuminate\Http\Request;

class ConferenceService
{

     /**
     * Get the list of all conferences with associated league name.
     */
    public function list()
    {
        $conferences = Conference::with('league')->get();
        return response()->json($conferences);
    }

    /**
     * Get the list of all conferences per league_id.
     */
    public function leagueConference(Request $request)
    {
        // Assuming 'league_id' is a parameter sent in the request
        $league_id = $request->league_id;

        // Assuming you have a relationship between leagues and conferences
        $conferences = Conference::where('league_id', $league_id)
            ->get(['id', 'name']);

        return response()->json($conferences);
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

        $conference = new Conference();
        $conference->name = $request->input('name');
        $conference->league_id = $request->input('league_id');
        $conference->save();

        return response()->json(['message' => 'Conference added successfully']);
    }

    /**
     * Delete a conference.
     */
    public function delete(Conference $conference)
    {
        $conference->delete();
        return response()->json(['message' => 'Conference deleted successfully']);
    }
}
