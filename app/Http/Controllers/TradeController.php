<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 0);

use Illuminate\Http\Request;
use App\Services\Trade\TradeService;

class TradeController extends Controller
{
    protected $tradeService;

    public function __construct()
    {
        $this->tradeService = new TradeService();
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */
    public function getPendingTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        return $this->tradeService->getPendingTradeProposals($request);
    }

    public function getApprovedTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        return $this->tradeService->getApprovedTradeProposals($request);
    }

    public function generateTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        return $this->tradeService->generateTradeProposals($request);
    }

    public function automatedTradeDecision(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        return $this->tradeService->automatedTradeDecision($request);
    }

    /*
    |--------------------------------------------------------------------------
    | LOG TRADE
    |--------------------------------------------------------------------------
    */

    public static function logTrade(
        $teamFromId,
        $teamToId,
        $playerId,
        $tradePlayerId = null,
        $message = 'Trade completed.',
        $isOffSeason = false,
        $tradeProposalId = null
    ) {

       return $this->tradeService->logTrade($teamFromId,$teamToId,$playerId,$tradePlayerId,$message,$isOffSeason,$tradeProposalId = null); 
    }

    /*
    |--------------------------------------------------------------------------
    | END IN-SEASON TRADE WINDOW
    |--------------------------------------------------------------------------
    */
    public function endInSeasonTradeWindow()
    {
        return $this->tradeService->endInSeasonTradeWindow();
    }

    public function endOffSeasonTradeWindow()
    {
        return $this->tradeService->endOffSeasonTradeWindow();
    }

}    
   
