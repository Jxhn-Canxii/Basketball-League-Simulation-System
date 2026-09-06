<?php

namespace App\Services\League;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class StoryLineService
{
   protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }

    public function upsertCurrentSeasonStoryline()
    {
        try {

            $storylineData =
                DB::table('current_season_storyline')
                ->first();

            if (!$storylineData) {

                return response()->json([
                    'message' =>
                    'No current season storyline found.',
                ], 404);
            }

            DB::table('storylines')
                ->updateOrInsert(
                    [
                        'season_id' =>
                        $storylineData->season_id,
                    ],
                    [
                        'storyline' =>
                        $storylineData->storyline,

                        'updated_at' =>
                        now(),

                        'created_at' =>
                        now(),
                    ]
                );

            return true;
        } catch (\Exception $e) {

            // Log::error(
            //     'Failed to upsert storyline: ' .
            //         $e->getMessage()
            // );

            return response()->json([
                'error' =>
                'Failed to upsert storyline',

                'message' =>
                $e->getMessage(),
            ], 500);
        }
    }
   
}
