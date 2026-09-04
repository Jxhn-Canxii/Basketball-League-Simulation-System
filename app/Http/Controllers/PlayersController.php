<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Player\PlayerService;
use App\Services\Analytics\LeadersService;
use App\Services\Player\RoleService;
use App\Services\Player\PlayerGeneratorService;
use App\Services\Player\InjuryService;
use App\Services\Schedule\ScheduleService;
use Inertia\Inertia;
class PlayersController extends Controller
{

    protected $playerService;
    protected $leaderService;
    protected $roleService;
    protected $scheduleService;
    protected $playerGeneratorService;
    protected $injuryService;

    public function __construct()
    {
        $this->playerService = new PlayerService();
        $this->leaderService = new LeadersService();
        $this->roleService = new RoleService();
        $this->scheduleService = new ScheduleService();
        $this->injuryService = new InjuryService();
        $this->playerGeneratorService = new PlayerGeneratorService();
    }

    public function index()
    {
        return Inertia::render('Players/Index', [
            'status' => session('status'),
        ]);
    }
    public function freeagents()
    {
        return Inertia::render('FreeAgents/Index', [
            'status' => session('status'),
        ]);
    }
    public function experience()
    {
        return Inertia::render('Experience/Index', [
            'status' => session('status'),
        ]);
    }
    
    public function listTeamRoster(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'season_id' => 'nullable|integer',
        ]);

        return $this->playerService->listTeamRoster($request);
    }

    public function getFreeAgents(Request $request)
    {
        return $this->playerService->getFreeAgents($request);
    }

    public function getAllPlayers(Request $request)
    {
        return $this->playerService->getAllPlayers($request);
    }

    public function generateNewPlayer()
    {
        return $this->playerGeneratorService->generateNewPlayer();
    }

    // Add a player to a team with random attributes
    public function addPlayer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'team_id' => 'required|exists:teams,id',
        ]);

        return $this->playerGeneratorService->addPlayer($request);

    }

    public function addFreeAgentPlayer(Request $request)
    {
        $messages = [
            'name.regex' => 'The name must contain at least one vowel.',
            'name.max' => 'The name must not exceed 30 characters.',
        ];

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:30',
                'regex:/[aeiouAEIOU]/',
            ],
            'address' => 'required|string|max:255',
            'country' => 'required|string',
        ], $messages);


        return $this->playerGeneratorService->addFreeAgentPlayer($request);

    }

    public function getPlayerSeasonPerformance(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->playerService->getPlayerSeasonPerformance($request);

    }

    public function getPlayerPlayoffPerformance(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->playerService->getPlayerPlayoffPerformance($request);

    }
    
    public function getPlayerMainPerformance(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->playerService->getPlayerMainPerformance($request);

    }

    public function getPlayerGameLogs(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
            'season_id' => 'required|exists:seasons,id',
            'page_num' => 'required|integer|min:1',
            'itemsperpage' => 'required|integer|min:1',
        ]);

        return $this->playerService->getPlayerGameLogs($request);
    
    }

    public function getPlayerLatestGameLogs(Request $request)
    {
        // Validate the request data
        $request->validate([
            'player_id' => 'required|exists:players,id',
            'season_id' => 'required|exists:seasons,id',
            'page_num' => 'required|integer|min:1',
            'itemsperpage' => 'required|integer|min:1',
        ]);

        return $this->playerService->getPlayerLatestGameLogs($request);
    
    }

    public function getPlayersWithFilters(Request $request)
    {
        return $this->playerService->getPlayersWithFilters($request);

    }

    public function getTop20PlayersAllTime()
    {   
        return $this->leaderService->getTop20PlayersAllTime();

    }

    public function getTop10PlayersByTeam(Request $request)
    {   
        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        return $this->leaderService->getTop10PlayersByTeam($request);

    }

    public function getStarPlayersByTeam(Request $request)
    {   
        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        return $this->playerService->getStarPlayersByTeam($request);

    }

    public function getPlayerTransactions(Request $request)
    {   
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->playerService->getPlayerTransactions($request);

    }

    public function getPlayerContracts(Request $request)
    {   
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->playerService->getPlayerContracts($request);

    }

    public function getCareerHighs(Request $request)
    {   
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->playerService->getCareerHighs($request);

    }

    public function getRoleChangeHistory(Request $request)
    {   
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->roleService->getRoleChangeHistory($request);

    }

    public function getPlayerInjuryHistory(Request $request)
    {   
        $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);

        return $this->injuryService->getPlayerInjuryHistory($request);

    }

}
