<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 0);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TradeController extends Controller
{
    protected $archive;
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperController();
        $this->archive = new ArchiveController();
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
            ->join(
                'players',
                'trade_players.player_id',
                '=',
                'players.id'
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
                'trade_players.from_team_id',
                'trade_players.to_team_id',

                'players.name as player_name',
                'players.role',

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

            try {

                /*
                |--------------------------------------------------------------------------
                | Get every player in this trade
                |--------------------------------------------------------------------------
                */

                $tradePlayers =
                    DB::table('trade_players')
                    ->where(
                        'trade_proposal_id',
                        $proposal->id
                    )
                    ->get();

                /*
                |--------------------------------------------------------------------------
                | Validate proposal
                |--------------------------------------------------------------------------
                */

                if ($tradePlayers->count() < 2) {

                    $this->rejectTradeProposal(
                        $proposal->id
                    );

                    $decisions[] = [
                        'proposal_id' =>
                        $proposal->id,

                        'status' =>
                        'rejected',

                        'reason' =>
                        'Invalid trade proposal.',
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validate players and teams
                |--------------------------------------------------------------------------
                */

                $invalidTrade = false;

                foreach ($tradePlayers as $tradePlayer) {

                    $player =
                        DB::table('players')
                        ->where(
                            'id',
                            $tradePlayer->player_id
                        )
                        ->first();

                    if (!$player) {
                        $invalidTrade = true;
                        break;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Player must still belong to original team
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $player->team_id
                        !==
                        (int) $tradePlayer->from_team_id
                    ) {
                        $invalidTrade = true;
                        break;
                    }
                }

                if ($invalidTrade) {

                    $this->rejectTradeProposal(
                        $proposal->id
                    );

                    $decisions[] = [
                        'proposal_id' =>
                        $proposal->id,

                        'status' =>
                        'rejected',

                        'reason' =>
                        'One or more players are no longer available.',
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Check if any player has already been traded
                |--------------------------------------------------------------------------
                */

                $playerIds =
                    $tradePlayers
                    ->pluck('player_id')
                    ->toArray();

                $alreadyTraded =
                    DB::table('trade_players')
                    ->join(
                        'trade_proposals',
                        'trade_players.trade_proposal_id',
                        '=',
                        'trade_proposals.id'
                    )
                    ->where(
                        'trade_proposals.season_id',
                        $latestSeasonId
                    )
                    ->where(
                        'trade_proposals.status',
                        'approved'
                    )
                    ->whereIn(
                        'trade_players.player_id',
                        $playerIds
                    )
                    ->where(
                        'trade_players.trade_proposal_id',
                        '!=',
                        $proposal->id
                    )
                    ->exists();

                if ($alreadyTraded) {

                    $this->rejectTradeProposal(
                        $proposal->id
                    );

                    $decisions[] = [
                        'proposal_id' =>
                        $proposal->id,

                        'status' =>
                        'rejected',

                        'reason' =>
                        'Player already involved in another approved trade.',
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Calculate player values
                |--------------------------------------------------------------------------
                */

                $scores = [];

                foreach ($tradePlayers as $tradePlayer) {

                    $stats =
                        $this->getPlayerStats(
                            $tradePlayer->player_id
                        );

                    $scores[] =
                        $this->calculatePerformanceScore(
                            $stats
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Calculate trade balance
                |--------------------------------------------------------------------------
                */

                $maxScore =
                    max($scores);

                $minScore =
                    min($scores);

                $scoreDifference =
                    $maxScore - $minScore;

                /*
                |--------------------------------------------------------------------------
                | Determine approval chance
                |--------------------------------------------------------------------------
                */

                $approvalChance = 60;

                if ($scoreDifference <= 5) {

                    $approvalChance = 85;
                } elseif ($scoreDifference <= 10) {

                    $approvalChance = 75;
                } elseif ($scoreDifference <= 15) {

                    $approvalChance = 65;
                }

                /*
                |--------------------------------------------------------------------------
                | Random decision
                |--------------------------------------------------------------------------
                */

                if (
                    rand(1, 100)
                    >
                    $approvalChance
                ) {

                    $this->rejectTradeProposal(
                        $proposal->id
                    );

                    $decisions[] = [
                        'proposal_id' =>
                        $proposal->id,

                        'status' =>
                        'rejected',

                        'reason' =>
                        'Trade rejected based on team decision logic.',
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Execute entire trade atomically
                |--------------------------------------------------------------------------
                */

                DB::transaction(function () use (
                    $proposal,
                    $tradePlayers,
                    $isOffSeason,
                    $storeStats
                ) {

                    foreach ($tradePlayers as $tradePlayer) {

                        /*
                        |--------------------------------------------------------------------------
                        | Lock player row
                        |--------------------------------------------------------------------------
                        */

                        $player =
                            DB::table('players')
                            ->where(
                                'id',
                                $tradePlayer->player_id
                            )
                            ->lockForUpdate()
                            ->first();

                        if (!$player) {

                            throw new \Exception(
                                "Player {$tradePlayer->player_id} not found."
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Make sure player is still on original team
                        |--------------------------------------------------------------------------
                        */

                        if (
                            (int) $player->team_id
                            !==
                            (int) $tradePlayer->from_team_id
                        ) {

                            throw new \Exception(
                                "Player {$player->name} is no longer on the expected team."
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Move player
                        |--------------------------------------------------------------------------
                        */

                        $updated =
                            DB::table('players')
                            ->where(
                                'id',
                                $tradePlayer->player_id
                            )
                            ->where(
                                'team_id',
                                $tradePlayer->from_team_id
                            )
                            ->update([
                                'team_id' =>
                                $tradePlayer->to_team_id,
                            ]);

                        if (!$updated) {

                            throw new \Exception(
                                "Failed to move player {$player->name}."
                            );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Mark master proposal approved
                    |--------------------------------------------------------------------------
                    */

                    DB::table('trade_proposals')
                        ->where(
                            'id',
                            $proposal->id
                        )
                        ->update([
                            'status' =>
                            'approved',

                            'updated_at' =>
                            now(),
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Store player season stats
                    |--------------------------------------------------------------------------
                    */

                    foreach ($tradePlayers as $tradePlayer) {

                        if ($isOffSeason) {

                            $storeStats
                                ->storePlayerNextSeasonStats(
                                    $tradePlayer->to_team_id,
                                    $tradePlayer->player_id
                                );
                        } else {

                            $storeStats
                                ->storePlayerCurrentSeasonStats(
                                    $tradePlayer->to_team_id,
                                    $tradePlayer->player_id
                                );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Log each player movement
                    |--------------------------------------------------------------------------
                    */

                    foreach ($tradePlayers as $tradePlayer) {

                        self::logTrade(
                            $tradePlayer->from_team_id,
                            $tradePlayer->to_team_id,
                            $tradePlayer->player_id,
                            null,
                            'Multi-team trade accepted.',
                            $isOffSeason,
                            $proposal->id
                        );
                    }
                });

                $decisions[] = [
                    'proposal_id' =>
                    $proposal->id,

                    'status' =>
                    'approved',

                    'team_count' =>
                    $proposal->team_count,

                    'players' =>
                    $tradePlayers->count(),

                    'reason' =>
                    'Multi-team trade approved successfully.',
                ];
            } catch (\Exception $e) {

                Log::error(
                    'Trade processing error: ' .
                        $e->getMessage(),
                    [
                        'proposal_id' =>
                        $proposal->id,
                    ]
                );

                $decisions[] = [
                    'proposal_id' =>
                    $proposal->id,

                    'status' =>
                    'error',

                    'reason' =>
                    $e->getMessage(),
                ];
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

    private function calculatePerformanceScore($player)
    {
        if (!$player) {
            return 0;
        }

        return (float) (

            ($player->avg_points_per_game ?? 0) * 2 +

            ($player->avg_rebounds_per_game ?? 0) * 1.5 +

            ($player->avg_assists_per_game ?? 0) * 1.5 +

            ($player->avg_steals_per_game ?? 0) * 2 +

            ($player->avg_blocks_per_game ?? 0) * 2 -

            ($player->avg_turnovers_per_game ?? 0) * 1.5
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FIND UNDERPERFORMING PLAYERS
    |--------------------------------------------------------------------------
    */

    private function findUnderperformingPlayers($teamId)
    {
        $latestSeasonId =
            get_current_season_id();

        $previousSeasonId =
            get_previous_season_id();

        /*
        |--------------------------------------------------------------------------
        | Current season stats
        |--------------------------------------------------------------------------
        */

        $latestStats =
            DB::table('player_season_stats')
            ->join(
                'players',
                'player_season_stats.player_id',
                '=',
                'players.id'
            )
            ->where(
                'players.team_id',
                $teamId
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

        $underperformingPlayers = [];

        /*
        |--------------------------------------------------------------------------
        | Previous season stats
        |--------------------------------------------------------------------------
        |
        | Instead of querying the archive once per player,
        | load the previous season archive once.
        |
        */

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

            /*
            |--------------------------------------------------------------------------
            | Super decline
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Only underperforming players
            |--------------------------------------------------------------------------
            */

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
}
