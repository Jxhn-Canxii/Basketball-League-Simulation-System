<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoundController extends Controller
{
       // Function to get the results of the previous round in a conference
    public function previousConferenceRoundResults(Request $request)
    {
        // Retrieve the season_id and conference_id from the request
        $seasonId = $request->season_id;
        $conferenceId = $request->conference_id;

        // Retrieve the current round from the request
        $currentRound = $request->current_round;

        // Determine the previous round in the specified conference
        $previousRound = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->where('conference_id', $conferenceId)
            ->where('round', '<', $currentRound) // Adjust comparison as needed
            ->orderByDesc('round')
            ->value('round');

        // Fetch results for the previous round in the specified conference
        $previousRoundResults = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->where('conference_id', $conferenceId)
            ->where('round', $previousRound)
            ->get();

        return response()->json([
            'previous_round_results' => $previousRoundResults,
        ]);
    }

    public function getConferenceRoundNotSimulated(Request $request)
    {
        $seasonId = $request->season_id;
        $conferenceId = $request->conference_id;
        $excludedRounds = config('playoffs');

        $rounds = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->where('conference_id', $conferenceId)
            ->whereNotIn('round', $excludedRounds)
            ->where('status', 1)  // Filter to include only rounds with status = 1
            ->distinct('round')
            ->orderByRaw('CAST(round AS UNSIGNED) ASC')  // Order by round as an integer
            ->pluck('round'); // Get a list of distinct rounds

        $isFullySimulated = !DB::table('schedules')
            ->where('season_id', $seasonId)
            ->where('conference_id', $conferenceId)
            ->whereNotIn('round', $excludedRounds)
            ->where('status', '!=', 2) // Check if any game is not yet simulated
            ->exists(); // If no such games exist, the conference is fully simulated

        // if ($rounds->isEmpty()) {
        //     return response()->json([
        //         'error' => 'All conference rounds already simulated!.',
        //     ], 404); // Return 404 error with the message if no rounds are found
        // }

        return response()->json([
            'rounds' => $rounds, // Include the list of rounds in the response
            'is_finished' => $isFullySimulated,
        ]);
    }

    public function getSeasonRoundNotSimulated(Request $request)
    {
        $seasonId = $request->season_id;
        $excludedRounds = config('playoffs');

        // Get the list of distinct rounds in the season (excluding the ones in $excludedRounds)
        $rounds = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->whereNotIn('round', $excludedRounds)
            ->where('status', 1)  // Filter to include only rounds with status = 1
            ->distinct('round')
            ->orderByRaw('CAST(round AS UNSIGNED) ASC')  // Order by round as an integer
            ->pluck('round'); // Get a list of distinct rounds


        // Determine if the season is fully simulated
        $isFullySimulated = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->whereNotIn('round', $excludedRounds)
            ->where('status', '==', 2) // Check if any game is not yet simulated
            ->exists(); // If no such games exist, the conference is fully simulated

        // Optionally, you could handle the trade deadline flag here
        // if ($isTradeDeadline) {
        //     // Set is_trade_deadline = true (you can update the status in the database or perform some action)
        //     DB::table('seasons')->where('id', $seasonId)->update([
        //         'is_trade_deadline' => true,
        //     ]);
        // }

        return response()->json([
            'rounds' => $rounds, // Include the list of rounds
            'is_finished' => $isFullySimulated,
        ]);
    }
}
