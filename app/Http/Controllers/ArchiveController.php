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
    public static function archiveGameStats()
    {
        return $this->archiveService->archiveGameStats();
    }

    public static function archivePlayerSeasonStats()
    {
        return $this->archiveService->archivePlayerSeasonStats();
    }

    public static function storeTeamSeasonInfo()
    {
        return $this->archiveService->storeTeamSeasonInfo();
    }

    public static function archiveStandingViewTable()
    {
        return $this->archiveService->archiveStandingViewTable();
    }

    public static function archiveScheduleViewTable()
    {
        return $this->archiveService->archiveScheduleViewTable();
    }

    public static function archiveScheduleWriteTable()
    {
        return $this->archiveService->archiveScheduleWriteTable();
    }

}