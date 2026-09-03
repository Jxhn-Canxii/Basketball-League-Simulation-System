<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 600); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use App\Services\Playoff\PlayoffService;

class PlayoffController extends Controller
{
    protected $playoffService;

    public function __construct(){
        $this->playoffService = new PlayoffService();
    }

    public function seasonsplayoffs(Request $request){
        return $this->playoffService->seasonsPlayoffs($request);
    }
  
    public function seasonsPlayoffsSeries(Request $request){
        return $this->playoffService->seasonsPlayoffsSeries($request);
    }

    public function playoffSchedule(Request $request){
        return $this->playoffService->playoffSchedule($request);
    }
}