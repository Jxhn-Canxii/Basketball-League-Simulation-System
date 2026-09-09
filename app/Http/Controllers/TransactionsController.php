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
}
