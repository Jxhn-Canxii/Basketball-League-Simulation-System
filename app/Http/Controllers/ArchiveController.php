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

    public function runArchives()
    {
        $seasonId = get_current_season_id();
        
        $disabled = false;
        if($disabled){
            return response()->json([
                'message' => 'Archiving end-point disabled!',
                'success' => false,
            ],404);
        }

        return $this->archiveService->runArchives($seasonId);
    }
}