<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use App\Services\Transaction\TransactionsService;


class TransactionsController extends Controller
{
    protected $transactions;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->transactions = new TransactionsService();
    }

    public function getRecentNonTransferTransactions(){

        return $this->transactions->getRecentNonTransferTransactions();
    }

    public function getRecentTransferTransactions(){

        return $this->transactions->getRecentTransferTransactions();
    }

    public function getTransactions(Request $request){

        return $this->transactions->getTransactions($request);
    }

    public function getSeasonTransferTransactions(Request $request){

        return $this->transactions->getSeasonTransferTransactions($request);
    }

    public function waivePlayer(Request $request){

        return $this->transactions->waivePlayer($request);
    }

    public function extendContract(Request $request){

        $request->validate([
            'id' => 'required|exists:players,id',
            'additional_years' => 'required|integer|min:1|max:5',
        ]);

        return $this->transactions->extendContract($request);
    }

    public function assignPlayerToRandomTeam(Request $request){

        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->transactions->assignPlayerToRandomTeam($request);
    }

    public function assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId)
    {
        return $this->transactions->assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId);
    }

    public function signFreeAgent(Request $request)
    {
        $request->validate([
            'player_id' => 'required|exists:players,id',
            'team_id' => 'required|exists:teams,id',
        ]);

        return $this->transactions->signFreeAgent($request);
    }

    public function assignRemainingFreeAgents()
    {
        return $this->transactions->assignRemainingFreeAgents();
    }

    public function autoAssignFreeAgents()
    {
        return $this->transactions->autoAssignFreeAgents();
    }

    public function getTeamPositionCounts($teamId)
    {
        return $this->transactions->getTeamPositionCounts($teamId);
    }

    public function checkPositionAvailability()
    {
        return $this->transactions->checkPositionAvailability();
    }

}
