<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Draft\DraftService;
use Inertia\Inertia;

class DraftController extends Controller
{

    protected $draftService;

    public function __construct()
    {
        $this->draftService = new DraftService();
    }

    public function index()
    {
        return Inertia::render('Draft/Index', [
            'status' => session('status'),
        ]);
    }
 
    public function draftOrder()
    {
        return $this->draftService->draftOrder();
    }
    public function draftPlayers()
    {
        return $this->draftService->draftPlayers();
    }

    public function rookieDraftees(Request $request)
    {
       return $this->draftService->rookieDraftees($request);
    }

    public function draftResultsPerSeason(Request $request)
    {
       return $this->draftService->draftResultsPerSeason($request);
    }

    public function draftResults()
    {
       return $this->draftService->draftResults();
    }
}
