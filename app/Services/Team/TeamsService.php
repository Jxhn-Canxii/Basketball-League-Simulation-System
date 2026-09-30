<?php

namespace App\Services\Team;

ini_set('max_execution_time', 1024); // 300 seconds = 5 minutes

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Teams;
use App\Services\Contract\ContractService;
use App\Services\Helper\HelperService;
use App\Services\Schedule\ScheduleService;
use App\Services\Team\TeamChemistryService;
use App\Services\Trade\TradeService;

class TeamsService
{
    protected $chemistry;
    protected $helper;
    protected $tradeService;
    protected $contract;
    protected $scheduleService;

    public function __construct(){

        $this->chemistry = new TeamChemistryService();
        $this->helper = new HelperService();
        $this->tradeService = new TradeService();
        $this->contract = new ContractService();
        $this->scheduleService = new ScheduleService();
    }
    // Display a listing of the resource.
    public function index()
    {
        return Inertia::render('Teams/Index', [
            'status' => session('status'),
        ]);
    }

    public function list($request)
    {
        // Retrieve search query from request
        $searchQuery = $request->search;

        // Query builder for teams with join on leagues and conferences tables
        $query = Teams::query()
            ->join('leagues', 'teams.league_id', '=', 'leagues.id')
            ->join('conferences', 'teams.conference_id', '=', 'conferences.id','left')
            ->select('teams.*', 'leagues.name as league_name', 'conferences.name as conference_name')
            ->orderBy('teams.id','asc')
            ->where('teams.conference_id','>',0);

        // Apply search filter if search query is provided
        if ($searchQuery) {
            $query->where('teams.name', 'like', '%' . $searchQuery . '%')
                ->orWhere('leagues.name', 'like', '%' . $searchQuery . '%')
                ->orWhere('conferences.name', 'like', '%' . $searchQuery . '%');
        }

        // Get total count of records before pagination
        $totalCount = $query->count();

        // Set the number of records to display per page
        $perPage = 10;

        // Calculate the total number of pages
        $totalPages = ceil($totalCount / $perPage);

        // Get the current page from the request, default to 1 if not provided
        $currentPage = $request->page_num ?? 1;

        // Calculate the offset for pagination
        $offset = ($currentPage - 1) * $perPage;

        // Retrieve teams data with pagination
        $teams = $query->offset($offset)
            ->limit($perPage)
            ->get();

        $latestSeason = get_current_season_id() ?? 0;

        return response()->json([
            'teams' => $teams,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'total_count' => $totalCount,
            'search' => $searchQuery,
            'current_season' => $latestSeason,
        ]);
    }

    public function teamslatestseason($request)
    {
        $team_id = $request->team_id;
        $season_id = $request->season_id;

        $team_info = $this->currentseasonstatistics($team_id, $season_id);

        return response()->json([
            'data' => $team_info,
        ]);
    }

    public function matchhistory($request)
    {
        $home_id = $request->home_id;
        $away_id = $request->away_id;
        $season_id = $request->season_id;

        $matches = self::getLastMatchResults($season_id, $home_id, $away_id);
        $series = self::getTeamSeriesResults($season_id, $home_id, $away_id);

        return response()->json([
            'matches' => $matches,
            'series' => $series,
        ]);
    }

    public function currentseasonstatistics($teamId, $seasonId)
    {
        $previousSeasonId = $seasonId - 1;

        $teamInfo = self::getTeamInfo($teamId);
        $allTimeStats = self::getAllTimeStats($teamId);
        $finalsStats = self::getFinalsStats($teamId);
        $roundStats = self::getRoundStats($teamId);
        $playoffStats = self::getPlayoffStats($teamId);
        $seasonStats = self::getSeasonHistoryCount($teamId, $seasonId);
        $latestSeason = self::getLatestSeason($teamId, $seasonId);
        $chemistry =  $this->chemistry->getChemistryCalculation($teamId,$seasonId,$previousSeasonId);

        return [
            'teams' => $teamInfo,
            'allTimeStats' => $allTimeStats,
            'finalsStats' => $finalsStats,
            'roundStats' => $roundStats,
            'playoffStats' => $playoffStats,
            'seasonStats' => $seasonStats,
            'chemistry' => $chemistry,
            'latestSeason' => $latestSeason,
        ];
    }

    private function getLatestSeason($teamId, $seasonId)
    {
        $latestSeasonId = get_current_season_id();

        $table = ($seasonId == $latestSeasonId) ? 'standings_view' : 'standings_snapshots';

        return DB::table($table)
            ->select('*') // Optionally specify only necessary fields
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->get();
    }

    public function getLastMatchResults($seasonId, $homeId, $awayId)
    {
        $scheduleViewTable = $this->helper->getScheduleViewDBName($seasonId);

        $matchResults = DB::table($scheduleViewTable.' as schedule_view')
            ->select(
                'schedule_view.id',
                'schedule_view.season_id',
                'schedule_view.round',
                'schedule_view.home_id',
                'schedule_view.away_id',
                'schedule_view.home_score',
                'schedule_view.away_score',
                'schedule_view.status',
                'schedule_view.winner_id',
                'schedule_view.created_at',

                // Home team info
                'home.name as home_name',
                'home.acronym as home_acronym',
                'home.primary_color as home_primary_color',
                'home.secondary_color as home_secondary_color',

                // Away team info
                'away.name as away_name',
                'away.acronym as away_acronym',
                'away.primary_color as away_primary_color',
                'away.secondary_color as away_secondary_color',

                // Winner info
                'winner.name as winner_name',
                'winner.acronym as winner_acronym',

                // Result summary
                DB::raw("CONCAT(
                    winner.acronym, ' def. ',
                    CASE WHEN winner_id = home_id THEN away.acronym ELSE home.acronym END,
                    ' ',
                    CASE WHEN winner_id = home_id THEN home_score ELSE away_score END,
                    '-',
                    CASE WHEN winner_id = home_id THEN away_score ELSE home_score END
                ) as result_summary")
            )
            ->join('teams as home', 'schedule_view.home_id', '=', 'home.id')
            ->join('teams as away', 'schedule_view.away_id', '=', 'away.id')
            ->leftJoin('teams as winner', 'schedule_view.winner_id', '=', 'winner.id')
            ->where(function ($query) use ($homeId, $awayId, $seasonId) {
                $query->where(function ($q) use ($homeId, $awayId, $seasonId) {
                    $q->where('season_id', '<=', $seasonId)
                    ->where('home_id', $homeId)
                    ->where('away_id', $awayId);
                })
                ->orWhere(function ($q) use ($homeId, $awayId, $seasonId) {
                    $q->where('season_id', '<=', $seasonId)
                    ->where('home_id', $awayId)
                    ->where('away_id', $homeId);
                });
            })
            ->where('schedule_view.status', 2) // applies to both conditions
            ->orderByDesc('schedule_view.id')
            ->get();

        return $matchResults;
    }

    public function getTeamSeriesResults($seasonId, $homeId, $awayId)
    {
        $seriesDbName = $this->helper->getSeriesDBName($seasonId);

        $seriesResults = DB::table($seriesDbName.' as playoff_series')
            ->select(
                'playoff_series.id',
                'playoff_series.season_id',
                'playoff_series.conference_id',
                'playoff_series.round',
                'playoff_series.series_id',
                'playoff_series.home_team_id',
                'playoff_series.away_team_id',
                'playoff_series.race_to',
                'playoff_series.home_wins',
                'playoff_series.away_wins',
                'playoff_series.series_length',
                'playoff_series.status',
                'playoff_series.winner_team_id',
                'playoff_series.loser_team_id',
                'playoff_series.created_at',
                'playoff_series.updated_at',

                // Home team info
                'home.name as home_name',
                'home.acronym as home_acronym',
                'home.primary_color as home_primary_color',
                'home.secondary_color as home_secondary_color',

                // Away team info
                'away.name as away_name',
                'away.acronym as away_acronym',
                'away.primary_color as away_primary_color',
                'away.secondary_color as away_secondary_color',

                // Winner / loser
                'winner.name as winner_name',
                'winner.acronym as winner_acronym',
                'loser.name as loser_name',
                'loser.acronym as loser_acronym',

                // Series result string like "EAG wins 4-2"
                DB::raw("CONCAT(
                winner.acronym, ' wins ', 
                CASE WHEN winner_team_id = home_team_id THEN home_wins ELSE away_wins END,
                '-', 
                CASE WHEN loser_team_id = home_team_id THEN home_wins ELSE away_wins END
            ) as result_summary")
            )
            ->join('teams as home', 'playoff_series.home_team_id', '=', 'home.id')
            ->join('teams as away', 'playoff_series.away_team_id', '=', 'away.id')
            ->leftJoin('teams as winner', 'playoff_series.winner_team_id', '=', 'winner.id')
            ->leftJoin('teams as loser', 'playoff_series.loser_team_id', '=', 'loser.id')
            ->where(function ($query) use ($homeId, $awayId, $seasonId) {
                $query->where('season_id', '<=', $seasonId)
                    ->where('home_team_id', $homeId)
                    ->where('away_team_id', $awayId);
            })
            ->orWhere(function ($query) use ($homeId, $awayId, $seasonId) {
                $query->where('season_id', '<=', $seasonId)
                    ->where('home_team_id', $awayId)
                    ->where('away_team_id', $homeId);
            })
            ->orderByDesc('playoff_series.created_at')
            ->take(10)
            ->get();

        return $seriesResults;
    }

