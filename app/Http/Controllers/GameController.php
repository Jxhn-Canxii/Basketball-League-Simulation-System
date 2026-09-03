<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Game\GameResultService;
class GameController extends Controller
{

    protected $gameResultService;

    public function __construct(){

        $this->gameResultService = new GameResultService();
    }
   
    public function getBoxScore(Request $request)
    {
        // Validate the request
        $request->validate([
            'game_id' => 'required|string',
            'show_stats'  => 'required|boolean',
            'season_id'  => 'required|exists:seasons,id',
        ]);

        return $this->gameResultService->getBoxScore($request);
    }
}
