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
use Carbon\Carbon;

class PlayoffController extends Controller
{
    
    public function seasonsplayoffs(Request $request)
    {
        $seasonId = $request->season_id;
        $status   = $request->status;
        $start    = $request->start;
        $type     = $request->type; // 1 = finished playoffs, 2 = single update

        $playoffs   = $this->playoffTree($seasonId, $status, $type, $start);
        $roundOrder = $this->getRoundOrder($start, $type, $status);

        return response()->json([
            'playoffs'    => $playoffs,
            'round_order' => $roundOrder,
        ]);
    }

    public function seasonsPlayoffsSeries(Request $request)
    {
        $seasonId = $request->season_id;
        $status   = $request->status;
        $start    = $request->start;
        $type     = $request->type; // 1 = finished playoffs, 2 = single update

        $playoffs   = $this->playoffTreeSeries($seasonId, $status, $type, $start);
        $games   = $this->playoffSeriesGameTree($seasonId, $status, $type, $start);
        $roundOrder = $this->getRoundOrder($start, $type, $status);

        return response()->json([
            'playoffs'=> $playoffs,
            'games'=> $games,
            'round_order' => $roundOrder,
        ]);
    }

    private function getRoundOrder($start, $type)
    {
        $formats = config('playoff_formats');

        foreach ($formats as $format) {
            if ($format['start'] == $start && $format['type'] == $type) {
                return array_values(array_unique($format['round_sequence']));
            }
        }

        return [];
    }

    private function playoffTreeSeries($seasonId, $status, $type, $start, $formatKey = 'single_elim_16_with_playins')
    {
        $currentSeasonId = get_current_season_id();
        $status = $status >= 8 ? 8 : $status;

        // Load formats from config
        $formats = config('playoff_formats');

        // Auto-pick format if no key is given
        if (!$formatKey) {
            foreach ($formats as $key => $format) {
                if ($format['type'] == $type && $format['start'] == $start) {
                    $formatKey = $key;
                    break;
                }
            }
        }

        if (!isset($formats[$formatKey])) {
            throw new \Exception("Invalid playoff format: {$formatKey}");
        }

        $format = $formats[$formatKey];

        // Build $roundIndices dynamically if using round_sequence
        if (isset($format['round_sequence'])) {
            $roundIndices = self::buildRoundsFromSequence($format['round_sequence']);
        }

        $currentRounds = $roundIndices[$status] ?? [];

        // Fetch data and build tree
        $tree = [];
        foreach ($currentRounds as $round) {
            $tree[$round] = [];
            $playoffSeries = DB::table('playoff_series')
                ->select(
                    'playoff_series.id',
                    'playoff_series.series_id',
                    'playoff_series.season_id',
                    'conferences.name as conference',
                    'playoff_series.round',
                    'playoff_series.home_team_id',
                    'playoff_series.away_team_id',
                    'playoff_series.series_length as best_of',
                    'playoff_series.home_wins',
                    'playoff_series.away_wins',
                    DB::raw('CASE WHEN playoff_series.status = 2 THEN 1 ELSE 0 END as completed'),
                    'playoff_series.winner_team_id',
                    'playoff_series.loser_team_id',
                    'playoff_series.created_at',
                    'playoff_series.updated_at'
                )
                ->leftJoin('conferences', 'playoff_series.conference_id', '=', 'conferences.id')
                ->where('playoff_series.season_id', $seasonId)
                ->where('playoff_series.round', $round)
                ->orderBy('playoff_series.round', 'asc')
                ->get();

            $teamIds = $playoffSeries->pluck('home_team_id')->merge($playoffSeries->pluck('away_team_id'))->unique();
            $standingsTable = ($seasonId == $currentSeasonId) ? 'standings_view' : 'standings_snapshots';
            $standingsData = DB::table($standingsTable)
                ->whereIn('team_id', $teamIds)
                ->where('season_id', $seasonId)
                ->get()
                ->keyBy('team_id');

            foreach ($playoffSeries as $series) {
                $homeTeamName = $standingsData[$series->home_team_id]->name ?? DB::table('teams')->where('id', $series->home_team_id)->value('name');
                $awayTeamName = $standingsData[$series->away_team_id]->name ?? DB::table('teams')->where('id', $series->away_team_id)->value('name');

                // Determine series lead or result
                $seriesLead = '';
                if ($series->completed) {
                    $winnerName = $series->winner_team_id == $series->home_team_id ? $homeTeamName : $awayTeamName;
                    $seriesLead = "{$winnerName} Wins {$series->home_wins}-{$series->away_wins}";
                } else {
                    if ($series->home_wins == $series->away_wins) {
                        $seriesLead = "Series Tied {$series->home_wins}-{$series->away_wins}";
                    } else {
                        $leaderName = $series->home_wins > $series->away_wins ? $homeTeamName : $awayTeamName;
                        $leadWins = max($series->home_wins, $series->away_wins);
                        $trailWins = min($series->home_wins, $series->away_wins);
                        $seriesLead = "{$leaderName} Leads {$leadWins}-{$trailWins}";
                    }
                }

                $tree[$round][] = [
                    'id' => $series->id,
                    'series_id' => $series->series_id,
                    'season_id' => $series->season_id,
                    'conference' => $series->conference ?? 'Interconference',
                    'round' => $series->round,
                    'best_of' => $series->best_of,
                    'home_team' => [
                        'id' => $series->home_team_id,
                        'name' => $homeTeamName,
                        'wins' => $series->home_wins,
                        'conference' => $standingsData[$series->home_team_id]->conference_name ?? null,
                        'conference_rank' => $standingsData[$series->home_team_id]->conference_rank ?? null,
                        'overall_rank' => $standingsData[$series->home_team_id]->overall_rank ?? null,
                        'primary_color' => $standingsData[$series->home_team_id]->primary_color ?? '00000',
                        'secondary_color' => $standingsData[$series->home_team_id]->secondary_color ?? '00000',
                    ],
                    'away_team' => [
                        'id' => $series->away_team_id,
                        'name' => $awayTeamName,
                        'wins' => $series->away_wins,
                        'conference' => $standingsData[$series->away_team_id]->conference_name ?? null,
                        'conference_rank' => $standingsData[$series->away_team_id]->conference_rank ?? null,
                        'overall_rank' => $standingsData[$series->away_team_id]->overall_rank ?? null,
                        'primary_color' => $standingsData[$series->away_team_id]->primary_color ?? '00000',
                        'secondary_color' => $standingsData[$series->away_team_id]->secondary_color ?? '00000',
                    ],
                    'series_lead' => $seriesLead,
                    'completed' => $series->completed,
                    'winner_id' => $series->winner_team_id,
                    'loser_id' => $series->loser_team_id,
                    'created_at' => $series->created_at,
                    'updated_at' => $series->updated_at,
                ];
            }
        }

        return $tree;
    }

