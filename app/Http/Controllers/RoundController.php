<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\League\RoundService;
class RoundController extends Controller
{
    protected $roundService;

    public function __construct(){
        $this->roundService = new RoundService;
    }
    
    public function previousConferenceRoundResults(Request $request){
        return $this->roundService->previousConferenceRoundResults($request);
    }
    
    public function getConferenceRoundNotSimulated(Request $request){
        return $this->roundService->getConferenceRoundNotSimulated($request);
    }
    
    public function getSeasonRoundNotSimulated(Request $request){
        return $this->roundService->getSeasonRoundNotSimulated($request);
    }
}
