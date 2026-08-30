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
            $query->where(function ($q) use ($searchQuery) {
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
        $age = rand(35, 45);
        $retirement_age = rand(50, 65);
        $coachIq = rand(75, 99);
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


        return response()->json(['message' => 'Coach ' . $request->name . ' has applied for the coaching pool.'], 201);
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
                $sorted = $teamCoaches->sortByDesc(function ($coach) {
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

    public function getCoachInfo(Request $request)
    {
        // Validate the request data
        $request->validate([
            'coach_id' => 'required|exists:coaches,id',
        ]);

        $coachId = $request->coach_id;

        // Fetch player and team details
        $coachDetails = DB::table('coaches')
            ->join('teams', 'coaches.team_id', '=', 'teams.id', 'left') // Join teams table to get team details
            ->where('coaches.id', $coachId)
            ->select(
                'coaches.*',
                'teams.name as team_name',
                'teams.city as team_city',
                'teams.primary_color',
                'teams.secondary_color',
            )
            ->first();

        if (!$coachDetails) {
            return response()->json([
                'error' => 'Player not found.',
            ], 404);
        }

        // Fetch playoff performance
        $playoffQualified = DB::table('team_season_info')
            ->where('is_playoff_qualified', 1)
            ->where('coach_id', $coachId)
            ->count();

        $isDefendingChampion = DB::table('team_season_info')
            ->where('is_defending_champion', 1)
            ->where('coach_id', $coachId)
            ->count();

        // Set default values if no performance data found
        $playoffPerformance = (object)[
            'playoff_count' => $playoffQualified,
            'champion_count' => $isDefendingChampion,
        ];

        // Fetch championship count and season names
        $championships = DB::table('seasons')
            ->join('team_season_info', 'seasons.id', '=', 'team_season_info.season_id')
            ->join('playoff_series', 'seasons.id', '=', 'playoff_series.season_id')
            ->join('teams as team', 'team_season_info.team_id', '=', 'team.id')
            ->join('teams as winner_team', 'playoff_series.winner_team_id', '=', 'winner_team.id')
            ->select(
                'seasons.id as season_id',
                'seasons.name as season_name',
                'winner_team.name as championship_team'
            )
            ->where('team_season_info.coach_id', $coachId)
            ->where('playoff_series.round', 'finals')
            ->where('playoff_series.status', 2) // Series is finished
            ->whereColumn('playoff_series.winner_team_id', 'team_season_info.team_id') // Match columns correctly
            ->groupBy('seasons.id', 'seasons.name', 'winner_team.name')
            ->distinct()
            ->get();

        $conference_championships = DB::table('seasons')
            ->join('team_season_info', 'seasons.id', '=', 'team_season_info.season_id')
            ->join('playoff_series', 'seasons.id', '=', 'playoff_series.season_id')
            ->join('teams as team', 'team_season_info.team_id', '=', 'team.id')
            ->join('teams as winner_team', 'playoff_series.winner_team_id', '=', 'winner_team.id')
            ->select(
                'seasons.id as season_id',
                'seasons.name as season_name',
                'winner_team.name as championship_team'
            )
            ->where('team_season_info.coach_id', $coachId)
            ->where('playoff_series.round', 'semi_finals')
            ->where('playoff_series.status', 2) // Series is finished
            ->whereColumn('playoff_series.winner_team_id', 'team_season_info.team_id') // Match columns correctly
            ->groupBy('seasons.id', 'seasons.name', 'winner_team.name')
            ->distinct()
            ->get();

        // Calculate season count
        $seasonCount = DB::table('team_season_info')
            ->where('coach_id', $coachId)
            ->distinct('season_id')
            ->count('season_id');

        // Calculate playoff count
        $playoffCount = DB::table('team_season_info')
            ->where('coach_id', $coachId)
            ->where('is_playoff_qualified', 1)
            ->count('season_id');

        $overallRankSeasons = DB::table('team_season_info')
            ->join('standings_snapshots', function ($join) {
                $join->on('team_season_info.team_id', '=', 'standings_snapshots.team_id')
                    ->on('team_season_info.season_id', '=', 'standings_snapshots.season_id');
            })
            ->join('seasons', 'standings_snapshots.season_id', '=', 'seasons.id')
            ->join('teams', 'team_season_info.team_id', '=', 'teams.id')
            ->where('team_season_info.coach_id', $coachId)
            ->where('standings_snapshots.overall_rank', 1)
            ->distinct()
            ->get([
                'standings_snapshots.season_id',
                'seasons.name as season_name',
                'standings_snapshots.overall_rank',
                'teams.name as team_name'
            ]);

        $conferenceRankSeasons = DB::table('team_season_info')
            ->join('standings_snapshots', function ($join) {
                $join->on('team_season_info.team_id', '=', 'standings_snapshots.team_id')
                    ->on('team_season_info.season_id', '=', 'standings_snapshots.season_id');
            })
            ->join('seasons', 'standings_snapshots.season_id', '=', 'seasons.id')
            ->join('teams', 'team_season_info.team_id', '=', 'teams.id')
            ->where('team_season_info.coach_id', $coachId)
            ->where('standings_snapshots.conference_rank', 1)
            ->distinct()
            ->get([
                'standings_snapshots.season_id',
                'seasons.name as season_name',
                'standings_snapshots.conference_rank',
                'teams.name as team_name'
            ]);

        return response()->json([
            'coach_details' => $coachDetails,
            'playoff_performance' => $playoffPerformance,
            'national_championships' => $championships,
            'conference_championships' => $conference_championships,
            'national_overall_champions' => $overallRankSeasons,
            'conference_overall_champions' => $conferenceRankSeasons,
            'season_count' => $seasonCount,
            'playoff_count' => $playoffCount,
        ]);
    }

    public function getSeasonHistory(Request $request)
    {
        $coach_id = $request->coach_id;
        $page = $request->page_num ?? 1;
        $itemsPerPage = $request->itemsperpage ?? 10;

        $offset = ($page - 1) * $itemsPerPage;

        $seasonHistory = DB::table('team_season_info')
            ->select(
                'team_season_info.coach_id',
                'team_season_info.season_id',
                'team_season_info.team_id',

                // Team
                'teams.*',

                // Coach
                'coaches.name as coach_name',

                // Season
                'seasons.name as season_name',
                'seasons.status as season_status',

                // Team season info
                'team_season_info.is_playoff_qualified',
                'team_season_info.is_defending_champion',
                'team_season_info.chemistry',

                // Standings
                'standings_snapshots.team_name',
                'standings_snapshots.team_acronym',
                'standings_snapshots.conference_id',
                'standings_snapshots.conference_name',
                'standings_snapshots.wins',
                'standings_snapshots.losses',
                'standings_snapshots.total_home_score',
                'standings_snapshots.total_away_score',
                'standings_snapshots.home_ppg',
                'standings_snapshots.away_ppg',
                'standings_snapshots.score_difference',
                'standings_snapshots.overall_rank',
                'standings_snapshots.conference_rank',
                'standings_snapshots.primary_color',
                'standings_snapshots.secondary_color',

                // Won Semi Finals
                DB::raw("
                EXISTS (
                    SELECT 1
                    FROM playoff_series ps
                    WHERE ps.season_id = team_season_info.season_id
                    AND ps.round = 'semi_finals'
                    AND ps.winner_team_id = team_season_info.team_id
                ) as won_semi_finals
            "),

                // Won Finals
                DB::raw("
                EXISTS (
                    SELECT 1
                    FROM playoff_series ps
                    WHERE ps.season_id = team_season_info.season_id
                    AND ps.round = 'finals'
                    AND ps.winner_team_id = team_season_info.team_id
                ) as won_finals
            ")
            )

            ->leftJoin(
                'coaches',
                'coaches.id',
                '=',
                'team_season_info.coach_id'
            )

            ->join(
                'teams',
                'teams.id',
                '=',
                'team_season_info.team_id'
            )

            ->leftJoin(
                'seasons',
                'seasons.season_id',
                '=',
                'team_season_info.season_id'
            )

            ->leftJoin('standings_snapshots', function ($join) {
                $join->on(
                    'standings_snapshots.season_id',
                    '=',
                    'team_season_info.season_id'
                )->on(
                    'standings_snapshots.team_id',
                    '=',
                    'team_season_info.team_id'
                );
            })

            ->where(
                'team_season_info.coach_id',
                $coach_id
            )

            ->where(
                'seasons.status',
                17
            )

            ->orderBy(
                'team_season_info.season_id',
                'desc'
            )

            ->offset($offset)
            ->limit($itemsPerPage)
            ->get();


        // Get total number of records
        $totalItems = DB::table('team_season_info')
            ->join(
                'seasons',
                'seasons.season_id',
                '=',
                'team_season_info.season_id'
            )
            ->where(
                'team_season_info.coach_id',
                $coach_id
            )
            ->where(
                'seasons.status',
                17
            )
            ->count();


        $totalPages = ceil($totalItems / $itemsPerPage);


        return [
            'history' => $seasonHistory,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'shit' => true
        ];
    }
}
