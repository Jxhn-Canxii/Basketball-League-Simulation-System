<?php

namespace App\Services\League;

use App\Services\Archive\ArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class StoryLineService
{
   protected $helper;
   protected $archive;

    public function __construct()
    {
        $this->helper = new HelperService();
        $this->archive = new ArchiveService();
    }

    public function generateStoryLine()
    {
        return $this->upsertCurrentSeasonStoryline();
    }

    private function upsertCurrentSeasonStoryline()
    {
        try {

            $storylineData = DB::table('current_season_storyline')->first();

            if (!$storylineData) {

                return false;
            }

            DB::table('storylines')
                ->updateOrInsert(
                    [
                        'season_id' => $storylineData->season_id,
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
