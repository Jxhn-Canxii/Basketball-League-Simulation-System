<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 300); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use App\Services\Player\PlayerRatingsService;
use Illuminate\Support\Facades\DB;

class RatingsController extends Controller
{
    protected  $ratingsService;

    public function __construct()
    {
        $this->ratingsService = new PlayerRatingsService();
    }

    public function updateActivePlayers(Request $request)
    {
        // Validate the request data
        $request->validate([
            'team_id' => 'required|integer|min:0',
            'is_last' => 'required|boolean',
        ]);

        return $this->ratingsService->updateActivePlayers($request);
    }

    public function updateCoachContract(int $teamId)
    {
        return $this->ratingsService->updateCoachContract($teamId);
    }

    public function playerCoachDecision($player,$teamId,$teamName,$seasonId)
    {
        return $this->ratingsService->playerCoachDecision($player,$teamId,$teamName,$seasonId);
    }
}
