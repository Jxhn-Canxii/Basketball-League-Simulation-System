<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\League\LeaguesService;

class LeaguesController extends Controller
{
    protected $leagueService;

    public function __construct()
    {
        $this->leagueService = new LeaguesService();
    }
    //
    public function index()
    {
        return Inertia::render('Leagues/Index', [
            'status' => session('status'),
        ]);
    }
    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        return $this->leagueService->add($request);
    }
    public function list(Request $request)
    {
        return $this->leagueService->list($request);
    }

    // Update the specified resource in storage.
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        return $this->leagueService->update($request);
    }

    // Remove the specified resource from storage.
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);
        
        return $this->leagueService->delete($request);
    }
    public function dropdown()
    {
        return $this->leagueService->dropdown();
    }

    public function resetLeague()
    {
        return $this->leagueService->resetLeague();
    }
}
