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
use App\Http\Controllers\TeamStreakController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\ArchiveController;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    protected $archive;
    protected $helper;
    protected $streak;

    public function __construct()
    {

        $this->archive = new ArchiveController();
        $this->helper = new HelperController();
        $this->streak = new TeamStreakController();
    }
    //
    public function index()
    {
        return Inertia::render('Schedules/Index', [
            'status' => session('status'),
        ]);
    }

    public function getScheduleIds(Request $request)
    {
        // Validate the request data
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'round' => 'required',
        ]);

        $seasonId = $request->season_id;
        $round = $request->round;

        // Get the latest season status (assuming you store this status in the 'seasons' table)
        $latestSeasonStatus = $this->helper->seasonStatus($seasonId);

        // Get the number of rounds that are already simulated (status != 2)
        $simulatedRounds = $this->helper->simulatedRounds($seasonId);

        // Get the total number of rounds in the season
        $totalRounds = $this->helper->totalRounds($seasonId);

        // Retrieve schedule records for the given season and round
        $schedules = Schedules::where('season_id', $seasonId)
            ->where('round', $round)
            ->where('status', 1)
            ->orderBy('id')
            ->select('id', 'conference_id')
            ->get();

        // Check if half of the rounds are simulated
        $now = now();
        $currentHour = $now->hour;

        // // Check if the time is between 6 PM (18) and 6 AM (6)
        // $isTimeRestricted = ($currentHour >= 18 || $currentHour < 6);

        $tradeDeadlineThreshold = ($totalRounds / 2) - 2;

        $isTradeDeadline = $simulatedRounds >= $tradeDeadlineThreshold && $latestSeasonStatus == 1;

        if ($isTradeDeadline) {
            // Update the season status to indicate trade deadline
            DB::table('seasons')
                ->where('id', $seasonId)
                ->update(['status' => config('timeline.in_season_trade')]);

            $isTradeDeadline = false; // Reset after executing
        }

        // Group by conference_id
        $groupedByConference = $schedules->groupBy('conference_id');

        // Interleave results to alternate by conference
        $interleaved = [];
        $hasData = true;

        while ($hasData) {
            $hasData = false;

            foreach ($groupedByConference as $conferenceId => $games) {
                if (!$games->isEmpty()) {
                    $interleaved[] = $games->shift(); // Take the first available game
                    $hasData = true;
                }
            }
        }

        // Count distinct conferences in this round
        $conferenceCount = $groupedByConference->count();

        return response()->json([
            'schedule_ids' => $interleaved,
            'conference_count' => $conferenceCount,
            'is_trade_deadline' => $isTradeDeadline, // Add trade deadline info
            'simulated_rounds' => $simulatedRounds,
            'total_rounds' => $totalRounds,
            'status' => $latestSeasonStatus,
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
            'playoff_type' => 'required|in:1,2',
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
                    $this->createCustomRoundRobinScheduleByConference($nextSeasonid, $request->league_id, 5);
                    break;
                case 6:
                    $this->createCustomRoundRobinScheduleByConference($nextSeasonid, $request->league_id, 10);
                    break;
                case 7:
                    $this->createRoundRobinSchedule($nextSeasonid, $request->league_id);
                    break;
                case 1:
                    throw new \Exception('Single Elimination not available for this season type.');
                default:
                    throw new \Exception('Invalid season type.');
            }

            $totalRegularGames = DB::table('schedules')
                ->where('season_id', $nextSeasonid)
                ->where('conference_id', 1) // specify the conference
                ->whereRaw('round REGEXP "^[0-9]+$"')   // only numeric rounds
                ->count();


            $season = Seasons::create([
                'id' => $nextSeasonid,
                'name' => $request->season_name,
                'type' => $request->type,
                'start_playoffs' => $request->start,
                'league_id' => $request->league_id,
                'is_conference' => 1,
                'playoff_type' => $request->playoff_type,
                'status' => config('timeline.start'),
                'total_regular_games' => $totalRegularGames,
            ]);

            // Store team season info (make sure this throws exception if it fails)
            if ($nextSeasonid == 1) {
                $this->streak->insertTeamStreak();
            }

            $this->archive->storeTeamSeasonInfo();

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

    /// regular match schedule
    private function createRoundRobinSchedule($seasonId, $leagueId)
    {
        DB::beginTransaction(); // Start transaction
        try {
            // Retrieve teams based on league_id
            $teams = Teams::where('league_id', $leagueId)->get();

            // Generate matches for each conference
            foreach ($teams as $allTeams) {
                $roundCounter = 0; // Initialize round counter
                $numTeams = count($allTeams);
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

                        $homeTeam = $allTeams[$homeIndex];
                        $awayTeam = $allTeams[$awayIndex];

                        // Ensure both teams are not null (bye team)
                        if ($homeTeam->id != $awayTeam->id) {
                            $conferenceId = $homeTeam->conference_id;
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

    public function playOffSeriesResults(Request $request)
    {
        $seriesId = $request->series_id;
        $seasonId = $request->season_id;
        $currentSeasonId = get_current_season_id();

        // Get playoff series info with team names and colors
        $seriesInfo = DB::table('playoff_series as ps')
            ->leftJoin('teams as ht', 'ps.home_team_id', '=', 'ht.id')
            ->leftJoin('teams as at', 'ps.away_team_id', '=', 'at.id')
            ->select(
                'ps.*',
                'ht.name as home_team_name',
                'ht.primary_color as home_primary_color',
                'ht.secondary_color as home_secondary_color',
                'at.name as away_team_name',
                'at.primary_color as away_primary_color',
                'at.secondary_color as away_secondary_color'
            )
            ->where('ps.series_id', $seriesId)
            ->where('ps.season_id', $seasonId)
            ->first();

        // Fetch games in this series
        $playoffSchedule = DB::table('schedule_view')
            ->where('series_id', $seriesId)
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();

        $games = [];
        $teamIds = collect($playoffSchedule)->pluck('home_id')->merge(
            collect($playoffSchedule)->pluck('away_id')
        )->unique();

        // Standings source
        $standingsTable = ($seasonId == $currentSeasonId) ? 'standings_view' : 'standings_snapshots';
        $standingsData = DB::table($standingsTable)
            ->whereIn('team_id', $teamIds)
            ->where('season_id', $seasonId)
            ->get()
            ->keyBy('team_id');

        foreach ($playoffSchedule as $game) {
            $homeTeamName = optional($standingsData[$game->home_id])->name ?? DB::table('teams')->where('id', $game->home_id)->value('name');
            $awayTeamName = optional($standingsData[$game->away_id])->name ?? DB::table('teams')->where('id', $game->away_id)->value('name');

            $game_number = $game->game_number_formatted;
            $games[] = [
                'id' => $game->id,
                'game_id' => $game->game_id,
                'game_number' => $game_number,
                'home_team' => [
                    'id' => $game->home_id,
                    'name' => $homeTeamName,
                    'home_score' => $game->home_score,
                    'conference' => optional($standingsData[$game->home_id])->conference_name,
                    'conference_rank' => optional($standingsData[$game->home_id])->conference_rank,
                    'overall_rank' => optional($standingsData[$game->home_id])->overall_rank,
                    'primary_color' => optional($standingsData[$game->home_id])->primary_color ?? '000000',
                    'secondary_color' => optional($standingsData[$game->home_id])->secondary_color ?? '000000',
                ],
                'away_team' => [
                    'id' => $game->away_id,
                    'name' => $awayTeamName,
                    'away_score' => $game->away_score,
                    'conference' => optional($standingsData[$game->away_id])->conference_name,
                    'conference_rank' => optional($standingsData[$game->away_id])->conference_rank,
                    'overall_rank' => optional($standingsData[$game->away_id])->overall_rank,
                    'primary_color' => optional($standingsData[$game->away_id])->primary_color ?? '000000',
                    'secondary_color' => optional($standingsData[$game->away_id])->secondary_color ?? '000000',
                ],
                'winner' => $game->winner_id,
                'season_id' => $seasonId,
            ];
        }

        $statLeaders = DB::table('player_game_stats as pgs')
            ->join('players as p', 'pgs.player_id', '=', 'p.id')
            ->join('teams as t', 'pgs.team_id', '=', 't.id')
            ->leftJoin('drafts as d', 'p.draft_id', '=', 'd.id')
            ->leftJoin('teams as dt', 'p.drafted_team_id', '=', 'dt.id')
            ->select(
                'pgs.player_id',
                'p.name as player_name',
                'pgs.team_id',
                't.name as team_name',
                't.primary_color',
                't.secondary_color',
                DB::raw('AVG(points) as total_points'),
                DB::raw('AVG(rebounds) as total_rebounds'),
                DB::raw('AVG(assists) as total_assists'),
                DB::raw('AVG(steals) as total_steals'),
                DB::raw('AVG(blocks) as total_blocks'),
                DB::raw('AVG(eff) as total_eff'),
                DB::raw('AVG(turnovers) as total_turnovers'),
                'p.age',
                'p.position',
                'p.role',
                DB::raw('AVG(pgs.minutes) as total_minutes'),
                'p.draft_id',
                DB::raw("COALESCE(d.draft_status, 'Undrafted') as draft_status"),
                'dt.acronym as drafted_team_acro',

                DB::raw("CASE WHEN p.id = (SELECT finals_mvp_id FROM seasons WHERE seasons.finals_mvp_id = p.id LIMIT 1) THEN 1 ELSE 0 END as is_finals_mvp"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Best Defensive Player') THEN 1 ELSE 0 END) AS is_defensive_poy"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Sixth Man of the Year') THEN 1 ELSE 0 END) AS is_sixth_man"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Rookie of the Season') THEN 1 ELSE 0 END) AS is_rookie_poy"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Most Improved Player') THEN 1 ELSE 0 END) AS is_most_improved"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Best Overall Player') THEN 1 ELSE 0 END) AS is_season_mvp")
            )
            ->whereIn('pgs.game_id', collect($playoffSchedule)->pluck('game_id'))
            ->groupBy(
                'pgs.player_id',
                'p.name',
                'pgs.team_id',
                't.name',
                'p.age',
                'p.position',
                'p.role',
                'p.draft_id',
                'draft_status',
                'dt.acronym',
                'is_finals_mvp',
                'is_defensive_poy',
                'is_sixth_man',
                'is_rookie_poy',
                'is_most_improved',
                'is_season_mvp'
            )
            ->get();

        $seriesBestPlayer = [];
        $leaders = [];

        if ($statLeaders && $statLeaders->isNotEmpty()) {
            $leaders = [
                'points' => $statLeaders->sortByDesc('total_points')->first(),
                'assists' => $statLeaders->sortByDesc('total_assists')->first(),
                'rebounds' => $statLeaders->sortByDesc('total_rebounds')->first(),
                'steals' => $statLeaders->sortByDesc('total_steals')->first(),
                'blocks' => $statLeaders->sortByDesc('total_blocks')->first(),
                'eff' => $statLeaders->sortByDesc('total_eff')->first(),
            ];

            $best = $statLeaders->sortByDesc('total_eff')->first();

            $seriesBestPlayer = [
                'game_id'           => $playoffSchedule[0]->game_id ?? null,
                'name'              => $best->player_name ?? '',
                'team'              => $best->team_name ?? '',
                'age'               => $best->age ?? null,
                'position'          => $best->position ?? '',
                'points'            => (int) number_format($best->total_points, 2),
                'assists'           => (int) number_format($best->total_assists, 2),
                'rebounds'          => (int) number_format($best->total_rebounds, 2),
                'steals'            => (int) number_format($best->total_steals, 2),
                'blocks'            => (int) number_format($best->total_blocks, 2),
                'turnovers'         => (int) number_format($best->total_turnovers, 2),
                'fouls'             => null,
                'role'              => $best->role ?? '',
                'minutes'           => $best->total_minutes ?? 0,
                'draft_id'          => $best->draft_id ?? null,
                'draft_status'      => $best->draft_status ?? 'Undrafted',
                'drafted_team_acro' => $best->drafted_team_acro ?? '',
                'is_finals_mvp'     => $best->is_finals_mvp ?? 0,
                'is_season_mvp'     => $best->is_season_mvp ?? 0,
                'is_defensive_poy'  => $best->is_defensive_poy ?? 0,
                'is_rookie_poy'     => $best->is_rookie_poy ?? 0,
                'is_most_improved'  => $best->is_most_improved ?? 0,
                'secondary_color'   => $best->secondary_color ?? '000000',
                'primary_color'     => $best->primary_color ?? '000000',
            ];
        }

        // Last finished game in the series
        $lastFinishedGame = DB::table('schedule_view')
            ->where('series_id', $seriesId)
            ->where('status', 2) // finished
            ->orderBy('id', 'desc')
            ->first();

        $lastFinishedGameId = $lastFinishedGame->game_id ?? null;

        // Compute series lead text
        if ($seriesInfo->home_wins == $seriesInfo->away_wins) {
            $seriesLead = "Series Tied {$seriesInfo->home_wins}-{$seriesInfo->away_wins}";
        } else {
            $homeTeamName = $seriesInfo->home_team_name;
            $awayTeamName = $seriesInfo->away_team_name;

            $leaderName = $seriesInfo->home_wins > $seriesInfo->away_wins ? $homeTeamName : $awayTeamName;
            $leadWins = max($seriesInfo->home_wins, $seriesInfo->away_wins);
            $trailWins = min($seriesInfo->home_wins, $seriesInfo->away_wins);
            $seriesLead = "{$leaderName} Leads {$leadWins}-{$trailWins}";
        }

        return response()->json([
            'series_id' => $seriesId,
            'series_info' => $seriesInfo,
            'games' => $games,
            'player_stat_leaders' => $leaders,
            'series_best_player' => $seriesBestPlayer,
            'last_finished_game_id' => $lastFinishedGameId,
            'series_lead' => $seriesLead
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

    public function teamseasonschedules(Request $request)
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

        return response()->json([
            'schedules' => $games,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_count' => $totalSchedules,
        ]);
    }
}
