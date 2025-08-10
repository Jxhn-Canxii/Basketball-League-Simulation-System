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
            'type' => 'required|in:1,2,3,4,5,6',
            'start' => 'required',
            'league_id' => 'required|exists:leagues,id',
        ]);
        
        DB::beginTransaction();

        try {
            // Create season
            $nextSeasonid = get_current_season_id() + 1;

            // Create schedule based on type
            switch ((int)$request->type) {
                case 2:
                    $this->createSingleRoundRobinScheduleByConference($nextSeasonid, $request->league_id);
                    break;
                case 3:
                    $this->createDoubleRoundRobinScheduleByConference($nextSeasonid, $request->league_id);
                    break;
                case 4:
                    $this->createHybridRoundRobinScheduleByConference($nextSeasonid, $request->league_id);
                    break;
                case 5:
                    $this->createCustomRoundRobinScheduleByConference($nextSeasonid, $request->league_id,5);
                    break;
                case 6:
                    $this->createCustomRoundRobinScheduleByConference($nextSeasonid, $request->league_id,10);
                    break;
                case 1:
                    throw new \Exception('Single Elimination not available for this season type.');
                default:
                    throw new \Exception('Invalid season type.');
            }

            $season = Seasons::create([
                'id' => $nextSeasonid,
                'name' => $request->season_name,
                'type' => $request->type,
                'start_playoffs' => $request->start,
                'league_id' => $request->league_id,
                'is_conference' => 1,
                'status' => config('timeline.start'),
            ]);

            // Store team season info (make sure this throws exception if it fails)
            $this->storeTeamSeasonInfo();

            DB::commit();

            return response()->json([
                'message' => 'Created game schedule successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create game schedule.',
                'error' => 'Error creating season and schedule: ' . $e->getMessage(),
                'season_id' => $request->season_name,
            ], 500);
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

    private function createCustomRoundRobinScheduleByConference($seasonId, $leagueId, $roundLimit)
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

                // Generate matches, limiting to 10 rounds
                for ($round = 0; $round < ($numTeams - 1) && $roundCounter < $roundLimit; $round++) {
                    for ($i = 0; $i < $numTeams / 2; $i++) {
                        $homeIndex = ($round + $i) % ($numTeams - 1);
                        $awayIndex = ($numTeams - 1 - $i + $round) % ($numTeams - 1);

                        if ($i == 0) {
                            $awayIndex = $numTeams - 1;
                        }

                        $homeTeam = $conferenceTeams[$homeIndex];
                        $awayTeam = $conferenceTeams[$awayIndex];

                        if ($homeTeam->id != $awayTeam->id) {
                            $gameId = $seasonId . '-' . ($roundCounter + 1) . '-' . $conferenceId . '-' . $gameIdCounter;
                            $matches[] = [
                                'season_id' => $seasonId,
                                'game_id' => $gameId,
                                'round' => $roundCounter + 1,
                                'conference_id' => $conferenceId,
                                'home_id' => $homeTeam->id,
                                'away_id' => $awayTeam->id,
                                'home_score' => 0,
                                'away_score' => 0,
                                'winner_id' => 0,
                            ];
                            $gameIdCounter++;
                        }
                    }
                    $roundCounter++;
                }

                // Save matches to the database
                Schedules::insert($matches);
            }

            DB::commit(); // Commit transaction if all operations succeed
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback all changes on error
            throw $e;
        }
    }
    
    private function createHybridRoundRobinScheduleByConference($seasonId, $leagueId, $maxInterGames = 5)
    {
        DB::beginTransaction();
        try {
            $teams = Teams::where('league_id', $leagueId)->get();
            if ($teams->isEmpty()) {
                throw new \Exception("No teams found");
            }

            $teamsByConf = $teams->groupBy('conference_id');
            $conferenceMap = [];
            foreach ($teamsByConf as $confId => $group) {
                foreach ($group as $team) {
                    $conferenceMap[$team->id] = $confId;
                }
            }

            $teamIds = $teams->pluck('id')->toArray();
            $teamHomeGames = array_fill_keys($teamIds, 0);
            $teamInterGames = array_fill_keys($teamIds, 0);
            $teamInterOpponents = array_fill_keys($teamIds, []);
            $roundGames = [];

            $globalGameCounter = 1;
            $confGameCounters = [];

            // === STEP 1: Intra-conference round robin ===
            $totalRounds = 0;
            foreach ($teamsByConf as $confId => $group) {
                $ids = $group->pluck('id')->toArray();
                $numTeams = count($ids);
                $numRounds = $numTeams - 1;
                $half = $numTeams / 2;

                $rotating = $ids;
                array_shift($rotating);

                $confGameCounters[$confId] = 1;

                for ($round = 0; $round < $numRounds; $round++) {
                    for ($i = 0; $i < $half; $i++) {
                        $teamA = $i === 0 ? $ids[0] : $rotating[$i - 1];
                        $teamB = $rotating[$numRounds - 1 - $i];
                        if (rand(0, 1)) [$teamA, $teamB] = [$teamB, $teamA];

                        $roundGames[$round + 1][] = [
                            'season_id' => $seasonId,
                            'game_id' => '',
                            'conference_id' => $confId,
                            'home_id' => $teamA,
                            'away_id' => $teamB,
                            'home_score' => 0,
                            'away_score' => 0,
                            'winner_id' => 0,
                            'round' => $round + 1,
                            'type' => 'intra',
                        ];
                        $teamHomeGames[$teamA]++;
                    }

                    array_unshift($rotating, array_pop($rotating));
                }
                $totalRounds = max($totalRounds, $numRounds);
            }

            // === STEP 2: Inter-conference matchups with max per round enforcement ===
            $interPairs = [];
            for ($i = 0; $i < count($teamIds); $i++) {
                for ($j = $i + 1; $j < count($teamIds); $j++) {
                    $a = $teamIds[$i];
                    $b = $teamIds[$j];
                    if ($conferenceMap[$a] !== $conferenceMap[$b]) {
                        $interPairs[] = [$a, $b];
                    }
                }
            }

            shuffle($interPairs);
            $roundPointer = 1;
            $totalInterGames = (count($teamIds) * $maxInterGames) / 2;
            $maxInterPerRound = ceil($totalInterGames / $totalRounds);
            $interGamesPerRound = array_fill(1, $totalRounds, 0);

            foreach ($interPairs as [$a, $b]) {
                if (
                    $teamInterGames[$a] >= $maxInterGames ||
                    $teamInterGames[$b] >= $maxInterGames ||
                    in_array($b, $teamInterOpponents[$a]) ||
                    in_array($a, $teamInterOpponents[$b])
                ) continue;

                $placed = false;
                $startRound = $roundPointer;

                do {
                    $games = $roundGames[$roundPointer] ?? [];
                    $roundTeamIds = array_merge(array_column($games, 'home_id'), array_column($games, 'away_id'));

                    if (!in_array($a, $roundTeamIds) && !in_array($b, $roundTeamIds) && $interGamesPerRound[$roundPointer] < $maxInterPerRound) {
                        $home = $teamHomeGames[$a] <= $teamHomeGames[$b] ? $a : $b;
                        $away = $home === $a ? $b : $a;

                        $confId = $conferenceMap[$home];
                        if (!isset($confGameCounters[$confId])) {
                            $confGameCounters[$confId] = 1;
                        }

                        $roundGames[$roundPointer][] = [
                            'season_id' => $seasonId,
                            'game_id' => '',
                            'conference_id' => $confId,
                            'home_id' => $home,
                            'away_id' => $away,
                            'home_score' => 0,
                            'away_score' => 0,
                            'winner_id' => 0,
                            'round' => $roundPointer,
                            'type' => 'inter',
                        ];

                        $teamHomeGames[$home]++;
                        $teamInterGames[$a]++;
                        $teamInterGames[$b]++;
                        $teamInterOpponents[$a][] = $b;
                        $teamInterOpponents[$b][] = $a;
                        $interGamesPerRound[$roundPointer]++;
                        $placed = true;
                    }

                    $roundPointer = ($roundPointer % $totalRounds) + 1;
                } while (!$placed && $roundPointer !== $startRound);
            }

            // === STEP 3: Generate game_id + Final insert ===
            $finalMatches = [];
            foreach (range(1, $totalRounds) as $round) {
                $games = $roundGames[$round] ?? [];
                foreach ($games as &$game) {
                    $type = $game['type'] ?? 'intra';
                    $confId = $game['conference_id'];
                    $game['game_id'] = "S{$seasonId}-C{$confId}-{$type}-G{$confGameCounters[$confId]}-GLOB{$globalGameCounter}";
                    $confGameCounters[$confId]++;
                    $globalGameCounter++;
                    $finalMatches[] = $game;
                }
            }

            if (empty($finalMatches)) {
                throw new \Exception("No games scheduled.");
            }

            foreach (array_chunk($finalMatches, 100) as $chunk) {
                DB::table('schedules')->insert($chunk);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Schedule generated with balanced inter and intra rounds',
                'total_rounds' => $totalRounds,
                'total_matches' => count($finalMatches),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Schedule failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Schedule failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function storeTeamSeasonInfo()
    {
        $latestSeasonId = get_current_season_id();
        $previousSeasonId = $latestSeasonId - 1;

        $teamsCoach = DB::table('teams')->get();

        // Get the previous season's champion team_id
        $prevChampion = DB::table('seasons')
            ->where('id', $previousSeasonId)
            ->value('finals_winner_id');

        foreach ($teamsCoach as $team) {
            try {
                // Ensure necessary fields are present
                if (!$team->id || !$latestSeasonId) {
                    throw new \Exception("Missing team ID or season ID.");
                }

                $coach = DB::table('coaches')->where('id', $team->coach_id)->first();
                $coachIq = $coach ? $coach->coach_iq : 0;
                $chemistry = 75;
                $conferenceId = $team->conference_id ?? 0;

                $isDefendingChampion = $team->id == $prevChampion ? 1 : 0;
                // Perform the insert/update
                DB::table('team_season_info')->updateOrInsert(
                    [
                        'team_id' => $team->id,
                        'season_id' => $latestSeasonId,
                    ],
                    [
                        'coach_id' => $team->coach_id,
                        'coach_iq' => $coachIq,
                        'chemistry' => $chemistry,
                        'conference_id' => $conferenceId,
                        'is_defending_champion' => $isDefendingChampion,
                        'updated_at' => now(),
                    ]
                );
            } catch (\Exception $e) {
                // Optional: log individual team errors
                throw new \Exception("Failed to store team season info for team ID {$team->id}: " . $e->getMessage());
            }
        }
    }

}