    private function playoffSeriesGameTree($seasonId, $status, $type, $start, $formatKey = 'single_elim_16_with_playins')
    {
        $currentSeasonId = get_current_season_id();
        $status = $status >= 8 ? 8 : $status;

        // Load formats from config
        $formats = config('playoff_formats');

        // Auto-pick format if no key is given
        if (!$formatKey) {
            foreach ($formats as $key => $format) {
                if ($format['type'] == $type && $format['start'] == $start) {
                    $formatKey = $key;
                    break;
                }
            }
        }

        if (!isset($formats[$formatKey])) {
            throw new \Exception("Invalid playoff format: {$formatKey}");
        }

        $format = $formats[$formatKey];

        // Build $roundIndices dynamically if using round_sequence
        if (isset($format['round_sequence'])) {
            $roundIndices = self::buildRoundsFromSequence($format['round_sequence']);
        }

        $currentRounds = $roundIndices[$status] ?? [];

        $tree = [];
        foreach ($currentRounds as $round) {
            $tree[$round] = [];

            $playoffSchedule = DB::table('schedules')
                ->select('game_id', 'home_id', 'away_id', 'home_score', 'away_score', 'round', 'id', 'winner_id')
                ->where('season_id', $seasonId)
                ->where('round', $round)
                ->where('status', 1)
                ->orderBy('round', 'asc')
                ->get();

            if ($playoffSchedule->isEmpty()) {
                continue; // Skip if no games for this round
            }

            $teamIds = $playoffSchedule->pluck('home_id')->merge($playoffSchedule->pluck('away_id'))->unique();
            $standingsTable = ($seasonId == $currentSeasonId) ? 'standings_view' : 'standings_snapshots';
            $standingsData = DB::table($standingsTable)
                ->whereIn('team_id', $teamIds)
                ->where('season_id', $seasonId)
                ->get()
                ->keyBy('team_id');

            foreach ($playoffSchedule as $game) {
                $homeTeamName = $standingsData[$game->home_id]->name ?? DB::table('teams')->where('id', $game->home_id)->value('name');
                $awayTeamName = $standingsData[$game->away_id]->name ?? DB::table('teams')->where('id', $game->away_id)->value('name');

                $tree[$round][] = [
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
        }

        // Remove empty rounds
        $tree = array_filter($tree, function ($games) {
            return !empty($games);
        });

        return $tree;
    }

    private function playoffTree($seasonId, $status, $type, $start, $formatKey = 'single_elim_16_with_playins')
    {
        $currentSeasonId = get_current_season_id();
        $status = $status >= 8 ? 8 : $status;

        // Load formats from config
        $formats = config('playoff_formats');

        // Auto-pick format if no key is given
        if (!$formatKey) {
            foreach ($formats as $key => $format) {
                if ($format['type'] == $type && $format['start'] == $start) {
                    $formatKey = $key;
                    break;
                }
            }
        }

        if (!isset($formats[$formatKey])) {
            throw new \Exception("Invalid playoff format: {$formatKey}");
        }

        $format = $formats[$formatKey];

        // NEW: Build $roundIndices dynamically if using round_sequence
        if (isset($format['round_sequence'])) {
            $roundIndices = self::buildRoundsFromSequence($format['round_sequence']);
        }

        $currentRounds = $roundIndices[$status] ?? [];

        // dd($format['round_sequence']);
        // Fetch data and build tree
        $tree = [];
        foreach ($currentRounds as $round) {
            $tree[$round] = [];
            $playoffSchedule = DB::table('schedules')
                ->select('game_id', 'home_id', 'away_id', 'home_score', 'away_score', 'round', 'id', 'winner_id')
                ->where('season_id', $seasonId)
                ->where('round', $round)
                ->orderBy('round', 'asc')
                ->get();

            $teamIds = $playoffSchedule->pluck('home_id')->merge($playoffSchedule->pluck('away_id'))->unique();
            $standingsTable = ($seasonId == $currentSeasonId) ? 'standings_view' : 'standings_snapshots';
            $standingsData = DB::table($standingsTable)
                ->whereIn('team_id', $teamIds)
                ->where('season_id', $seasonId)
                ->get()
                ->keyBy('team_id');

            foreach ($playoffSchedule as $game) {
                $homeTeamName = $standingsData[$game->home_id]->name ?? DB::table('teams')->where('id', $game->home_id)->value('name');
                $awayTeamName = $standingsData[$game->away_id]->name ?? DB::table('teams')->where('id', $game->away_id)->value('name');

                $tree[$round][] = [
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
        }

        return $tree;
    }

    private static function buildRoundsFromSequence(array $sequence)
    {
        $rounds = [];
        $totalStatuses = count($sequence);

        for ($status = 1; $status <= $totalStatuses; $status++) {
            $rounds[$status] = array_slice($sequence, 0, $status);
        }

        return $rounds;
    }

    //start playoff algo
    public static function playoffSchedule(Request $request)
    {
        try {
            // Retrieve inputs
            $seasonId = $request->season_id;
            $prev_round = $request->prev_round;
            $round = $request->round;
            $start = $request->start;

            // playoff types
            // 1 = by overall rank non series;
            // 2 = by conference rank by series;
            // 3 = by conference rank best of 3;
            $playoff_type = $request->playoff_type;

            $schedules = DB::table('schedules')
                ->where('season_id', $seasonId)
                ->whereIn('round', [$prev_round, $round]) // Fetch previous round + current round in one query
                ->get();

            $series = DB::table('playoff_series')
                ->where('season_id', $seasonId)
                ->whereIn('round', [$prev_round, $round]) // Fetch previous round + current round in one query
                ->get();
            
            $allPrevRoundsSeriesFinished = $series->where('round', $prev_round)
                ->every(fn($schedule) => $schedule->status == 2);

            // Check if the current round already exists
            $currentRoundExists = $schedules->where('round', $round)->isNotEmpty();

            if (!$allPrevRoundsSeriesFinished) {
                return response()->json([
                    'message' => 'Current round series schedule is ongoing. Cannot create series schedule for next round.',
                ],400);
            }

            if ($currentRoundExists) {
                return response()->json([
                    'message' => 'Round schedule already created',
                ],200);
            }

            // Retrieve the league_id from the seasons table
            $leagueId = DB::table('seasons')
                ->where('id', $seasonId)
                ->value('league_id');

            // Retrieve the number of conferences based on the league_id
            $conferenceCount = DB::table('conferences')
                ->where('league_id', $leagueId)
                ->count();

            // Logic based on playoff type
            if ($playoff_type == 1) {
                if ($conferenceCount < 4) {
                    return self::playoffschedulebyrank($request);
                } else {
                    return self::playoffschedulebyconference($request);
                }
            } else {
                return self::playoffSeriesScheduleByConference($request);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while generating the playoff schedule.',
                'details' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : [],
            ], 500);
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

            self::updateSeasonPlayoffRound($seasonId,$round);
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
                    $scheduleFirstRound = self::getPlayInSchedule($seasonId, $conferenceId, [7, 8], 2, $round);
                    $allSchedules = array_merge($allSchedules, $scheduleFirstRound);
                }
            }
            
            else if ($round == 'play_ins_elims_round_2') {
                foreach ($conferences as $conferenceId) {
                    $scheduleFirstRound = self::getPlayInSchedule($seasonId, $conferenceId, [7, 8], 2, $round);
                    $allSchedules = array_merge($allSchedules, $scheduleFirstRound);
                }
            }

            else if ($round == 'play_ins_finals') {
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
            } 
            
            else if ($round == 'interconference_semi_finals' || $round == 'finals') {
                $pairings = self::generatePairings16($seasonId, 0, $round);
                $allSchedules = self::createSchedule($pairings, $seasonId, $round, 0);
            } 
            else {
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

                self::updateSeasonPlayoffRound($seasonId,$round);
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

    private static function playoffSeriesScheduleByConference($request)
    {
        try {
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

            // Ensure we only process if there are exactly 2, 4, or 8 conferences
            if (!in_array($conferenceCount, [2, 4, 8])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid number of conferences.',
                    'conference_count' => $conferenceCount,
                ], 400);
            }

            // Initialize arrays to collect all schedules and series
            $allSchedules = [];
            $allSeries = [];

            // Load playoff series configuration
            $playoffConfig = config('playoff_series.rounds');

            // Get series length for the given round
            $seriesLength = $playoffConfig[$round]['series_length'] ?? 3;
            
            // Get conferences
            $conferences = DB::table('conferences')
                ->where('league_id', $leagueId)
                ->pluck('id')
                ->toArray();

            if ($round == 'play_ins_elims_round_1') {
                foreach ($conferences as $conferenceId) {
                    $scheduleFirstRound = self::getPlayInSchedule($seasonId, $conferenceId, [7, 8], 2, $round);
                    
                    list($seriesData, $scheduleData) = self::createPlayInSeriesAndSchedule(
                        $scheduleFirstRound,
                        $seasonId,
                        $round,
                        $conferenceId
                    );
                    
                    $allSeries = array_merge($allSeries, $seriesData);
                    $allSchedules = array_merge($allSchedules, $scheduleData);
                }
            } 
            else if ($round == 'play_ins_elims_round_2') {
                foreach ($conferences as $conferenceId) {
                    $scheduleFirstRound = self::getPlayInSchedule($seasonId, $conferenceId, [9, 10], 2, $round);
                    
                    list($seriesData, $scheduleData) = self::createPlayInSeriesAndSchedule(
                        $scheduleFirstRound,
                        $seasonId,
                        $round,
                        $conferenceId
                    );
                    
                    $allSeries = array_merge($allSeries, $seriesData);
                    $allSchedules = array_merge($allSchedules, $scheduleData);
                }
            } 
            else if ($round == 'play_ins_finals') {
                $previousRounds = [
                    'first' => 'play_ins_elims_round_1',
                    'second' => 'play_ins_elims_round_2'
                ];

                foreach ($conferences as $conferenceId) {
                    $scheduleFinalRound = self::handlePlayInFinals(
                        $seasonId,
                        $conferenceId,
                        $previousRounds,
                        $round
                    );
                    list($seriesData, $scheduleData) = self::createPlayInSeriesAndSchedule(
                        $scheduleFinalRound,
                        $seasonId,
                        $round,
                        $conferenceId
                    );
                    $allSeries = array_merge($allSeries, $seriesData);
                    $allSchedules = array_merge($allSchedules, $scheduleData);
                }
            } 
            else if ($round == 'interconference_semi_finals' || $round == 'finals') {
            
                $pairings = self::generateSeriesPairings16($seasonId, $conferenceId, $round);
                list($seriesData, $scheduleData) = self::createSeriesAndSchedule(
                    $pairings, 
                    $seasonId, 
                    $round, 
                    0, 
                    $seriesLength
                );

                $allSeries = array_merge($allSeries, $seriesData);
                $allSchedules = array_merge($allSchedules, $scheduleData);
            }
            else if ($round == 'round_of_16') {
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

                   
                    // Generate pairings
                    $pairings = self::pairSeriesTeams($topTeamsByOverallRank, 8);

                    //  dd($pairings );
                    // Create series and schedule for the round
                    list($seriesData, $scheduleData) = self::createSeriesAndSchedule(
                        $pairings, 
                        $seasonId, 
                        $round, 
                        $conferenceId, 
                        $seriesLength
                    );

                    $allSeries = array_merge($allSeries, $seriesData);
                    $allSchedules = array_merge($allSchedules, $scheduleData);
                }
            }
            else {
                foreach ($conferences as $conferenceId) {
                    
                    // Generate pairings
                    $pairings = self::generateSeriesPairings16($seasonId, $conferenceId, $round);

                    // Create series and schedule for the round
                    list($seriesData, $scheduleData) = self::createSeriesAndSchedule(
                        $pairings, 
                        $seasonId, 
                        $round, 
                        $conferenceId, 
                        $seriesLength
                    );

                    $allSeries = array_merge($allSeries, $seriesData);
                    $allSchedules = array_merge($allSchedules, $scheduleData);
                }
            }

            // Insert series and schedules in a transaction
            try {
                DB::transaction(function () use ($seasonId, $round, $allSeries, $allSchedules) {
                    if (!empty($allSeries)) {
                        self::insertPlayoffSeries($seasonId, $round, $allSeries);
                    }
                    if (!empty($allSchedules)) {
                        self::insertSeriesSchedule($seasonId, $round, $allSchedules);
                    }

                    self::updateSeasonPlayoffRound($seasonId,$round);
                });

                return response()->json(['success' => true, 'message' => 'Series and schedule inserted successfully']);
            } catch (Exception $e) {
                return response()->json(['success' => false, 'error' => $e->getMessage(), 'line' => __LINE__], 400);
            }
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage(), 'line' => __LINE__], 400);
        }
    }
    /**
     * Create playoff series and schedule for play-in rounds (best-of-1)
     */
    private static function createPlayInSeriesAndSchedule($scheduleData, $seasonId, $round, $conferenceId)
    {
        $seriesData = [];
        $formattedSchedule = [];
        $seriesIndex = 1;
        // Get conference name (e.g., "East") or use "Interconference" for conferenceId = 0
        $conferenceName = $conferenceId 
            ? DB::table('conferences')->where('id', $conferenceId)->value('name') 
            : 'Interconference';

        foreach ($scheduleData as $game) {
            // Generate series_id (e.g., play_ins_elims_round_1-East-S1)
            $seriesId = "S{$seasonId}-C{$conferenceId}-R{$round}-Series{$seriesIndex}";
            $gameId = "S{$seasonId}-C{$conferenceId}-R{$round}-Series{$seriesIndex}-G1";
            // Create playoff series entry (best-of-1)
            $seriesData[] = [
                'series_id' => $seriesId,
                'season_id' => $seasonId,
                'round' => $round,
                'conference_id' => $conferenceId,
                'home_team_id' => $game['home_id'],
                'away_team_id' => $game['away_id'],
                'best_of' => 1,
                'series_length' => 1, // Fixed best-of-1 for play-ins
                'home_wins' => 0,
                'away_wins' => 0,
                'status' => 1, // Integer status
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            // Create schedule entry
            $formattedSchedule[] = [
                'game_id' => $gameId, // Generate or retrieve game_id if needed
                'round' => $round,
                'season_id' => $seasonId,
                'conference_id' => $conferenceId,
                'home_id' => $game['home_id'], // Higher seed as home team
                'home_score' => null,
                'away_id' => $game['away_id'],
                'away_score' => null,
                'winner_id' => 0,
                'status' => 1,
                'series_id' => $seriesId, // Use series_id instead of series_number
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            $seriesIndex++;
        }

        return [$seriesData, $formattedSchedule];
    }

    /**
     * Create both playoff series and schedule for game one (non-play-in rounds)
     */
    private static function createSeriesAndSchedule($pairings, $seasonId, $round, $conferenceId, $seriesLength)
    {
        $seriesData = [];
        $scheduleData = [];
        $seriesIndex = 1;

        // Get conference name (use "Interconference" for conferenceId = 0)
        $conferenceName = $conferenceId 
            ? DB::table('conferences')->where('id', $conferenceId)->value('name') 
            : 'Interconference';

        foreach ($pairings as $pairing) {
            $seriesId = "S{$seasonId}-C{$conferenceId}-R{$round}-Series{$seriesIndex}";
            $bestOf = intval(floor($seriesLength / 2) + 1);

            // Create playoff series entry
            $seriesData[] = [
                'series_id' => $seriesId,
                'season_id' => $seasonId,
                'round' => $round,
                'conference_id' => $conferenceId,
                'home_team_id' => $pairing[0],
                'away_team_id' => $pairing[1],
                'best_of' => $bestOf,
                'series_length' => $seriesLength,
                'home_wins' => 0,
                'away_wins' => 0,
                'status' => 1, // Integer status
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Create schedule entries for all games in the series
            for ($gameNum = 1; $gameNum <= $bestOf; $gameNum++) {
                $gameId = "S{$seasonId}-C{$conferenceId}-R{$round}-Series{$seriesIndex}-G{$gameNum}";

                $scheduleData[] = [
                    'game_id' => $gameId,
                    'round' => $round,
                    'season_id' => $seasonId,
                    'conference_id' => $conferenceId,
                    'home_id' => $pairing[0], // Initially higher seed is home
                    'home_score' => null,
                    'away_id' => $pairing[1],
                    'away_score' => null,
                    'winner_id' => 0,
                    'status' => 1,
                    'series_id' => $seriesId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $seriesIndex++;
        }

        return [$seriesData, $scheduleData];
    }

    /**
     * Insert playoff series into playoff_series table
     */
    private static function insertPlayoffSeries($seasonId, $round, $seriesData)
    {
        if (!empty($seriesData)) {
            DB::table('playoff_series')->insert($seriesData);
        }
    }

    /**
     * Insert schedule into schedules table
     */
    private static function insertSeriesSchedule($seasonId, $round, $scheduleData)
    {
        if (!empty($scheduleData)) {
            DB::table('schedules')->insert($scheduleData);
        }
    }

    private static function updateSeasonPlayoffRound($seasonId,$round){

        $status = self::roundStatusFormatter($round);

        DB::table('seasons')
            ->where('id', $seasonId)
            ->update(['status' => $status]);
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

    private static function generateSeriesPairings16($seasonId, $conferenceId, $round)
    {
        // Initialize pairings array
        $pairings = [];

        // Generate pairings based on the round
        switch ($round) {
            case 'quarter_finals':
                // Pair the teams for quarter-finals
                $winners = self::getSeriesWinnersOfRound('round_of_16', $seasonId, $conferenceId);
                $pairings = self::pairTeams($winners, 4);
                break;
            case 'semi_finals':
                // Pair the winners of quarter-finals for semi-finals
                $winners = self::getSeriesWinnersOfRound('quarter_finals', $seasonId, $conferenceId);
                $pairings = self::pairTeams($winners, 2);
                break;
            case 'interconference_semi_finals':
                // Pair the winners of semi-finals for finals
                $winners = self::getSeriesWinnersOfRound('semi_finals', $seasonId, $conferenceId);

                $pairings = self::pairTeams($winners, 4);
                break;
            case 'finals':
                // Pair the winners of semi-finals for finals
                $winners = self::getSeriesWinnersOfRound('interconference_semi_finals', $seasonId, $conferenceId);
                $pairings = self::pairTeams($winners, 2);
                break;
        }

        return $pairings;
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
        if (in_array($round, ['semi_finals', 'inter_conference_semi_finals','quarter_finals','round_of_16'])) {
            $winners = DB::table('standings_view')
                ->where('season_id', $seasonId)
                ->whereIn('team_id', $winners)
                ->orderBy('overall_rank', 'asc')
                ->pluck('team_id')
                ->toArray();
        }

        return $winners;
    }

     private static function getSeriesWinnersOfRound($round, $seasonId, $conferenceId)
    {
        $winners = [];

        // Build base query depending on round
        $query = DB::table('playoff_series')
            ->where('round', $round)
            ->where('season_id', $seasonId);

        if (!in_array($round, ['semi_finals', 'inter_conference_semi_finals'])) {
            // Filter by conference only if not special round
            $query->where('conference_id', $conferenceId);
        }

        $winners = $query->pluck('winner_team_id')->toArray();

        // If the round is semi_finals or inter_conference_semi_finals, rank the winners by overall_rank
        if (in_array($round, ['semi_finals', 'inter_conference_semi_finals','quarter_finals','round_of_16'])) {
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

    /**
     * Get play-in teams for a given conference, round, and rank range.
     *
     * @param int $seasonId
     * @param int $conferenceId
     * @param array $ranks   Array of ranks to include (e.g., [9, 10])
     * @param int $pairCount Number of teams per pairing
     * @param string $round  Round name
     * @return array         Array of scheduled games
     */
    private static function getPlayInSchedule($seasonId, $conferenceId, array $ranks, $pairCount, $round)
    {
        $teams = DB::table('standings_view')
            ->where('season_id', $seasonId)
            ->where('conference_id', $conferenceId)
            ->orderBy('conference_rank', 'asc')
            ->orderBy('overall_rank', 'asc')
            ->limit(10)
            ->get()
            ->filter(function ($team) use ($ranks) {
                return in_array($team->conference_rank, $ranks);
            })
            ->pluck('team_id')
            ->toArray();

        if (count($teams) === $pairCount) {
            $pairing = self::pairTeams($teams, $pairCount);
            return self::createSchedule($pairing, $seasonId, $round, $conferenceId);
        }

        return [];
    }

    private static function handlePlayInFinals($seasonId, $conferenceId, $previousRounds, $finalRoundName)
    {
        // Fetch both rounds' results
        $round1Results = DB::table('playoff_series')
            ->where('season_id', $seasonId)
            ->where('round', $previousRounds['first'])
            ->where('conference_id', $conferenceId)
            ->get();

        $round2Results = DB::table('playoff_series')
            ->where('season_id', $seasonId)
            ->where('round', $previousRounds['second'])
            ->where('conference_id', $conferenceId)
            ->get();

        // Skip if no results
        if ($round1Results->isEmpty() || $round2Results->isEmpty()) {
            return [];
        }

        // Determine winners & losers using winner_id
        [$winner7vs8, $loser7vs8] = self::determineWinnerLoser($round1Results->first());
        [$winner9vs10, $loser9vs10] = self::determineWinnerLoser($round2Results->first());

        // Create schedule for this conference
        $pairing = [$loser7vs8, $winner9vs10];

        $pairing = self::pairTeams($pairing, 2);
        return self::createSchedule($pairing, $seasonId, $finalRoundName, $conferenceId);
    }

    /**
     * Determine winner and loser from a game result using winner_id.
     *
     * @param object $game
     * @return array [winnerId, loserId]
     */
    private static function determineWinnerLoser($game)
    {
        $winner = $game->winner_team_id;
        $loser = $game->loser_team_id;
        return [$winner, $loser];
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
}