    public function teamInfo($request)
    {
        $teamId = $request->team_id;
        $seasonId = get_current_season_id();
        $previousSeasonId = $seasonId - 1;

        $teamInfo = self::getTeamInfo($teamId);
        $allTimeStats = self::getAllTimeStats($teamId);
        $finalsStats = self::getFinalsStats($teamId);
        $roundStats = self::getRoundStats($teamId);
        $playoffStats = self::getPlayoffStats($teamId);
        $gameStreaks = self::getTeamStreaks($teamId);
        $chemistry =  $this->chemistry->getChemistryCalculation($teamId,$seasonId,$previousSeasonId);
        $remainingCapSpace = $this->contract->getRemainingCapSpace($teamId);

        return response()->json([
            'teams' => $teamInfo,
            'allTimeStats' => $allTimeStats,
            'finalsStats' => $finalsStats,
            'roundStats' => $roundStats,
            'playoffStats' => $playoffStats,
            'streaks' => $gameStreaks,
            'chemistry' => $chemistry,
            'current_season_id' => $seasonId ?? 0,
            'remaining_cap_space' => $remainingCapSpace
        ]);
    }
    public function teamSeasonFinals($request)
    {
        $teamId = $request->team_id;
        $finalsSeasons = self::getFinalsSeasons($teamId);
        $finalsWinSeasons = self::getFinalsWinSeasons($teamId);

        return response()->json([
            'finalsSeasons' => $finalsSeasons,
            'finalsWinSeasons' => $finalsWinSeasons,
        ]);
    }
    public function teamSeasonStandings($request)
    {
        $teamId = $request->team_id;
        $topStandingsSeasons = self::getTopStandingsSeasons($teamId);
        $bottomStandingsSeasons = self::getBottomStandingsSeasons($teamId);
        $playOffAppearance = self::getPlayoffAppearance($teamId);

        return response()->json([
            'topStandingsSeasons' => $topStandingsSeasons,
            'bottomStandingsSeasons' => $bottomStandingsSeasons,
            'playOffAppearance' =>  $playOffAppearance,
        ]);
    }
    public function teamSeasonHistory($request)
    {
        $teamId = $request->team_id;
        $page = $request->page_num ?? 1;
        $itemsPerPage = $request->itemsperpage ?? 10;

        $teamSeasonHistory = $this->getSeasonHistory($teamId, $page, $itemsPerPage);

        return response()->json($teamSeasonHistory);
    }
    public function teamsTransactionHistory($request)
    {
        $teamId = $request->team_id;
        $page = $request->page_num ?? 1;
        $itemsPerPage = $request->itemsperpage ?? 10;

        $teamTransactionHistory = $this->getTransactionHistory($teamId, $page, $itemsPerPage);

        return response()->json($teamTransactionHistory);
    }

    private function getLatestSeasonHistory($teamId){
         // Fetch data from standings_snapshots with pagination
        $seasonHistoryLatest = DB::table('standings_view as standings_snapshots')
            ->select(
                'standings_snapshots.team_id',
                'standings_snapshots.team_name',
                'standings_snapshots.team_acronym',
                'standings_snapshots.conference_id',
                'standings_snapshots.conference_name',
                'standings_snapshots.wins',
                'standings_snapshots.losses',
                'standings_snapshots.total_home_score',
                'standings_snapshots.total_away_score',
                'standings_snapshots.home_ppg',
                'standings_snapshots.away_ppg',
                'standings_snapshots.score_difference',
                'standings_snapshots.season_id',
                'standings_snapshots.overall_rank',
                'standings_snapshots.conference_rank',
                'seasons.name as season_name',
                'seasons.status as season_status',
                DB::raw('CASE WHEN standings_snapshots.overall_rank <= CASE WHEN seasons.start_playoffs = 16 THEN 16 ELSE 32 END THEN TRUE ELSE FALSE END AS isPlayoffQualified'),
                DB::raw('MAX(schedules.id) as last_round_played_id')
            )
            ->join('seasons', 'seasons.id', '=', 'standings_snapshots.season_id')
            ->leftJoin('schedules_archives as schedules', function ($join) use ($teamId) {
                $join->on('schedules.season_id', '=', 'standings_snapshots.season_id')
                    ->where('schedules.status', 2) // only finished games
                    ->where(function ($query) use ($teamId) {
                        $query->where('schedules.home_id', '=', $teamId)
                            ->orWhere('schedules.away_id', '=', $teamId);
                    });
            })
            ->where('standings_snapshots.team_id', $teamId)
            ->orderBy('standings_snapshots.season_id', 'desc')
            ->get();

        // Process the collection and append round information
        return $seasonHistoryLatest;
    }
    private function getSeasonHistory($teamId, $page, $itemsPerPage)
    {
        // Calculate the offset for pagination
        $offset = ($page - 1) * $itemsPerPage;

        $allSeasonHistory = [];
        if($page == 1){
            $seasonHistoryLatest = $this->getLatestSeasonHistory($teamId);

            foreach ($seasonHistoryLatest as $season) {
                $roundInfo = $this->getLastRoundPlayed($season->last_round_played_id, $teamId);
                $season->coach_info = $this->getTeamCoachSeasonInfo($season->season_id, $teamId);
                $season->round_info = $roundInfo;

                $allSeasonHistory[] = $season;
            }
        }

        // Fetch data from standings_snapshots with pagination
        $seasonHistory = DB::table('standings_snapshots')
            ->select(
                'standings_snapshots.team_id',
                'standings_snapshots.team_name',
                'standings_snapshots.team_acronym',
                'standings_snapshots.conference_id',
                'standings_snapshots.conference_name',
                'standings_snapshots.wins',
                'standings_snapshots.losses',
                'standings_snapshots.total_home_score',
                'standings_snapshots.total_away_score',
                'standings_snapshots.home_ppg',
                'standings_snapshots.away_ppg',
                'standings_snapshots.score_difference',
                'standings_snapshots.season_id',
                'standings_snapshots.overall_rank',
                'standings_snapshots.conference_rank',
                'seasons.name as season_name',
                'seasons.status as season_status',
                DB::raw('CASE WHEN standings_snapshots.overall_rank <= CASE WHEN seasons.start_playoffs = 16 THEN 16 ELSE 32 END THEN TRUE ELSE FALSE END AS isPlayoffQualified'),
                DB::raw('MAX(schedules.id) as last_round_played_id')
            )
            ->join('seasons', 'seasons.id', '=', 'standings_snapshots.season_id')
            ->leftJoin('schedules_archives as schedules', function ($join) use ($teamId) {
                $join->on('schedules.season_id', '=', 'standings_snapshots.season_id')
                    ->where('schedules.status', 2) // only finished games
                    ->where(function ($query) use ($teamId) {
                        $query->where('schedules.home_id', '=', $teamId)
                            ->orWhere('schedules.away_id', '=', $teamId);
                    });
            })
            ->where('standings_snapshots.team_id', $teamId)
            ->groupBy(
                'standings_snapshots.id',
                'standings_snapshots.team_id',
                'standings_snapshots.team_name',
                'standings_snapshots.team_acronym',
                'standings_snapshots.conference_id',
                'standings_snapshots.conference_name',
                'standings_snapshots.wins',
                'standings_snapshots.losses',
                'standings_snapshots.total_home_score',
                'standings_snapshots.total_away_score',
                'standings_snapshots.home_ppg',
                'standings_snapshots.away_ppg',
                'standings_snapshots.score_difference',
                'standings_snapshots.season_id',
                'standings_snapshots.overall_rank',
                'standings_snapshots.conference_rank',
                'seasons.name',
                'seasons.status',
                'seasons.start_playoffs'
            )
            ->orderBy('standings_snapshots.season_id', 'desc')
            ->offset($offset)
            ->limit($itemsPerPage)
            ->get();

        // Process the collection and append round information
        foreach ($seasonHistory as $season) {
            $roundInfo = $this->getLastRoundPlayed($season->last_round_played_id, $teamId);
            $season->coach_info = $this->getTeamCoachSeasonInfo($season->season_id, $teamId);
            $season->round_info = $roundInfo;

            $allSeasonHistory[] = $season;
        }


        // Get the total number of records
        $totalItems = DB::table('standings_snapshots')
            ->where('team_id', $teamId)
            ->count();

        // Calculate the total number of pages
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        return [
            'history' => $allSeasonHistory,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'current_page' => $page,
            'total_pages' => $totalPages
        ];
    }

