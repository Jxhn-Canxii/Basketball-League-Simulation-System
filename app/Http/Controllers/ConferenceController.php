<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // $standings = DB::table($table)
        //     ->where('season_id', $seasonId)
        //     ->where('conference_id', $conferenceId)
        //     ->orderByDesc('wins') // Order by wins in descending order
        //     ->orderBy('conference_rank', 'asc') // If wins are tied, order by conference_rank ascending
        //     ->get();

        $standings = DB::table($table . ' as s')
            ->join('team_reputation_view as trv', function ($join) use ($seasonId) {
                $join->on('s.team_id', '=', 'trv.team_id');
            })
            ->where('s.season_id', $seasonId)
            ->where('s.conference_id', $conferenceId)
            ->orderByDesc('s.wins')
            ->orderBy('s.conference_rank', 'asc')
            ->select([
                's.*', // All columns from standings
                'trv.reputation_score',
                'trv.estimated_fans',
                'trv.streak_status',
                'trv.chemistry',
                'trv.wins_diff',
                'trv.rank_improvement',
                'trv.chemistry_diff',
                'trv.prev_wins',
                'trv.prev_rank',
                'trv.prev_chemistry'
            ])
            ->get();

        // Get all-time conference_rank = 1 and overall_rank = 1 counts per team
        $rankCounts = DB::table('standings_snapshots')
            ->where('conference_id', $conferenceId)
            ->select('team_id')
            ->selectRaw('SUM(conference_rank = 1) as conference_rank_1_count')
            ->selectRaw('SUM(conference_rank <= 3) as conference_top_3_count')
            ->selectRaw('SUM(overall_rank = 1) as overall_rank_1_count')
            ->groupBy('team_id')
            ->get()
            ->keyBy('team_id')
            ->toArray();

        // Add the all-time conference_rank = 1 and overall_rank = 1 counts to each team's standings
        $standings = $standings->map(function ($team) use ($rankCounts) {
            $team->conference_rank_count   = $rankCounts[$team->team_id]->conference_rank_1_count ?? 0;
            $team->conference_top_3_count  = $rankCounts[$team->team_id]->conference_top_3_count ?? 0;
            $team->overall_rank_count      = $rankCounts[$team->team_id]->overall_rank_1_count ?? 0;

             // Get rookies + stats for this team
            $rookies = DB::table('players as p')
                ->join('player_season_stats as ps', 'p.id', '=', 'ps.player_id')
                ->where('p.team_id', $team->team_id)
                ->where('p.is_rookie', 1)
                ->select('p.name', 'ps.avg_points_per_game', 'ps.avg_assists_per_game', 'ps.avg_rebounds_per_game')
                ->get();

            // Format rookies as "Name(23.4ppg,3.1apg,5.2rpg)"
            $team->rookies = $rookies->map(function ($r) {
                return sprintf(
                    "%s(%.1fppg,%.1fapg,%.1frpg)",
                    $r->name,
                    $r->avg_points_per_game,
                    $r->avg_assists_per_game,
                    $r->avg_rebounds_per_game
                );
            })->implode('\n');

            return $team;
        });


        $latestNews = DB::table('game_news')
            ->select('id', 'game_id', 'season_id', 'round', 'title', 'content', 'created_at', 'updated_at')
             ->where('season_id', $seasonId)
            ->orderBy('id', 'desc')
            ->first();

        // Return the standings along with the conference name
        return response()->json([
            'standings' => $standings,
            'conference_name' => $conference ? $conference->name : 'N/A', // Check if conference exists
            'latest_news' => $latestNews,
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
        $currentSeasonId = get_current_season_id();

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

        $teamIds = collect($schedules)->pluck('home_id')->merge(
            collect($schedules)->pluck('away_id')
        )->unique();

        $standingsTable = ($seasonId == $currentSeasonId) ? 'standings_view' : 'standings_snapshots';
        $standingsData = DB::table($standingsTable)
            ->whereIn('team_id', $teamIds)
            ->where('season_id', $seasonId)
            ->get()
            ->keyBy('team_id');

        $games = [];
        foreach ($schedules as $game) {
            $homeTeamName = $standingsData[$game->home_id]->name ?? DB::table('teams')->where('id', $game->home_id)->value('name');
            $awayTeamName = $standingsData[$game->away_id]->name ?? DB::table('teams')->where('id', $game->away_id)->value('name');

            $games[] = [
                'id' => $game->id,
                'game_id' => $game->game_id,
                'home_team' => [
                    'id' => $game->home_id,
                    'name' => $homeTeamName,
                    'home_score' => $game->home_score,
                    'conference' => $standingsData[$game->home_id]->conference_name ?? null,
                    'conference_rank' => $standingsData[$game->home_id]->conference_rank ?? null,
                    'overall_rank' => $standingsData[$game->home_id]->overall_rank ?? null,
                    'primary_color' => $standingsData[$game->home_id]->primary_color ?? '00000',
                    'secondary_color' => $standingsData[$game->home_id]->secondary_color ?? '00000',
                ],
                'away_team' => [
                    'id' => $game->away_id,
                    'name' => $awayTeamName,
                    'away_score' => $game->away_score,
                    'conference' => $standingsData[$game->away_id]->conference_name ?? null,
                    'conference_rank' => $standingsData[$game->away_id]->conference_rank ?? null,
                    'overall_rank' => $standingsData[$game->away_id]->overall_rank ?? null,
                    'primary_color' => $standingsData[$game->away_id]->primary_color ?? '00000',
                    'secondary_color' => $standingsData[$game->away_id]->secondary_color ?? '00000',
                ],
                'winner' => $game->winner_id,
                'season_id' => $seasonId,
            ];
        }
        // Check if all non-final rounds are simulated
        $allRoundsSimulated = DB::table('schedule_view')
            ->where('season_id', $seasonId)
            ->whereNotIn('round', $excludedRounds)
            ->where('status', 1)
            ->doesntExist();

        return response()->json([
            'schedules' => $games,
            'is_simulated' => $allRoundsSimulated,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_count' => $totalSchedules,
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
