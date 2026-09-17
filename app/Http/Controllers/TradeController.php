<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 0);

use App\Services\Trade\TradeDecisionService;
use Illuminate\Http\Request;
use App\Services\Trade\TradeService;

class TradeController extends Controller
{
    protected $tradeService;
    protected $tradeDecision;

    public function __construct()
    {
        $this->tradeService = new TradeService();
        $this->tradeDecision = new TradeDecisionService();
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */
    public function getAllTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
            'season_id' => 'required|int'
        ]);

        return $this->tradeService->getAllTradeProposals($request);
    }

    public function getRecentTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
            'season_id' => 'required|int',
            'limit' => 'required|int',
        ]);

        return $this->tradeService->getRecentTradeProposals($request);
    }

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

        return $this->tradeDecision->generateTradeProposals($request->is_off_season);
    }


    public function automatedTradeDecision(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        return $this->tradeDecision->automatedTradeDecision($request->is_off_season);
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
