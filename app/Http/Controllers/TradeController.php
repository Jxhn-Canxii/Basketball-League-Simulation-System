<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 0);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\Player\PlayerValuationService;
use App\Services\Coach\CoachDecisionService;

class TradeController extends Controller
{
    protected $archive;
    protected $helper;
    protected $coachDecisionService;
    protected $valuationService;

    public function __construct()
    {
        $this->helper = new HelperController();
        $this->archive = new ArchiveController();
        $this->valuationService = new PlayerValuationService();
        $this->coachDecisionService = new CoachDecisionService();
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

        $isOffSeason = (bool) $request->is_off_season;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $latestSeasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $latestSeasonId)
            ->where('type', $tradeType)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $latestSeasonId,
            'trade_type' => $tradeType,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVED TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function getApprovedTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        $isOffSeason = (bool) $request->is_off_season;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $latestSeasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $latestSeasonStatus = DB::table('seasons')
            ->where('id', DB::table('seasons')->max('id'))
            ->value('status');

        $tradeSeasonEnd =
            config('timeline.off_season_trade') === $latestSeasonStatus;

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $latestSeasonId)
            ->where('type', $tradeType)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $latestSeasonId,
            'trade_season_end' => $tradeSeasonEnd,
            'current_status' => $latestSeasonStatus,
            'trade_type' => $tradeType,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTACH PLAYERS TO TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    private function attachTradePlayers($proposals)
    {
        if ($proposals->isEmpty()) {
            return;
        }

        $proposalIds = $proposals
            ->pluck('id')
            ->toArray();

        $players = DB::table('trade_players')
            ->leftJoin(
                'players',
                'trade_players.player_id',
                '=',
                'players.id'
            )
            ->leftJoin(
                'draft_pick_rights',
                'trade_players.draft_pick_right_id',
                '=',
                'draft_pick_rights.id'
            )
            ->join(
                'teams as from_team',
                'trade_players.from_team_id',
                '=',
                'from_team.id'
            )
            ->join(
                'teams as to_team',
                'trade_players.to_team_id',
                '=',
                'to_team.id'
            )
            ->whereIn(
                'trade_players.trade_proposal_id',
                $proposalIds
            )
            ->select(
                'trade_players.id',
                'trade_players.trade_proposal_id',
                'trade_players.player_id',
                'trade_players.draft_pick_right_id',
                'trade_players.from_team_id',
                'trade_players.to_team_id',

                'players.name as player_name',
                'players.role',
                'draft_pick_rights.season_id as pick_season_id',
                'draft_pick_rights.round as pick_round',
                'draft_pick_rights.original_team_id as pick_original_team_id',
                'draft_pick_rights.current_owner_id as pick_current_owner_id',
                'draft_pick_rights.protections as pick_protections',

                'from_team.name as from_team',
                'to_team.name as to_team',

                'from_team.primary_color as from_team_primary_color',
                'from_team.secondary_color as from_team_secondary_color',

                'to_team.primary_color as to_team_primary_color',
                'to_team.secondary_color as to_team_secondary_color'
            )
            ->orderBy('trade_players.id')
            ->get()
            ->groupBy('trade_proposal_id');

            foreach ($proposals as $proposal) {

                $proposal->players =
                    $players->get($proposal->id, collect())->values();

                $proposal->players = $proposal->players->map(function ($tradePlayer) {
                    if (!empty($tradePlayer->draft_pick_right_id)) {
                        $tradePlayer->player_name = 'Draft Pick: ' . $tradePlayer->pick_round . ' / ' . $tradePlayer->pick_season_id;
                        $tradePlayer->role = 'draft pick';
                    }

                    return $tradePlayer;
                });

                /*
                |--------------------------------------------------------------------------
                | Get unique teams involved
                |--------------------------------------------------------------------------
                */

                $teams = collect();

                foreach ($proposal->players as $player) {
                    $teams->push($player->from_team_id);
                    $teams->push($player->to_team_id);
                }

                $teamName = collect();

                foreach ($proposal->players as $player) {
                    $teamName->push($player->from_team);
                    $teamName->push($player->to_team);
                }

                $proposal->teams_involved =
                    $teams->unique()->values();

                $proposal->team_name_involved =
                    $teamName->unique()->values();

                $proposal->team_count =
                    $proposal->team_count
                    ?? $proposal->teams_involved->count();
            }
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE MULTI-TEAM TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function generateTradeProposals(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        $isOffSeason = (bool) $request->is_off_season;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $latestSeasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        /*
        |--------------------------------------------------------------------------
        | Get teams
        |--------------------------------------------------------------------------
        */

        $teams = DB::table('teams')
            ->pluck('id')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Get tradeable players per team
        |--------------------------------------------------------------------------
        */

        $playersByTeam = [];

        foreach ($teams as $teamId) {

            $players = $this->findUnderperformingPlayers($teamId);

            foreach ($players as &$player) {

                $player['composite_score'] =
                    $this->calculatePerformanceScore(
                        (object) $player
                    );
            }

            unset($player);

            if (!empty($players)) {
                $playersByTeam[$teamId] = $players;
            }
        }

        if (count($playersByTeam) < 2) {

            return response()->json([
                'message' =>
                'Not enough teams with tradeable players.',
                'trades' => [],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Existing players already involved in pending/approved trades
        |--------------------------------------------------------------------------
        */

        $usedPlayers = DB::table('trade_players')
            ->join(
                'trade_proposals',
                'trade_players.trade_proposal_id',
                '=',
                'trade_proposals.id'
            )
            ->where('trade_proposals.season_id', $latestSeasonId)
            ->whereIn(
                'trade_proposals.status',
                ['pending', 'approved']
            )
            ->pluck('trade_players.player_id')
            ->unique()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Available teams
        |--------------------------------------------------------------------------
        */

        $availableTeamIds = array_keys($playersByTeam);

        shuffle($availableTeamIds);

        $createdTrades = [];

        /*
        |--------------------------------------------------------------------------
        | Maximum number of generated proposals
        |--------------------------------------------------------------------------
        */

        $maxTrades = 20;

        while (
            count($createdTrades) < $maxTrades &&
            count($availableTeamIds) >= 2
        ) {

            /*
            |--------------------------------------------------------------------------
            | Random trade size
            |--------------------------------------------------------------------------
            |
            | 2 = normal trade
            | 3 = three-team trade
            | 4 = four-team trade
            |
            */

            $maxTeamCount = min(
                4,
                count($availableTeamIds)
            );

            $teamCount = rand(2, $maxTeamCount);

            $selectedTeams = array_splice(
                $availableTeamIds,
                0,
                $teamCount
            );

            /*
            |--------------------------------------------------------------------------
            | Find one tradeable player from each team
            |--------------------------------------------------------------------------
            */

            $selectedPlayers = [];

            foreach ($selectedTeams as $teamId) {

                $candidates = array_filter(
                    $playersByTeam[$teamId] ?? [],
                    function ($player) use ($usedPlayers) {

                        return !in_array(
                            $player['player_id'],
                            $usedPlayers
                        );
                    }
                );

                if (empty($candidates)) {
                    continue 2;
                }

                /*
                |--------------------------------------------------------------------------
                | Sort by player value
                |--------------------------------------------------------------------------
                */

                usort(
                    $candidates,
                    function ($a, $b) {

                        return $b['composite_score']
                            <=>
                            $a['composite_score'];
                    }
                );

                /*
                |--------------------------------------------------------------------------
                | Pick from top candidates
                |--------------------------------------------------------------------------
                */

                $topCandidates = array_slice(
                    $candidates,
                    0,
                    min(5, count($candidates))
                );

                $selectedPlayer =
                    $topCandidates[array_rand($topCandidates)];

                $selectedPlayers[] = $selectedPlayer;
            }

            if (count($selectedPlayers) !== $teamCount) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Validate trade balance
            |--------------------------------------------------------------------------
            */

            $scores = array_map(
                function ($player) {
                    return $player['composite_score'];
                },
                $selectedPlayers
            );

            $maxScore = max($scores);
            $minScore = min($scores);

            $scoreDifference =
                $maxScore - $minScore;

            /*
            |--------------------------------------------------------------------------
            | Reject badly unbalanced trades
            |--------------------------------------------------------------------------
            */

            if ($scoreDifference > 15) {
                continue;
            }

            if (!$this->validateTradeBalance($selectedPlayers)) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Validate each team's incoming/outgoing value
            |--------------------------------------------------------------------------
            |
            | Team A:
            | Player A leaves
            | Player from previous team arrives
            |
            */

            $balanced = true;

            for ($i = 0; $i < $teamCount; $i++) {

                $outgoing =
                    $selectedPlayers[$i];

                $incomingIndex =
                    ($i - 1 + $teamCount)
                    % $teamCount;

                $incoming =
                    $selectedPlayers[$incomingIndex];

                $teamDifference =
                    abs(
                        $outgoing['composite_score']
                            -
                            $incoming['composite_score']
                    );

                if ($teamDifference > 15) {
                    $balanced = false;
                    break;
                }
            }

            if (!$balanced) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create master proposal
            |--------------------------------------------------------------------------
            */

            $tradeProposalId =
                DB::table('trade_proposals')
                ->insertGetId([
                    'season_id' =>
                    $latestSeasonId,

                    'type' =>
                    $tradeType,

                    'status' =>
                    'pending',

                    'team_count' =>
                    $teamCount,

                    'created_at' =>
                    now(),

                    'updated_at' =>
                    now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Create player movements
            |--------------------------------------------------------------------------
            |
            | A -> B
            | B -> C
            | C -> A
            |
            */

            $tradePlayerRows = [];

            for (
                $i = 0;
                $i < $teamCount;
                $i++
            ) {

                $player =
                    $selectedPlayers[$i];

                $nextIndex =
                    ($i + 1) % $teamCount;

                $fromTeamId =
                    $player['team_id'];

                $toTeamId =
                    $selectedPlayers[$nextIndex]['team_id'];

                $tradePlayerRows[] = [
                    'trade_proposal_id' =>
                    $tradeProposalId,

                    'player_id' =>
                    $player['player_id'],

                    'from_team_id' =>
                    $fromTeamId,

                    'to_team_id' =>
                    $toTeamId,

                    'player_name' =>
                    $player['player_name'],

                    'role' =>
                    $player['role'],

                    'created_at' =>
                    now(),

                    'updated_at' =>
                    now(),
                ];

                $usedPlayers[] =
                    $player['player_id'];
            }

            DB::table('trade_players')
                ->insert($tradePlayerRows);

            /*
            |--------------------------------------------------------------------------
            | Save generated trade
            |--------------------------------------------------------------------------
            */

            $createdTrades[] = [
                'trade_proposal_id' =>
                $tradeProposalId,

                'team_count' =>
                $teamCount,

                'players' =>
                $tradePlayerRows,
            ];
        }

        return response()->json([
            'message' =>
            'Multi-team trade proposals generated successfully.',

            'trade_type' =>
            $tradeType,

            'season_id' =>
            $latestSeasonId,

            'trades' =>
            $createdTrades,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTOMATED TRADE DECISION
    |--------------------------------------------------------------------------
    */

    public function automatedTradeDecision(Request $request)
    {
        $request->validate([
            'is_off_season' => 'required|boolean',
        ]);

        $isOffSeason =
            (bool) $request->is_off_season;

        $latestSeasonId =
            $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $storeStats = new PlayerSeasonStatsController();

        /*
        |--------------------------------------------------------------------------
        | Get pending master proposals
        |--------------------------------------------------------------------------
        */

        $proposals =
            DB::table('trade_proposals')
            ->where('season_id', $latestSeasonId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get();

        $decisions = [];

       foreach ($proposals as $proposal) {

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Existing validation
        |--------------------------------------------------------------------------
        */

        $valid = true;

        $teamEvaluations = [];

        foreach ($proposal->tradePlayers as $tradePlayer) {

            /*
            |--------------------------------------------------------------------------
            | Example:
            |
            | tradePlayer->team_id
            | tradePlayer->player_id
            | tradePlayer->receiving_team_id
            |--------------------------------------------------------------------------
            */

            $outgoingTeamId =
                (int) $tradePlayer->team_id;

            $incomingTeamId =
                (int) $tradePlayer->receiving_team_id;

            $player = DB::table('players')
                ->where(
                    'id',
                    $tradePlayer->player_id
                )
                ->first();

            if (!$player) {
                $valid = false;
                break;
            }

            /*
            |--------------------------------------------------------------------------
            | Make sure player is still on original team.
            |--------------------------------------------------------------------------
            */

            if (
                (int) $player->team_id !==
                $outgoingTeamId
            ) {
                $valid = false;
                break;
            }

            /*
            |--------------------------------------------------------------------------
            | Player valuation
            |--------------------------------------------------------------------------
            */

            $baseValue =
                $this->valuationService
                    ->calculatePlayerValue(
                        $player
                    );

            /*
            |--------------------------------------------------------------------------
            | Find the player this team receives.
            |--------------------------------------------------------------------------
            */

            $incomingTradePlayer =
                $proposal->tradePlayers
                    ->first(function ($item) use (
                        $incomingTeamId
                    ) {
                        return (
                            (int) $item->receiving_team_id ===
                            $incomingTeamId
                        );
                    });

            if (!$incomingTradePlayer) {
                $valid = false;
                break;
            }

            $incomingPlayer =
                DB::table('players')
                    ->where(
                        'id',
                        $incomingTradePlayer->player_id
                    )
                    ->first();

            if (!$incomingPlayer) {
                $valid = false;
                break;
            }

            $incomingBaseValue =
                $this->valuationService
                    ->calculatePlayerValue(
                        $incomingPlayer
                    );

            /*
            |--------------------------------------------------------------------------
            | Coach evaluation
            |--------------------------------------------------------------------------
            */

            $evaluation =
                $this->evaluateTradeForTeam(
                    $incomingTeamId,
                    $incomingPlayer,
                    $player,
                    $incomingBaseValue,
                    $baseValue
                );

            $teamEvaluations[] =
                $evaluation;
        }

        if (!$valid) {

            DB::rollBack();

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Every team must approve.
        |--------------------------------------------------------------------------
        */

        $allTeamsApprove = true;

        foreach ($teamEvaluations as $evaluation) {

            if (
                !$this->coachDecisionService
                    ->randomDecision(
                        $evaluation['approval_chance']
                    )
            ) {
                $allTeamsApprove = false;
                break;
            }
        }

        if (!$allTeamsApprove) {

            DB::rollBack();

            /*
            | Mark proposal rejected if this is how
            | your existing system handles failed decisions.
            */

            DB::table('trade_proposals')
                ->where('id', $proposal->id)
                ->update([
                    'status' => 3,
                    'updated_at' => now(),
                ]);

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Execute existing trade transaction
        |--------------------------------------------------------------------------
        */

        foreach ($proposal->tradePlayers as $tradePlayer) {

            DB::table('players')
                ->where(
                    'id',
                    $tradePlayer->player_id
                )
                ->update([
                    'team_id' =>
                        $tradePlayer->receiving_team_id,
                    'updated_at' => now(),
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Existing approval/logging code
        |--------------------------------------------------------------------------
        */

        DB::table('trade_proposals')
            ->where('id', $proposal->id)
            ->update([
                'status' => 2,
                'updated_at' => now(),
            ]);

        DB::commit();

    } catch (\Throwable $e) {

        DB::rollBack();

        throw $e;
    }
}

        return response()->json([
            'decisions' =>
            $decisions,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT TRADE PROPOSAL
    |--------------------------------------------------------------------------
    */

    private function rejectTradeProposal($proposalId)
    {
        DB::table('trade_proposals')
            ->where('id', $proposalId)
            ->update([
                'status' => 'rejected',
                'updated_at' => now(),
            ]);
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

        try {

            $latestSeasonId =
                get_current_season_id();

            $tradeType =
                $isOffSeason
                ? 'off-season trade'
                : 'in-season trade';

            /*
            |--------------------------------------------------------------------------
            | Player
            |--------------------------------------------------------------------------
            */

            $player =
                DB::table('players')
                ->select(
                    'name',
                    'role'
                )
                ->where(
                    'id',
                    $playerId
                )
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Teams
            |--------------------------------------------------------------------------
            */

            $teamFrom =
                DB::table('teams')
                ->where(
                    'id',
                    $teamFromId
                )
                ->value('name');

            $teamTo =
                DB::table('teams')
                ->where(
                    'id',
                    $teamToId
                )
                ->value('name');

            if (
                !$player ||
                !$teamFrom ||
                !$teamTo
            ) {

                Log::error(
                    'Trade log failed: Missing player or team data.',
                    [
                        'player_id' =>
                        $playerId,

                        'team_from_id' =>
                        $teamFromId,

                        'team_to_id' =>
                        $teamToId,

                        'trade_proposal_id' =>
                        $tradeProposalId,
                    ]
                );

                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Transaction
            |--------------------------------------------------------------------------
            */

            DB::table('transactions')
                ->insert([
                    'player_id' =>
                    $playerId,

                    'season_id' =>
                    $latestSeasonId,

                    'details' =>
                    "Traded {$player->name} ({$teamFrom}) to {$teamTo}",

                    'from_team_id' =>
                    $teamFromId,

                    'to_team_id' =>
                    $teamToId,

                    'status' =>
                    $tradeType,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Trade Log
            |--------------------------------------------------------------------------
            */

            DB::table('trade_logs')
                ->insert([
                    'season_id' =>
                    $latestSeasonId,

                    'trade_proposal_id' =>
                    $tradeProposalId,

                    'team_from_id' =>
                    $teamFromId,

                    'team_to_id' =>
                    $teamToId,

                    'player_id' =>
                    $playerId,

                    'player_name' =>
                    $player->name,

                    'role' =>
                    $player->role,

                    'trade_reason' =>
                    $message,
                ]);

            return true;
        } catch (\Exception $e) {

            Log::error(
                'Trade Log Error: ' .
                    $e->getMessage(),
                [
                    'player_id' =>
                    $playerId,

                    'trade_proposal_id' =>
                    $tradeProposalId,
                ]
            );

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | END IN-SEASON TRADE WINDOW
    |--------------------------------------------------------------------------
    */

    public function endInSeasonTradeWindow()
    {
        $latestSeasonId =
            get_current_season_id();

        DB::table('seasons')
            ->where(
                'id',
                $latestSeasonId
            )
            ->update([
                'status' =>
                config('timeline.in_season_trade'),
            ]);

        return response()->json([
            'message' =>
            'Trade window ended!',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | END OFF-SEASON TRADE WINDOW
    |--------------------------------------------------------------------------
    */

    public function endOffSeasonTradeWindow()
    {
        $latestSeasonId =
            get_current_season_id();

        $storyline =
            $this->upsertCurrentSeasonStoryline();

        if ($storyline) {

            $this->archive->archiveGameStats();

            $this->archive->archivePlayerSeasonStats();

            $this->archive->archiveScheduleViewTable();

            $this->archive->archiveScheduleWriteTable();

            DB::table('seasons')
                ->where(
                    'id',
                    $latestSeasonId
                )
                ->update([
                    'status' =>
                    config('timeline.off_season_trade'),
                ]);

            return response()->json([
                'message' =>
                'Trade window ended!',
            ]);
        }

        return $storyline;
    }

    /*
    |--------------------------------------------------------------------------
    | STORYLINE
    |--------------------------------------------------------------------------
    */

    private function upsertCurrentSeasonStoryline()
    {
        try {

            $storylineData =
                DB::table('current_season_storyline')
                ->first();

            if (!$storylineData) {

                return response()->json([
                    'message' =>
                    'No current season storyline found.',
                ], 404);
            }

            DB::table('storylines')
                ->updateOrInsert(
                    [
                        'season_id' =>
                        $storylineData->season_id,
                    ],
                    [
                        'storyline' =>
                        $storylineData->storyline,

                        'updated_at' =>
                        now(),

                        'created_at' =>
                        now(),
                    ]
                );

            return true;
        } catch (\Exception $e) {

            Log::error(
                'Failed to upsert storyline: ' .
                    $e->getMessage()
            );

            return response()->json([
                'error' =>
                'Failed to upsert storyline',

                'message' =>
                $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PERFORMANCE SCORE
    |--------------------------------------------------------------------------
    */

    private function validateTradeBalance(array $players): bool
    {
        if (count($players) < 2) {
            return false;
        }

        $scores = array_map(function ($player) {
            return (float) ($player['composite_score'] ?? 0);
        }, $players);

        $maxScore = max($scores);
        $minScore = min($scores);

        if (($maxScore - $minScore) > 15) {
            return false;
        }

        $salaries = array_map(function ($player) {
            return (float) ($player['salary'] ?? 0);
        }, $players);

        $maxSalary = max($salaries);
        $minSalary = min($salaries);

        if ($maxSalary <= 0 || $minSalary <= 0) {
            return true;
        }

        if ($maxSalary > ($minSalary * 1.25) + 100000) {
            return false;
        }

        return true;
    }

    private function calculatePerformanceScore($player)
    {
        return $this->calculatePlayerValue($player);
    }

    private function calculatePlayerValue($player)
    {
        if (!$player) {
            return 0;
        }

        if (is_array($player) || is_object($player)) {
            return (float) $this->valuationService->calculatePlayerValue($player);
        }

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND UNDERPERFORMING PLAYERS
    |--------------------------------------------------------------------------
    */

    private function findUnderperformingPlayers(
        int $teamId
    ) {
        $seasonId = get_current_season_id();

        $coach = $this->coachDecisionService
            ->getTeamCoach($teamId);

        /*
    |--------------------------------------------------------------------------
    | Current season stats
    |--------------------------------------------------------------------------
    */

        $currentPlayers = DB::table('player_season_stats')
            ->join(
                'players',
                'players.id',
                '=',
                'player_season_stats.player_id'
            )
            ->where(
                'player_season_stats.season_id',
                $seasonId
            )
            ->where(
                'players.team_id',
                $teamId
            )
            ->where(
                'players.is_active',
                1
            )
            ->where(
                'players.contract_years',
                '<=',
                5
            )
            ->select(
                'players.*',
                'player_season_stats.*'
            )
            ->get();

        if ($currentPlayers->isEmpty()) {
            return collect();
        }

        /*
    |--------------------------------------------------------------------------
    | Previous season
    |--------------------------------------------------------------------------
    */

        $previousSeasonId = $seasonId - 1;

        $previousStats = DB::table(
            'player_season_stats_archives'
        )
            ->where(
                'season_id',
                $previousSeasonId
            )
            ->get()
            ->keyBy('player_id');

        $tradeable = collect();

        foreach ($currentPlayers as $player) {

            $previous = $previousStats
                ->get($player->player_id);

            if (!$previous) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Calculate performance
        |--------------------------------------------------------------------------
        */

            $currentScore = $this->calculatePerformanceScore(
                $player
            );

            $previousScore = $this->calculatePerformanceScore(
                $previous
            );

            if ($previousScore <= 0) {
                continue;
            }

            if ($currentScore >= $previousScore) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Decline
        |--------------------------------------------------------------------------
        */

            $decline = $previousScore - $currentScore;

            $declinePercentage =
                ($decline / $previousScore) * 100;

            $superDecline = (
                $declinePercentage >= 20
            );

            /*
        |--------------------------------------------------------------------------
        | Coach trade pressure
        |--------------------------------------------------------------------------
        */

            $expiringContract = (
                (int) ($player->contract_years ?? 0) <= 1
            );

            $tradePressure =
                $this->coachDecisionService
                ->getTradePressure(
                    $coach,
                    $player,
                    $declinePercentage,
                    $superDecline,
                    $expiringContract
                );

            /*
        |--------------------------------------------------------------------------
        | Minimum pressure
        |--------------------------------------------------------------------------
        */

            if ($tradePressure < 15) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Trade randomness
        |--------------------------------------------------------------------------
        */

            $tradeChance = min(
                90,
                30 + $tradePressure
            );

            if (
                !$this->coachDecisionService
                    ->randomDecision($tradeChance)
            ) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Add metadata
        |--------------------------------------------------------------------------
        */

            $player->current_performance_score =
                $currentScore;

            $player->previous_performance_score =
                $previousScore;

            $player->performance_decline =
                $decline;

            $player->performance_decline_percentage =
                $declinePercentage;

            $player->super_decline =
                $superDecline;

            $player->trade_pressure =
                $tradePressure;

            $player->coach_id =
                $coach?->id;

            $tradeable->push($player);
        }

        return $tradeable
            ->sortByDesc('trade_pressure')
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL UNDERPERFORMING PLAYERS
    |--------------------------------------------------------------------------
    */

    private function getUnderperformingPlayers()
    {
        $latestSeasonId =
            get_current_season_id();

        $previousSeasonId =
            get_previous_season_id();

        $latestStats =
            DB::table('player_season_stats')
            ->join(
                'players',
                'player_season_stats.player_id',
                '=',
                'players.id'
            )
            ->where(
                'players.contract_years',
                '<=',
                5
            )
            ->where(
                'player_season_stats.season_id',
                $latestSeasonId
            )
            ->select(

                'players.id as player_id',

                'players.team_id',

                'players.role',

                'players.salary',

                'players.name as player_name',

                'player_season_stats.total_games',

                'player_season_stats.total_games_played',

                'player_season_stats.avg_minutes_per_game',

                'player_season_stats.avg_points_per_game',

                'player_season_stats.avg_rebounds_per_game',

                'player_season_stats.avg_assists_per_game',

                'player_season_stats.avg_steals_per_game',

                'player_season_stats.avg_blocks_per_game',

                'player_season_stats.avg_turnovers_per_game',

                'player_season_stats.avg_fouls_per_game'
            )
            ->get();

        $previousStats =
            DB::table('player_season_stats_archives')
            ->where(
                'season_id',
                $previousSeasonId
            )
            ->whereIn(
                'player_id',
                $latestStats
                    ->pluck('player_id')
                    ->toArray()
            )
            ->get()
            ->keyBy('player_id');

        $underperformingPlayers = [];

        foreach ($latestStats as $playerStats) {

            $previous =
                $previousStats->get(
                    $playerStats->player_id
                );

            $latestScore =
                $this->calculatePerformanceScore(
                    $playerStats
                );

            $previousScore =
                $previous
                ? $this->calculatePerformanceScore(
                    $previous
                )
                : 0;

            $playerStats->super_decline = false;

            if ($previousScore > 0) {

                $declinePercentage =
                    (
                        ($previousScore - $latestScore)
                        /
                        $previousScore
                    ) * 100;

                if ($declinePercentage >= 20) {

                    $playerStats->super_decline = true;
                }
            }

            if (
                $previousScore > 0 &&
                $latestScore < $previousScore
            ) {

                $underperformingPlayers[] =
                    (array) $playerStats;
            }
        }

        return $underperformingPlayers;
    }

    /*
    |--------------------------------------------------------------------------
    | GET PLAYER STATS
    |--------------------------------------------------------------------------
    */

    private function getPlayerStats($playerId)
    {
        $latestSeasonId =
            get_current_season_id();

        /*
        |--------------------------------------------------------------------------
        | Existing dynamic stats database logic
        |--------------------------------------------------------------------------
        */

        $dbName =
            $this->helper
            ->getSeasonStatsDBName(
                $latestSeasonId
            );

        $stats =
            DB::table($dbName)
            ->where(
                'player_id',
                $playerId
            )
            ->where(
                'season_id',
                $latestSeasonId
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Fallback to player_season_stats
        |--------------------------------------------------------------------------
        */

        if (!$stats) {

            $stats =
                DB::table('player_season_stats')
                ->where(
                    'player_id',
                    $playerId
                )
                ->where(
                    'season_id',
                    $latestSeasonId
                )
                ->first();
        }

        return $stats;
    }

    private function calculateCoachAdjustedTradeValue(
        int $teamId,
        $player,
        float $baseValue
    ): float {
        $coach = $this->coachDecisionService
            ->getTeamCoach($teamId);

        return $this->coachDecisionService
            ->getTradeValue(
                $coach,
                $player,
                $baseValue
            );
    }

    private function evaluateTradeForTeam(
    int $teamId,
    $outgoingPlayer,
    $incomingPlayer,
    float $outgoingBaseValue,
    float $incomingBaseValue
): array {
    $coach = $this->coachDecisionService
        ->getTeamCoach($teamId);

    /*
    |--------------------------------------------------------------------------
    | Coach-adjusted values
    |--------------------------------------------------------------------------
    */

    $outgoingValue =
        $this->coachDecisionService
            ->getTradeValue(
                $coach,
                $outgoingPlayer,
                $outgoingBaseValue
            );

    $incomingValue =
        $this->coachDecisionService
            ->getTradeValue(
                $coach,
                $incomingPlayer,
                $incomingBaseValue
            );

    /*
    |--------------------------------------------------------------------------
    | Net benefit
    |--------------------------------------------------------------------------
    */

    $netBenefit =
        $incomingValue -
        $outgoingValue;

    /*
    |--------------------------------------------------------------------------
    | Coach approval
    |--------------------------------------------------------------------------
    */

    $approvalChance =
        $this->coachDecisionService
            ->getTradeApprovalChance(
                $coach,
                $netBenefit
            );

    return [
        'team_id' => $teamId,
        'coach_id' => $coach?->id,
        'outgoing_value' => $outgoingValue,
        'incoming_value' => $incomingValue,
        'net_benefit' => $netBenefit,
        'approval_chance' => $approvalChance,
    ];
}
}
