<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Archive\ArchiveService;
use Inertia\Inertia;

class ArchiveController extends Controller
{
    protected $archiveService;

    public function __construct(){
        $this->archiveService = new ArchiveService();
    }
    public function archiveGameStats()
    {
        return $this->archiveService->archiveGameStats();
    }

    public function archivePlayerSeasonStats()
    {
        return $this->archiveService->archivePlayerSeasonStats();
    }

    public function runArchives(Request $request)
    {
        $seasonId = $request->season_id;
        $disabled = true;
        if($disabled){
            return response()->json([
                'message' => 'Archiving end-point disabled!',
                'success' => false,
            ],404);
        }

        return $this->archiveService->runArchives($seasonId);
    }

    public function archiveStandingViewTable()
    {
        return $this->archiveService->archiveStandingViewTable();
    }

    public function archiveScheduleViewTable()
    {
        return $this->archiveService->archiveScheduleViewTable();
    }

    public function archiveScheduleWriteTable()
    {
        return $this->archiveService->archiveScheduleWriteTable();
    }

}