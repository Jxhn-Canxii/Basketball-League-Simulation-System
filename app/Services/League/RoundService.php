<?php

namespace App\Services\League;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class RoundService
{
   protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }
      // Function to get the results of the previous round in a conference
    public function previousConferenceRoundResults($request)
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

    public function getConferenceRoundNotSimulated($request)
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

    public function getSeasonRoundNotSimulated($request)
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

    public function formatRound($round, $game_number)
    {
        switch ($round) {
            case 'play_ins_elims_round_1':
                return 'Conference Play-ins (7th vs 8th)';
            case 'play_ins_elims_round_2':
                return 'Conference Play-ins (9th vs 10th)';
            case 'play_ins_elims':
                return 'Conference Play-ins';
            case 'play_ins_finals':
                return 'Conference Play-ins Finals';
            case 'round_of_32':
                return 'Conference Round of 32 Game ' . $game_number;
            case 'round_of_16':
                return 'Conference Round of 16 Game ' . $game_number;
            case 'quarter_finals':
                return 'Conference Quarterfinals Game ' . $game_number;
            case 'semi_finals':
                return 'Conference Semi-Finals Game ' . $game_number;
            case 'interconference_semi_finals':
                return 'The Big 4 (Semifinals) Game ' . $game_number;
            case 'finals':
                return 'The Finals Game ' . $game_number;
            default:
                return "Round # {$round}";
        }
    }

    public function roundStatusFormatter($round)
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

    public function prevRoundFormatter($round)
    {

        switch ($round) {
            case 'round_of_32':
                return 'none';
                break;
            case 'play_ins_elims_round_1':
                return 'none';
                break;
            case 'play_ins_elims_round_2':
                return 'play_ins_elims_round_1';
                break;
            case 'play_ins_finals':
                return 'play_ins_elims_round_2';
                break;
            case 'round_of_16':
                return 'play_ins_finals';
                break;
            case 'quarter_finals':
                return 'round_of_16';
                break;
            case 'semi_finals':
                return 'quarter_finals';
            case 'interconference_semi_finals':
                return 'semi_finals';
                break;
            case 'finals':
                return 'interconference_semi_finals';
                break;
            default:
                return 8;
                break;
        }
    }

    public function buildRoundsFromSequence($roundSequence, $status)
    {
        // Map status to cumulative rounds for single_elim_16_with_playins
        $statusToRounds = [
            1 => ['play_ins_elims_round_1'],
            2 => ['play_ins_elims_round_1'],
            3 => ['play_ins_elims_round_1'],
            4 => ['play_ins_elims_round_1'],
            5 => ['play_ins_elims_round_1', 'play_ins_elims_round_2'],
            6 => ['play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals'],
            7 => ['play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals', 'round_of_16'],
            8 => ['play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals', 'round_of_16', 'quarter_finals'],
            9 => ['play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals', 'round_of_16', 'quarter_finals', 'semi_finals'],
            10 => ['play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals', 'round_of_16', 'quarter_finals', 'semi_finals', 'interconference_semi_finals'],
            11 => ['play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals', 'round_of_16', 'quarter_finals', 'semi_finals', 'interconference_semi_finals', 'finals'],
        ];

        // Return rounds up to the given status, default to all rounds if status is invalid
        return $statusToRounds[$status] ?? $roundSequence;
    }
}

