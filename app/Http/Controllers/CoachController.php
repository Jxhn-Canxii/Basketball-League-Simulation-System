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
            ->orderBy('is_active', 'desc') // active
            ->orderBy('career_wins', 'desc') // Highest win rate first
            ->offset($offset)
            ->limit($perPage)
            ->get();
    
        $latestSeason = get_current_season_id();
    
        $teamsWithoutCoach = DB::table('teams')->select('name')->where('coach_id', 0)->get();
        $teamsWithoutCoachCount =  $teamsWithoutCoach->count();
        
        return response()->json([
            'coaches' => $coaches,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'total_count' => $totalCount,
            'search' => $searchQuery,
            'current_season' => $latestSeason,
            'teams_without_coach' =>  $teamsWithoutCoach,
            'teams_without_coach_count' =>  $teamsWithoutCoachCount,
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

        return response()->json(['message' => 'Coach signings period ended and team season info updated!']);
    }

    public function assignFreeAgentCoaches()
    {
        $currentSeasonId = get_current_season_id() ?? 1;
    
        $teamsWithoutCoach = DB::table('teams')->where('coach_id', 0)->get();
        $teamCount = $teamsWithoutCoach->count();
        $maxCoachesToQuery = $teamCount + 10;

        if ($teamsWithoutCoach->isEmpty()) {
            $this->endCoachSignings();
    
            return response()->json([
                'message' => 'No teams need a coach. Ended coach signings.'
            ], 400);
        }
    
        $freeCoaches = DB::table('coaches')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->select('*')
            ->selectRaw('
                CASE 
                    WHEN experience_years = 0 THEN 2
                    WHEN winning_percentage < 0.3 THEN 3
                    ELSE 1
                END as priority_group
            ')
            ->orderBy('priority_group', 'asc')
            ->orderBy('winning_percentage', 'desc')
            ->limit($maxCoachesToQuery)
            ->get()
            ->shuffle(); // Randomize after limiting
    
        if ($freeCoaches->isEmpty()) {
            return response()->json([
                'message' => 'No free agent coaches available. Please invite coaches for applications.'
            ], 400);
        }
    
        $assigned = [];
        $freeCoachesArray = $freeCoaches->toArray();
    
        // Start Transaction
        DB::beginTransaction();
    
        try {
            foreach ($teamsWithoutCoach as $team) {
                $coach = array_shift($freeCoachesArray);
    
                if ($coach) {
                    $contractYears = rand(3, 7);
    
                    // Update team
                    DB::table('teams')
                        ->where('id', $team->id)
                        ->update(['coach_id' => $coach->id]);
    
                    // Update coach
                    DB::table('coaches')
                        ->where('id', $coach->id)
                        ->update([
                            'team_id' => $team->id,
                            'contract_years' => $contractYears
                        ]);
    
                    // Insert into transactions
                    DB::table('transactions')->insert([
                        'player_id' => 0,
                        'season_id' => $currentSeasonId,
                        'details' => $coach->name . ' has been appointed as the new coach of ' . $team->name,
                        'from_team_id' => 0,
                        'to_team_id' => $team->id,
                        'status' => 'appointed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
    
                    $assigned[] = [
                        'team_id' => $team->id,
                        'coach_id' => $coach->id,
                    ];
                } else {
                    break;
                }
            }
    
            DB::commit(); // Everything good
    
            return response()->json([
                'message' => 'Coaches assigned successfully.',
                'assigned' => $assigned,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Something went wrong, cancel everything
    
            return response()->json([
                'message' => 'Failed to assign coaches. Error: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function fixDuplicateCoaches()
    {
        $coaches = DB::table('coaches')
            ->where('team_id', '!=', 0)
            ->get();

        // Group coaches by team_id
        $grouped = $coaches->groupBy('team_id');

        foreach ($grouped as $teamId => $teamCoaches) {
            if ($teamCoaches->count() > 1) {
                // Sort by (career_wins + career_losses) DESC
                $sorted = $teamCoaches->sortByDesc(function($coach) {
                    return $coach->career_wins + $coach->career_losses;
                });

                // Get the best coach (keep this one)
                $bestCoach = $sorted->first();

                // All others need to be "free agents"
                $otherCoaches = $sorted->slice(1);

                foreach ($otherCoaches as $coach) {
                    DB::table('coaches')
                        ->where('id', $coach->id)
                        ->update([
                            'team_id' => 0,
                            'career_wins' => 0,
                            'career_losses' => 0,
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        return response()->json(['message' => 'Duplicate coaches fixed successfully!']);
    }

}
