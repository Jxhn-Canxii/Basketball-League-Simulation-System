```php
<?php

namespace App\Services\Trade;

ini_set('max_execution_time', 0);

use App\Services\Archive\ArchiveService;
use App\Services\Player\PlayerValuationService;
use App\Services\Coach\CoachDecisionService;
use App\Services\Helper\HelperService;
use App\Services\League\StoryLineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TradeDecisionService
{
    protected $storyLineService;
    protected $helper;
    protected $coachDecisionService;
    protected $valuationService;
    protected $archive;
    protected $tradeValuation;

    /*
    |--------------------------------------------------------------------------
    | TRADE SETTINGS
    |--------------------------------------------------------------------------
    */

    protected int $maxGeneratedTrades = 20;

    protected int $maxTeamsPerTrade = 6;

    protected int $maxPlayersPerTeam = 5;

    protected int $maxPicksPerTeam = 2;

    protected float $maxPackageDifferencePercentage = 25.0;

    public function __construct()
    {
        $this->helper = new HelperService();

        $this->valuationService = new PlayerValuationService();

        $this->coachDecisionService = new CoachDecisionService();

        $this->storyLineService = new StoryLineService();

        $this->archive = new ArchiveService();

        $this->tradeValuation = new TradeValuationService();
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function generateTradeProposals($isOffSeason)
    {
        return $this->generateTradeProposalsInternal(
            (bool) $isOffSeason
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEST GENERATE TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function testGenerateTradeProposals()
    {
        return $this->generateTradeProposalsInternal(false);
    }

    /*
    |--------------------------------------------------------------------------
    | INTERNAL TRADE GENERATOR
    |--------------------------------------------------------------------------
    */

    private function generateTradeProposalsInternal(bool $isOffSeason)
    {
        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        /*
        |--------------------------------------------------------------------------
        | Teams
        |--------------------------------------------------------------------------
        */

        $teams = DB::table('teams')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        shuffle($teams);

        /*
        |--------------------------------------------------------------------------
        | Existing assets already involved in pending/approved trades
        |--------------------------------------------------------------------------
        |
        | Players cannot be reused while the trade is pending/approved.
        |
        | Draft picks are different:
        |
        | A pick can be traded again after a previous trade because
        | current_owner_id changes.
        |
        | Therefore only PENDING pick trades lock the pick.
        |--------------------------------------------------------------------------
        */

        $usedPlayers = $this->getUsedTradePlayers($seasonId);

        $usedPicks = $this->getPendingTradePicks();

        /*
        |--------------------------------------------------------------------------
        | Build trade assets
        |--------------------------------------------------------------------------
        */

        $assetsByTeam = [];

        foreach ($teams as $teamId) {

            $players = $this->findUnderperformingPlayers($teamId);

            $assets = [];

            /*
            |--------------------------------------------------------------------------
            | Player assets
            |--------------------------------------------------------------------------
            */

            foreach ($players as $player) {

                if (
                    in_array(
                        (int) $player->player_id,
                        $usedPlayers,
                        true
                    )
                ) {
                    continue;
                }

                $player->composite_score =
                    $this->tradeValuation->calculatePlayerValue($player);

                $assets[] = [
                    'type' => 'player',
                    'player' => $player,
                    'value' => (float) $player->composite_score,
                    'salary' => (float) ($player->salary ?? 0),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Draft pick assets
            |--------------------------------------------------------------------------
            */

            $pickAssets = $this->getTradeableDraftPicks(
                $teamId,
                $seasonId
            );

            foreach ($pickAssets as $pick) {

                if (
                    in_array(
                        (int) $pick->id,
                        $usedPicks,
                        true
                    )
                ) {
                    continue;
                }

                $pickValue =
                    $this->tradeValuation->calculateDraftPickValue($pick);

                $assets[] = [
                    'type' => 'draft_pick',
                    'pick' => $pick,
                    'value' => (float) $pickValue,
                    'salary' => 0,
                ];
            }

            if (!empty($assets)) {
                $assetsByTeam[$teamId] = $assets;
            }
        }

        if (count($assetsByTeam) < 2) {

            return response()->json([
                'message' => 'Not enough teams with tradeable assets.',
                'trades' => [],
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate proposals
        |--------------------------------------------------------------------------
        */

        $createdTrades = [];

        $attempts = 0;

        $maxAttempts = 200;

        while (
            count($createdTrades) < $this->maxGeneratedTrades &&
            $attempts < $maxAttempts
        ) {

            $attempts++;

            $availableTeams = array_keys($assetsByTeam);

            if (count($availableTeams) < 2) {
                break;
            }

            shuffle($availableTeams);

            $teamCount = rand(
                2,
                min(
                    $this->maxTeamsPerTrade,
                    count($availableTeams)
                )
            );

            $selectedTeams = array_slice(
                $availableTeams,
                0,
                $teamCount
            );

            /*
            |--------------------------------------------------------------------------
            | Select packages
            |--------------------------------------------------------------------------
            */

            $selectedAssets = [];

            foreach ($selectedTeams as $teamId) {

                $candidates = array_values(
                    array_filter(
                        $assetsByTeam[$teamId] ?? [],
                        function ($asset) use (
                            $usedPlayers,
                            $usedPicks
                        ) {

                            if ($asset['type'] === 'player') {

                                return !in_array(
                                    (int) $asset['player']->player_id,
                                    $usedPlayers,
                                    true
                                );
                            }

                            return !in_array(
                                (int) $asset['pick']->id,
                                $usedPicks,
                                true
                            );
                        }
                    )
                );

                if (empty($candidates)) {
                    continue 2;
                }

                shuffle($candidates);

                /*
                |--------------------------------------------------------------------------
                | Limit player/pick package independently
                |--------------------------------------------------------------------------
                */

                $players = array_values(
                    array_filter(
                        $candidates,
                        fn ($asset) =>
                            $asset['type'] === 'player'
                    )
                );

                $picks = array_values(
                    array_filter(
                        $candidates,
                        fn ($asset) =>
                            $asset['type'] === 'draft_pick'
                    )
                );

                shuffle($players);
                shuffle($picks);

                $selectedPackage = [];

                /*
                |--------------------------------------------------------------------------
                | Maximum player assets
                |--------------------------------------------------------------------------
                */

                $playerCount = min(
                    rand(
                        1,
                        min(
                            2,
                            count($players)
                        )
                    ),
                    $this->maxPlayersPerTeam
                );

                if ($playerCount > 0) {

                    $selectedPackage = array_merge(
                        $selectedPackage,
                        array_slice(
                            $players,
                            0,
                            $playerCount
                        )
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Maximum draft picks
                |--------------------------------------------------------------------------
                */

                if (
                    count($picks) > 0 &&
                    rand(0, 100) <= 35
                ) {

                    $pickCount = rand(
                        1,
                        min(
                            $this->maxPicksPerTeam,
                            count($picks)
                        )
                    );

                    $selectedPackage = array_merge(
                        $selectedPackage,
                        array_slice(
                            $picks,
                            0,
                            $pickCount
                        )
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Fallback if package ended empty
                |--------------------------------------------------------------------------
                */

                if (empty($selectedPackage)) {

                    $selectedPackage[] =
                        $candidates[array_rand($candidates)];
                }

                $selectedAssets[$teamId] =
                    $selectedPackage;
            }

            if (count($selectedAssets) !== $teamCount) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create random N-way distribution
            |--------------------------------------------------------------------------
            */

            $recipients = $selectedTeams;

            shuffle($recipients);

            /*
            |--------------------------------------------------------------------------
            | Prevent self-trades
            |--------------------------------------------------------------------------
            */

            $validDistribution = true;

            foreach ($selectedTeams as $index => $fromTeamId) {

                if (
                    (int) $fromTeamId ===
                    (int) $recipients[$index]
                ) {

                    $validDistribution = false;

                    break;
                }
            }

            if (!$validDistribution) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Incoming packages
            |--------------------------------------------------------------------------
            */

            $incomingByTeam = [];

            foreach ($selectedTeams as $index => $fromTeamId) {

                $toTeamId =
                    $recipients[$index];

                foreach (
                    $selectedAssets[$fromTeamId]
                    as $asset
                ) {

                    $incomingByTeam[$toTeamId][] =
                        $asset;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Validate package balance
            |--------------------------------------------------------------------------
            */

            $balanced = true;

            foreach ($selectedTeams as $teamId) {

                $outgoing =
                    $selectedAssets[$teamId] ?? [];

                $incoming =
                    $incomingByTeam[$teamId] ?? [];

                $outgoingValue =
                    $this->tradeValuation->getPackageValue(
                        $outgoing
                    );

                $incomingValue =
                    $this->tradeValuation->getPackageValue(
                        $incoming
                    );

                if (
                    !$this->tradeValuation->isPackageBalanced(
                        $outgoing,
                        $incoming
                    )
                ) {

                    $balanced = false;

                    break;
                }

                $difference =
                    abs(
                        $incomingValue -
                        $outgoingValue
                    );

                $baseValue =
                    max(
                        $incomingValue,
                        $outgoingValue,
                        1
                    );

                $differencePercentage =
                    ($difference / $baseValue) * 100;

                if (
                    $differencePercentage >
                    $this->maxSalaryDifferencePercentage
                ) {

                    $balanced = false;

                    break;
                }
            }

            if (!$balanced) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create proposal
            |--------------------------------------------------------------------------
            */

            $tradeProposalId =
                DB::table('trade_proposals')
                    ->insertGetId([
                        'season_id' => $seasonId,
                        'type' => $tradeType,
                        'status' => 'pending',
                        'team_count' => $teamCount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

            $tradePlayerRows = [];

            foreach ($selectedTeams as $index => $fromTeamId) {

                $toTeamId =
                    $recipients[$index];

                foreach (
                    $selectedAssets[$fromTeamId]
                    as $asset
                ) {

                    if ($asset['type'] === 'player') {

                        $player =
                            $asset['player'];

                        $tradePlayerRows[] = [
                            'trade_proposal_id' =>
                                $tradeProposalId,

                            'player_id' =>
                                $player->player_id,

                            'draft_pick_right_id' =>
                                null,

                            'from_team_id' =>
                                $fromTeamId,

                            'to_team_id' =>
                                $toTeamId,

                            'player_name' =>
                                $player->name,

                            'role' =>
                                $player->role,

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ];

                        $usedPlayers[] =
                            (int) $player->player_id;

                    } else {

                        $pick =
                            $asset['pick'];

                        $tradePlayerRows[] = [
                            'trade_proposal_id' =>
                                $tradeProposalId,

                            'player_id' =>
                                null,

                            'draft_pick_right_id' =>
                                $pick->id,

                            'from_team_id' =>
                                $fromTeamId,

                            'to_team_id' =>
                                $toTeamId,

                            'player_name' =>
                                null,

                            'role' =>
                                'draft pick',

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ];

                        /*
                        |--------------------------------------------------------------------------
                        | Lock this pick for the remainder of generation.
                        |--------------------------------------------------------------------------
                        */

                        $usedPicks[] =
                            (int) $pick->id;
                    }
                }
            }

            if (empty($tradePlayerRows)) {

                DB::table('trade_proposals')
                    ->where('id', $tradeProposalId)
                    ->delete();

                continue;
            }

            DB::table('trade_players')
                ->insert($tradePlayerRows);

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
                $seasonId,

            'trades' =>
                $createdTrades,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | USED TRADE PLAYERS
    |--------------------------------------------------------------------------
    */

    private function getUsedTradePlayers(int $seasonId): array
    {
        return DB::table('trade_players')
            ->join(
                'trade_proposals',
                'trade_players.trade_proposal_id',
                '=',
                'trade_proposals.id'
            )
            ->where(
                'trade_proposals.season_id',
                $seasonId
            )
            ->whereIn(
                'trade_proposals.status',
                [
                    'pending',
                    'approved',
                ]
            )
            ->whereNotNull(
                'trade_players.player_id'
            )
            ->pluck(
                'trade_players.player_id'
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING DRAFT PICKS
    |--------------------------------------------------------------------------
    */

    private function getPendingTradePicks(): array
    {
        return DB::table('trade_players')
            ->join(
                'trade_proposals',
                'trade_players.trade_proposal_id',
                '=',
                'trade_proposals.id'
            )
            ->where(
                'trade_proposals.status',
                'pending'
            )
            ->whereNotNull(
                'trade_players.draft_pick_right_id'
            )
            ->pluck(
                'trade_players.draft_pick_right_id'
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | AUTOMATED TRADE DECISION
    |--------------------------------------------------------------------------
    */

    public function automatedTradeDecision($isOffSeason)
    {
        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $proposals = DB::table('trade_proposals')
            ->where(
                'season_id',
                $seasonId
            )
            ->where(
                'status',
                'pending'
            )
            ->orderBy('id')
            ->get();

        $decisions = [];

        foreach ($proposals as $proposal) {

            DB::beginTransaction();

            try {

                $tradePlayers =
                    DB::table('trade_players')
                        ->where(
                            'trade_proposal_id',
                            $proposal->id
                        )
                        ->orderBy('id')
                        ->get();

                /*
                |--------------------------------------------------------------------------
                | Basic validation
                |--------------------------------------------------------------------------
                */

                if ($tradePlayers->count() < 2) {

                    $this->rejectTradeProposal(
                        $proposal->id
                    );

                    DB::commit();

                    $decisions[] = [
                        'trade_proposal_id' =>
                            $proposal->id,

                        'status' =>
                            'rejected',

                        'reason' =>
                            'Trade contains fewer than two assets.',
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validate assets
                |--------------------------------------------------------------------------
                */

                $validation =
                    $this->validateTradeAssets(
                        $tradePlayers
                    );

                if (!$validation['valid']) {

                    $this->rejectTradeProposal(
                        $proposal->id
                    );

                    DB::commit();

                    $decisions[] = [
                        'trade_proposal_id' =>
                            $proposal->id,

                        'status' =>
                            'rejected',

                        'reason' =>
                            $validation['reason'],
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Receiving teams
                |--------------------------------------------------------------------------
                */

                $teams =
                    $tradePlayers
                        ->pluck('to_team_id')
                        ->unique()
                        ->values();

                $evaluations = [];

                $approved = true;

                foreach ($teams as $teamId) {

                    $outgoing =
                        $tradePlayers->where(
                            'from_team_id',
                            $teamId
                        );

                    $incoming =
                        $tradePlayers->where(
                            'to_team_id',
                            $teamId
                        );

                    $evaluation =
                        $this->tradeValuation
                            ->evaluateTeamPackage(
                                (int) $teamId,
                                $outgoing,
                                $incoming
                            );

                    $evaluations[] =
                        $evaluation;

                    if (
                        !$this->coachDecisionService
                            ->randomDecision(
                                $evaluation['approval_chance']
                            )
                    ) {

                        $approved = false;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Rejected
                |--------------------------------------------------------------------------
                */

                if (!$approved) {

                    $this->rejectTradeProposal(
                        $proposal->id
                    );

                    DB::commit();

                    $decisions[] = [
                        'trade_proposal_id' =>
                            $proposal->id,

                        'status' =>
                            'rejected',

                        'evaluations' =>
                            $evaluations,
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Execute
                |--------------------------------------------------------------------------
                */

                $execution =
                    $this->executeTrade(
                        $proposal,
                        $tradePlayers,
                        (bool) $isOffSeason
                    );

                if (!$execution['success']) {

                    DB::rollBack();

                    $decisions[] = [
                        'trade_proposal_id' =>
                            $proposal->id,

                        'status' =>
                            'rejected',

                        'reason' =>
                            $execution['reason'],
                    ];

                    continue;
                }

                DB::commit();

                $decisions[] = [
                    'trade_proposal_id' =>
                        $proposal->id,

                    'status' =>
                        'approved',

                    'evaluations' =>
                        $evaluations,
                ];

            } catch (\Throwable $e) {

                DB::rollBack();

                Log::error(
                    'Automated trade decision failed.',
                    [
                        'trade_proposal_id' =>
                            $proposal->id,

                        'error' =>
                            $e->getMessage(),
                    ]
                );

                $decisions[] = [
                    'trade_proposal_id' =>
                        $proposal->id,

                    'status' =>
                        'rejected',

                    'reason' =>
                        $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'season_id' =>
                $seasonId,

            'decisions' =>
                $decisions,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE TRADE ASSETS
    |--------------------------------------------------------------------------
    */

    private function validateTradeAssets($tradePlayers): array
    {
        foreach ($tradePlayers as $tradePlayer) {

            /*
            |--------------------------------------------------------------------------
            | Player
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->player_id)) {

                $player =
                    DB::table('players')
                        ->where(
                            'id',
                            $tradePlayer->player_id
                        )
                        ->first();

                if (!$player) {

                    return [
                        'valid' => false,
                        'reason' =>
                            'Player no longer exists.',
                    ];
                }

                if (
                    (int) $player->team_id !==
                    (int) $tradePlayer->from_team_id
                ) {

                    return [
                        'valid' => false,
                        'reason' =>
                            "{$player->name} is no longer on the original team.",
                    ];
                }

                if (
                    isset($player->is_active) &&
                    !(bool) $player->is_active
                ) {

                    return [
                        'valid' => false,
                        'reason' =>
                            "{$player->name} is inactive.",
                    ];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Draft Pick
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->draft_pick_right_id)) {

                $pick =
                    DB::table('draft_pick_rights')
                        ->where(
                            'id',
                            $tradePlayer->draft_pick_right_id
                        )
                        ->first();

                if (!$pick) {

                    return [
                        'valid' => false,
                        'reason' =>
                            'Draft pick no longer exists.',
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Ownership must still match.
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $pick->current_owner_id !==
                    (int) $tradePlayer->from_team_id
                ) {

                    return [
                        'valid' => false,
                        'reason' =>
                            'Draft pick is no longer owned by the original team.',
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Used picks cannot be traded.
                |--------------------------------------------------------------------------
                */

                if ((bool) $pick->is_used) {

                    return [
                        'valid' => false,
                        'reason' =>
                            'Draft pick has already been used.',
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | A pick may be traded multiple times.
                |
                | Only a currently pending trade locks it.
                |--------------------------------------------------------------------------
                */

                $pendingTrade =
                    DB::table('trade_players')
                        ->join(
                            'trade_proposals',
                            'trade_players.trade_proposal_id',
                            '=',
                            'trade_proposals.id'
                        )
                        ->where(
                            'trade_players.draft_pick_right_id',
                            $tradePlayer->draft_pick_right_id
                        )
                        ->where(
                            'trade_proposals.status',
                            'pending'
                        )
                        ->where(
                            'trade_proposals.id',
                            '!=',
                            $tradePlayer->trade_proposal_id
                        )
                        ->exists();

                if ($pendingTrade) {

                    return [
                        'valid' => false,
                        'reason' =>
                            'Draft pick is already included in another pending trade.',
                    ];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent self-trade
            |--------------------------------------------------------------------------
            */

            if (
                (int) $tradePlayer->from_team_id ===
                (int) $tradePlayer->to_team_id
            ) {

                return [
                    'valid' => false,
                    'reason' =>
                        'An asset cannot be traded to the same team.',
                ];
            }
        }

        return [
            'valid' => true,
            'reason' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | EXECUTE TRADE
    |--------------------------------------------------------------------------
    */

    private function executeTrade(
        $proposal,
        $tradePlayers,
        bool $isOffSeason
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Final validation
        |--------------------------------------------------------------------------
        */

        $validation =
            $this->validateTradeAssets(
                $tradePlayers
            );

        if (!$validation['valid']) {

            return [
                'success' => false,
                'reason' =>
                    $validation['reason'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Build complete N-way trade details BEFORE movement
        |--------------------------------------------------------------------------
        */

        $tradeDetails = [];

        foreach ($tradePlayers as $tradePlayer) {

            $fromTeamId =
                $tradePlayer->from_team_id;

            $toTeamId =
                $tradePlayer->to_team_id;

            $routeKey =
                $fromTeamId . '_' . $toTeamId;

            if (!isset($tradeDetails[$routeKey])) {

                $tradeDetails[$routeKey] = [
                    'from_team_id' =>
                        $fromTeamId,

                    'to_team_id' =>
                        $toTeamId,

                    'players' => [],

                    'draft_picks' => [],
                ];
            }

            if (!empty($tradePlayer->player_id)) {

                $tradeDetails[$routeKey]['players'][] = [
                    'id' =>
                        $tradePlayer->player_id,
                ];
            }

            if (!empty($tradePlayer->draft_pick_right_id)) {

                $draftPick =
                    DB::table('draft_pick_rights')
                        ->where(
                            'id',
                            $tradePlayer->draft_pick_right_id
                        )
                        ->first();

                if ($draftPick) {

                    $tradeDetails[$routeKey]['draft_picks'][] = [
                        'id' =>
                            $draftPick->id,

                        'season_id' =>
                            $draftPick->season_id,

                        'round' =>
                            $draftPick->round,

                        'pick_number' =>
                            $draftPick->pick_number,

                        'original_team_id' =>
                            $draftPick->original_team_id,

                        'protections' =>
                            $draftPick->protections,
                    ];
                }
            }
        }

        $tradeDetails =
            array_values($tradeDetails);

        /*
        |--------------------------------------------------------------------------
        | Move assets
        |--------------------------------------------------------------------------
        */

        foreach ($tradePlayers as $tradePlayer) {

            /*
            |--------------------------------------------------------------------------
            | Player
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->player_id)) {

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

                            'updated_at' =>
                                now(),
                        ]);

                if (!$updated) {

                    return [
                        'success' => false,
                        'reason' =>
                            'Player could not be transferred.',
                    ];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Draft pick
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->draft_pick_right_id)) {

                $updated =
                    DB::table('draft_pick_rights')
                        ->where(
                            'id',
                            $tradePlayer->draft_pick_right_id
                        )
                        ->where(
                            'current_owner_id',
                            $tradePlayer->from_team_id
                        )
                        ->where(
                            'is_used',
                            0
                        )
                        ->update([
                            'current_owner_id' =>
                                $tradePlayer->to_team_id,

                            'is_traded' =>
                                1,

                            'trade_proposal_id' =>
                                $proposal->id,

                            'updated_at' =>
                                now(),
                        ]);

                if (!$updated) {

                    return [
                        'success' => false,
                        'reason' =>
                            'Draft pick could not be transferred.',
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Log players
        |--------------------------------------------------------------------------
        */

        foreach ($tradePlayers as $tradePlayer) {

            if (empty($tradePlayer->player_id)) {
                continue;
            }

            $logged =
                self::logTrade(
                    $tradePlayer->from_team_id,
                    $tradePlayer->to_team_id,
                    $tradePlayer->player_id,
                    $tradePlayer->id,
                    'Trade approved by all teams.',
                    $isOffSeason,
                    $proposal->id,
                    $proposal->season_id,
                    $tradeDetails
                );

            if (!$logged) {

                return [
                    'success' => false,
                    'reason' =>
                        'Trade was completed but transaction logging failed.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Approve proposal
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

        return [
            'success' => true,
            'reason' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT TRADE
    |--------------------------------------------------------------------------
    */

    private function rejectTradeProposal($proposalId): void
    {
        DB::table('trade_proposals')
            ->where(
                'id',
                $proposalId
            )
            ->update([
                'status' =>
                    'rejected',

                'updated_at' =>
                    now(),
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
        $tradeProposalId = null,
        $seasonId = null,
        array $tradeDetails = []
    ) {
        try {

            $seasonId =
                $seasonId ??
                get_current_season_id();

            $tradeType =
                $isOffSeason
                    ? 'off-season trade'
                    : 'in-season trade';

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
                return false;
            }

            $tradeDescription =
                self::buildTradeDescription(
                    $teamFromId,
                    $teamToId,
                    $playerId,
                    $tradeDetails
                );

            DB::table('transactions')
                ->insert([
                    'player_id' =>
                        $playerId,

                    'season_id' =>
                        $seasonId,

                    'details' =>
                        $tradeDescription,

                    'from_team_id' =>
                        $teamFromId,

                    'to_team_id' =>
                        $teamToId,

                    'status' =>
                        $tradeType,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

            DB::table('trade_logs')
                ->insert([
                    'season_id' =>
                        $seasonId,

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

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

            return true;

        } catch (\Throwable $e) {

            Log::error(
                'Trade logging failed.',
                [
                    'player_id' =>
                        $playerId,

                    'proposal_id' =>
                        $tradeProposalId,

                    'error' =>
                        $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD TRADE DESCRIPTION
    |--------------------------------------------------------------------------
    */

    private static function buildTradeDescription(
        $teamFromId,
        $teamToId,
        $playerId,
        array $tradeDetails = []
    ) {

        $teamNames =
            DB::table('teams')
                ->whereIn(
                    'id',
                    collect($tradeDetails)
                        ->flatMap(function ($trade) {
                            return [
                                $trade['from_team_id'] ?? null,
                                $trade['to_team_id'] ?? null,
                            ];
                        })
                        ->push($teamFromId)
                        ->push($teamToId)
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray()
                )
                ->pluck(
                    'name',
                    'id'
                );

        if (empty($tradeDetails)) {

            $player =
                DB::table('players')
                    ->where(
                        'id',
                        $playerId
                    )
                    ->value('name');

            $from =
                $teamNames[$teamFromId] ??
                'Unknown Team';

            $to =
                $teamNames[$teamToId] ??
                'Unknown Team';

            return
                "Traded {$player} ({$from}) to {$to}.";
        }

        $teams =
            collect($tradeDetails)
                ->flatMap(function ($trade) {
                    return [
                        $trade['from_team_id'] ?? null,
                        $trade['to_team_id'] ?? null,
                    ];
                })
                ->filter()
                ->unique()
                ->values();

        $teamCount =
            $teams->count();

        $tradeLabel =
            match ($teamCount) {
                2 => '2-team trade',
                3 => '3-team trade',
                4 => '4-team trade',
                default => "{$teamCount}-team trade",
            };

        $teamSummaries = [];

        foreach ($tradeDetails as $trade) {

            $fromId =
                $trade['from_team_id'] ??
                null;

            $toId =
                $trade['to_team_id'] ??
                null;

            if (!$fromId || !$toId) {
                continue;
            }

            $fromName =
                $teamNames[$fromId] ??
                'Unknown Team';

            $toName =
                $teamNames[$toId] ??
                'Unknown Team';

            $assets = [];

            /*
            |--------------------------------------------------------------------------
            | Players
            |--------------------------------------------------------------------------
            */

            $playerIds =
                collect(
                    $trade['players'] ?? []
                )
                    ->map(function ($player) {
                        return is_array($player)
                            ? ($player['id'] ?? null)
                            : $player;
                    })
                    ->filter()
                    ->values();

            if ($playerIds->isNotEmpty()) {

                $players =
                    DB::table('players')
                        ->whereIn(
                            'id',
                            $playerIds
                        )
                        ->pluck(
                            'name',
                            'id'
                        );

                foreach ($playerIds as $id) {

                    if (isset($players[$id])) {
                        $assets[] =
                            $players[$id];
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Draft Picks
            |--------------------------------------------------------------------------
            */

            foreach (
                $trade['draft_picks'] ?? []
                as $pick
            ) {

                $season =
                    $pick['season_id'] ??
                    null;

                $round =
                    $pick['round'] ??
                    null;

                $pickNumber =
                    $pick['pick_number'] ??
                    null;

                $protections =
                    $pick['protections'] ??
                    null;

                if (!$season || !$round) {
                    continue;
                }

                $roundText =
                    match ((int) $round) {
                        1 => '1st',
                        2 => '2nd',
                        3 => '3rd',
                        default =>
                            $round . 'th',
                    };

                $pickText =
                    "Season {$season} {$roundText} Round Draft Pick";

                if ($pickNumber !== null) {
                    $pickText .=
                        " (#{$pickNumber})";
                }

                if (!empty($protections)) {
                    $pickText .=
                        " ({$protections})";
                }

                $assets[] =
                    $pickText;
            }

            /*
            |--------------------------------------------------------------------------
            | Future Draft Rights
            |--------------------------------------------------------------------------
            */

            foreach (
                $trade['draft_rights'] ?? []
                as $right
            ) {

                $season =
                    $right['season_id'] ??
                    null;

                if ($season) {

                    $assets[] =
                        "Season {$season} Draft Rights";
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Other assets
            |--------------------------------------------------------------------------
            */

            foreach (
                $trade['other_assets'] ?? []
                as $asset
            ) {

                if (!empty($asset)) {
                    $assets[] =
                        $asset;
                }
            }

            if (!empty($assets)) {

                $teamSummaries[] =
                    "{$fromName} sent " .
                    implode(', ', $assets) .
                    " to {$toName}";
            }
        }

        $mainPlayer =
            DB::table('players')
                ->where(
                    'id',
                    $playerId
                )
                ->value('name');

        $mainFrom =
            $teamNames[$teamFromId] ??
            'Unknown Team';

        $mainTo =
            $teamNames[$teamToId] ??
            'Unknown Team';

        $description =
            "Traded {$mainPlayer} ({$mainFrom}) to {$mainTo} " .
            "as part of a {$tradeLabel}. ";

        if (!empty($teamSummaries)) {

            $description .=
                "Trade details: " .
                implode(
                    '; ',
                    $teamSummaries
                ) .
                '.';
        }

        return $description;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND UNDERPERFORMING PLAYERS
    |--------------------------------------------------------------------------
    */

    public function findUnderperformingPlayers(int $teamId)
    {
        $seasonId =
            get_current_season_id();

        $coach =
            $this->coachDecisionService
                ->getTeamCoach($teamId);

        $currentPlayers =
            DB::table('player_season_stats')
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
                    'players.no_trade_clause',
                    0
                )
                ->where(
                    'players.contract_years',
                    '<=',
                    5
                )
                ->select(
                    'players.id as player_id',
                    'players.name',
                    'players.role',
                    'players.salary',
                    'players.team_id',
                    'players.contract_years',
                    'players.is_active',

                    'player_season_stats.season_id',
                    'player_season_stats.total_games',
                    'player_season_stats.total_games_played',
                    'player_season_stats.avg_minutes_per_game',
                    'player_season_stats.avg_points_per_game',
                    'player_season_stats.avg_rebounds_per_game',
                    'player_season_stats.avg_assists_per_game',
                    'player_season_stats.avg_steals_per_game',
                    'player_season_stats.avg_blocks_per_game',
                    'player_season_stats.avg_turnovers_per_game',
                    'player_season_stats.avg_fouls_per_game',
                    'player_season_stats.eff'
                )
                ->get();

        if ($currentPlayers->isEmpty()) {
            return collect();
        }

        $previousSeasonId =
            get_previous_season_id();

        $previousStats =
            DB::table('player_season_stats_archives')
                ->where(
                    'season_id',
                    $previousSeasonId
                )
                ->whereIn(
                    'player_id',
                    $currentPlayers
                        ->pluck('player_id')
                        ->toArray()
                )
                ->get()
                ->keyBy('player_id');

        $tradeable =
            collect();

        foreach ($currentPlayers as $player) {

            $previous =
                $previousStats
                    ->get(
                        $player->player_id
                    );

            if (!$previous) {
                continue;
            }

            $currentScore =
                $this->tradeValuation
                    ->calculatePlayerValue(
                        $player
                    );

            $previousScore =
                $this->tradeValuation
                    ->calculatePlayerValue(
                        $previous
                    );

            if ($previousScore <= 0) {
                continue;
            }

            if ($currentScore >= $previousScore) {
                continue;
            }

            $decline =
                $previousScore -
                $currentScore;

            $declinePercentage =
                ($decline / $previousScore) *
                100;

            $superDecline =
                $declinePercentage >= 20;

            $expiringContract =
                (
                    (int) (
                        $player->contract_years ??
                        0
                    ) <= 1
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

            if ($tradePressure < 15) {
                continue;
            }

            $tradeChance =
                min(
                    90,
                    30 + $tradePressure
                );

            if (
                !$this->coachDecisionService
                    ->randomDecision(
                        $tradeChance
                    )
            ) {
                continue;
            }

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
    | GET TRADEABLE FUTURE DRAFT PICKS
    |--------------------------------------------------------------------------
    |
    | Rules:
    |
    | - Current season picks are NOT tradeable.
    | - Next 5 draft seasons are tradeable.
    | - Pick can be traded multiple times.
    | - Used picks cannot be traded.
    | - Picks in pending proposals cannot be traded.
    | - Previous approved/rejected trade_proposal_id does NOT lock the pick.
    |--------------------------------------------------------------------------
    */

    public function getTradeableDraftPicks(
        int $teamId,
        ?int $seasonId = null
    ) {

        $currentSeasonId =
            (int) get_current_season_id();

        /*
        |--------------------------------------------------------------------------
        | Use supplied season when available.
        |--------------------------------------------------------------------------
        |
        | The season ID here is the trade context, not the draft pick season.
        |
        */

        $tradeSeasonId =
            $seasonId ??
            $currentSeasonId;

        /*
        |--------------------------------------------------------------------------
        | Picks currently locked in pending proposals
        |--------------------------------------------------------------------------
        */

        $pendingPickIds =
            $this->getPendingTradePicks();

        /*
        |--------------------------------------------------------------------------
        | Future picks
        |--------------------------------------------------------------------------
        |
        | We use currentSeasonId + 1 through +5.
        |
        | This means:
        |
        | Current season = NOT tradeable
        | +1 = tradeable
        | +2 = tradeable
        | +3 = tradeable
        | +4 = tradeable
        | +5 = tradeable
        |
        */

        $query =
            DB::table('draft_pick_rights')
                ->where(
                    'current_owner_id',
                    $teamId
                )
                ->whereBetween(
                    'season_id',
                    [
                        $currentSeasonId + 1,
                        $currentSeasonId + 5,
                    ]
                )
                ->where(
                    'is_used',
                    0
                );

        /*
        |--------------------------------------------------------------------------
        | Exclude pending picks
        |--------------------------------------------------------------------------
        */

        if (!empty($pendingPickIds)) {

            $query->whereNotIn(
                'id',
                $pendingPickIds
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Optional season sanity check
        |--------------------------------------------------------------------------
        |
        | Keep the variable intentionally available for future rules where
        | trade context may determine how many future picks are allowed.
        |
        */

        if ($tradeSeasonId <= 0) {
            return collect();
        }

        return $query
            ->orderBy('season_id')
            ->orderBy('round')
            ->orderBy('pick_number')
            ->get();
    }
}

