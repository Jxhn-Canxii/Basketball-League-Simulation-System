<?php

namespace App\Http\Controllers;

use App\Models\Seasons;
use App\Models\Schedules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SeasonsController extends Controller
{
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
        // Retrieve seasons based on the provided league_id
        $query = DB::table('seasons');

        // Join the teams table to retrieve conference names
        $query->leftJoin('teams as winner', 'seasons.finals_winner_id', '=', 'winner.id')
            ->leftJoin('teams as loser', 'seasons.finals_loser_id', '=', 'loser.id')
            ->leftJoin('teams as weakest', 'seasons.weakest_id', '=', 'weakest.id')
            ->leftJoin('teams as champion', 'seasons.champion_id', '=', 'champion.id')
            ->leftJoin('conferences as winner_conference', 'winner.conference_id', '=', 'winner_conference.id')
            ->leftJoin('conferences as loser_conference', 'loser.conference_id', '=', 'loser_conference.id')
            ->leftJoin('conferences as weakest_conference', 'weakest.conference_id', '=', 'weakest_conference.id')
            ->leftJoin('conferences as champion_conference', 'champion.conference_id', '=', 'champion_conference.id');

        // Retrieve search query from request
        $searchQuery = $request->search;

        // Apply search filter if search query is provided
        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('seasons.name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('winner.name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('loser.name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('weakest.name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('champion.name', 'like', '%' . $searchQuery . '%');
            });
        }

        // Order the seasons by the season_id column in descending order (latest first)
        $query->orderByDesc('id');

        // Get the total count of records after applying search filter
        $totalCount = $query->count();

        // Set the number of records to display per page
        $perPage = 10;

        // Calculate the total number of pages
        $totalPages = ceil($totalCount / $perPage);

        // Get the current page from the request, default to 1 if not provided
        $currentPage = $request->page_num ?? 1;

        // Calculate the offset for pagination
        $offset = ($currentPage - 1) * $perPage;

        // Retrieve seasons data with pagination after applying search filter
        $seasons = $query->offset($offset)
            ->limit($perPage)
            ->select('seasons.*', 'winner_conference.name as winner_conference_name', 'loser_conference.name as loser_conference_name', 'weakest_conference.name as weakest_conference_name', 'champion_conference.name as champion_conference_name')
            ->get();

        $isNewSeason = $this->isNewSeason();

        $teamIds = DB::table('teams')->pluck('id')->toArray();

        // If you need the result as a Collection again, you can convert it back
        $teamIdsCollection = collect($teamIds);
        // Create the response array

        $response = [
            'seasons' => $seasons,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'total_count' => $totalCount,
            'is_new_season' => $isNewSeason,
            'team_ids' => $teamIdsCollection, // Include team count in the response
            'current_season' =>  get_current_season_id(),
            'previous_season' =>  get_previous_season_id(),
        ];

        // Return the seasons data along with pagination information as a JSON response
        return response()->json($response);
    }

    public function seasonsPerLeague(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'league_id' => 'required|exists:leagues,id', // Ensure the league_id exists in the leagues table
        ]);

        // Retrieve seasons based on the provided league_id
        $seasons = Seasons::where('league_id', $request->league_id)->get(['id', 'name']);

        return response()->json($seasons);
    }
    public function seasonsPerLeaguePaginate(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'league_id' => 'required|exists:leagues,id', // Ensure the league_id exists in the leagues table
            'page_num' => 'required|numeric|min:1',
        ]);

        // Retrieve seasons based on the provided league_id
        $seasonsQuery = Seasons::where('league_id', $request->league_id)
            ->orderBy('id', 'desc'); // Order by ID in descending order

        // Get the total count of seasons
        $totalSeasons = $seasonsQuery->count();

        // Set the number of seasons per page
        $perPage = 5; // Change this value according to your requirements

        // Calculate the total pages
        $totalPages = ceil($totalSeasons / $perPage);

        // Retrieve the current page number from the request
        $pageNum = $request->page_num;

        // Validate the page number
        $pageNum = max(1, min($pageNum, $totalPages));

        // Calculate the offset based on the current page number
        $offset = ($pageNum - 1) * $perPage;

        // Retrieve seasons for the current page
        $seasons = $seasonsQuery->skip($offset)->take($perPage)->get(['id', 'name', 'finals_winner_name']);

        // Prepare the page numbers for pagination
        $pageNumbers = range($pageNum, min($totalPages, $pageNum + 2));

        // Return the seasons and page numbers as JSON response
        return response()->json([
            'seasons' => $seasons,
            'page_numbers' => $pageNumbers,
            'total_pages' => $totalPages,
            'page_num' => $pageNum,
        ]);
    }

    public function seasonInfo(Request $request)
    {
        // Retrieve the season_id from the request
        $seasonId = $request->season_id;

        // Retrieve season information and associated conferences
        $seasons = DB::table('seasons')
            ->where('id', $seasonId)
            ->get();

        $conferences = self::allconference($seasonId);

        $totalConference = count($conferences);
        return response()->json([
            'seasons' => $seasons,
            'conferences' => $conferences,
            'is_play_ins' =>  $totalConference > 3,
            'conference_count' => $totalConference
        ]);
    }

    private function allConference($seasonId)
    {
        // Check season type
        $season = DB::table('seasons')
            ->where('id', $seasonId)
            ->select('type')
            ->first();

        if (!$season) {
            throw new \Exception("Season with ID {$seasonId} not found.");
        }

        // Fetch conferences
        $conferences = DB::table('conferences')
            ->join('seasons', 'conferences.league_id', '=', 'seasons.league_id')
            ->where('seasons.id', $seasonId)
            ->select('conferences.id as conference_id', 'conferences.name as conference_name')
            ->distinct()
            ->get();

        // Convert to array for modification
        $conferences = $conferences->toArray();

        $conferenceChampions = [];
        foreach ($conferences as $conference) {
            $champions = self::championsperconference($conference->conference_id);

            $championshipSeasons = [];
            foreach ($champions as $champion) {
                $championshipSeasons[] = [
                    'season' => $champion->season_name,
                    'team_name' => $champion->finals_winner_name
                ];
            }

            $championCount = count($championshipSeasons);

            $conferenceChampions[] = [
                'id' => $conference->conference_id,
                'name' => $conference->conference_name,
                'champions_count' => $championCount,
                'championship_season' => $championshipSeasons
            ];
        }

        return $conferenceChampions;
    }
    public function seasonStoryLine(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'season_id' => 'required|exists:seasons,id', // Ensure the season_id exists in the seasons table
        ]);

        // Retrieve the storylines for the given season_id
        $storylines = DB::table('storylines')
            ->where('season_id', $request->season_id)
            ->get(['id', 'storyline'])
            ->first(); // Get the first storyline or null if none exists

        // Return the storylines as a JSON response
        return response()->json($storylines);
    }
    public function getSeasonsDropdown()
    {
        // Fetch all seasons with their id and name, ordered by the latest season_id
        $seasons = DB::table('seasons')
            ->select('id as season_id', 'name', 'status as status')
            ->orderBy('id', 'desc') // Order by season_id in descending order
            ->get();

        // Return the data as JSON response
        return response()->json($seasons);
    }

    public function getTeamSeasonsDropdown(Request $request){

        $teamId = $request->team_id;

        // Fetch all seasons with their id and name, ordered by the latest season_id
        $seasons = DB::table('team_season_info as tsi')
            ->join('seasons as s', 's.id', '=', 'tsi.season_id','inner')
            ->select('s.id as season_id', 's.name', 's.status as status')
             ->where('tsi.team_id', $teamId) // Filter by the team id
            ->orderBy('tsi.id', 'desc') // Order by season_id in descending order
            ->get();

        // Return the data as JSON response
        return response()->json($seasons);
    }
    // Other methods...

    private function championsPerConference($conference_id)
    {
        $result = DB::table('teams')
            ->join('seasons', 'teams.id', '=', 'seasons.finals_winner_id')
            ->where('teams.conference_id', $conference_id)
            ->select('seasons.finals_winner_name', 'seasons.name as season_name')
            ->get();
        return $result;
    }

    private function isNewSeason()
    {
        // Get the total count of seasons
        $totalSeasons = DB::table('seasons')->count();

        // Return 4 if there are no seasons
        if ($totalSeasons == 0) {
            return 8;
        }

        // Get the last season status
        $lastSeasonStatus = DB::table('seasons')
            ->orderBy('id', 'desc')
            ->value('status');

        // Check the status and return the appropriate value
        if ($lastSeasonStatus == config('timeline.finals')) {
            return 1; //show update awards update to 9
        } elseif ($lastSeasonStatus == config('timeline.awards')) {
            return 2; //update player status update to 10
        } elseif ($lastSeasonStatus == config('timeline.player_update')) {
            return 3; //player rookie drafting update
        } elseif ($lastSeasonStatus == config('timeline.draft')) {
            return 4; //player signing
        } elseif ($lastSeasonStatus == config('timeline.off_season_trade')) {
            return 5; // player trade
        } elseif ($lastSeasonStatus == config('timeline.player_signings')) {
            return 6; // new season
        } elseif ($lastSeasonStatus == config('timeline.coach_signings')) {
            return 7; // new season
        }
        // Optionally, you can return a default value if no status matches
        return null;
    }
}
