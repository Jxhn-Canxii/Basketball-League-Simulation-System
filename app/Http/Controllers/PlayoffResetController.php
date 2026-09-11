<?php

namespace App\Http\Controllers;

use App\Services\League\PlayoffResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PlayoffResetController extends Controller
{
    protected $resetService;

    public function __construct()
    {
        $this->resetService = new PlayoffResetService();
    }

    /**
     * Reset a specific playoff round and all rounds after it.
     *
     * Example:
     * POST /playoff-reset/round
     *
     * {
     *     "season_id": 13,
     *     "round": "quarter_finals"
     * }
     */
    public function resetRound(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'season_id' => [
                'required',
                'integer',
                'exists:seasons,id',
            ],

            'round' => [
                'required',
                'string',
                'in:play_ins_elims_round_1,
                    play_ins_elims_round_2,
                    play_ins_finals,
                    round_of_16,
                    quarter_finals,
                    semi_finals,
                    interconference_semi_finals,
                    finals',
            ],
        ]);

        try {
            $result = $this->resetService->resetRound(
                (int) $validated['season_id'],
                $validated['round']
            );

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Successfully reset %s and all subsequent playoff rounds.',
                    $validated['round']
                ),
                'data' => $result,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset playoff round.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset the entire playoff tournament.
     *
     * Regular-season data remains untouched.
     *
     * Example:
     * POST /playoff-reset/playoffs
     *
     * {
     *     "season_id": 13
     * }
     */
    public function resetPlayoffs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'season_id' => [
                'required',
                'integer',
                'exists:seasons,id',
            ],
        ]);

        try {
            $result = $this->resetService->resetPlayoffs(
                (int) $validated['season_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully reset the entire playoffs.',
                'data' => $result,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset playoffs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset the entire season.
     *
     * WARNING:
     * This removes both regular-season and playoff game data.
     *
     * Example:
     * POST /playoff-reset/season
     *
     * {
     *     "season_id": 13
     * }
     */
    public function resetSeason(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'season_id' => [
                'required',
                'integer',
                'exists:seasons,id',
            ],
        ]);

        try {
            $result = $this->resetService->resetSeason(
                (int) $validated['season_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully reset the entire season.',
                'data' => $result,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset entire season.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}