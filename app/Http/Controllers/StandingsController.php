<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\League\StandingsService;

class StandingsController extends Controller
{
    protected $standingsService;

    public function __construct()
    {
        $this->standingsService = new StandingsService();
    }
   
    public function seasonStandings (Request $request)
    {
        return $this->standingsService->seasonStandings($request);
    }
}
