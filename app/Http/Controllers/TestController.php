<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 0); // Unlimited execution time

use Illuminate\Http\Request;
use App\Models\Teams;
use App\Models\Player; // <-- Add this if not yet imported
use App\Services\Archive\ArchiveService;
use App\Services\Archive\ArchiveToOtherDBService;
use App\Services\Draft\DraftPickRightsService;
use App\Services\Helper\HelperService;
use App\Services\Player\PlayerRatingsService;
use App\Services\Stats\PlayoffStatsService;
use App\Services\Schedule\ScheduleService;
use App\Services\Team\TeamChemistryService;
use App\Services\Team\TeamRoleService;
use App\Services\Team\TeamStreakService;
use App\Services\Trade\TradeService;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{

    protected $schedule;
    protected $chemistry;
    protected $streak;
    protected $helper;
    protected $archive;
    protected $playoff;
    protected $tradeService;
    protected $teamRole;
    protected $playerRatingService;
    protected $draftRights;
    protected $archivesToDB;

    public function __construct(){

        $this->draftRights = new DraftPickRightsService();
        $this->teamRole = new TeamRoleService();
        $this->archivesToDB = new ArchiveToOtherDBService();
        $this->chemistry = new TeamChemistryService();
        $this->streak = new TeamStreakService();
        $this->helper = new HelperService();
        $this->schedule = new ScheduleService();
        $this->archive = new ArchiveService();
        $this->playoff = new PlayoffStatsService();
        $this->tradeService = new TradeService();
        $this->playerRatingService = new PlayerRatingsService();
        
    }
    public function testSchedule(){
        $players = DB::table('players')->get();

        foreach ($players as $player) {

            $overallRating = $player->overall_rating ?? 70;
            $potentialRating = 70;

            if ($overallRating >= 90) {
                $potentialRating = 99;
            } elseif ($overallRating >= 85 && $overallRating <= 89) {
                $potentialRating = rand(90,95);
            } elseif ($overallRating >= 75 && $overallRating <= 84) {
                $potentialRating = rand(85,90);
            } elseif ($overallRating >= 60 && $overallRating <= 74) {
                $potentialRating = rand(75,85);
            } else {
                $potentialRating = 74;
            }

            Player::where('id', $player->id)->update(['potential_rating' => $potentialRating]);

        }

        return response()->json(['success!']);
    }

    public function waiveTeam(Request $request, $teamId)
    {
        $seasonStatus = (int) $request->input('season_status', 2);
        $requiredRecoveryGames = (int) $request->input('required_games', 8);
        $seed = $request->input('seed');

        if (!is_null($seed)) {
            mt_srand((int) $seed);
        }

        // Find the team
        $team = Teams::findOrFail($teamId);

        // Fetch players for this team
        $players = Player::where('team_id', $teamId)->get();
        $seasonId = get_current_season_id() ?? 1;

        $waivablePlayers = [];
        foreach ($players as $player) {
            if ($this->shouldWaivePlayer($player, $seasonId, $seasonStatus)) {
                $replacement = $this->getBestFreeAgentAvailable($player->position);

                $waivablePlayers[] = [
                    'player_id' => $player->id,
                    'name' => $player->name ?? $player->full_name ?? null,
                    'replacement' => $replacement ? [
                        'name' => $replacement->name ?? $replacement->full_name ?? null,
                        'overall_rating' => $replacement->overall_rating,
                    ] : null,
                    'role' => $player->role,
                    'overall_rating' => $player->overall_rating,
                    'age' => $player->age,
                    'injury_recovery_games' => $player->injury_recovery_games,
                    'injury_prone_percentage' => $player->injury_prone_percentage,
                    'morale' => $player->morale,
                    'stamina_rating' => $player->stamina_rating,
                    'work_ethic_rating' => $player->work_ethic_rating,
                    'leadership_rating' => $player->leadership_rating,
                    'contract_years' => $player->contract_years,
                ];
            }
        }

        return response()->json([
            'team_id' => $team->id,
            'team_name' => $team->name ?? null,
            'season_status' => $seasonStatus,
            'required_recovery_games' => $requiredRecoveryGames,
            'has_waived' => count($waivablePlayers) > 0,
            'waivable_players' => $waivablePlayers,
        ]);
    }

    // Keep waiveScan as-is or adjust similarly
    public function waiveScan(Request $request)
    {
        $seasonStatus = (int) $request->input('season_status', 2);
        $requiredRecoveryGames = (int) $request->input('required_games', 6);
        $seed = $request->input('seed');

        if (!is_null($seed)) {
            mt_srand((int) $seed);
        }

        $teams = Teams::all();
        $seasonId = get_current_season_id() ?? 1;

        $report = [];
        foreach ($teams as $team) {
            $players = Player::where('team_id', $team->id)->get();
            $waivablePlayers = [];

            foreach ($players as $player) {
                // $waivablePlayers[] = $this->shouldWaivePlayer($player, $seasonId, $seasonStatus);
                if ($this->shouldWaivePlayer($player, $seasonId, $seasonStatus)) {
                    $replacement = $this->getBestFreeAgentAvailable($player->position);

                    $waivablePlayers[] = [
                        'player_id' => $player->id,
                        'name' => $player->name ?? $player->full_name ?? null,
                        'replacement' => $replacement ?: null,
                        'role' => $player->role,
                        'overall_rating' => $player->overall_rating,
                        'age' => $player->age,
                        'injury_recovery_games' => $player->injury_recovery_games,
                        'injury_prone_percentage' => $player->injury_prone_percentage,
                        'morale' => $player->morale,
                        'stamina_rating' => $player->stamina_rating,
                        'work_ethic_rating' => $player->work_ethic_rating,
                        'leadership_rating' => $player->leadership_rating,
                        'contract_years' => $player->contract_years,
                    ];
                }
            }

            $report[] = [
                'team_id' => $team->id,
                'team_name' => $team->name ?? null,
                'has_waived' => count($waivablePlayers) > 0,
                'waivable_players' => $waivablePlayers,
            ];
        }

        return response()->json([
            'season_status' => $seasonStatus,
            'required_recovery_games' => $requiredRecoveryGames,
            'total_teams' => $teams->count(),
            'results' => $report,
        ]);
    }

    private function shouldWaivePlayer($player, int $seasonId, int $seasonStatus)
    {
        // Only consider waiving during first half of season (e.g., before trade deadline)
        if ($seasonStatus > 2) return false;

        // How many regular-season games this specific team will play (dynamic)
        $totalTeamGames = $this->getRegularSeasonGameCount($seasonId, $player->id);

        // % of the season a player must be out before we even consider waiving
        $rolePctMap = [
            'star player' => 0.80,
            'all star'    => 0.75,
            'starter'     => 0.60,
            'role player' => 0.45,
            'bench'       => 0.30,
        ];

        $defaultPct = 0.45;
        $pct = $rolePctMap[strtolower($player->role)] ?? $defaultPct;

        $minReq = 2;
        $maxReq = $totalTeamGames;
        $requiredRecoveryGames = (int) ceil($totalTeamGames * $pct);
        $requiredRecoveryGames = max($minReq, min($requiredRecoveryGames, $maxReq));

        if ($player->injury_recovery_games >= $requiredRecoveryGames) {
            return true;
        }

        return false;
    }

    private function getRegularSeasonGameCount(int $seasonId, int $playerId): int
    {
        return (int) (
            DB::table('player_season_stats')
            ->where('season_id', $seasonId)
            ->where('player_id', $playerId)
            ->orderByDesc('id') // get the latest record
            ->value('total_games') ?? 19
        );
    }

    private function getBestFreeAgentAvailable($position)
    {
        $positions = explode('/', strtoupper($position)); // Normalize casing

        // Flexible position filter: match any part of multi-position fields
        $positionFilter = function ($query) use ($positions) {
            $query->where(function ($q) use ($positions) {
                foreach ($positions as $pos) {
                    $q->orWhere('players.position', 'LIKE', '%' . $pos . '%');
                }
            });
        };

        // Get latest season id (adjust if your season logic is different)
        $latestSeasonId = get_current_season_id();

        // Top 10 by overall_rating
        $byOverall = DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(10)
            ->get();

        // Top 10 by awards count
        $byAwards = DB::table('players')
            ->leftJoin('season_awards', 'players.id', '=', 'season_awards.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                DB::raw('COUNT(season_awards.id) as awards_count')
            )
            ->groupBy(
                'players.id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('awards_count')
            ->limit(10)
            ->get();

        // Top 10 by EFF in latest season
        $byEff = DB::table('players')
            ->leftJoin('player_season_stats', 'players.id', '=', 'player_season_stats.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where('player_season_stats.season_id', $latestSeasonId)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                'player_season_stats.eff'
            )
            ->orderByDesc('player_season_stats.eff')
            ->limit(10)
            ->get();

        // Merge all and deduplicate by player_id
        $merged = $byOverall->merge($byAwards)->merge($byEff)->unique('player_id')->values();

        // Return a random player from the merged top candidates
        if ($merged->isNotEmpty()) {
            return $merged->random();
        }

        // Fallback: any available player at the position
        return DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(1)
            ->first();
    }

    public function testTeamBalance(){

        $results = DB::table('game_quarter_breakdown')->whereIn('team_id', [9,4])
                    ->where('game_id', '1-1-1-1')
                    ->pluck('total')
                    ->toArray();
        
        if($results[0] !=0 && $results[1] != 0)
        {
            return $results[0].' - '.$results[1];
        }

        return $results;
        
    }

    public function testChemistryCalculations(){

        $latestSeasonId = get_current_season_id() ?? 1;
        $previousSeasonId = $latestSeasonId - 1;

        $activeTeams = DB::table('teams')
            ->select('teams.id', 'teams.name')
            ->groupBy('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();
            
            $data = [];

            foreach($activeTeams as $team){
                $finalChemistry = $this->chemistry->getChemistryCalculation($team->id,$latestSeasonId,$previousSeasonId);
                $data[$team->name] = $finalChemistry;
                
                // DB::table('team_season_info')->updateOrInsert(
                //     [
                //         'team_id' => $team->id,
                //         'season_id' => $latestSeasonId,
                //     ],
                //     [
                //         'chemistry' => $finalChemistry['chemistry_score'],
                //     ]
                // );
            }

            usort($data, function($a, $b){
                return $b['chemistry_score'] <=> $a['chemistry_score'];
            });

            return response()->json([
                'data' => $data,
            ]);
    }

    public function testGenerateTradeProposals()
    {
        // return $this->tradeService->testGenerateTradeProposals();
        $seasonId = 5;

        return $this->draftRights->ensureFutureDraftRights($seasonId);
    }

    public function testGameStreak(Request $request){
        $gameId = $request->game_id;
        $teamId = 58;

        // return $this->streak->updateTeamStreaks($gameId,$teamId,0);

        $latestSeasonId = get_current_season_id();
        $previousSeasonId = $latestSeasonId - 1;

        $prevChampion = $this->helper->getNationalChampionId($previousSeasonId);

        return $prevChampion;

    }

    public function gameFlow(){
        for ($i=1; $i <= 4; $i++) { 
            # code...

            echo 'Q'.$i.'   ';
        }
        // $OT = true;
        // $tries = 0;
        // do {
        //     $homeScore = rand(1,10);
        //     $awayScore = rand(1,10);

        //     $tries++;

        //     if($homeScore == $awayScore){
        //         $OT = false;

        //         echo $homeScore." - ".$awayScore;
        //     }
        // } while ($OT);

        // echo 'Tied at '.$tries.'x tries';

    }
    public function checkUnderPerformedPlayersPerTeam(){
        
        $activeTeams = DB::table('teams')
            ->select('teams.id', 'teams.name')
            ->groupBy('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();
        
        $players = [];

        foreach($activeTeams as $team){
            $uPlayer = $this->tradeService->findUnderperformingPlayers($team->id);
            if($uPlayer){
                $players[$team->name]['players'] = $uPlayer;
                $players[$team->name]['team_name'] = $team->name;
            }
            
        }

        return response()->json($players,200);
    }

    public function updateContract(){
        
        $activeTeams = DB::table('teams')
            ->select('teams.id', 'teams.name')
            ->groupBy('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();
        
        $players = [];

        foreach($activeTeams as $team){
            $players[$team->name]['players'] = $this->playerRatingService->updateContract($team->id);
        }

        return response()->json($players,200);
    }

    public function insertTeamStreak(){

        $activeTeams = DB::table('teams')
            ->select('teams.id', 'teams.name')
            ->groupBy('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();
        
        foreach($activeTeams as $team){

            $data = [
                'best_winning_streak' => 0,
                'best_winning_streak_start_id' => 0,
                'best_winning_streak_end_id' => 0,
                'best_losing_streak' => 0,
                'best_losing_streak_start_id' => 0,
                'best_losing_streak_end_id' => 0,
            ];

            DB::table('streak')->updateOrInsert(
                [
                    'team_id' => $team->id,
                ],
                $data
            );
        }
    }

    public function redisTest()
    {
        
        $value = config()->get('cache.default');

        return response()->json([
            'cache' => $value,
        ]);
    }

    public function TestArchiving(){

        // $archive =  $this->archive->archivePlayerSeasonStats();
        $archive =  $this->archive->archiveQuarterGameBreakDown(1);

        return response()->json([
            'message' => $archive,
        ]);
    }

    public function testSnapShot(){

        $snap = $this->archive->archivePlayoffSeriesTable();

        return response()->json([
            'message' => $snap,
        ]);
    }
}
