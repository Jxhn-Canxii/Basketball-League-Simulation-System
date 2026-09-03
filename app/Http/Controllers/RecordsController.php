<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 1200); // 300 seconds = 5 minutes

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\Analytics\RecordsService;

class RecordsController extends Controller
{
    protected $recordsService;

    public function __construct()
    {
        $this->recordsService = new RecordsService();
        throw new \Exception('Not implemented');
    }
    public function index()
    {
        return Inertia::render('Records/Index', [
            'status' => session('status'),
        ]);
    }
    public function champions(Request $request){
        return $this->recordsService->champions($request);
    }

    public function recent(Request $request){
        return $this->recordsService->recent($request);
    }

    public function getRivalries(){
        return $this->recordsService->getRivalries();
    }

    public function playoffAppearances(){
        return $this->recordsService->getRivalries();
    }

    public function topScorerTeams(Request $request){
        return $this->recordsService->topScorerTeams($request);
    }

    public function statsLeaders(Request $request){
        return $this->recordsService->statsLeaders($request);
    }

    public function winningestTeams(Request $request){
        return $this->recordsService->winningestTeams($request);
    }

    public function updatePlayerPlayoffAppearances(Request $request){
        return $this->recordsService->updatePlayerPlayoffAppearances($request);
    }
}
