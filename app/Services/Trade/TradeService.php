<?php

namespace App\Services\Trade;

ini_set('max_execution_time', 0);

use App\Services\Archive\ArchiveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Player\PlayerValuationService;
use App\Services\Coach\CoachDecisionService;
use App\Services\Helper\HelperService;
use App\Services\League\StoryLineService;

class TradeService
{
    protected $storyLineService;
    protected $helper;
    protected $coachDecisionService;
    protected $valuationService;
    protected $archive;

    /*
    |--------------------------------------------------------------------------
    | TRADE SETTINGS
    |--------------------------------------------------------------------------
    */

    protected int $maxGeneratedTrades = 20;

    protected int $maxTeamsPerTrade = 6;

    protected int $maxPlayersPerTeam = 3;

    protected int $maxPicksPerTeam = 2;

    protected float $maxPackageDifference = 15.0;

    protected float $maxSalaryDifferencePercentage = 25.0;

    public function __construct()
    {
        $this->helper = new HelperService();

        $this->valuationService = new PlayerValuationService();

        $this->coachDecisionService = new CoachDecisionService();

        $this->storyLineService = new StoryLineService();

        $this->archive = new ArchiveService();
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function getPendingTradeProposals($request)
    {
        $isOffSeason = (bool) $request->is_off_season;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->where('type', $tradeType)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $players = $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $seasonId,
            'trade_type' => $tradeType,
        ]);
    }

     public function getAllTradeProposals($request)
    {
        $isOffSeason = (bool) $request->is_off_season;
        $seasonId = (int) $request->season_id;

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->orderBy('type')
            ->orderBy('status')
            ->orderByDesc('created_at')
            ->get();

        $players = $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $seasonId,
            'trade_type' => 'all',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVED TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function getApprovedTradeProposals($request)
    {
        $isOffSeason = (bool) $request->is_off_season;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $latestSeasonStatus = DB::table('seasons')
            ->where('id', DB::table('seasons')->max('id'))
            ->value('status');

        $tradeSeasonEnd =
            config('timeline.off_season_trade') === $latestSeasonStatus;

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->where('type', $tradeType)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        $this->attachTradePlayers($proposals);

        return response()->json([
            'trade_proposals' => $proposals,
            'current_season' => $seasonId,
            'trade_season_end' => $tradeSeasonEnd,
            'current_status' => $latestSeasonStatus,
            'trade_type' => $tradeType,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTACH TRADE ASSETS
    |--------------------------------------------------------------------------
    */

    private function attachTradePlayers($proposals): void
    {
        if ($proposals->isEmpty()) {
            return;
        }

        $proposalIds = $proposals
            ->pluck('id')
            ->toArray();

        $assets = DB::table('trade_players')
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

            $proposal->tradePlayers =
                $assets
                ->get($proposal->id, collect())
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Compatibility
            |--------------------------------------------------------------------------
            */

            $proposal->players =
                $proposal->tradePlayers;

            /*
            |--------------------------------------------------------------------------
            | Convert pick rows into readable names
            |--------------------------------------------------------------------------
            */

            $proposal->tradePlayers =
                $proposal->tradePlayers->map(
                    function ($asset) {

                        if (!empty($asset->draft_pick_right_id)) {

                            $asset->asset_type = 'draft_pick';

                            $asset->player_name =
                                'Draft Pick: ' .
                                $asset->pick_round ??
                                (
                                    'Round ' .
                                    $asset->pick_round .
                                    ' / Season ' .
                                    $asset->pick_season_id
                                );

                            $asset->role = 'draft pick';
                        } else {

                            $asset->asset_type = 'player';
                        }

                        return $asset;
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Teams involved
            |--------------------------------------------------------------------------
            */

            $teams = collect();

            $teamNames = collect();

            foreach ($proposal->tradePlayers as $asset) {

                $teams->push($asset->from_team_id);
                $teams->push($asset->to_team_id);

                $teamNames->push($asset->from_team);
                $teamNames->push($asset->to_team);
            }

            $proposal->teams_involved =
                $teams
                ->filter()
                ->unique()
                ->values();

            $proposal->team_name_involved =
                $teamNames
                ->filter()
                ->unique()
                ->values();

            $proposal->team_count =
                $proposal->team_count
                ?? $proposal->teams_involved->count();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE TRADE PROPOSALS
    |--------------------------------------------------------------------------
    */

    public function generateTradeProposals($isOffSeason)
    {
        // $isOffSeason = (bool) $request->is_off_season;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();


        /*
        |--------------------------------------------------------------------------
        | Teams with tradeable assets
        |--------------------------------------------------------------------------
        */

        $teams = DB::table('teams')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        shuffle($teams);

        /*
        |--------------------------------------------------------------------------
        | Build trade assets
        |--------------------------------------------------------------------------
        */

        $assetsByTeam = [];

        foreach ($teams as $teamId) {

            $players = $this->findUnderperformingPlayers($teamId);

            if ($players->isEmpty()) {
                continue;
            }

            $playerAssets = [];

            foreach ($players as $player) {

                $player->composite_score =
                    $this->calculatePlayerValue($player);

                $playerAssets[] = [
                    'type' => 'player',
                    'player' => $player,
                    'value' => (float) $player->composite_score,
                    'salary' => (float) ($player->salary ?? 0),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Draft picks
            |--------------------------------------------------------------------------
            */

            $pickAssets = $this->getTradeableDraftPicks(
                $teamId,
                $seasonId
            );

            foreach ($pickAssets as $pick) {

                $pickValue =
                    $this->calculateDraftPickValue($pick);

                $playerAssets[] = [
                    'type' => 'draft_pick',
                    'pick' => $pick,
                    'value' => $pickValue,
                    'salary' => 0,
                ];
            }

            if (!empty($playerAssets)) {
                $assetsByTeam[$teamId] = $playerAssets;
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
        | Existing assets already involved in trades
        |--------------------------------------------------------------------------
        */

        $usedPlayers = DB::table('trade_players')
            ->join(
                'trade_proposals',
                'trade_players.trade_proposal_id',
                '=',
                'trade_proposals.id'
            )
            ->where('trade_proposals.season_id', $seasonId)
            ->whereIn(
                'trade_proposals.status',
                ['pending', 'approved']
            )
            ->whereNotNull('trade_players.player_id')
            ->pluck('trade_players.player_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->toArray();

        $usedPicks = DB::table('trade_players')
            ->join(
                'trade_proposals',
                'trade_players.trade_proposal_id',
                '=',
                'trade_proposals.id'
            )
            ->where('trade_proposals.season_id', $seasonId)
            ->whereIn(
                'trade_proposals.status',
                ['pending', 'approved']
            )
            ->whereNotNull('trade_players.draft_pick_right_id')
            ->pluck('trade_players.draft_pick_right_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->toArray();

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

                $candidates =
                    array_values(
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

                /*
                |--------------------------------------------------------------------------
                | Usually 1–2 assets, occasionally 3
                |--------------------------------------------------------------------------
                */

                shuffle($candidates);

                $assetCount = rand(
                    1,
                    min(
                        $this->maxPlayersPerTeam,
                        count($candidates)
                    )
                );

                /*
                | Keep packages manageable.
                */

                $package = array_slice(
                    $candidates,
                    0,
                    $assetCount
                );

                $selectedAssets[$teamId] = $package;
            }

            if (count($selectedAssets) !== $teamCount) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create incoming distribution
            |--------------------------------------------------------------------------
            |
            | Assets from Team A go to another team.
            | We shuffle recipients instead of always using a simple
            | A → B → C → A rotation.
            |--------------------------------------------------------------------------
            */

            $recipients = $selectedTeams;

            shuffle($recipients);

            /*
            | Prevent a team from receiving its own assets.
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
            | Build packages by receiving team
            |--------------------------------------------------------------------------
            */

            $incomingByTeam = [];

            foreach ($selectedTeams as $index => $fromTeamId) {

                $toTeamId = $recipients[$index];

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
            | Validate every team's package balance
            |--------------------------------------------------------------------------
            */

            $balanced = true;

            foreach ($selectedTeams as $teamId) {

                $outgoing =
                    $selectedAssets[$teamId] ?? [];

                $incoming =
                    $incomingByTeam[$teamId] ?? [];

                $outgoingValue =
                    $this->getPackageValue($outgoing);

                $incomingValue =
                    $this->getPackageValue($incoming);

                if (!$this->isPackageBalanced(
                    $outgoing,
                    $incoming
                )) {
                    $balanced = false;
                    break;
                }

                /*
                |--------------------------------------------------------------------------
                | Prevent absurd package differences
                |--------------------------------------------------------------------------
                */

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

                if ($differencePercentage > 25) {
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

                        $player = $asset['player'];

                        $tradePlayerRows[] = [
                            'trade_proposal_id' => $tradeProposalId,
                            'player_id' => $player->player_id,
                            'draft_pick_right_id' => null,
                            'from_team_id' => $fromTeamId,
                            'to_team_id' => $toTeamId,
                            'player_name' => $player->name,
                            'role' => $player->role,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $usedPlayers[] =
                            (int) $player->player_id;
                    } else {

                        $pick = $asset['pick'];

                        $tradePlayerRows[] = [
                            'trade_proposal_id' => $tradeProposalId,
                            'player_id' => null,
                            'draft_pick_right_id' => $pick->id,
                            'from_team_id' => $fromTeamId,
                            'to_team_id' => $toTeamId,
                            'player_name' => null,
                            'role' => 'draft pick',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

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
                'trade_proposal_id' => $tradeProposalId,
                'team_count' => $teamCount,
                'players' => $tradePlayerRows,
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

    public function testGenerateTradeProposals()
    {
        $isOffSeason = false;

        $tradeType = $isOffSeason
            ? 'off-season'
            : 'in-season';

        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        /*
        |--------------------------------------------------------------------------
        | Teams with tradeable assets
        |--------------------------------------------------------------------------
        */

        $teams = DB::table('teams')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        shuffle($teams);

        /*
        |--------------------------------------------------------------------------
        | Build trade assets
        |--------------------------------------------------------------------------
        */

        $assetsByTeam = [];

        foreach ($teams as $teamId) {

            $players = $this->findUnderperformingPlayers($teamId);

            if ($players->isEmpty()) {
                continue;
            }

            $playerAssets = [];

            foreach ($players as $player) {

                $player->composite_score =
                    $this->calculatePlayerValue($player);

                $playerAssets[] = [
                    'type' => 'player',
                    'player' => $player,
                    'value' => (float) $player->composite_score,
                    'salary' => (float) ($player->salary ?? 0),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Draft picks
            |--------------------------------------------------------------------------
            */

            $pickAssets = $this->getTradeableDraftPicks(
                $teamId,
                $seasonId
            );

            foreach ($pickAssets as $pick) {

                $pickValue =
                    $this->calculateDraftPickValue($pick);

                $playerAssets[] = [
                    'type' => 'draft_pick',
                    'pick' => $pick,
                    'value' => $pickValue,
                    'salary' => 0,
                ];
            }

            if (!empty($playerAssets)) {
                $assetsByTeam[$teamId] = $playerAssets;
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
        | Existing assets already involved in trades
        |--------------------------------------------------------------------------
        */

        $usedPlayers = DB::table('trade_players')
            ->join(
                'trade_proposals',
                'trade_players.trade_proposal_id',
                '=',
                'trade_proposals.id'
            )
            ->where('trade_proposals.season_id', $seasonId)
            ->whereIn(
                'trade_proposals.status',
                ['pending', 'approved']
            )
            ->whereNotNull('trade_players.player_id')
            ->pluck('trade_players.player_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->toArray();

        $usedPicks = DB::table('trade_players')
            ->join(
                'trade_proposals',
                'trade_players.trade_proposal_id',
                '=',
                'trade_proposals.id'
            )
            ->where('trade_proposals.season_id', $seasonId)
            ->whereIn(
                'trade_proposals.status',
                ['pending', 'approved']
            )
            ->whereNotNull('trade_players.draft_pick_right_id')
            ->pluck('trade_players.draft_pick_right_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->toArray();

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

                $candidates =
                    array_values(
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

                /*
                |--------------------------------------------------------------------------
                | Usually 1–2 assets, occasionally 3
                |--------------------------------------------------------------------------
                */

                shuffle($candidates);

                $assetCount = rand(
                    1,
                    min(
                        $this->maxPlayersPerTeam,
                        count($candidates)
                    )
                );

                /*
                | Keep packages manageable.
                */

                $package = array_slice(
                    $candidates,
                    0,
                    $assetCount
                );

                $selectedAssets[$teamId] = $package;
            }

            if (count($selectedAssets) !== $teamCount) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create incoming distribution
            |--------------------------------------------------------------------------
            |
            | Assets from Team A go to another team.
            | We shuffle recipients instead of always using a simple
            | A → B → C → A rotation.
            |--------------------------------------------------------------------------
            */

            $recipients = $selectedTeams;

            shuffle($recipients);

            /*
            | Prevent a team from receiving its own assets.
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
            | Build packages by receiving team
            |--------------------------------------------------------------------------
            */

            $incomingByTeam = [];

            foreach ($selectedTeams as $index => $fromTeamId) {

                $toTeamId = $recipients[$index];

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
            | Validate every team's package balance
            |--------------------------------------------------------------------------
            */

            $balanced = true;

            foreach ($selectedTeams as $teamId) {

                $outgoing =
                    $selectedAssets[$teamId] ?? [];

                $incoming =
                    $incomingByTeam[$teamId] ?? [];

                $outgoingValue =
                    $this->getPackageValue($outgoing);

                $incomingValue =
                    $this->getPackageValue($incoming);

                if (!$this->isPackageBalanced(
                    $outgoing,
                    $incoming
                )) {
                    $balanced = false;
                    break;
                }

                /*
                |--------------------------------------------------------------------------
                | Prevent absurd package differences
                |--------------------------------------------------------------------------
                */

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

                if ($differencePercentage > 25) {
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

                        $player = $asset['player'];

                        $tradePlayerRows[] = [
                            'trade_proposal_id' => $tradeProposalId,
                            'player_id' => $player->player_id,
                            'draft_pick_right_id' => null,
                            'from_team_id' => $fromTeamId,
                            'to_team_id' => $toTeamId,
                            'player_name' => $player->name,
                            'role' => $player->role,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $usedPlayers[] =
                            (int) $player->player_id;
                    } else {

                        $pick = $asset['pick'];

                        $tradePlayerRows[] = [
                            'trade_proposal_id' => $tradeProposalId,
                            'player_id' => null,
                            'draft_pick_right_id' => $pick->id,
                            'from_team_id' => $fromTeamId,
                            'to_team_id' => $toTeamId,
                            'player_name' => null,
                            'role' => 'draft pick',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

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
                'trade_proposal_id' => $tradeProposalId,
                'team_count' => $teamCount,
                'players' => $tradePlayerRows,
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
    | AUTOMATED TRADE DECISION
    |--------------------------------------------------------------------------
    */

    public function automatedTradeDecision($isOffSeason)
    {
    
        $seasonId = $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $proposals = DB::table('trade_proposals')
            ->where('season_id', $seasonId)
            ->where('status', 'pending')
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
                        'trade_proposal_id' => $proposal->id,
                        'status' => 'rejected',
                        'reason' => 'Trade contains fewer than two assets.',
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validate all assets
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
                        'trade_proposal_id' => $proposal->id,
                        'status' => 'rejected',
                        'reason' => $validation['reason'],
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Evaluate every receiving team
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
                        $tradePlayers
                        ->where(
                            'from_team_id',
                            $teamId
                        );

                    $incoming =
                        $tradePlayers
                        ->where(
                            'to_team_id',
                            $teamId
                        );

                    $evaluation =
                        $this->evaluateTeamPackage(
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
                        'trade_proposal_id' => $proposal->id,
                        'status' => 'rejected',
                        'evaluations' => $evaluations,
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
                        $isOffSeason
                    );

                if (!$execution['success']) {

                    DB::rollBack();

                    $decisions[] = [
                        'trade_proposal_id' => $proposal->id,
                        'status' => 'rejected',
                        'reason' => $execution['reason'],
                    ];

                    continue;
                }

                DB::commit();

                $decisions[] = [
                    'trade_proposal_id' => $proposal->id,
                    'status' => 'approved',
                    'evaluations' => $evaluations,
                ];
            } catch (\Throwable $e) {

                DB::rollBack();

                Log::error(
                    'Automated trade decision failed.',
                    [
                        'trade_proposal_id' => $proposal->id,
                        'error' => $e->getMessage(),
                    ]
                );

                $decisions[] = [
                    'trade_proposal_id' => $proposal->id,
                    'status' => 'rejected',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'season_id' => $seasonId,
            'decisions' => $decisions,
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
                        'reason' => 'Player no longer exists.',
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
            | Draft pick
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
                        'reason' => 'Draft pick no longer exists.',
                    ];
                }

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

                if (
                    (bool) $pick->is_traded &&
                    !empty($pick->trade_proposal_id)
                ) {

                    return [
                        'valid' => false,
                        'reason' =>
                        'Draft pick has already been traded.',
                    ];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Team cannot trade asset to itself
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
    | EVALUATE TEAM PACKAGE
    |--------------------------------------------------------------------------
    */

    private function evaluateTeamPackage(
        int $teamId,
        $outgoing,
        $incoming
    ): array {

        $coach =
            $this->coachDecisionService
            ->getTeamCoach($teamId);

        $outgoingValue = 0;
        $incomingValue = 0;

        $outgoingSalary = 0;
        $incomingSalary = 0;

        /*
        |--------------------------------------------------------------------------
        | Outgoing
        |--------------------------------------------------------------------------
        */

        foreach ($outgoing as $asset) {

            if (!empty($asset->player_id)) {

                $player =
                    DB::table('players')
                    ->where('id', $asset->player_id)
                    ->first();

                if (!$player) {
                    continue;
                }

                $baseValue =
                    $this->calculatePlayerValue(
                        $player
                    );

                $adjustedValue =
                    $this->coachDecisionService
                    ->getTradeValue(
                        $coach,
                        $player,
                        $baseValue
                    );

                $outgoingValue +=
                    $adjustedValue;

                $outgoingSalary +=
                    (float) ($player->salary ?? 0);
            } elseif (!empty($asset->draft_pick_right_id)) {

                $pick =
                    DB::table('draft_pick_rights')
                    ->where(
                        'id',
                        $asset->draft_pick_right_id
                    )
                    ->first();

                if ($pick) {

                    $outgoingValue +=
                        $this->calculateDraftPickValue(
                            $pick
                        );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Incoming
        |--------------------------------------------------------------------------
        */

        foreach ($incoming as $asset) {

            if (!empty($asset->player_id)) {

                $player =
                    DB::table('players')
                    ->where('id', $asset->player_id)
                    ->first();

                if (!$player) {
                    continue;
                }

                $baseValue =
                    $this->calculatePlayerValue(
                        $player
                    );

                $adjustedValue =
                    $this->coachDecisionService
                    ->getTradeValue(
                        $coach,
                        $player,
                        $baseValue
                    );

                $incomingValue +=
                    $adjustedValue;

                $incomingSalary +=
                    (float) ($player->salary ?? 0);
            } elseif (!empty($asset->draft_pick_right_id)) {

                $pick =
                    DB::table('draft_pick_rights')
                    ->where(
                        'id',
                        $asset->draft_pick_right_id
                    )
                    ->first();

                if ($pick) {

                    $incomingValue +=
                        $this->calculateDraftPickValue(
                            $pick
                        );
                }
            }
        }

        $netBenefit =
            $incomingValue -
            $outgoingValue;

        /*
        |--------------------------------------------------------------------------
        | Salary difference
        |--------------------------------------------------------------------------
        */

        $salaryDifference =
            $incomingSalary -
            $outgoingSalary;

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

        /*
        |--------------------------------------------------------------------------
        | Package balance modifier
        |--------------------------------------------------------------------------
        */

        if ($outgoingValue > 0) {

            $differencePercentage =
                (
                    ($incomingValue - $outgoingValue)
                    /
                    $outgoingValue
                ) * 100;

            /*
            | Very bad value for the team.
            */

            if ($differencePercentage <= -20) {
                $approvalChance -= 25;
            }

            /*
            | Excellent value.
            */

            if ($differencePercentage >= 15) {
                $approvalChance += 15;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Salary penalty
        |--------------------------------------------------------------------------
        */

        if (
            $outgoingSalary > 0 &&
            $incomingSalary >
            ($outgoingSalary * 1.25) + 100000
        ) {

            $approvalChance -= 15;
        }

        $approvalChance =
            max(
                5,
                min(
                    95,
                    $approvalChance
                )
            );

        return [
            'team_id' => $teamId,
            'coach_id' => $coach?->id,

            'outgoing_value' => round(
                $outgoingValue,
                2
            ),

            'incoming_value' => round(
                $incomingValue,
                2
            ),

            'net_benefit' => round(
                $netBenefit,
                2
            ),

            'outgoing_salary' => round(
                $outgoingSalary,
                2
            ),

            'incoming_salary' => round(
                $incomingSalary,
                2
            ),

            'salary_difference' => round(
                $salaryDifference,
                2
            ),

            'approval_chance' => round(
                $approvalChance,
                2
            ),
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
    ): array 
    {

        /*
        |--------------------------------------------------------------------------
        | Validate again immediately before execution
        |--------------------------------------------------------------------------
        */

        $validation = $this->validateTradeAssets($tradePlayers);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'reason' => $validation['reason'],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Build complete N-way trade information
        |--------------------------------------------------------------------------
        |
        | Do this BEFORE moving anything.
        |
        | This gives logTrade() the complete picture of the trade:
        |
        | Team A -> Team B
        |   Player 1
        |   Draft Pick
        |
        | Team B -> Team C
        |   Player 2
        |
        | Team C -> Team A
        |   Player 3
        |
        */

        $tradeDetails = [];


        foreach ($tradePlayers as $tradePlayer) {

            $fromTeamId = $tradePlayer->from_team_id;
            $toTeamId   = $tradePlayer->to_team_id;


            /*
            |--------------------------------------------------------------------------
            | Find / create this route
            |--------------------------------------------------------------------------
            */

            $routeKey = $fromTeamId . '_' . $toTeamId;

            if (!isset($tradeDetails[$routeKey])) {

                $tradeDetails[$routeKey] = [
                    'from_team_id' => $fromTeamId,
                    'to_team_id'   => $toTeamId,

                    'players' => [],

                    'draft_picks' => [],
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Player Asset
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->player_id)) {

                $tradeDetails[$routeKey]['players'][] = [
                    'id' => $tradePlayer->player_id,
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Draft Pick Asset
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->draft_pick_right_id)) {

                    $draftPick = DB::table('draft_pick_rights')
                        ->where('id', $tradePlayer->draft_pick_right_id)
                        ->first();

                    if ($draftPick) {

                        $tradeDetails[$routeKey]['draft_picks'][] = [
                            'id' => $draftPick->id,
                            'season_id' => $draftPick->season_id,
                            'round' => $draftPick->round,
                            'pick_number' => $draftPick->pick_number,
                            'original_team_id' => $draftPick->original_team_id,
                            'protections' => $draftPick->protections,
                        ];
                    }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Convert associative array to normal array
        |--------------------------------------------------------------------------
        */

        $tradeDetails = array_values($tradeDetails);


        /*
        |--------------------------------------------------------------------------
        | Move Assets
        |--------------------------------------------------------------------------
        */

        foreach ($tradePlayers as $tradePlayer) {

            /*
            |--------------------------------------------------------------------------
            | Player
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->player_id)) {

                $updated = DB::table('players')
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
            | Draft Pick
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->draft_pick_right_id)) {

                $updated = DB::table('draft_pick_rights')
                    ->where(
                        'id',
                        $tradePlayer->draft_pick_right_id
                    )
                    ->where(
                        'current_owner_id',
                        $tradePlayer->from_team_id
                    )
                    ->update([
                        'current_owner_id' =>
                            $tradePlayer->to_team_id,

                        'is_traded' => 1,

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
        | Log Player Transactions
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We only log AFTER every asset has successfully moved.
        |
        | Every player receives the same complete N-way trade context.
        |
        */

        foreach ($tradePlayers as $tradePlayer) {

            if (empty($tradePlayer->player_id)) {
                continue;
            }


            $logged = self::logTrade(
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
        | Approve Proposal
        |--------------------------------------------------------------------------
        */

        DB::table('trade_proposals')
            ->where(
                'id',
                $proposal->id
            )
            ->update([
                'status' => 'approved',

                'updated_at' => now(),
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
        $tradeProposalId = null,
        $seasonId = null,
        array $tradeDetails = []
    ) 
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Season
            |--------------------------------------------------------------------------
            */

            $seasonId = $seasonId ?? get_current_season_id();

            $tradeType = $isOffSeason
                ? 'off-season trade'
                : 'in-season trade';


            /*
            |--------------------------------------------------------------------------
            | Main Player
            |--------------------------------------------------------------------------
            */

            $player = DB::table('players')
                ->select(
                    'name',
                    'role'
                )
                ->where('id', $playerId)
                ->first();


            /*
            |--------------------------------------------------------------------------
            | Teams
            |--------------------------------------------------------------------------
            */

            $teamFrom = DB::table('teams')
                ->where('id', $teamFromId)
                ->value('name');

            $teamTo = DB::table('teams')
                ->where('id', $teamToId)
                ->value('name');


            if (!$player || !$teamFrom || !$teamTo) {
                return false;
            }


            /*
            |--------------------------------------------------------------------------
            | Build Detailed Trade Description
            |--------------------------------------------------------------------------
            |
            | $tradeDetails example:
            |
            | [
            |     [
            |         'from_team_id' => 1,
            |         'to_team_id'   => 2,
            |         'players' => [
            |             ['id' => 10],
            |             ['id' => 15],
            |         ],
            |         'draft_picks' => [
            |             [
            |                 'season_id' => 15,
            |                 'round'     => 1,
            |                 'pick'      => 5,
            |             ]
            |         ]
            |     ],
            | ]
            |
            */

            $tradeDescription = self::buildTradeDescription(
                $teamFromId,
                $teamToId,
                $playerId,
                $tradeDetails
            );


            /*
            |--------------------------------------------------------------------------
            | Transaction
            |--------------------------------------------------------------------------
            */

            DB::table('transactions')->insert([
                'player_id' => $playerId,
                'season_id' => $seasonId,

                'details' => $tradeDescription,

                'from_team_id' => $teamFromId,
                'to_team_id' => $teamToId,

                'status' => $tradeType,

                'created_at' => now(),
                'updated_at' => now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Trade Log
            |--------------------------------------------------------------------------
            */

            DB::table('trade_logs')->insert([
                'season_id' => $seasonId,

                'trade_proposal_id' => $tradeProposalId,

                'team_from_id' => $teamFromId,
                'team_to_id' => $teamToId,

                'player_id' => $playerId,

                'player_name' => $player->name,
                'role' => $player->role,

                'trade_reason' => $message,

                'created_at' => now(),
                'updated_at' => now(),
            ]);


            return true;

        } catch (\Throwable $e) {

            Log::error(
                'Trade logging failed.',
                [
                    'player_id' => $playerId,
                    'proposal_id' => $tradeProposalId,
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Build Detailed Trade Description
    |--------------------------------------------------------------------------
    */

    private static function buildTradeDescription(
        $teamFromId,
        $teamToId,
        $playerId,
        array $tradeDetails = []
    ) 
    {

        $teamNames = DB::table('teams')
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
            ->pluck('name', 'id');


        /*
        |--------------------------------------------------------------------------
        | If no N-way information was supplied
        |--------------------------------------------------------------------------
        */

        if (empty($tradeDetails)) {

            $player = DB::table('players')
                ->where('id', $playerId)
                ->value('name');

            $from = $teamNames[$teamFromId] ?? 'Unknown Team';
            $to   = $teamNames[$teamToId] ?? 'Unknown Team';

            return "Traded {$player} ({$from}) to {$to}.";
        }


        /*
        |--------------------------------------------------------------------------
        | Determine number of teams
        |--------------------------------------------------------------------------
        */

        $teams = collect($tradeDetails)
            ->flatMap(function ($trade) {
                return [
                    $trade['from_team_id'] ?? null,
                    $trade['to_team_id'] ?? null,
                ];
            })
            ->filter()
            ->unique()
            ->values();

        $teamCount = $teams->count();

        $tradeLabel = match ($teamCount) {
            2 => '2-team trade',
            3 => '3-team trade',
            4 => '4-team trade',
            default => "{$teamCount}-team trade",
        };


        /*
        |--------------------------------------------------------------------------
        | Build Each Team's Outgoing Assets
        |--------------------------------------------------------------------------
        */

        $teamSummaries = [];

        foreach ($tradeDetails as $trade) {

            $fromId = $trade['from_team_id'] ?? null;
            $toId   = $trade['to_team_id'] ?? null;

            if (!$fromId || !$toId) {
                continue;
            }

            $fromName = $teamNames[$fromId] ?? 'Unknown Team';
            $toName   = $teamNames[$toId] ?? 'Unknown Team';

            $assets = [];


            /*
            |----------------------------------------------------------------------
            | Players
            |----------------------------------------------------------------------
            */

            $playerIds = collect($trade['players'] ?? [])
                ->map(function ($player) {
                    return is_array($player)
                        ? ($player['id'] ?? null)
                        : $player;
                })
                ->filter()
                ->values();

            if ($playerIds->isNotEmpty()) {

                $players = DB::table('players')
                    ->whereIn('id', $playerIds)
                    ->pluck('name', 'id');

                foreach ($playerIds as $id) {

                    if (isset($players[$id])) {
                        $assets[] = $players[$id];
                    }
                }
            }


            /*
            |----------------------------------------------------------------------
            | Draft Picks
            |----------------------------------------------------------------------
            */

            foreach ($trade['draft_picks'] ?? [] as $pick) {

                $season = $pick['season_id'] ?? null;
                $round = $pick['round'] ?? null;
                $pickNumber = $pick['pick_number'] ?? null;
                $protections = $pick['protections'] ?? null;

                if (!$season || !$round) {
                    continue;
                }

                $roundText = match ((int) $round) {
                    1 => '1st',
                    2 => '2nd',
                    3 => '3rd',
                    default => $round . 'th',
                };

                $pickText = "Season {$season} {$roundText} Round Draft Pick";

                if ($pickNumber !== null) {
                    $pickText .= " (#{$pickNumber})";
                }

                if (!empty($protections)) {
                    $pickText .= " ({$protections})";
                }

                $assets[] = $pickText;
            }
            /*
            |----------------------------------------------------------------------
            | Future Draft Rights
            |----------------------------------------------------------------------
            */

            foreach ($trade['draft_rights'] ?? [] as $right) {

                $season = $right['season_id'] ?? null;

                if ($season) {
                    $assets[] = "Season {$season} Draft Rights";
                }
            }


            /*
            |----------------------------------------------------------------------
            | Cash / Other Assets
            |----------------------------------------------------------------------
            */

            foreach ($trade['other_assets'] ?? [] as $asset) {

                if (!empty($asset)) {
                    $assets[] = $asset;
                }
            }


            /*
            |----------------------------------------------------------------------
            | Build Summary
            |----------------------------------------------------------------------
            */

            if (!empty($assets)) {

                $teamSummaries[] =
                    "{$fromName} sent "
                    . implode(', ', $assets)
                    . " to {$toName}";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Final Description
        |--------------------------------------------------------------------------
        */

        $mainPlayer = DB::table('players')
            ->where('id', $playerId)
            ->value('name');

        $mainFrom = $teamNames[$teamFromId] ?? 'Unknown Team';
        $mainTo   = $teamNames[$teamToId] ?? 'Unknown Team';


        $description =
            "Traded {$mainPlayer} ({$mainFrom}) to {$mainTo} "
            . "as part of a {$tradeLabel}. ";


        if (!empty($teamSummaries)) {

            $description .=
                "Trade details: "
                . implode('; ', $teamSummaries)
                . '.';
        }


        return $description;
    }



    /*
    |--------------------------------------------------------------------------
    | END IN-SEASON TRADE WINDOW
    |--------------------------------------------------------------------------
    */

    public function endInSeasonTradeWindow()
    {
        $seasonId =
            get_current_season_id();

        DB::table('seasons')
            ->where(
                'id',
                $seasonId
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
    |
    | ArchiveService intentionally does NOT belong here.
    |
    */

    public function endOffSeasonTradeWindow()
    {
        $seasonId = get_current_season_id();


        /*
        |--------------------------------------------------------------------------
        | End trade window only.
        |
        | Season archiving should be handled by the season/off-season
        | orchestration service.
        |--------------------------------------------------------------------------
        */
        $this->storyLineService->generateStoryLine();
        $this->archive->runArchives();

        DB::table('seasons')
            ->where('id',$seasonId)
            ->update(['status' =>config('timeline.off_season_trade'),]);

        
        return response()->json([
            'message' =>
            'Trade window ended!',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FIND UNDERPERFORMING PLAYERS
    |--------------------------------------------------------------------------
    */

    public function findUnderperformingPlayers(
        int $teamId
    ) {

        $seasonId =
            get_current_season_id();

        /*
        |--------------------------------------------------------------------------
        | Coach
        |--------------------------------------------------------------------------
        */

        $coach =
            $this->coachDecisionService
            ->getTeamCoach($teamId);

        /*
        |--------------------------------------------------------------------------
        | Current players + stats
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Previous season
        |--------------------------------------------------------------------------
        */

        $previousSeasonId =
            get_previous_season_id();

        $previousStats =
            DB::table(
                'player_season_stats_archives'
            )
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

            /*
            | No previous season means we cannot measure decline.
            */

            if (!$previous) {
                continue;
            }

            $currentScore =
                $this->calculatePlayerValue(
                    $player
                );

            $previousScore =
                $this->calculatePlayerValue(
                    $previous
                );

            if ($previousScore <= 0) {
                continue;
            }

            /*
            | Player improved.
            */

            if ($currentScore >= $previousScore) {
                continue;
            }

            $decline =
                $previousScore -
                $currentScore;

            $declinePercentage =
                (
                    $decline /
                    $previousScore
                ) * 100;

            $superDecline =
                $declinePercentage >= 20;

            $expiringContract =
                (
                    (int) (
                        $player->contract_years
                        ?? 0
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

            $tradeable->push(
                $player
            );
        }

        return $tradeable
            ->sortByDesc(
                'trade_pressure'
            )
            ->values();
    }

    /**
     * Get tradeable future draft picks.
     *
     * Rules:
     * - Current season picks are NOT tradeable.
     * - Only the next 3 draft seasons are tradeable.
     * - Maximum 3 future draft picks can be traded by a team.
     * - Already traded/proposed picks are excluded.
     */
    public function getTradeableDraftPicks(int $teamId)
    {
        $currentSeasonId = (int) get_current_season_id();

        return DB::table('draft_pick_rights')
            ->where('current_owner_id', $teamId)
            ->whereBetween('season_id', [
                $currentSeasonId + 1,
                $currentSeasonId + 5,
            ])
            ->where('is_used', 0)
            ->whereNull('trade_proposal_id')
            ->orderBy('season_id')
            ->orderBy('round')
            ->get();
    }
    /*
    |--------------------------------------------------------------------------
    | DRAFT PICK VALUE
    |--------------------------------------------------------------------------
    */

    private function calculateDraftPickValue(
        $pick
    ): float {

        $round =
            (int) ($pick->round ?? 2);

        $season =
            (int) ($pick->season_id ?? 0);

        $currentSeason =
            get_current_season_id();

        $yearsAway =
            max(
                0,
                $season -
                    $currentSeason
            );

        /*
        |--------------------------------------------------------------------------
        | Base value
        |--------------------------------------------------------------------------
        */

        if ($round === 1) {

            $value = 80;
        } elseif ($round === 2) {

            $value = 40;
        } else {

            $value = 20;
        }

        /*
        |--------------------------------------------------------------------------
        | Future pick discount
        |--------------------------------------------------------------------------
        */

        if ($yearsAway > 0) {

            $value *= pow(
                0.90,
                $yearsAway
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Protection
        |--------------------------------------------------------------------------
        */

        $protection =
            strtolower(
                trim(
                    (string) (
                        $pick->protections
                        ?? ''
                    )
                )
            );

        if (
            $protection !== '' &&
            $protection !== 'none'
        ) {

            $value *= 0.90;
        }

        return round(
            $value,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PACKAGE VALUE
    |--------------------------------------------------------------------------
    */

    private function getPackageValue(
        array $assets
    ): float {

        $value = 0;

        foreach ($assets as $asset) {

            $value +=
                (float) (
                    $asset['value']
                    ?? 0
                );
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | PACKAGE BALANCE
    |--------------------------------------------------------------------------
    */

    private function isPackageBalanced(
        array $outgoing,
        array $incoming
    ): bool {

        if (
            empty($outgoing) ||
            empty($incoming)
        ) {
            return false;
        }

        $outgoingValue =
            $this->getPackageValue(
                $outgoing
            );

        $incomingValue =
            $this->getPackageValue(
                $incoming
            );

        if (
            $outgoingValue <= 0 ||
            $incomingValue <= 0
        ) {
            return false;
        }

        $difference =
            abs(
                $outgoingValue -
                    $incomingValue
            );

        $largest =
            max(
                $outgoingValue,
                $incomingValue
            );

        $differencePercentage =
            (
                $difference /
                $largest
            ) * 100;

        return
            $differencePercentage <=
            $this->maxPackageDifference;
    }

    /*
    |--------------------------------------------------------------------------
    | PLAYER VALUE
    |--------------------------------------------------------------------------
    */

    private function calculatePlayerValue(
        $player
    ): float {

        if (!$player) {
            return 0;
        }

        return (float)
        $this->valuationService
            ->calculatePlayerValue(
                $player
            );
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY PERFORMANCE SCORE
    |--------------------------------------------------------------------------
    */

    private function calculatePerformanceScore(
        $player
    ): float {

        return $this->calculatePlayerValue(
            $player
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY TRADE BALANCE
    |--------------------------------------------------------------------------
    */

    private function validateTradeBalance(
        array $players
    ): bool {

        if (count($players) < 2) {
            return false;
        }

        $scores =
            array_map(
                function ($player) {

                    return (float) (
                        $player->composite_score
                        ?? 0
                    );
                },
                $players
            );

        $maxScore =
            max($scores);

        $minScore =
            min($scores);

        if (
            ($maxScore - $minScore)
            > 15
        ) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | COACH ADJUSTED TRADE VALUE
    |--------------------------------------------------------------------------
    */

    private function calculateCoachAdjustedTradeValue(
        int $teamId,
        $player,
        float $baseValue
    ): float {

        $coach =
            $this->coachDecisionService
            ->getTeamCoach(
                $teamId
            );

        return (float)
        $this->coachDecisionService
            ->getTradeValue(
                $coach,
                $player,
                $baseValue
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SINGLE PLAYER TRADE EVALUATION
    |--------------------------------------------------------------------------
    */

    private function evaluateTradeForTeam(
        int $teamId,
        $outgoingPlayer,
        $incomingPlayer,
        float $outgoingBaseValue,
        float $incomingBaseValue
    ): array {

        $coach =
            $this->coachDecisionService
            ->getTeamCoach(
                $teamId
            );

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

        $netBenefit =
            $incomingValue -
            $outgoingValue;

        $approvalChance =
            $this->coachDecisionService
            ->getTradeApprovalChance(
                $coach,
                $netBenefit
            );

        return [
            'team_id' =>
            $teamId,

            'coach_id' =>
            $coach?->id,

            'outgoing_value' =>
            $outgoingValue,

            'incoming_value' =>
            $incomingValue,

            'net_benefit' =>
            $netBenefit,

            'approval_chance' =>
            $approvalChance,
        ];
    }
}