    private function getTeamCoachSeasonInfo($seasonId, $teamId)
    {
        // Fetch the team_season_info along with the coach's name for the given season
        $teamCoachInfo = DB::table('team_season_info')
            ->join('coaches', 'coaches.id', '=', 'team_season_info.coach_id')
            ->select(
                'team_season_info.team_id',
                'team_season_info.season_id',
                'team_season_info.coach_id',
                'team_season_info.coach_iq',
                'team_season_info.chemistry',
                'coaches.name as coach_name'
            )
            ->where('team_season_info.season_id', $seasonId)
            ->where('team_season_info.team_id', $teamId)
            ->first(); // Since a team should have one coach per season, we use `first()`

        // Return the team coach info
        return $teamCoachInfo;
    }

    private function getTransactionHistory($teamId, $page, $itemsPerPage)
    {
        // Calculate the offset for pagination
        $offset = ($page - 1) * $itemsPerPage;

        // Fetch transaction history related to the team (either from_team_id or to_team_id)
        $transactionQuery = DB::table('transactions')
            ->select(
                'transactions.id',
                'transactions.player_id',
                'transactions.season_id',
                'transactions.details',
                'transactions.from_team_id',
                'transactions.to_team_id',
                'transactions.status',
                'players.name as player_name',
                'from_team.name as from_team_name', // Name of the team that sent the player
                'to_team.name as to_team_name' // Name of the team that received the player
            )
            ->leftJoin('teams as from_team', 'transactions.from_team_id', '=', 'from_team.id')
            ->leftJoin('teams as to_team', 'transactions.to_team_id', '=', 'to_team.id')
            ->leftJoin('players', 'transactions.player_id', '=', 'players.id');

        // Apply team filtering based on status
        $transactionQuery->where(function ($query) use ($teamId) {
            $query->where('transactions.status', 'signed')
                ->where('transactions.to_team_id', '=', $teamId)
                ->orWhere(function ($q) use ($teamId) {
                    $q->where('transactions.status', '!=', 'signed')
                        ->where(function ($subQuery) use ($teamId) {
                            $subQuery->where('transactions.from_team_id', '=', $teamId)
                                ->orWhere('transactions.to_team_id', '=', $teamId);
                        });
                });
        });

        // Exclude "transfer" status
        $transactionQuery->where('transactions.status', '!=', 'transfer')
            ->where('transactions.status', '!=', 'claimed-via-waiver')
            ->where('transactions.status', '!=', 'role change');

        // Sorting
        $transactionQuery->orderBy('transactions.season_id', 'desc')
            ->orderBy('transactions.id', 'desc')
            ->offset($offset)
            ->limit($itemsPerPage);

        $transactionHistory = $transactionQuery->get();

        // Get the total number of transactions for the team
        $totalItemsQuery = DB::table('transactions');

        $totalItemsQuery->where(function ($query) use ($teamId) {
            $query->where('transactions.status', 'signed')
                ->where('transactions.to_team_id', '=', $teamId)
                ->orWhere(function ($q) use ($teamId) {
                    $q->where('transactions.status', '!=', 'signed')
                        ->where(function ($subQuery) use ($teamId) {
                            $subQuery->where('transactions.from_team_id', '=', $teamId)
                                ->orWhere('transactions.to_team_id', '=', $teamId);
                        });
                });
        });

        $totalItemsQuery->where('transactions.status', '!=', 'transfer');

        $totalItems = $totalItemsQuery->count();


        // Calculate the total number of pages
        $totalPages = ceil($totalItems / $itemsPerPage);

        return [
            'transactions' => $transactionHistory,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'current_page' => $page,
            'total_pages' => $totalPages
        ];
    }

    public function teamLastSeason($request)
    {
        $teamId = $request->team_id;

        $lastPlayInsRound1Season = self::getLastSeasonOfRound($teamId, 'play_ins_elims_round_1');
        $lastPlayInsRound2Season = self::getLastSeasonOfRound($teamId, 'play_ins_elims_round_2');
        $lastPlayInsFinalsSeason = self::getLastSeasonOfRound($teamId, 'play_ins_finals');
        $lastQuarterFinalSeason = self::getLastSeasonOfRound($teamId, 'quarter_finals');
        $lastSemiFinalSeason = self::getLastSeasonOfRound($teamId, 'semi_finals');
        $lastFinalSeason = self::getLastSeasonOfRound($teamId, 'finals');
        $lastRoundOf16Season = self::getLastSeasonOfRound($teamId, 'round_of_16');
        $lastRoundOf32Season = self::getLastSeasonOfRound($teamId, 'round_of_32');

        return response()->json([
            'lastPlayInsRound1Season' =>  $lastPlayInsRound1Season,
            'lastPlayInsRound2Season' =>  $lastPlayInsRound2Season,
            'lastPlayInsFinalsSeason' =>  $lastPlayInsFinalsSeason,
            'lastRoundOf32Season' =>  $lastRoundOf32Season,
            'lastRoundOf16Season' =>  $lastRoundOf16Season,
            'lastQuarterFinalSeason' => $lastQuarterFinalSeason,
            'lastSemiFinalSeason' => $lastSemiFinalSeason,
            'lastFinalSeason' => $lastFinalSeason,
        ]);
    }
    public function teamMatches($request)
    {
        $teamId = $request->team_id;

        $lastTenGames = self::getLastTenGames($teamId);

        return response()->json([
            'lastTenGames' => $lastTenGames,
        ]);
    }
    public function teamMatchesH2H($request)
    {
        $teamId = $request->team_id;

        $headToHeadBattles = self::headToHead($teamId);

        return response()->json([
            'headToHeadBattles' => $headToHeadBattles,
        ]);
    }
    public function teamRivals($request)
    {
        $teamId = $request->team_id;

        $winLossRecords = $this->getWinLossRecords($teamId);

        return  response()->json([
            'top_rivals' => $winLossRecords,
        ]);
    }
    
