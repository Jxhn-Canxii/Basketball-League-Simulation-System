<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Exception;
use Inertia\Inertia;
use App\Models\Seasons;
use App\Models\Teams;
use App\Models\Schedules;
use App\Models\Conference;
use App\Models\Player;
use App\Models\PlayerGameStats;
use App\Http\Controllers\AwardsController;
use App\Http\Controllers\PlayersController;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('Schedules/Index', [
            'status' => session('status'),
        ]);
    }
    public function list(Request $request)
    {
        // Fetch schedules with teams' data for the specified league
        $seasons = Seasons::where('league_id', $request->league_id)
            ->paginate(10); // Adjust per your pagination needs

        return response()->json($seasons);
    }

    public function createSeasonandSchedule(Request $request)
    {
        $request->validate([
            'season_name' => 'required|unique:seasons,name',
            'type' => 'required|in:1,2,3,4,5',
            'start' => 'required',
            'league_id' => 'required|exists:leagues,id',
            'match_type' => 'required|in:1,2',
        ]);

        // Check if the match type is valid for double round robin by conference
        if ($request->match_type != 1) {
            return response()->json([
                'message' => 'Invalid match type or season type for double round robin by conference.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create a new seasons

            $season = Seasons::create([
                'name' => $request->season_name,
                'type' => $request->type,
                'match_type' => $request->match_type,
                'start_playoffs' => $request->start,
                'league_id' => $request->league_id,
                'is_conference' => 1,
                'status' => config('timeline.start'), // Assuming default status is 'active'
            ]);

            // Create the double round robin schedule by conference
            if($request->match_type == 1){
                if($request->type == 1){
                    DB::rollBack();

                    return response()->json([
                        'message' => 'Single Elimination not available for this season type.',
                    ], 400);
                } elseif($request->type == 2){
                    $this->createSingleRoundRobinScheduleByConference($season->id, $request->league_id);
                } elseif ($request->type == 3) {
                    $this->createDoubleRoundRobinScheduleByConference($season->id, $request->league_id);
                } elseif ($request->type == 4) {
                    $this->createHybridRoundRobinScheduleByConference($season->id, $request->league_id);
                } elseif ($request->type == 5) {
                    $this->createHalfRoundRobinScheduleByConference($season->id, $request->league_id);
                } 
                
                else {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'Invalid match type.',
                    ], 400);
                }
            }else{
                DB::rollBack();

                return response()->json([
                    'message' => 'Match type not available for this season type.',
                ], 400);
            }

            // to make sure the teamseasoninfo is stored
            $this->storeTeamSeasonInfo(); 
            DB::commit();

            return response()->json([
                'message' => 'Created game schedule successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log the exception or handle it as needed
            return response()->json([
                'message' => 'Failed to create game schedule.',
                'error' => 'Error creating season and schedule: ' . $e->getMessage(),
                'season_id' => $request->season_name,
            ], 500);
        }
    }

    private function storeTeamSeasonInfo(){

        $latestSeasonId = get_current_season_id();
        
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
                    'season_id' => $latestSeasonId,
                    'conference_id' => $conferenceId, // Add conference_id here
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
    }

    ///per conference match schedule
    private function createDoubleRoundRobinScheduleByConference($seasonId, $leagueId)
    {
        DB::beginTransaction(); // Start transaction
        try {
            // Retrieve teams based on league_id
            $teams = Teams::where('league_id', $leagueId)->get();

            // Group teams by conference_id and shuffle each conference's teams
            $teamsByConference = $teams->groupBy('conference_id')->map(function ($conferenceTeams) {
                return $conferenceTeams->shuffle();
            });

            // Generate matches for each conference
            foreach ($teamsByConference as $conferenceId => $conferenceTeams) {
                $roundCounter = 0; // Initialize round counter
                $numTeams = count($conferenceTeams);
                $gameIdCounter = 1; // Initialize game ID counter
                $matches = [];

                // Generate matches for each round 1st leg
                for ($round = 0; $round < ($numTeams - 1); $round++) {
                    for ($i = 0; $i < $numTeams / 2; $i++) {
                        $homeIndex = ($round + $i) % ($numTeams - 1);
                        $awayIndex = ($numTeams - 1 - $i + $round) % ($numTeams - 1);

                        if ($i == 0) {
                            $awayIndex = $numTeams - 1;
                        }

                        $homeTeam = $conferenceTeams[$homeIndex];
                        $awayTeam = $conferenceTeams[$awayIndex];

                        // Ensure both teams are not null (bye team)
                        if ($homeTeam->id != $awayTeam->id) {
                            // First leg match
                            $gameId = $seasonId . '-' . ($roundCounter + 1) . '-' . $conferenceId . '-' . $gameIdCounter;
                            $matches[] = [
                                'season_id' => $seasonId,
                                'game_id' => $gameId,
                                'round' => $roundCounter + 1, // Continue round number
                                'conference_id' => $conferenceId,
                                'home_id' => $homeTeam->id,
                                'away_id' => $awayTeam->id,
                                'home_score' => 0, // Initialize with default score
                                'away_score' => 0, // Initialize with default score
                                'winner_id' => 0,
                            ];
                            $gameIdCounter++;
                        }
                    }
                    $roundCounter++; // Increment round number after each round
                }
              
                // Generate matches for each round 2nd leg
                for ($round = 0; $round < ($numTeams - 1); $round++) {
                    for ($i = 0; $i < $numTeams / 2; $i++) {
                        $homeIndex = ($round + $i) % ($numTeams - 1);
                        $awayIndex = ($numTeams - 1 - $i + $round) % ($numTeams - 1);

                        if ($i == 0) {
                            $awayIndex = $numTeams - 1;
                        }

                        $homeTeam = $conferenceTeams[$homeIndex];
                        $awayTeam = $conferenceTeams[$awayIndex];

                        // Ensure both teams are not null (bye team)
                        if ($homeTeam->id != $awayTeam->id) {
                            // First leg match
                            $gameId = $seasonId . '-' . ($roundCounter + 1) . '-' . $conferenceId . '-' . $gameIdCounter;
                            $matches[] = [
                                'season_id' => $seasonId,
                                'game_id' => $gameId,
                                'round' => $roundCounter + 1, // Continue round number
                                'conference_id' => $conferenceId,
                                'home_id' => $awayTeam->id,
                                'away_id' => $homeTeam->id,
                                'home_score' => 0, // Initialize with default score
                                'away_score' => 0, // Initialize with default score
                                'winner_id' => 0,
                            ];
                            $gameIdCounter++;
                        }
                    }
                    $roundCounter++; // Increment round number after each round
                }
                
                
                // Save matches to the database
                Schedules::insert($matches);
                
                
            }

            DB::commit(); // Commit transaction if all operations succeed
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback all changes on error
            // Log the error for debugging

            // Optionally, you can throw the exception again or return a custom error message
            throw $e;
        }
    }

    private function createSingleRoundRobinScheduleByConference($seasonId, $leagueId)
    {
        DB::beginTransaction(); // Start transaction
        try {
            // Retrieve teams based on league_id
            $teams = Teams::where('league_id', $leagueId)->get();

            // Group teams by conference_id and shuffle each conference's teams
            $teamsByConference = $teams->groupBy('conference_id')->map(function ($conferenceTeams) {
                return $conferenceTeams->shuffle();
            });

            // Generate matches for each conference
            foreach ($teamsByConference as $conferenceId => $conferenceTeams) {
                $roundCounter = 0; // Initialize round counter
                $numTeams = count($conferenceTeams);
                $gameIdCounter = 1; // Initialize game ID counter
                $matches = [];

                // Generate matches for each round 1st leg
                for ($round = 0; $round < ($numTeams - 1); $round++) {
                    for ($i = 0; $i < $numTeams / 2; $i++) {
                        $homeIndex = ($round + $i) % ($numTeams - 1);
                        $awayIndex = ($numTeams - 1 - $i + $round) % ($numTeams - 1);

                        if ($i == 0) {
                            $awayIndex = $numTeams - 1;
                        }

                        $homeTeam = $conferenceTeams[$homeIndex];
                        $awayTeam = $conferenceTeams[$awayIndex];

                        // Ensure both teams are not null (bye team)
                        if ($homeTeam->id != $awayTeam->id) {
                            // First leg match
                            $gameId = $seasonId . '-' . ($roundCounter + 1) . '-' . $conferenceId . '-' . $gameIdCounter;
                            $matches[] = [
                                'season_id' => $seasonId,
                                'game_id' => $gameId,
                                'round' => $roundCounter + 1, // Continue round number
                                'conference_id' => $conferenceId,
                                'home_id' => $homeTeam->id,
                                'away_id' => $awayTeam->id,
                                'home_score' => 0, // Initialize with default score
                                'away_score' => 0, // Initialize with default score
                                'winner_id' => 0,
                            ];
                            $gameIdCounter++;
                        }
                    }
                    $roundCounter++; // Increment round number after each round
                }
                
                // Save matches to the database
                Schedules::insert($matches);
            
            }

            DB::commit(); // Commit transaction if all operations succeed
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback all changes on error
            // Log the error for debugging

            // Optionally, you can throw the exception again or return a custom error message
            throw $e;
        }
    }

    private function createHalfRoundRobinScheduleByConference($seasonId, $leagueId)
    {
        DB::beginTransaction(); // Start DB transaction
        try {
            // Get all teams for the league
            $teams = Teams::where('league_id', $leagueId)->get();

            // Group teams by conference and shuffle each group
            $teamsByConference = $teams->groupBy('conference_id')->map(function ($conferenceTeams) {
                return $conferenceTeams->shuffle();
            });

            // Loop through each conference
            foreach ($teamsByConference as $conferenceId => $conferenceTeams) {
                $matches = [];

                $numTeams = count($conferenceTeams);
                $isOdd = $numTeams % 2 !== 0;
                $matchLimit = 5; // Half round robin match count per team

                // Track each team's match and bye counts
                $teamGameCounts = [];
                $teamByeRounds = [];
                foreach ($conferenceTeams as $team) {
                    $teamGameCounts[$team->id] = 0;
                    $teamByeRounds[$team->id] = [];
                }

                // Extract team IDs and add dummy team for odd count
                $conferenceTeamIds = $conferenceTeams->pluck('id')->toArray();
                if ($isOdd) {
                    $byeTeamId = -1; // Dummy ID for bye
                    $conferenceTeamIds[] = $byeTeamId;
                }

                // Generate all unique pairings
                $allMatchups = [];
                for ($i = 0; $i < count($conferenceTeamIds); $i++) {
                    for ($j = $i + 1; $j < count($conferenceTeamIds); $j++) {
                        $allMatchups[] = [$conferenceTeamIds[$i], $conferenceTeamIds[$j]];
                    }
                }

                shuffle($allMatchups); // Randomize matchups

                $round = 1;
                $gameIdCounter = 1;

                foreach ($allMatchups as [$teamA, $teamB]) {
                    // Skip if either team has hit the match limit (ignore bye)
                    if ($teamA !== -1 && $teamGameCounts[$teamA] >= $matchLimit) continue;
                    if ($teamB !== -1 && $teamGameCounts[$teamB] >= $matchLimit) continue;
                    if ($teamA === -1 && $teamB === -1) continue; // Invalid

                    // Increment game count
                    if ($teamA !== -1) $teamGameCounts[$teamA]++;
                    if ($teamB !== -1) $teamGameCounts[$teamB]++;

                    // Handle bye week logging
                    if ($teamA === -1 || $teamB === -1) {
                        $realTeam = $teamA === -1 ? $teamB : $teamA;
                        $teamByeRounds[$realTeam][] = $round;
                        $round++;
                        continue;
                    }

                    // Randomly assign home/away
                    if (rand(0, 1)) {
                        $home = $teamA;
                        $away = $teamB;
                    } else {
                        $home = $teamB;
                        $away = $teamA;
                    }

                    $gameId = $seasonId . '-' . $round . '-' . $conferenceId . '-' . $gameIdCounter;
                    $matches[] = [
                        'season_id' => $seasonId,
                        'game_id' => $gameId,
                        'round' => $round,
                        'conference_id' => $conferenceId,
                        'home_id' => $home,
                        'away_id' => $away,
                        'home_score' => 0,
                        'away_score' => 0,
                        'winner_id' => 0,
                    ];

                    $round++;
                    $gameIdCounter++;
                }

                // Insert all matches for this conference
                Schedules::insert($matches);

                // Log bye weeks for review
                foreach ($teamByeRounds as $teamId => $byeRounds) {
                    if (!empty($byeRounds)) {
                        \Log::info("Team ID {$teamId} has byes in rounds: " . implode(', ', $byeRounds));
                    }
                }
            }

            DB::commit(); // Commit all changes
        } catch (\Exception $e) {
            DB::rollBack(); // Roll back on error
            \Log::error("Failed to generate schedule: " . $e->getMessage());
            throw $e;
        }
    }

    // Hybrid Round Robin Schedule by Conference
    private function createHybridRoundRobinScheduleByConference($seasonId, $leagueId)
    {
        DB::beginTransaction();
        try {
            // Step 1: Get teams grouped by conference
            $teams = Teams::where('league_id', $leagueId)->get();
            $teamsByConference = $teams->groupBy('conference_id');
            $conferenceIds = $teamsByConference->keys();

            $numTeamsPerConference = array_map('count', $teamsByConference->toArray());
            $isUniformConference = count(array_unique($numTeamsPerConference)) === 1;
            
            // Initialize matches array
            $allMatches = [];
            $globalRoundCounter = 1;

            // Step 2: Intra-conference Double Round Robin
            foreach ($teamsByConference as $conferenceId => $conferenceTeams) {
                $teamList = $conferenceTeams->pluck('id')->toArray();
                $numTeams = count($teamList);
                for ($i = 0; $i < $numTeams; $i++) {
                    for ($j = $i + 1; $j < $numTeams; $j++) {
                        $home = $teamList[$i];
                        $away = $teamList[$j];

                        // First match (home vs away)
                        $allMatches[] = [
                            'season_id' => $seasonId,
                            'game_id' => $seasonId . '-' . $conferenceId . '-intra-' . $globalRoundCounter,
                            'round' => $globalRoundCounter++,
                            'conference_id' => $conferenceId,
                            'home_id' => $home,
                            'away_id' => $away,
                            'home_score' => 0,
                            'away_score' => 0,
                           'winner_id' => 0,
                        ];

                        // Second match (reverse home/away)
                        $allMatches[] = [
                            'season_id' => $seasonId,
                            'game_id' => $seasonId . '-' . $conferenceId . '-intra-' . $globalRoundCounter,
                            'round' => $globalRoundCounter++,
                            'conference_id' => $conferenceId,
                            'home_id' => $away,
                            'away_id' => $home,
                            'home_score' => 0,
                            'away_score' => 0,
                            'winner_id' => 0,
                        ];
                    }
                }
            }

            // Step 3: Inter-conference - 10 matches per team, single matchup per team
            $teamIdsByConf = [];
            foreach ($teamsByConference as $confId => $confTeams) {
                foreach ($confTeams as $team) {
                    $teamIdsByConf[$confId][] = $team->id;
                }
            }

            $interMatchesPerTeam = array_fill_keys($teams->pluck('id')->toArray(), 0);
            $usedPairs = [];

            // Generate Inter-conference matchups
            foreach ($teams as $team) {
                $teamId = $team->id;
                $teamConf = $team->conference_id;
                $opponentsNeeded = 10;

                // Get teams from other conferences
                $opponents = collect($teamIdsByConf)
                    ->except($teamConf)
                    ->flatten()
                    ->shuffle()
                    ->take($opponentsNeeded)
                    ->toArray();

                foreach ($opponents as $opponentId) {
                    $pairKey = min($teamId, $opponentId) . '-' . max($teamId, $opponentId);
                    if (isset($usedPairs[$pairKey])) continue;

                    $homeId = rand(0, 1) ? $teamId : $opponentId;
                    $awayId = $homeId === $teamId ? $opponentId : $teamId;

                    $allMatches[] = [
                        'season_id' => $seasonId,
                        'game_id' => $seasonId . '-inter-' . $globalRoundCounter,
                        'round' => $globalRoundCounter++,
                        'conference_id' => $teamConf, // Inter-conference matches have no conference
                        'home_id' => $homeId,
                        'away_id' => $awayId,
                        'home_score' => 0,
                        'away_score' => 0,
                        'winner_id' => 0,
                    ];

                    $interMatchesPerTeam[$teamId]++;
                    $interMatchesPerTeam[$opponentId]++;
                    $usedPairs[$pairKey] = true;
                }

                // If we couldn't generate 10 inter-conference matches, throw an exception
                if ($interMatchesPerTeam[$teamId] < $opponentsNeeded) {
                    throw new \Exception("Unable to find enough inter-conference opponents for team {$teamId}.");
                }
            }

            // Insert all matches into the database
            DB::table('schedules')->insert($allMatches);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //start playoff algo
    public static function playoffSchedule(Request $request)
    {
        // Retrieve inputs
        $seasonId = $request->season_id;
        $prev_round = $request->prev_round;
        $round = $request->round;
        $start = $request->start;
    
        // Check if all previous rounds are finished and if the current round exists
        $schedules = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->whereIn('round', [$prev_round, $round])  // Fetch previous round and current round in one query
            ->get(); // Retrieve all results at once

        // Check if any previous round has status other than 2 (not finished)
        $allPrevRoundsFinished = $schedules->where('round', $prev_round)
            ->every(fn($schedule) => $schedule->status == 2); // Ensures previous round status is 2

        // Check if the current round exists
        $currentRoundExists = $schedules->where('round', $round)->isNotEmpty(); // If the current round exists in the schedule

        if (!$allPrevRoundsFinished) {
            return response()->json([
                'message' => 'Current round schedule is ongoing. Cannot create schedule for next round.',
            ], 404); // 404 - Not Found
        }

        if ($currentRoundExists) {
            return response()->json([
                'message' => 'Round schedule already created',
            ], 404); // 404 - Not Found
        }
        // Retrieve the league_id from the seasons table
        $leagueId = DB::table('seasons')
            ->where('id', $seasonId)
            ->value('league_id');
    
        // Retrieve the number of conferences based on the league_id
        $conferenceCount = DB::table('conferences')
            ->where('league_id', $leagueId)
            ->count();
    
        // Ensure we only process if there are exactly 2 conferences
        if ($conferenceCount < 4) {
            return Self::playoffschedulebyrank($request);
        } else {
            return Self::playoffschedulebyconference($request);
        }
    }
    
    private static function playoffScheduleByRank($request)
    {
        // Retrieve inputs
        $seasonId = $request->season_id;
        $round = $request->round;
        $start = $request->start;

        // Update season champions and losers if needed
        if (($start == 16 && $round === 'round_of_16')) {
            self::updateSeasonChampionsAndLosers($seasonId);
        }

        // Retrieve the league_id from the seasons table
        $leagueId = DB::table('seasons')
            ->where('id', $seasonId)
            ->value('league_id');

        // Retrieve the number of conferences based on the league_id
        $conferenceCount = DB::table('conferences')
            ->where('league_id', $leagueId)
            ->count();


        // Initialize an array to collect all schedules
        $allSchedules = [];

        $topTeamsCount = $conferenceCount * 16 / $conferenceCount;

        // Get top teams from each conference
        $conferences = DB::table('conferences')
            ->where('league_id', $leagueId)
            ->pluck('id')
            ->toArray();

        if ($round == 'finals') {
            $pairings = self::generatePairingsOneConference($seasonId, 0, $round);
            $allSchedules = self::createSchedule($pairings, $seasonId, $round, 0);
        } else {
            //round of 32,round of 36 and quarter finals
            // Determine the number of top teams to select per conference

            foreach ($conferences as $conferenceId) {
                // Get the top 6 teams by overall rank for the conference, breaking ties as necessary
                $topTeamsByConferenceRank = DB::table('standings_view')
                    ->where('season_id', $seasonId)
                    ->where('conference_id', $conferenceId)
                    ->orderBy('conference_rank', 'asc') // Order by Conference rank
                    ->take(8) // Get exactly 8 teams, breaking ties with the additional criteria
                    ->pluck('team_id')
                    ->toArray();

                // Generate pairings for the round of 16 (or other rounds)
                $pairings = ($round == 'round_of_16') ? self::pairTeams($topTeamsByConferenceRank, 8) : self::generatePairingsOneConference($seasonId, $conferenceId, $round);

                // Create the playoff schedule for the specified round for the current conference
                $schedule = self::createSchedule($pairings, $seasonId, $round, $conferenceId);

                // Append the current conference's schedule to the allSchedules array
                $allSchedules = array_merge($allSchedules, $schedule);
            }
        }
        // Insert all playoff schedules into the database in a single batch
        try {
            self::insertSchedule($seasonId, $round, $allSchedules);
            // Update the season's status based on the round
            $status = self::roundStatusFormatter($round);
            DB::table('seasons')
                ->where('id', $seasonId)
                ->update(['status' => $status]);

            // If the schedule was inserted successfully, return a success response
            return response()->json(['success' => true, 'message' => 'Schedule inserted successfully']);
        } catch (Exception $e) {
            // If an exception occurred (due to duplicate schedule), return an error response
            return response()->json(['success' => false, 'error' => $e->getMessage(), 'line' => 475], 400);
        }
    }

    private static function playoffScheduleByConference($request)
    {
        // Retrieve inputs
        try{
            $seasonId = $request->season_id;
            $round = $request->round;
            $start = $request->start;

        
            // Update season champions and losers if needed
            if ($start == 16 && $round === 'play_ins_elims_round_1') {
                self::updateSeasonChampionsAndLosers($seasonId);
            }

            // Retrieve the league_id from the seasons table
            $leagueId = DB::table('seasons')
                ->where('id', $seasonId)
                ->value('league_id');

            // Retrieve the number of conferences based on the league_id
            $conferenceCount = DB::table('conferences')
                ->where('league_id', $leagueId)
                ->count();

            // Ensure we only process if there are exactly 2 conferences
            if ($conferenceCount != 2 && $conferenceCount != 4 && $conferenceCount != 8) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid number of conferences.',
                    'conference_count' => $conferenceCount,
                ], 400);
            }

            // Initialize an array to collect all schedules
            $allSchedules = [];

            $topTeamsCount = $conferenceCount * 16 / $conferenceCount;

            // Get top teams from each conference
            $conferences = DB::table('conferences')
                ->where('league_id', $leagueId)
                ->pluck('id')
                ->toArray();

            if ($round == 'play_ins_elims_round_1') {
                foreach ($conferences as $conferenceId) {
                    $playInTeams = DB::table('standings_view')
                        ->where('season_id', $seasonId)
                        ->where('conference_id', $conferenceId)
                        ->orderBy('conference_rank', 'asc') // Primary sort by conference_rank
                        ->orderBy('overall_rank', 'asc')   // Secondary sort to handle ties
                        ->limit(10)                        // Limit to the top 10 teams
                        ->get();                           // Retrieve the result for further filtering

                    // Filter to get only the 7th and 8th team after handling ties
                    $playInTeams = $playInTeams->filter(function ($team) {
                        return $team->conference_rank == 7 || $team->conference_rank == 8;
                    })->pluck('team_id')->toArray();


                    // Ensure there are at least four teams for play-ins
                    if (count($playInTeams) == 2) {
                        // **First Round**: 7th vs 8th seed
                        $pairing1 = self::pairTeams([$playInTeams[0], $playInTeams[1]], 2);

                        // Create the first round schedule

                        $scheduleFirstRound = self::createSchedule($pairing1, $seasonId, 'play_ins_elims_round_1', $conferenceId);

                        // Add to overall schedule
                        $allSchedules = array_merge($allSchedules, $scheduleFirstRound);
                    } else {
                        // Handle insufficient teams (e.g., log an error, skip this conference, etc.)

                    }
                }
            }
            if ($round == 'play_ins_elims_round_2') {
                foreach ($conferences as $conferenceId) {
                    $playInTeams = DB::table('standings_view')
                        ->where('season_id', $seasonId)
                        ->where('conference_id', $conferenceId)
                        ->orderBy('conference_rank', 'asc') // Primary sort by conference_rank
                        ->orderBy('overall_rank', 'asc')   // Secondary sort to handle ties
                        ->limit(10)                        // Limit to the top 10 teams
                        ->get();                           // Retrieve the result for further filtering

                    // Filter to get only the 9th and 10th team after handling ties
                    $playInTeams = $playInTeams->filter(function ($team) {
                        return $team->conference_rank == 9 || $team->conference_rank == 10;
                    })->pluck('team_id')->toArray();

                    // Ensure there are at least four teams for play-ins
                    if (count($playInTeams) == 2) {
                        // **First Round**: 7th vs 8th seed
                        $pairing1 = self::pairTeams([$playInTeams[0], $playInTeams[1]], 2);

                        // Create the first round schedule
                        $scheduleFirstRound = self::createSchedule($pairing1, $seasonId, 'play_ins_elims_round_2', $conferenceId);

                        // Add to overall schedule
                        $allSchedules = array_merge($allSchedules, $scheduleFirstRound);
                    } else {
                        // Handle insufficient teams (e.g., log an error, skip this conference, etc.)

                    }
                }
            } else if ($round == 'play_ins_finals') {
                // Get the results of the previous play-in rounds to determine the winners and losers
                foreach ($conferences as $conferenceId) {
                    // Get the results of the 7th vs 8th and 9th vs 10th games
                    // Fetch results for the first round (7th vs 8th)
                    $round1Results = DB::table('schedules')
                        ->where('season_id', $seasonId)
                        ->where('round', 'play_ins_elims_round_1')
                        ->where('conference_id', $conferenceId)
                        ->get();

                    // Fetch results for the second round (9th vs 10th)
                    $round2Results = DB::table('schedules')
                        ->where('season_id', $seasonId)
                        ->where('round', 'play_ins_elims_round_2')
                        ->where('conference_id', $conferenceId)
                        ->get();

                    // Ensure the results contain at least one game for each round
                    if ($round1Results->isNotEmpty() && $round2Results->isNotEmpty()) {
                        // Determine the winner and loser for the 7th vs 8th game
                        $round1Game = $round1Results->first(); // Assuming one game per round
                        if ($round1Game->home_score > $round1Game->away_score) {
                            $winner7vs8 = $round1Game->home_id;
                            $loser7vs8 = $round1Game->away_id;
                        } else {
                            $winner7vs8 = $round1Game->away_id;
                            $loser7vs8 = $round1Game->home_id;
                        }

                        // Determine the winner and loser for the 9th vs 10th game
                        $round2Game = $round2Results->first(); // Assuming one game per round
                        if ($round2Game->home_score > $round2Game->away_score) {
                            $winner9vs10 = $round2Game->home_id;
                            $loser9vs10 = $round2Game->away_id;
                        } else {
                            $winner9vs10 = $round2Game->away_id;
                            $loser9vs10 = $round2Game->home_id;
                        }

                        // Output or process the winners and losers as needed
                        // Example:

                    } else {
                        // Handle cases where there are no results

                    }


                    // **Play-In Finals**: The loser of 7th vs 8th faces the winner of 9th vs 10th
                    $playInFinalsTeams = self::pairTeams([$loser7vs8, $winner9vs10], 2);

                    // Create the schedule for the Play-In Finals
                    // self::pairTeams($topTeamsByOverallRank, 8)
                    $schedulePlayInFinals = self::createSchedule($playInFinalsTeams, $seasonId, 'play_ins_finals', $conferenceId);
                    $allSchedules = array_merge($allSchedules, $schedulePlayInFinals);
                }
            } else if ($round == 'interconference_semi_finals' || $round == 'finals') {
                $pairings = self::generatePairings16($seasonId, 0, $round);
                $allSchedules = self::createSchedule($pairings, $seasonId, $round, 0);
            } else {
                //round of 32,round of 36 and quarter finals
                // Determine the number of top teams to select per conference

                foreach ($conferences as $conferenceId) {
                    // Get the top 6 teams by overall rank for the conference, breaking ties as necessary
                    $conferenceTeams = DB::table('standings_view')
                        ->where('season_id', $seasonId)
                        ->where('conference_id', $conferenceId)
                        ->orderBy('overall_rank', 'asc') // Order by overall rank
                        ->take(6) // Get exactly 6 teams, breaking ties with the additional criteria
                        ->pluck('team_id')
                        ->toArray();

                    // If there are still fewer than 6 teams, ensure that we get the correct padding teams
                    $totalTeams = count($conferenceTeams);
                    if ($totalTeams < 6) {
                        // Handle the case where there are fewer than 6 teams by padding
                        $paddingNeeded = 6 - $totalTeams;
                        $paddingTeams = DB::table('standings_view')
                            ->where('season_id', $seasonId)
                            ->where('conference_id', $conferenceId)
                            ->whereNotIn('team_id', $conferenceTeams)
                            ->orderBy('overall_rank', 'asc')
                            ->take($paddingNeeded)
                            ->pluck('team_id')
                            ->toArray();

                        $conferenceTeams = array_merge($conferenceTeams, $paddingTeams);
                    }

                    // Ensure we only have 6 teams, in case of any unforeseen issues
                    $conferenceTeams = array_slice($conferenceTeams, 0, 6); // Take the first 6 teams

                    // Get the results of the Play-In Elimination Round 1 and Play-In Finals to determine the winners
                    $playInTeams = self::getPlayInEliminationTeams($seasonId, $conferenceId);

                    $winnerPlayInRound1 = $playInTeams['winner_of_7vs8']; // Winner of 7th vs 8th (Play-In Elimination Round 1)
                    $winnerPlayInFinals = $playInTeams['winner_of_9vs10']; // Winner of 9th vs 10th (Play-In Finals)

                    // Add the winners from the Play-In rounds into the 7th and 8th spots
                    $conferenceTeams[6] = $winnerPlayInRound1; // Place the Play-In Elimination Round 1 winner at 7th
                    $conferenceTeams[7] = $winnerPlayInFinals; // Place the Play-In Finals winner at 8th

                    // Step 4: Combine the top 6 teams with the play-in winners to form the full list of teams for the round
                    $topTeamsByOverallRank = $conferenceTeams; // Now this contains the 8 teams (6 top + 2 play-in winners)

                    // Generate pairings for the round of 16 (or other rounds)
                    $pairings = ($round == 'round_of_16') ? self::pairTeams($topTeamsByOverallRank, 8) : self::generatePairings16($seasonId, $conferenceId, $round);

                    // Create the playoff schedule for the specified round for the current conference
                    $schedule = self::createSchedule($pairings, $seasonId, $round, $conferenceId);

                    // Append the current conference's schedule to the allSchedules array
                    $allSchedules = array_merge($allSchedules, $schedule);
                }
            }
            // Insert all playoff schedules into the database in a single batch
            
            try {
                self::insertSchedule($seasonId, $round, $allSchedules);
                // Update the season's status based on the round
                $status = self::roundStatusFormatter($round);
                DB::table('seasons')
                    ->where('id', $seasonId)
                    ->update(['status' => $status]);

                // If the schedule was inserted successfully, return a success response
                return response()->json(['success' => true, 'message' => 'Schedule inserted successfully']);
            } catch (Exception $e) {
                // If an exception occurred (due to duplicate schedule), return an error response
                return response()->json(['success' => false, 'error' => $e->getMessage(), 'line' => 475], 400);
                
            }
        } catch (Exception $e) {
            // If an exception occurred (due to duplicate schedule), return an error response
            return response()->json(['success' => false, 'error' => $e->getMessage(), 'line' => 475], 400);
            
        }
    }

    private static function getPlayInEliminationTeams($seasonId, $conferenceId)
    {
        // Get the results of the Play-In Elimination Rounds and Finals
        $playInRound1Results = DB::table('schedules')
            ->select('winner_id')
            ->where('season_id', $seasonId)
            ->where('round', 'play_ins_elims_round_1')
            ->where('conference_id', $conferenceId)
            ->where('status', 2)
            ->first();
    
        $playInFinalsResults = DB::table('schedules')
            ->select('winner_id')
            ->where('season_id', $seasonId)
            ->where('round', 'play_ins_finals')
            ->where('conference_id', $conferenceId)
            ->where('status', 2)
            ->first();
    
        // Ensure there are results before accessing them
        if (!$playInRound1Results || !$playInFinalsResults) {
            return [
                'winner_of_7vs8' => null,
                'winner_of_9vs10' => null,
            ];
        }
    
        return [
            'winner_of_7vs8' => $playInRound1Results->winner_id,
            'winner_of_9vs10' => $playInFinalsResults->winner_id,
        ];
    }
    

    private static function updateSeasonChampionsAndLosers($seasonId)
    {
        // Retrieve the top and bottom teams from standings view
        $topTeam = DB::table('standings_view')
            ->where('season_id', $seasonId)
            ->orderBy('overall_rank') // Ascending order for the top team
            ->first();

        $bottomTeam = DB::table('standings_view')
            ->where('season_id', $seasonId)
            ->orderBy('overall_rank', 'desc') // Descending order for the bottom team
            ->first();


        // Check if top and bottom teams exist before updating
        if ($topTeam && $bottomTeam) {
            // Update the season's champion and weakest teams
            DB::table('seasons')
                ->where('id', $seasonId)
                ->update([
                    'champion_id' => $topTeam->team_id,
                    'champion_name' => $topTeam->team_name,
                    'weakest_id' => $bottomTeam->team_id,
                    'weakest_name' => $bottomTeam->team_name,
                ]);
        } else {
            // Handle case where top or bottom team is not found
            // You may log an error or handle it according to your application's logic
        }
    }

    private static function roundStatusFormatter($round)
    {

        switch ($round) {
            case 'round_of_32':
                return config('timeline.round_of_32');
                break;
            case 'play_ins_elims_round_1':
                return config('timeline.play_ins_elims_round_1');
                break;
            case 'play_ins_elims_round_2':
                return config('timeline.play_ins_elims_round_2');
                break;
            case 'play_ins_finals':
                return config('timeline.play_ins_finals');
                break;
            case 'round_of_16':
                return config('timeline.round_of_16');
                break;
            case 'quarter_finals':
                return config('timeline.quarter_finals');
                break;
            case 'semi_finals':
                return config('timeline.semi_finals');
            case 'interconference_semi_finals':
                return config('timeline.interconference_semi_finals');
                break;
            case 'finals':
                return config('timeline.finals');
                break;
            default:
                return 8;
                break;
        }
    }

    // Function to generate pairings for playoff matches based on the round
    private static function generatePairingsOneConference($seasonId, $conferenceId, $round)
    {
        // Initialize pairings array
        $pairings = [];

        // Generate pairings based on the round
        switch ($round) {
            case 'quarter_finals':
                // Pair the teams for quarter-finals
                $winners = self::getWinnersOfRound('round_of_16', $seasonId, $conferenceId);
                $pairings = self::pairTeams($winners, 4);
                break;
            case 'semi_finals':
                // Pair the winners of quarter-finals for semi-finals
                $winners = self::getWinnersOfRound('quarter_finals', $seasonId, $conferenceId);
                $pairings = self::pairTeams($winners, 2);
                break;
            case 'finals':
                // Pair the winners of semi-finals for finals
                $winners = self::getWinnersOfRound('semi_finals', $seasonId, $conferenceId);
                
                $pairings = self::pairTeams($winners, 2);
                break;
        }

        return $pairings;
    }

    private static function generatePairings16($seasonId, $conferenceId, $round)
    {
        // Initialize pairings array
        $pairings = [];

        // Generate pairings based on the round
        switch ($round) {
            case 'quarter_finals':
                // Pair the teams for quarter-finals
                $winners = self::getWinnersOfRound('round_of_16', $seasonId, $conferenceId);
                $pairings = self::pairTeams($winners, 4);
                break;
            case 'semi_finals':
                // Pair the winners of quarter-finals for semi-finals
                $winners = self::getWinnersOfRound('quarter_finals', $seasonId, $conferenceId);
                $pairings = self::pairTeams($winners, 2);
                break;
            case 'interconference_semi_finals':
                // Pair the winners of semi-finals for finals
                $winners = self::getWinnersOfRound('semi_finals', $seasonId, $conferenceId);

                $pairings = self::pairTeams($winners, 4);
                break;
            case 'finals':
                // Pair the winners of semi-finals for finals
                $winners = self::getWinnersOfRound('interconference_semi_finals', $seasonId, $conferenceId);
                $pairings = self::pairTeams($winners, 2);
                break;
        }

        return $pairings;
    }

    private static function getWinnersOfRoundV1($round, $seasonId, $conferenceId)
    {
        // Retrieve the winners of the specified round from the database
        $winners = false;
        if ($round != 'semi_finals') {
            $winners = DB::table('schedules')
                ->where('round', $round)
                ->where('conference_id', $conferenceId)
                ->where('season_id', $seasonId)
                ->get();
        } else {
            $winners = DB::table('schedules')
                ->where('round', $round)
                ->where('season_id', $seasonId)
                ->get();
        }

        $winningIds = [];

        // Iterate through the winners to determine the winning teams
        foreach ($winners as $game) {
            $winningIds[] = $game->winner_id;
        }

        return $winningIds;
    }

    private static function getWinnersOfRound($round, $seasonId, $conferenceId)
    {
        $winners = [];

        // Build base query depending on round
        $query = DB::table('schedules')
            ->where('round', $round)
            ->where('season_id', $seasonId);

        if (!in_array($round, ['semi_finals', 'inter_conference_semi_finals'])) {
            // Filter by conference only if not special round
            $query->where('conference_id', $conferenceId);
        }

        $winners = $query->pluck('winner_id')->toArray();

        // If the round is semi_finals or inter_conference_semi_finals, rank the winners by overall_rank
        if (in_array($round, ['semi_finals', 'inter_conference_semi_finals'])) {
            $winners = DB::table('standings_view')
                ->where('season_id', $seasonId)
                ->whereIn('team_id', $winners)
                ->orderBy('overall_rank', 'asc')
                ->pluck('team_id')
                ->toArray();
        }

        return $winners;
    }

    private static function pairTeams($teams, $pairCount)
    {
        // Generate pairings based on the teams array
        $pairings = [];
        for ($i = 0; $i < $pairCount / 2; $i++) {
            $pairings[] = [$teams[$i], $teams[$pairCount - $i - 1]];
        }

        return $pairings;
    }

    // Function to create schedule for a round of playoff matches
    private static function createSchedule($pairings, $seasonId, $round, $conferenceId)
    {

        $schedule = [];
        // dd($pairings);
        foreach ($pairings as $game_number => $pair) {

            if (!is_array($pair) || count($pair) < 2) {
                throw new \Exception("Each pairing must contain exactly two team IDs.");
            }
            // dd($game_number);
            // Create schedule entries for each pairing in the round
            $game_id = 'S' . $seasonId . '-R' . $round . '-G' . ($game_number + 1) . 'C-' . $conferenceId;
            $schedule[] = [
                'home_id' => $pair[0],
                'conference_id' => ($round == 'finals' || $round == 'interconference_semi_finals') ? 0 : $conferenceId,
                'game_id' => $game_id,
                'away_id' => $pair[1],
                'season_id' => $seasonId,
                'round' => $round,
                'home_score' => 0,
                'away_score' => 0,
                'winner_id' => 0,
                // Add more fields as needed, such as date and time
            ];
        }

        return $schedule;
    }

    private static function insertSchedule($season, $round, $schedule)
    {
        // Start a database transaction
        DB::transaction(function () use ($season, $round, $schedule) {
            // Filter out any matches with duplicate game_id values that already exist in the database
            $existingGameIds = DB::table('schedules')
                ->whereIn('game_id', array_column($schedule, 'game_id'))
                ->pluck('game_id')
                ->toArray();

            // If any game_ids already exist, throw an exception or return an error response
            if (!empty($existingGameIds)) {
                $existingGameIdsStr = implode(', ', $existingGameIds);
                return response()->json([
                    'success' => false, 
                    'message' => "Duplicate game_id(s) found: {$existingGameIdsStr}. No schedules were inserted.",
                ],500);
                // throw new \Exception("Duplicate game_id(s) found: {$existingGameIdsStr}. No schedules were inserted.");
            }

            // Insert schedule entries into the database
            DB::table('schedules')->insert($schedule);

            // Prepare player game stats entries
            $playerGameStats = [];
            foreach ($schedule as $match) {
                // Fetch players for home and away teams
                $homeTeamPlayers = Player::where('team_id', $match['home_id'])
                    ->where('is_active', 1)
                    ->get();

                $awayTeamPlayers = Player::where('team_id', $match['away_id'])
                    ->where('is_active', 1)
                    ->get();

                // Create player game stats entries for home team players
                foreach ($homeTeamPlayers as $player) {
                    $playerGameStats[] = [
                        'player_id' => $player->id,
                        'season_id' => $season,
                        'game_id' => $match['game_id'],
                        'team_id' => $match['home_id'],
                        'points' => 0,
                        'rebounds' => 0,
                        'assists' => 0,
                        'steals' => 0,
                        'blocks' => 0,
                        'turnovers' => 0,
                        'fouls' => 0,
                    ];
                }

                // Create player game stats entries for away team players
                foreach ($awayTeamPlayers as $player) {
                    $playerGameStats[] = [
                        'player_id' => $player->id,
                        'season_id' => $season,
                        'game_id' => $match['game_id'],
                        'team_id' => $match['away_id'],
                        'points' => 0,
                        'rebounds' => 0,
                        'assists' => 0,
                        'steals' => 0,
                        'blocks' => 0,
                        'turnovers' => 0,
                        'fouls' => 0,
                    ];
                }
            }

            // Insert player game stats entries into the database
            if (!empty($playerGameStats)) {
                DB::table('player_game_stats')->insert($playerGameStats);
            }
        });
    }
}
