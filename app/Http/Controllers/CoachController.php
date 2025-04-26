<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CoachController extends Controller
{
    
    // Read all coaches
    public function index()
    {
        return Inertia::render('Coaches/Index', [
            'status' => session('status'),
        ]);
    }

    public function listCoaches(Request $request)
    {
        // Retrieve search query from request
        $searchQuery = $request->search;
    
        // Query builder for coaches with join on teams
        $query = DB::table('coaches')
            ->leftJoin('teams', 'coaches.team_id', '=', 'teams.id')
            ->select('coaches.*', 'teams.name as team_name');
    
        // Apply search filter if search query is provided
        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('teams.name', 'like', '%' . $searchQuery . '%')
                  ->orWhere('coaches.name', 'like', '%' . $searchQuery . '%');
            });
        }
    
        // Get total count of records before pagination
        $totalCount = $query->count();
    
        // Set the number of records to display per page
        $perPage = 10;
    
        // Calculate the total number of pages
        $totalPages = ceil($totalCount / $perPage);
    
        // Get the current page from the request, default to 1 if not provided
        $currentPage = $request->page_num ?? 1;
    
        // Calculate the offset for pagination
        $offset = ($currentPage - 1) * $perPage;
    
        // Clone the query before applying pagination (important)
        $coaches = (clone $query)
            ->offset($offset)
            ->limit($perPage)
            ->get();
    
        $latestSeason = get_current_season_id();
    
        return response()->json([
            'coaches' => $coaches,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'total_count' => $totalCount,
            'search' => $searchQuery,
            'current_season' => $latestSeason,
        ]);
    }
    
    // Create a new coach
    public function addFreeAgentCoach(Request $request)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        // Get the current season ID
        $currentSeasonId = get_current_season_id();
        $age = rand(35,45);
        $retirement_age = rand(50,65);
        $coachIq = rand(75,99);
        // Insert the new coach into the database
        $coachId = DB::table('coaches')->insertGetId([
            'name' => $request->name,
            'team_id' => 0,
            'coach_iq' => $coachIq,
            'age' => $age,
            'retirement_age' => $retirement_age ?? 65,
            'experience_years' => 0,
            'contract_years' => 0,
            'is_active' => 1,  // Default active
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        return response()->json(['message' => 'Coach '.$request->name.' has applied for the coaching pool.'], 201);
    }

    public function endCoachSignings()
    {
        $latestSeasonId = get_current_season_id() ?? 1; // Get the current season ID

        // Get teams that have no coach assigned
        $teamsWithoutCoach = DB::table('teams')->where('coach_id', 0)->get();

        // Check if there are any teams without a coach
        if ($teamsWithoutCoach->isNotEmpty()) {
            // Return an error response if there are teams without a coach
            return response()->json([
                'message' => 'Some teams have no coach assigned. Please auto-assign a coach.'
            ], 400); // 400 = Bad Request
        }

        // Start by updating the season status to indicate coach signings have ended
        DB::table('seasons')
            ->where('id', $latestSeasonId)
            ->update(['status' => config('timeline.coach_signings')]);

        $teamsCoach = DB::table('teams')->get();
        // Loop through each team to store their coach, coach IQ, chemistry, and conference info before the season starts
        foreach ($teamsCoach as $team) {
            // Fetch the coach information and team chemistry (or set defaults)
            $coach = DB::table('coaches')->where('id', $team->coach_id)->first();
            $coachIq = $coach ? $coach->coach_iq : 0; // Default coach IQ to 0 if no coach data
            $chemistry = 75; // Use existing team chemistry or default to 0

            // Fetch the conference_id for the team
            $conferenceId = $team->conference_id ?? 0; // Default to 0 if no conference assigned

            // Insert or update the team season info
            DB::table('team_season_info')->updateOrInsert(
                [
                    'team_id' => $team->id,
                    'season_id' => $latestSeasonId
                ],
                [
                    'coach_id' => $team->coach_id,
                    'coach_iq' => $coachIq,
                    'chemistry' => $chemistry,
                    'conference_id' => $conferenceId, // Add conference_id here
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json(['message' => 'Coach signings period ended and team season info updated!']);
    }

    public function assignFreeAgentCoaches()
    {
        $currentSeasonId = get_current_season_id() ?? 1;

        // Get all teams without a coach
        $teamsWithoutCoach = DB::table('teams')->where('coach_id', 0)->get();

        // Check if there are no teams without a coach
        if ($teamsWithoutCoach->isEmpty()) {
            // Call your endCoachSignings function (you should create this function separately)
            $this->endCoachSignings();

            // Return error response
            return response()->json([
                'message' => 'No teams need a coach. Ended coach signings.'
            ], 400); // 400 = Bad Request (you can also use 200 if you want success response)
        }

        // Get available free agent coaches
        $freeCoaches = DB::table('coaches')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->orderBy('id') // You can change this ordering if needed
            ->get();

        if ($freeCoaches->isEmpty()) {
            return response()->json([
                'message' => 'No free agent coaches available. Please invite coach for coaching application signings.'
            ], 400);
        }  
        // Track assigned coaches
        $assigned = [];

        foreach ($teamsWithoutCoach as $team) {
            // Get one free coach
            $coach = $freeCoaches->shift(); // Take first available coach

            if ($coach) {
                // Update the team's coach_id
                DB::table('teams')
                    ->where('id', $team->id)
                    ->update(['coach_id' => $coach->id]);

                $contractYears = rand(3,7);
                // Update the coach's team_id
                DB::table('coaches')
                    ->where('id', $coach->id)
                    ->update(['team_id' => $team->id,'contract_years' => $contractYears]);
                    
                 // Insert a transaction log with the current season ID

                DB::table('transactions')->insert([
                    'player_id' => 0, // No player involved here, can be adjusted if needed
                    'season_id' => $currentSeasonId, // Use the current season ID here
                    'details' => $coach->name . ' has been appointed as the new coach of '.$team->name,
                    'from_team_id' => 0,
                    'to_team_id' =>  $team->id,
                    'status' => 'appointed',
                ]);

                $assigned[] = [
                    'team_id' => $team->id,
                    'coach_id' => $coach->id,
                ];
            } else {
                // No more free coaches available
                break;
            }
        }

        return response()->json([
            'message' => 'Coaches assigned successfully.',
            'assigned' => $assigned,
        ]);
    }

}