    private function getTeamInfo($teamId)
    {
        $teamData = DB::table('teams')
            ->join('conferences', 'teams.conference_id', '=', 'conferences.id')
            ->leftJoin('coaches', 'teams.coach_id', '=', 'coaches.id')
            ->join('team_reputation_view', 'teams.id', '=', 'team_reputation_view.team_id')
            ->select(
                'teams.id',
                'teams.name as team_name',
                'teams.acronym',
                'teams.city',
                DB::raw("CASE teams.market_size 
                        WHEN 1 THEN 'Small' 
                        WHEN 2 THEN 'Medium' 
                        WHEN 3 THEN 'Large' 
                        ELSE 'Unknown' 
                    END as market_size"),
                'teams.sponsor',
                'teams.description',
                'teams.primary_color',
                'teams.secondary_color',
                'conferences.name as conference_name',
                'coaches.id as coach_id',
                'coaches.name as coach_name',
                'coaches.winning_percentage as coach_winning',

                // From team_reputation_view
                'team_reputation_view.season_id',
                'team_reputation_view.wins',
                'team_reputation_view.chemistry',
                'team_reputation_view.prev_wins',
                'team_reputation_view.prev_rank',
                'team_reputation_view.prev_chemistry',
                'team_reputation_view.wins_diff',
                'team_reputation_view.streak_status',
                'team_reputation_view.rank_improvement',
                'team_reputation_view.chemistry_diff',
                'team_reputation_view.reputation_score',
                'team_reputation_view.estimated_fans'
            )
            ->where('teams.id', $teamId)
            ->first();

        return $teamData;
    }

    private function getTeamStreaks($teamId)
    {
        return DB::table('streak')
            ->where('team_id', $teamId)
            ->get();
    }


    private function getAllTimeStats($teamId)
    {
        return DB::table('standings_snapshots')
            ->where('team_id', $teamId)
            ->selectRaw('SUM(wins) AS all_time_wins, SUM(losses) AS all_time_losses')
            ->first();
    }

    private function getSeasonHistoryCount($teamId, $seasonId)
    {
        // Fetch data from standings_snapshots
        $seasonHistory = DB::table('standings_snapshots')
            ->select(
                'standings_snapshots.*',
                'seasons.name as season_name',
                'standings_snapshots.conference_championships as conference_championship',
                DB::raw('CASE WHEN standings_snapshots.overall_rank <= CASE WHEN seasons.start_playoffs = 16 THEN 16 ELSE 32 END THEN TRUE ELSE FALSE END AS isPlayoffQualified'),
                DB::raw('MAX(schedules.id) as last_round_played_id')
            )
            ->join('seasons', 'seasons.id', '=', 'standings_snapshots.season_id')
            ->leftJoin('schedules_archives as schedules', function ($join) use ($teamId) {
                $join->on('schedules.season_id', '=', 'standings_snapshots.season_id')
                    ->where(function ($query) use ($teamId) {
                        $query->where('schedules.home_id', '=', $teamId)
                            ->orWhere('schedules.away_id', '=', $teamId);
                    });
            })
            ->where('standings_snapshots.team_id', $teamId)
            ->groupBy(
                'standings_snapshots.id',
                'standings_snapshots.team_id',
                'standings_snapshots.team_name',
                'standings_snapshots.team_city',
                'standings_snapshots.primary_color',
                'standings_snapshots.secondary_color',
                'standings_snapshots.team_acronym',
                'standings_snapshots.conference_id',
                'standings_snapshots.conference_name',
                'standings_snapshots.season_id',
                'standings_snapshots.wins',
                'standings_snapshots.losses',
                'standings_snapshots.total_home_score',
                'standings_snapshots.total_away_score',
                'standings_snapshots.home_ppg',
                'standings_snapshots.away_ppg',
                'standings_snapshots.score_difference',
                'standings_snapshots.conference_rank',
                'standings_snapshots.overall_rank',
                'standings_snapshots.is_defending_champion',
                'standings_snapshots.chemistry',
                'standings_snapshots.last_playoff_season_name',
                'standings_snapshots.playoff_appearances',
                'standings_snapshots.finals_appearances',
                'standings_snapshots.conference_finals_appearances',
                'standings_snapshots.conference_championships',
                'standings_snapshots.championships',
                'standings_snapshots.streak_status',
                'standings_snapshots.last_5_games',
                'standings_snapshots.created_at',
                'standings_snapshots.updated_at',
                'seasons.name',
                'seasons.start_playoffs'
            )
            ->orderBy('standings_snapshots.season_id', 'desc')
            ->get();

        // Process the collection and append round information
        foreach ($seasonHistory as $season) {
            $roundInfo = $this->getLastRoundPlayed($season->last_round_played_id, $teamId);
            $season->round_info = $roundInfo;
        }

        // Count various conditions
        $playoffQualifiedCount = $seasonHistory->filter(function ($season) {
            return $season->isPlayoffQualified == 1;
        })->count();

        $overallRank1Count = $seasonHistory->filter(function ($season) {
            return $season->overall_rank == 1;
        })->count();

        $conferenceRank1Count = $seasonHistory->filter(function ($season) {
            return $season->conference_rank == 1;
        })->count();

        // Count the standings_snapshots where season_id = $seasonId to get the last team number
        $seasonCount = DB::table('standings_snapshots')
            ->where('season_id', $seasonId)
            ->count();

        // Count seasons where the team had the last overall rank
        $lastOverallRankCount = $seasonHistory->filter(function ($season) use ($teamId, $seasonCount) {
            return $season->team_id == $teamId && $season->overall_rank == $seasonCount;
        })->count();

        // Get the conference championships for the latest season in the collection
        $conferenceChampions = $seasonHistory->first()->conference_championship ?? 0;

        return [
            'playoffQualifiedCount' => $playoffQualifiedCount,
            'overallRank1Count' => $overallRank1Count,
            'conferenceRank1Count' => $conferenceRank1Count,
            'lastOverallRankCount' => $lastOverallRankCount,
            'conferenceChampions' => $conferenceChampions,
        ];
    }

    private function getLastRoundPlayed($lastRoundPlayedId, $teamId)
    {
        // Retrieve the round and result information based on last_round_played_id
        $roundInfo = DB::table('schedules_archives')
            ->select('round', 'home_score', 'away_score', 'home_id', 'away_id')
            ->where('id', $lastRoundPlayedId)
            ->first();

        if (!$roundInfo) {
            return null; // Handle case where no round info found
        }

        // Check if the round is finals
        if ($roundInfo->round) {
            // Determine winner based on team's perspective
            if ($roundInfo->home_id == $teamId) {
                // If the team is home and home_score > away_score, they win
                $teamWon = ($roundInfo->home_score > $roundInfo->away_score);
                $teamScore = $roundInfo->home_score;
                $opponentScore = $roundInfo->away_score;
                $opponentId = $roundInfo->away_id;
            } elseif ($roundInfo->away_id == $teamId) {
                // If the team is away and away_score > home_score, they win
                $teamWon = ($roundInfo->away_score > $roundInfo->home_score);
                $teamScore = $roundInfo->away_score;
                $opponentScore = $roundInfo->home_score;
                $opponentId = $roundInfo->home_id;
            } else {
                // If neither home_id nor away_id matches teamId, consider as not won
                $teamWon = false;
                $teamScore = null;
                $opponentScore = null;
                $opponentId = null;
            }

            // Fetch opponent's name
            $opponentName = null;
            if ($opponentId) {
                $opponentInfo = DB::table('teams')
                    ->select('name')
                    ->where('id', $opponentId)
                    ->first();
                if ($opponentInfo) {
                    $opponentName = $opponentInfo->name;
                }
            }

            return [
                'round' => $roundInfo->round,
                'won' => $teamWon,
                'score' => $teamScore,
                'opponent_id' => $opponentId,
                'opponent_name' => $opponentName,
                'opponent_score' => $opponentScore
            ];
        } else {
            return [
                'round' => $roundInfo->round,
                'won' => null, // Not applicable for non-finals rounds
                'score' => null, // Score not applicable for non-finals rounds
                'opponent_id' => null, // Opponent id not applicable for non-finals rounds
                'opponent_name' => null, // Opponent name not applicable for non-finals rounds
                'opponent_score' => null // Opponent score not applicable for non-finals rounds
            ];
        }
    }

    private function getFinalsStats(int $teamId)
    {
        $latest = DB::table('playoff_series')
            ->where('round', 'finals')
            ->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->selectRaw('SUM(CASE WHEN winner_team_id = ? THEN 1 ELSE 0 END) AS finals_wins', [$teamId])
            ->selectRaw('SUM(CASE WHEN loser_team_id = ? THEN 1 ELSE 0 END) AS finals_losses', [$teamId])
            ->selectRaw('COUNT(*) AS finals_appearances')
            ->first();

        $old = DB::table('playoff_series_archives')
            ->where('round', 'finals')
            ->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->selectRaw('SUM(CASE WHEN winner_team_id = ? THEN 1 ELSE 0 END) AS finals_wins', [$teamId])
            ->selectRaw('SUM(CASE WHEN loser_team_id = ? THEN 1 ELSE 0 END) AS finals_losses', [$teamId])
            ->selectRaw('COUNT(*) AS finals_appearances')
            ->first();

        $data = [
                'finals_wins' => $latest->finals_wins + $old->finals_wins,
                'finals_losses' => $latest->finals_losses + $old->finals_losses,
                'finals_appearances' => $latest->finals_appearances + $old->finals_appearances,
            ];

        return $data;
    }

    private function getRoundStats($teamId)
    {
        return DB::table('schedules_archives')
            ->where(function ($query) use ($teamId) {
                $query->where('away_id', $teamId)
                    ->orWhere('home_id', $teamId);
            })
            ->selectRaw('
                COUNT(CASE WHEN round = "semi_finals" THEN 1 END) AS semi_final_appearances,
                COUNT(CASE WHEN round = "quarter_finals" THEN 1 END) AS quarter_final_appearances,
                COUNT(CASE WHEN round = "round_of_16" THEN 1 END) AS round_of_16_appearances,
                COUNT(CASE WHEN round = "round_of_32" THEN 1 END) AS round_of_32_appearances,
                COUNT(CASE WHEN round IN ("play_ins_elims_round_1", "play_ins_elims_round_2","play_ins_finals") THEN 1 END) AS play_in_appearances
            ')
            ->first();
    }

    private function getPlayoffStats($teamId)
    {
        return DB::table('schedules_archives as schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('away_id', $teamId)
                    ->orWhere('home_id', $teamId);
            })
            ->whereIn('round', [
                'play_ins_elims_round_1',
                'play_ins_elims_round_2',
                'play_ins_finals',
                'round_of_16',
                'quarter_finals',
                'semi_finals',
                'interconference_finals',
                'interconference_semi_finals',
                'finals'
            ])
            ->join('seasons', 'schedules.season_id', '=', 'seasons.id')
            ->selectRaw('SUM(CASE WHEN (away_id = ? AND away_score > home_score OR home_id = ? AND home_score > away_score) THEN 1 ELSE 0 END) AS playoff_wins', [$teamId, $teamId])
            ->selectRaw('SUM(CASE WHEN (away_id = ? AND away_score < home_score OR home_id = ? AND home_score < away_score) THEN 1 ELSE 0 END) AS playoff_losses', [$teamId, $teamId])
            ->selectRaw('COUNT(CASE WHEN (round = "round_of_16" AND seasons.start_playoffs = 16) OR (round = "round_of_32" AND seasons.start_playoffs = 32) THEN 1 END) AS playoff_appearances')
            // Add condition for play-in rounds (teams ranked 7th-10th)
            ->selectRaw('COUNT(CASE WHEN round IN ("play_ins_elims_round_1", "play_ins_elims_round_2") THEN 1 END) AS play_in_appearances')
            ->first();
    }


    private function getLastTenGames($teamId)
    {
        return DB::table('schedules')
            ->select('schedules.*', 'home_teams.name as home_team_name', 'away_teams.name as away_team_name')
            ->join('teams as home_teams', 'schedules.home_id', '=', 'home_teams.id')
            ->join('teams as away_teams', 'schedules.away_id', '=', 'away_teams.id')
            ->where(function ($query) use ($teamId) {
                $query->where('schedules.away_id', $teamId)
                    ->orWhere('schedules.home_id', $teamId);
            })
            ->where(function ($query) {
                $query->where('schedules.away_score', '>', 0)
                    ->orWhere('schedules.home_score', '>', 0);
            })
            ->orderByDesc('schedules.id')
            ->limit(10)
            ->get()
            ->map(function ($game) use ($teamId) {
                $status = '';

                if ($game->home_id == $teamId) {
                    $status = $game->home_score > $game->away_score ? 'Win' : 'Loss';
                } elseif ($game->away_id == $teamId) {
                    $status = $game->away_score > $game->home_score ? 'Win' : 'Loss';
                }

                return (object) array_merge((array) $game, ['status' => $status]);
            });
    }

    private function getLastSeasonOfRound($teamId, $round)
    {
        $lastSeasonId = DB::table('schedules_archives')
            ->where(function ($query) use ($teamId) {
                $query->where('away_id', $teamId)
                    ->orWhere('home_id', $teamId);
            })
            ->where('round', $round)
            ->orderByDesc('id')
            ->value('season_id');

        return DB::table('seasons')
            ->where('id', $lastSeasonId)
            ->value('name');
    }
    private function getTopStandingsSeasons($teamId)
    {
        return DB::table('seasons')
            ->where('champion_id', $teamId) // Filter for champion_id == teamId
            ->orderByDesc('id') // Order by season ID in descending order
            ->pluck('name'); // Retrieve the season names
    }
    private function getBottomStandingsSeasons($teamId)
    {
        return DB::table('seasons')
            ->where('weakest_id', $teamId) // Filter for champion_id == teamId
            ->orderByDesc('id') // Order by season ID in descending order
            ->pluck('name'); // Retrieve the season names
    }
    private function getPlayoffAppearance($teamId)
    {
        return DB::table('schedules_archives as schedules')
            ->where(function ($query) use ($teamId) {
                // Check if the team is involved in any game (either as home or away)
                $query->where('away_id', $teamId)
                    ->orWhere('home_id', $teamId);
            })
            ->join('seasons', 'schedules.season_id', '=', 'seasons.id')
            ->where(function ($query) {
                // Check for playoff rounds based on the season's start playoffs
                $query->where(function ($subQuery) {
                    // Playoff round of 16 if start playoffs is 16
                    $subQuery->where('seasons.start_playoffs', '=', 16)
                        ->where('schedules.round', '=', 'round_of_16');
                })
                    ->orWhere(function ($subQuery) {
                        // Playoff round of 32 if start playoffs is 32
                        $subQuery->where('seasons.start_playoffs', '=', 32)
                            ->where('schedules.round', '=', 'round_of_32');
                    })
                    // Add condition to check for the play-in rounds
                    ->orWhere(function ($subQuery) {
                        // Check if the team is part of play-in rounds 1 or 2
                        $subQuery->whereIn('schedules.round', [
                            'play_ins_elims_round_1',
                            'play_ins_elims_round_2'
                        ]);
                    });
            })
            ->orderByDesc('seasons.id')  // Ensure to get the most recent season first
            ->distinct()  // Ensure that we only get unique season names
            ->pluck('seasons.name');  // Return the unique season names where the team participated in the playoffs
    }

    private function getFinalsSeasons($teamId)
    {
        return DB::table('schedules_archives as schedules')
            ->where('round', 'finals')
            ->where(function ($query) use ($teamId) {
                $query->where('away_id', $teamId)
                    ->orWhere('home_id', $teamId);
            })
            ->join('seasons', 'schedules.season_id', '=', 'seasons.id')
            ->distinct('season.id')
            ->orderByDesc('seasons.id') // Order by season ID in descending order
            ->pluck('seasons.name');
    }
    private function getFinalsWinSeasons($teamId)
    {
        return DB::table('seasons')
            ->where('finals_winner_id', $teamId)
            ->pluck('seasons.name');
    }

    private function headToHead($teamId)
    {
        return DB::table('schedules_archives as schedules')
            ->select(
                DB::raw('CASE WHEN schedules.away_id = ' . $teamId . ' THEN home_team.name ELSE away_team.name END as opponent_name'),
                DB::raw('(SUM(CASE WHEN schedules.home_id = ' . $teamId . ' THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' THEN 1 ELSE 0 END)) as total_games'),
                DB::raw('SUM(CASE WHEN schedules.home_id = ' . $teamId . ' AND schedules.home_score > schedules.away_score THEN 1 ELSE 0 END) as home_wins'),
                DB::raw('SUM(CASE WHEN schedules.home_id = ' . $teamId . ' AND schedules.home_score < schedules.away_score THEN 1 ELSE 0 END) as home_losses'),
                DB::raw('SUM(CASE WHEN schedules.away_id = ' . $teamId . ' AND schedules.away_score > schedules.home_score THEN 1 ELSE 0 END) as away_wins'),
                DB::raw('SUM(CASE WHEN schedules.away_id = ' . $teamId . ' AND schedules.away_score < schedules.home_score THEN 1 ELSE 0 END) as away_losses'),
                DB::raw('ROUND(((SUM(CASE WHEN schedules.home_id = ' . $teamId . ' AND schedules.home_score > schedules.away_score THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' AND schedules.away_score > schedules.home_score THEN 1 ELSE 0 END)) / (SUM(CASE WHEN schedules.home_id = ' . $teamId . ' THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' THEN 1 ELSE 0 END))) * 100, 2) as home_win_percentage'),
                DB::raw('ROUND(((SUM(CASE WHEN schedules.home_id = ' . $teamId . ' AND schedules.home_score < schedules.away_score THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' AND schedules.away_score < schedules.home_score THEN 1 ELSE 0 END)) / (SUM(CASE WHEN schedules.home_id = ' . $teamId . ' THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' THEN 1 ELSE 0 END))) * 100, 2) as home_loss_percentage'),
                DB::raw('ROUND(((SUM(CASE WHEN schedules.away_id = ' . $teamId . ' AND schedules.away_score > schedules.home_score THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.home_id = ' . $teamId . ' AND schedules.home_score > schedules.away_score THEN 1 ELSE 0 END)) / (SUM(CASE WHEN schedules.away_id = ' . $teamId . ' THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.home_id = ' . $teamId . ' THEN 1 ELSE 0 END))) * 100, 2) as away_win_percentage'),
                DB::raw('ROUND(((SUM(CASE WHEN schedules.away_id = ' . $teamId . ' AND schedules.away_score < schedules.home_score THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.home_id = ' . $teamId . ' AND schedules.home_score < schedules.away_score THEN 1 ELSE 0 END)) / (SUM(CASE WHEN schedules.away_id = ' . $teamId . ' THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.home_id = ' . $teamId . ' THEN 1 ELSE 0 END))) * 100, 2) as away_loss_percentage'),
                DB::raw('ROUND((((SUM(CASE WHEN schedules.home_id = ' . $teamId . ' AND schedules.home_score > schedules.away_score THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' AND schedules.away_score > schedules.home_score THEN 1 ELSE 0 END)) + (SUM(CASE WHEN schedules.home_id = ' . $teamId . ' AND schedules.home_score < schedules.away_score THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' AND schedules.away_score < schedules.home_score THEN 1 ELSE 0 END))) / (SUM(CASE WHEN schedules.home_id = ' . $teamId . ' THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' THEN 1 ELSE 0 END))) * 100, 2) as overall_win_percentage')
            )
            ->join('teams as home_team', 'schedules.home_id', '=', 'home_team.id')
            ->join('teams as away_team', 'schedules.away_id', '=', 'away_team.id')
            ->where('schedules.home_id', $teamId)
            ->orWhere('schedules.away_id', $teamId)
            ->groupBy('opponent_name')
            ->get();
    }

    private function getTopRivals1($teamId)
    {
        return DB::table('schedules_archives as schedules')
            ->select(
                DB::raw('CASE WHEN schedules.away_id = ' . $teamId . ' THEN home_team.name ELSE away_team.name END as opponent_name'),
                DB::raw('(SUM(CASE WHEN schedules.home_id = ' . $teamId . ' THEN 1 ELSE 0 END) + SUM(CASE WHEN schedules.away_id = ' . $teamId . ' THEN 1 ELSE 0 END)) as total_games')
            )
            ->join('teams as home_team', 'schedules.home_id', '=', 'home_team.id')
            ->join('teams as away_team', 'schedules.away_id', '=', 'away_team.id')
            ->where('schedules.home_id', $teamId)
            ->orWhere('schedules.away_id', $teamId)
            ->groupBy('opponent_name')
            ->orderBy('total_games', 'desc') // Order by total games played against each opponent in descending order
            ->limit(4) // Limit to top 3 rivals
            ->pluck('opponent_name');
    }


    private function getWinLossRecords($teamId)
    {
        // Fetch top 5 head-to-head records with the highest total wins + losses
        $results = DB::table('head_to_head')
            ->join('teams as team', 'team.id', '=', 'head_to_head.team_id') // Join to get team's name
            ->join('teams as opponent', 'opponent.id', '=', 'head_to_head.opponent_id') // Join to get opponent's name
            ->where('head_to_head.team_id', $teamId)
            ->select(
                'head_to_head.team_id',
                'team.name as team_name', // Fetch team name
                'head_to_head.opponent_id',
                'opponent.name as opponent_name', // Fetch opponent name
                'head_to_head.wins',
                'head_to_head.losses',
                DB::raw('(head_to_head.wins + head_to_head.losses) as total_games') // Calculate total games played
            )
            ->orderByDesc('total_games') // Sort by total games (wins + losses) in descending order
            ->limit(5) // Get only the top 5 records
            ->get();

        $records = [];

        foreach ($results as $record) {
            $records[] = [
                'team_id' => $record->team_id,
                'team_name' => $record->team_name, // Include team name
                'opponent_id' => $record->opponent_id,
                'opponent_name' => $record->opponent_name, // Include opponent name
                'wins' => $record->wins,
                'losses' => $record->losses,
                'total_games' => $record->total_games, // Show total games played
                'home_id' => $record->wins > $record->losses ? $teamId : null,
                'away_id' => $record->wins < $record->losses ? $teamId : null
            ];
        }

        return $records;
    }


    public static function countTeamOnePicksAndCheckChampion($request)
    {   
        // If validation passes, the $teamId is valid
        $teamId = $request->team_id;

        // Get the most recent season (the latest season by id)
        $latestSeasonId = get_current_season_id();

        // If season_id is greater than 0, override the latest season with the provided season_id
        if ((int) $request->season_id > 0) {
            $latestSeasonId = (int) $request->season_id;  // Make sure this is an integer
        }

        if (!$latestSeasonId) {
            return [
                'team_one_pick_count' => 0,
                'is_defending_champion' => false,
                'is_weakest' => false,
                'finals_mvp_count' => 0,
                'overall_mvp_count' => 0,
                'dpos_count' => 0,
                'ros_count' => 0,
                'is_conference_champion' => false,
                'is_finals_champion' => false,
                'is_finalist' => false,
                'overall_rank' => null,
            ];
        }

        // Get the previous season by selecting the season with id = latestSeasonId - 1
        $previousSeason = DB::table('seasons')
            ->where('id', $latestSeasonId - 1)
            ->first();

        if (!$previousSeason) {
            return [
                'team_one_pick_count' => 0,
                'is_defending_champion' => false,
                'is_weakest' => false,
                'finals_mvp_count' => 0,
                'overall_mvp_count' => 0,
                'dpos_count' => 0,
                'ros_count' => 0,
                'is_conference_champion' => false,
                'is_finals_champion' => false,
                'is_finalist' => false,
                'overall_rank' => null,
            ];
        }

        // Check if the team is the defending champion in the previous season
        $isDefendingChampion = DB::table('seasons')
            ->where('champion_id', $teamId)
            ->where('id', $previousSeason->id)  // Check for the previous season by id
            ->exists();

        // Count the number of team #1 picks where draft_status ends with 'R1 P1'
        $teamOnePickCount = DB::table('player_season_stats_archives as player_season_stats')
            ->join('players', 'players.id', '=', 'player_season_stats.player_id') // Joining player_season_stats with players table
            ->where('player_season_stats.team_id', $teamId) // Corrected 'team_id' reference
            ->where('player_season_stats.season_id', $latestSeasonId) // Filter by season ID
            ->where('players.draft_status', 'like', '%R1 P1') // Check for 'R1 P1' draft status
            ->count();


        // Check if the team is considered the weakest in the previous season
        $isWeakest = DB::table('seasons')
            ->where('weakest_id', $teamId)
            ->where('id', $previousSeason->id)  // Check for the previous season by id
            ->exists();

        // Count how many times the team has been the Finals MVP
        $finalsMvpCount = DB::table('seasons')
            ->join('player_season_stats_archives as player_season_stats', 'player_season_stats.player_id', '=', 'seasons.finals_mvp_id')  // Join on MVP player_id
            ->where('player_season_stats.team_id', $teamId)  // Ensure player was on the given team
            ->where('player_season_stats.season_id', $latestSeasonId)  // Ensure the player played in the current season
            ->count();

        $seasonMvpCount = DB::table('season_awards')
            ->join('player_season_stats_archives as player_season_stats', 'player_season_stats.player_id', '=', 'season_awards.player_id')  // Join on MVP player_id
            ->where('player_season_stats.team_id', $teamId)  // Ensure player was on the given team
            ->where('player_season_stats.season_id', $latestSeasonId)  // Ensure the player played in the current season
            ->where('season_awards.award_name', 'Best Overall Player')  // Ensure the player played in the current season
            ->count();

        $defensivePlayerOfTheSeasonCount = DB::table('season_awards')
            ->join('player_season_stats_archives as player_season_stats', 'player_season_stats.player_id', '=', 'season_awards.player_id')  // Join on MVP player_id
            ->where('player_season_stats.team_id', $teamId)  // Ensure player was on the given team
            ->where('player_season_stats.season_id', $latestSeasonId)  // Ensure the player played in the current season
            ->where('season_awards.award_name', 'Best Defensive Player')  // Ensure the player played in the current season
            ->count();

        $rookiePlayerOfTheSeasonCount = DB::table('season_awards')
            ->join('player_season_stats_archives as player_season_stats', 'player_season_stats.player_id', '=', 'season_awards.player_id')  // Join on MVP player_id
            ->where('player_season_stats.team_id', $teamId)  // Ensure player was on the given team
            ->where('player_season_stats.season_id', $latestSeasonId)  // Ensure the player played in the current season
            ->where('season_awards.award_name', 'Rookie of the Season')  // Ensure the player played in the current season
            ->count();
        // Check if the team is a conference champion in any conference (West, East, North, South)
        $isConferenceChampion = DB::table('seasons')
            ->where(function ($query) use ($teamId) {
                $query->where('west_champion_id', $teamId)
                    ->orWhere('east_champion_id', $teamId)
                    ->orWhere('north_champion_id', $teamId)
                    ->orWhere('south_champion_id', $teamId);
            })
            ->where('id', $previousSeason->id)  // Check for the previous season by id
            ->exists();

        // Check if the team is the finals champion
        $isFinalsChampion = DB::table('seasons')
            ->where('finals_winner_id', $teamId)
            ->where('id', $previousSeason->id)  // Check for the previous season by id
            ->exists();

        // Check if the team is a finalist (winner or loser in the finals)
        $isFinalist = DB::table('seasons')
            ->where(function ($query) use ($teamId) {
                $query->where('finals_winner_id', $teamId)
                    ->orWhere('finals_loser_id', $teamId);
            })
            ->where('id', $previousSeason->id)  // Check for the previous season by id
            ->exists();

        // Check the conference overall rank from the standings_view for the previous season
        $conferenceRank = DB::table('standings_view')
            ->where('team_id', $teamId)
            ->where('season_id', $previousSeason->id)
            ->value('conference_rank');  // Assuming 'conference_rank' is a column in 'standings_view'

        return [
            'team_one_pick_count' => $teamOnePickCount,
            'is_defending_champion' => $isDefendingChampion,
            'is_weakest' => $isWeakest,
            'finals_mvp_count' => $finalsMvpCount,
            'is_conference_champion' => $isConferenceChampion,
            'overall_mvp_count' => $seasonMvpCount,
            'dpos_count' => $defensivePlayerOfTheSeasonCount,
            'ros_count' => $rookiePlayerOfTheSeasonCount,
            'is_finals_champion' => $isFinalsChampion,
            'is_finalist' => $isFinalist,
            'prev_conference_rank' => $conferenceRank,
            'prev_season' => $previousSeason->id,
            'curr_season' => $latestSeasonId,
        ];
    }

    // Store a newly created resource in storage.
    public function add($request)
    {

        // Generate random color hex codes for primary and secondary colors
        // $primaryColor = $this->generateRandomColor();
        // $secondaryColor = $this->generateRandomColor($primaryColor); // Ensure it’s different from primary

        // Merge the colors with the request data
        $data = $request->all();
        // $data['primary_color'] = $primaryColor;
        // $data['secondary_color'] = $secondaryColor;

        // Create the new team with the randomized colors
        Teams::create($data);

        return redirect()->route('teams.index');
    }

    /**
     * Generate a random hex color.
     * If a different color is needed, a second parameter is passed to ensure it's not the same.
     */
    private function generateRandomColor($excludeColor = null)
    {
        do {
            // Generate a random hex color (e.g., #FF5733)
            $color = sprintf('%06X', mt_rand(0, 0xFFFFFF));
        } while ($color === $excludeColor); // Ensure the generated color is not the same as the excluded one

        return $color;
    }

    // Update the specified resource in storage.
    public function update($request)
    {
    
        $team = Teams::findOrFail($request->id);
        $team->update($request->all());

        return redirect()->route('teams.index');
    }

    // Remove the specified resource from storage.
    public function delete($request)
    {
        
        $team = Teams::findOrFail($request->team_id);
        $team->delete();

        return redirect()->route('teams.index');
    }

    public function getTeamsByConference($request)
    {
        $conferenceId = $request->conference_id;

        // Validate if conference_id is provided
        if (!$conferenceId) {
            return response()->json(['error' => 'conference_id is required'], 400);
        }

        $teams = DB::table('teams')
            ->select('id', 'name')
            ->where('conference_id', $conferenceId)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($teams);
    }

    public function listTeamRoster($request)
    {
        
        $teamId = $request->team_id;
        $seasonId = $request->season_id;


        $remainingCapSpace = $this->contract->getRemainingCapSpace($teamId);

        // Initialize an array to hold player stats
        $playerStats = [];
        $latestSeasonId = get_current_season_id();

        $isLatestSeason = ($seasonId == $latestSeasonId);
        // Fetch the season status
        $seasonStatus = DB::table('seasons')->where('id', $seasonId)->value('status');

        $seasonStatsDBName = $this->helper->getSeasonStatsDBName($seasonId);

        // Fetch player stats for the given team_id and season_id
        $playerStatsData = DB::table($seasonStatsDBName)
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->get();

        if (count($playerStatsData) > 0) {
            foreach ($playerStatsData as $stats) {
                // Fetch the player with playoff appearances and awards
                $player = DB::table('players')
                    ->select(
                        'players.*',
                        'teams.acronym as drafted_team',
                        'seasons.name as draft_class',
                        'player_playoff_appearances.conference_championships_won',
                        'player_playoff_appearances.championships_won',
                        'player_playoff_appearances.total_playoff_series_appearances',
                        'player_game_highs.all_star_count',
                        DB::raw('(SELECT COUNT(*) FROM season_awards WHERE season_awards.player_id = players.id) as awards_won')
                    )
                    ->leftJoin('seasons', 'players.draft_id', '=', 'seasons.id')
                    ->leftJoin('player_game_highs', 'player_game_highs.player_id', '=', 'players.id')
                    ->leftJoin('player_playoff_appearances', 'players.id', '=', 'player_playoff_appearances.player_id')
                    ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id')
                    ->where('players.id', $stats->player_id)
                    ->first();

                if ($player) {
                    // Count the number of games played for the player
                    // $gamesPlayed = DB::table('player_game_stats')
                    //     ->where('player_id', $player->id)
                    //     ->where('team_id', $teamId)
                    //     ->where('season_id', $seasonId)
                    //     ->where('minutes', '>=', 0)
                    //     ->count();

                    $latestRatings = DB::table('player_ratings')
                        ->where('player_id', $player->id)
                        ->where('season_id', $seasonId - 1)
                        ->value('overall_rating') ??  75;

                    $prevRatings = ($seasonId == 1) ? $player->overall_rating  :  DB::table('player_ratings')
                        ->where('player_id', $player->id)
                        ->where('season_id', $seasonId - 2)
                        ->value('overall_rating') ?? 75;

                    $latestRatings = ($seasonId < 3 || $isLatestSeason) ? $player->overall_rating : $latestRatings;


                    $prevRatings = ($seasonId == 1) ? $player->overall_rating : $prevRatings;

                    $hasImproved = $this->helper->hasImproved($latestRatings, $prevRatings);
                    // $hasImproved = 'Latest: '.$latestRatings.' - Prev: '.$prevRatings;                  // Count seasons played with the team
                    $seasonsPlayedWithTeam = DB::table('player_season_stats_archives')
                        ->select(DB::raw('DISTINCT season_id, team_id'))
                        ->where('player_id', $player->id)
                        ->where('team_id', $teamId)
                        ->where('season_id', '<=', $seasonId)
                        ->get()
                        ->count() + 1;

                    $totalSeasonsPlayed = DB::table('player_season_stats_archives')
                        ->where('player_id', $player->id)
                        ->where('season_id', '<=', $seasonId)
                        ->distinct('season_id')
                        ->count('season_id') + 1;

                    $playerStatus = $player->team_id == $teamId ? ($player->is_active ? 1 : 0) : 2;
                    $playerCUtfromFinalRoster = ($player->team_id != $teamId && $player->is_active && $stats->total_games_played == 0) ? 1 : 0;
                    // Add player stats to the array
                    //if ($playerCUtfromFinalRoster) continue;
                    $playerRole = $player->is_reserved ? 'reserved' : $stats->role;

                    $playerStats[] = [
                        'player_id' => $player->id,
                        'name' => $player->name,
                        'position' => $player->position,
                        'age' => $player->age,
                        'role' => $playerRole,
                        'is_active' => $player->is_active,
                        'morale' => $player->morale,
                        'all_star_count' => $player->all_star_count,
                        'hardship_contract' => $player->hardship_contract,
                        'salary' => '₱'.number_format($player->salary,2,'.',','),
                        'contract_type' => $player->contract_type,
                        'injury_recovery_games' => $player->injury_recovery_games,
                        'is_rookie' => $player->is_rookie,
                        'is_injured' => $player->is_injured,
                        'is_reserved' => $player->is_reserved,
                        'injury_type' => $player->injury_type,
                        'contract_years' => $player->contract_years,
                        'retirement_age' => $player->retirement_age,
                        'drafted_team' => $player->drafted_team,
                        'draft_id' => $player->draft_id,
                        'draft_class' => $player->draft_class,
                        'draft_status' => $player->draft_status,
                        'overall_rating' => $latestRatings,
                        'potential_rating' => $player->potential_rating,
                        'potential_status' => $player->potential_rating == $latestRatings ? 'MAX' : '-',
                        'status' => $playerStatus,
                        'is_cut' => $playerCUtfromFinalRoster,
                        'has_improved' => $hasImproved,
                        'average_minutes_per_game' => (float)$stats->avg_minutes_per_game,
                        'average_points_per_game' => (float)$stats->avg_points_per_game,
                        'average_rebounds_per_game' => (float)$stats->avg_rebounds_per_game,
                        'average_assists_per_game' => (float)$stats->avg_assists_per_game,
                        'average_steals_per_game' => (float)$stats->avg_steals_per_game,
                        'average_blocks_per_game' => (float)$stats->avg_blocks_per_game,
                        'average_turnovers_per_game' => (float)$stats->avg_turnovers_per_game,
                        'average_fouls_per_game' => (float)$stats->avg_fouls_per_game,
                        'bpg_game_leader' => (float)$stats->bpg_game_leader,
                        'effeciency' => (float)$stats->eff,
                        'field_goal_percentage' => (float)$stats->field_goal_percentage,
                        'three_point_percentage' => (float)$stats->three_point_percentage,
                        'two_point_percentage' => (float)$stats->two_point_percentage,
                        'free_throw_percentage' => (float)$stats->free_throw_percentage,
                        'team_total_games' => (float)$stats->total_games,
                        'games_played' => (float)$stats->total_games_played,
                        'games_started' => (float)$stats->total_games_started,
                        'per_game_score' => (float)$stats->per,
                        'total_score' => 0,
                        'combined_score' => 0,
                        'seasons_played_with_team' => $seasonsPlayedWithTeam,
                        'total_seasons_played' => $totalSeasonsPlayed,
                        'latest_season' => $latestSeasonId,
                        'playoff_appearances' => (int)$player->total_playoff_series_appearances,
                        'conference_championships_won' => (int)$player->conference_championships_won,
                        'championships_won' => (int)$player->championships_won,
                        'awards_won' => (int)$player->awards_won,
                        'player_valuation' => (int)$stats->player_valuation,
                        'is_latest' => $isLatestSeason,
                    ];
                }
            }
        } else {
            // Fetch players from the players table and set all stats to zero
            $players = DB::table('players')
                ->select(
                    'players.*',
                    'teams.acronym as drafted_team',
                    'seasons.name as draft_class',
                    'player_playoff_appearances.conference_championships_won',
                    'player_playoff_appearances.championships_won',
                    'player_game_highs.all_star_count',
                    'player_playoff_appearances.total_playoff_series_appearances',
                    DB::raw('(SELECT COUNT(*) FROM season_awards WHERE season_awards.player_id = players.id) as awards_won')
                )
                ->leftJoin('seasons', 'players.draft_id', '=', 'seasons.id')
                ->leftJoin('player_game_highs', 'player_game_highs.player_id', '=', 'players.id')
                ->leftJoin('player_playoff_appearances', 'players.id', '=', 'player_playoff_appearances.player_id')
                ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id')
                ->where('team_id', $teamId)
                ->get();

            // Fetch average statistics for players
            $playerGameStats = DB::table('player_game_stats')
                ->select(
                    'player_id',
                    DB::raw('COUNT(CASE WHEN minutes > 0 THEN 1 END) as games_played'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN minutes ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_minutes'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN points ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_points'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN rebounds ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_rebounds'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN assists ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_assists'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN steals ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_steals'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN blocks ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_blocks'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN turnovers ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_turnovers'),
                    DB::raw('SUM(CASE WHEN minutes > 0 THEN fouls ELSE 0 END) / NULLIF(COUNT(CASE WHEN minutes > 0 THEN 1 END), 0) as avg_fouls'),
                    DB::raw('SUM(CASE WHEN eff > 0 THEN eff ELSE 0 END) / NULLIF(COUNT(CASE WHEN eff > 0 THEN 1 END), 0) as avg_eff'),
                    DB::raw('SUM(CASE WHEN field_goal_percentage > 0 THEN field_goal_percentage ELSE 0 END) / NULLIF(COUNT(CASE WHEN field_goal_percentage > 0 THEN 1 END), 0) as field_goal_percentage'),
                    DB::raw('SUM(CASE WHEN per > 0 THEN per ELSE 0 END) / NULLIF(COUNT(CASE WHEN per > 0 THEN 1 END), 0) as per_game_score')
                )
                ->where('season_id', $seasonId)
                ->groupBy('player_id')
                ->get()
                ->keyBy('player_id');

            foreach ($players as $player) {
                $playerId = $player->id;

                $totalSeasonsPlayed = DB::table('player_season_stats_archives')
                    ->where('player_id', $playerId)
                    ->distinct('season_id')
                    ->count();

                $seasonsPlayedWithTeam = DB::table('player_season_stats_archives')
                    ->where('player_id', $player->id)
                    ->where('team_id', $teamId)
                    ->count('team_id') + 1;

                $totalSeasonGameSchedule = $this->helper->getScheduleCount($teamId, $seasonId);

                // Default values in case there are no stats
                $stats = [
                    'average_minutes_per_game' => (float)0,
                    'average_points_per_game' => (float)0,
                    'average_rebounds_per_game' => (float)0,
                    'average_assists_per_game' => (float)0,
                    'average_steals_per_game' => (float)0,
                    'average_blocks_per_game' => (float)0,
                    'average_turnovers_per_game' => (float)0,
                    'average_fouls_per_game' => (float)0,
                    'bpg_game_leader' => (float)0,
                    'field_goal_percentage' => (float)0,
                    'three_point_percentage' => (float)0,
                    'two_point_percentage' => (float)0,
                    'free_throw_percentage' => (float)0,
                    'per_game_score' => (float)0,
                    'total_score' => (float)0,
                    'combined_score' => (float)0,
                    'seasons_played_with_team' => $seasonsPlayedWithTeam,
                    'team_total_games' => (float)$totalSeasonGameSchedule,
                    'total_seasons_played' => $totalSeasonsPlayed + 1,
                    'average_eff' => (float)0,
                    'games_played' => (int)0,
                    'games_started' => (int)0,
                ];

                // If there are stats for this player, update values
                if (isset($playerGameStats[$playerId])) {
                    $stats = [
                        'average_minutes_per_game' => (float)$playerGameStats[$playerId]->avg_minutes,
                        'average_points_per_game' => (float)$playerGameStats[$playerId]->avg_points,
                        'average_rebounds_per_game' => (float)$playerGameStats[$playerId]->avg_rebounds,
                        'average_assists_per_game' => (float)$playerGameStats[$playerId]->avg_assists,
                        'average_steals_per_game' => (float)$playerGameStats[$playerId]->avg_steals,
                        'average_blocks_per_game' => (float)$playerGameStats[$playerId]->avg_blocks,
                        'average_turnovers_per_game' => (float)$playerGameStats[$playerId]->avg_turnovers,
                        'average_fouls_per_game' => (float)$playerGameStats[$playerId]->avg_fouls,
                        'bpg_game_leader' => (float)0,
                        'field_goal_percentage' => (float)$playerGameStats[$playerId]->field_goal_percentage,
                        'per_game_score' => (float)$playerGameStats[$playerId]->per_game_score,
                        'games_played' => (int)$playerGameStats[$playerId]->games_played,
                        'games_started' => (int)$playerGameStats[$playerId]->games_started,
                        'average_eff' => (float)$playerGameStats[$playerId]->avg_eff,
                    ];
                }

                // Only include players with games played > 0 if season status is 11
                if ($seasonStatus != 11 || $stats['games_played'] > 0) {
                    $playerStats[] = [
                        'player_id' => $playerId,
                        'name' => $player->name,
                        'position' => $player->position,
                        'age' => $player->age,
                        'role' => $player->role ?? 'reserved',
                        'is_active' => $player->is_active,
                        'morale' => $player->morale,
                        'all_star_count' => $player->all_star_count,
                        'is_rookie' => $player->is_rookie,
                        'is_reserved' => $player->is_reserved,
                        'injury_type' => $player->injury_type,
                        'contract_years' => $player->contract_years,
                        'retirement_age' => $player->retirement_age,
                        'draft_id' => $player->draft_id,
                        'drafted_team' => $player->drafted_team,
                        'draft_status' => $player->draft_status,
                        'draft_class' => $player->draft_class,
                        'overall_rating' => $player->overall_rating,
                        'status' => $player->team_id == $teamId ? ($player->is_active ? 1 : 0) : 2,
                        'average_minutes_per_game' => $stats['average_minutes_per_game'],
                        'average_points_per_game' => $stats['average_points_per_game'],
                        'average_rebounds_per_game' => $stats['average_rebounds_per_game'],
                        'average_assists_per_game' => $stats['average_assists_per_game'],
                        'average_steals_per_game' => $stats['average_steals_per_game'],
                        'average_blocks_per_game' => $stats['average_blocks_per_game'],
                        'average_turnovers_per_game' => $stats['average_turnovers_per_game'],
                        'average_fouls_per_game' => $stats['average_fouls_per_game'],
                        'bpg_game_leader' => $stats['bpg_game_leader'],
                        'effeciency' => number_format($stats['average_eff'], 2),
                        'games_played' => $stats['games_played'],
                        'games_started' => $stats['games_started'],
                        'field_goal_percentage' => number_format($stats['field_goal_percentage'], 2),
                        'three_point_percentage' => number_format($stats['three_point_percentage'], 2),
                        'two_point_percentage' => number_format($stats['two_point_percentage'], 2),
                        'free_throw_percentage' => number_format($stats['free_throw_percentage'], 2),
                        'per_game_score' => number_format($stats['per_game_score'], 2),
                        'total_score' => number_format(0, 2),
                        'combined_score' => number_format(0, 2),
                        'seasons_played_with_team' => $seasonsPlayedWithTeam,
                        'team_total_games' => (float)$totalSeasonGameSchedule,
                        'total_seasons_played' => $totalSeasonsPlayed + 1,
                        'latest_season' => $latestSeasonId,
                        'playoff_appearances' => (int)$player->total_playoff_series_appearances,
                        'conference_championships_won' => (int)$player->conference_championships_won,
                        'championships_won' => (int)$player->championships_won,
                        'awards_won' => (int)$player->awards_won,
                        'player_valuation' =>(int) $stats['player_valuation'] ?? 0,
                    ];
                }
            }
        }

        // Sort players by role and efficiency
        if (!empty($playerStats)) {
            $rolePriority = [
                'star player' => 1,
                'all star' => 2,
                'starter' => 3,
                'role player' => 4,
                'bench' => 5
            ];

            usort($playerStats, function ($a, $b) use ($rolePriority) {
                if ($a['status'] == 2 && $b['status'] != 2) {
                    return 1;
                }
                if ($b['status'] == 2 && $a['status'] != 2) {
                    return -1;
                }

                $roleA = $rolePriority[$a['role']] ?? 6;
                $roleB = $rolePriority[$b['role']] ?? 6;

                if ($roleA !== $roleB) {
                    return $a['is_reserved'] <=> $b['is_reserved'] ?: $roleA <=> $roleB;
                }

                return $a['is_reserved'] <=> $b['is_reserved'] ?: $b['per_game_score'] <=> $a['per_game_score'];
            });
        }

        return response()->json([
            'players' => $playerStats,
            'season_id' => $seasonId,
            'team_id' => $teamId,
            'stats_count' => count($playerStatsData),
            'table' => $seasonStatsDBName,
            'remaining_cap_space' => $remainingCapSpace,
        ]);
    }
}
