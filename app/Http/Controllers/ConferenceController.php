<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PlayoffTreeService;


class ConferenceController extends Controller
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

    public function seasonInfo(Request $request)
    {
        // Retrieve the season_id from the request
        $seasonId = $request->season_id;

        // Retrieve season information and associated conferences
        $seasons = DB::table('seasons')
            ->where('id', $seasonId)
            ->get();

        // Retrieve conferences associated with the league of the provided season_id
        $conferences = DB::table('conferences')
            ->join('seasons', 'conferences.league_id', '=', 'seasons.league_id')
            ->where('seasons.id', $seasonId)
            ->select('conferences.id', 'conferences.name')
            ->distinct()
            ->get();

        return response()->json([
            'seasons' => $seasons,
            'conferences' => $conferences,
        ]);
    }

    public function seasonStandings(Request $request)
    {
        // Retrieve the season_id and conference_id from the request
        $seasonId = $request->season_id;
        $conferenceId = $request->conference_id;

        $latestSeasonId = get_current_season_id();

        // Determine the table to query based on season_id
        $table = ($seasonId == $latestSeasonId) ? 'standings_view' : 'standings_snapshots';

        // Fetch the conference name from the conferences table
        $conference = DB::table('conferences')
            ->where('id', $conferenceId)
            ->first();

        // Fetch standings filtered by season_id and conference_id
        $standings = DB::table($table)
            ->where('season_id', $seasonId)
            ->where('conference_id', $conferenceId)
            ->orderByDesc('wins') // Order by wins in descending order
            ->orderBy('conference_rank', 'asc') // If wins are tied, order by conference_rank ascending
            ->get();

        // Get all-time conference_rank = 1 and overall_rank = 1 counts per team
        $rankCounts = DB::table('standings_snapshots') // Always use snapshots for historical rank counts
            ->where('conference_id', $conferenceId)
            ->select('team_id')
            ->selectRaw('SUM(conference_rank = 1) as conference_rank_1_count')
            ->selectRaw('SUM(overall_rank = 1) as overall_rank_1_count')
            ->groupBy('team_id')
            ->pluck('conference_rank_1_count', 'team_id')
            ->toArray();

        $overallCounts = DB::table('standings_snapshots') // Always use snapshots for historical rank counts
            ->where('conference_id', $conferenceId)
            ->select('team_id')
            ->selectRaw('SUM(overall_rank = 1) as overall_rank_1_count')
            ->groupBy('team_id')
            ->pluck('overall_rank_1_count', 'team_id')
            ->toArray();

        // Add the all-time conference_rank = 1 and overall_rank = 1 counts to each team's standings
        $standings = $standings->map(function ($team) use ($rankCounts, $overallCounts) {
            $team->conference_rank_count = isset($rankCounts[$team->team_id]) ? (int)$rankCounts[$team->team_id] : 0;
            $team->overall_rank_count = isset($overallCounts[$team->team_id]) ? (int)$overallCounts[$team->team_id] : 0;
            return $team;
        });

        // Return the standings along with the conference name
        return response()->json([
            'standings' => $standings,
            'conference_name' => $conference ? $conference->name : 'N/A', // Check if conference exists
        ]);
    }

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

    public function seasonschedules(Request $request)
    {
        $seasonId = $request->season_id;
        $teamId = $request->team_id;
        $conferenceId = $request->conference_id;
        $excludedRounds = config('playoffs');
        $itemsPerPage = $request->itemsperpage ?: 6;
        $currentPage = $request->page_num ?: 1;
    
        // Calculate offset for pagination based on current page
        $offset = ($currentPage - 1) * $itemsPerPage;
    
        // Get total count first
        $totalSchedules = DB::table('schedule_view')
            ->where('season_id', $seasonId)
            ->where('conference_id', $conferenceId)
            ->when($teamId != 0, function ($query) use ($teamId) {
                return $query->where(function ($q) use ($teamId) {
                    $q->where('home_id', $teamId)
                      ->orWhere('away_id', $teamId);
                });
            })
            ->whereNotIn('round', $excludedRounds)
            ->count();
    
        $totalPages = ceil($totalSchedules / $itemsPerPage);
    
        // Reset to first page if requested page is too high
        if ($offset >= $totalSchedules) {
            $offset = 0;
            $currentPage = 1;
        }
    
        // Fetch the actual schedule data
        $schedules = DB::table('schedule_view')
            ->where('season_id', $seasonId)
            ->where('conference_id', $conferenceId)
            ->when($teamId != 0, function ($query) use ($teamId) {
                return $query->where(function ($q) use ($teamId) {
                    $q->where('home_id', $teamId)
                      ->orWhere('away_id', $teamId);
                });
            })
            ->whereNotIn('round', $excludedRounds)
            ->orderBy('status', 'desc')  // Sort status 2 first
            ->orderBy('id', 'desc')  // Within status 2, order by updated_at
            ->skip($offset)
            ->take($itemsPerPage)
            ->get()
            ->toArray();
    
        // Check if all non-final rounds are simulated
        $allRoundsSimulated = DB::table('schedule_view')
            ->where('season_id', $seasonId)
            ->whereNotIn('round', $excludedRounds)
            ->where('status', 1)
            ->doesntExist();
    
        return response()->json([
            'schedules' => $schedules,
            'is_simulated' => $allRoundsSimulated,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_count' => $totalSchedules,
        ]);
    }
    
    public function getConferenceRoundNotSimulated(Request $request){
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

    
    private function updateInjuryFreeAgents()
    {
        // Update injury recovery games for free agents and mark them as not injured if recovery games reach 0
        DB::table('players')
            ->where('team_id', 0) // Only for free agents (team_id = 0)
            ->where('is_injured', 1) // Only consider injured players
            ->where('is_active', 1) // Only consider active players
            ->where('injury_recovery_games', '>', 0) // Only consider players with recovery games left
            ->decrement('injury_recovery_games', 1); // Decrease recovery games by 1

        // After decrementing, check if recovery games is 0, and mark player as not injured
        DB::table('players')
            ->where('team_id', 0) // Only for free agents
            ->where('is_injured', 1) // Only for injured players
            ->where('injury_recovery_games', 0) // Check if recovery games are 0 after decrement
            ->update([
                'is_injured' => 0, // Set is_injured to 0 for players with no injury recovery games left
            ]);
    }

    public function seasonsplayoffs(Request $request)
    {
        // Retrieve the season_id from the request
        $seasonId = $request->season_id;
        $status = $request->status;
        $start = $request->start;
        $type = $request->type; // 1 is for finished playoffs : 2 for single update
        $playoffs = $this->playoffTree($seasonId, $status, $type, $start);

        return response()->json([
            'playoffs' => $playoffs,
        ]);
    }
    
    private static function playoffTree($seasonId, $status, $type, $start)
    {
        
        $status = 6;
        $type = 1;
        $start = 16;

        $tree = PlayoffTreeService::buildPlayoffTree($seasonId, $status, $type, $start);

        return response()->json($tree);
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
