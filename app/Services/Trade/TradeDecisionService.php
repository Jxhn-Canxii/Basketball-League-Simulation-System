<?php

namespace App\Services\Trade;

ini_set('max_execution_time', 1048);

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
    | Trade Generation Settings
    |--------------------------------------------------------------------------
    */

    protected int $maxGeneratedTrades = 70;

    protected int $maxTeamsPerTrade = 6;

    /*
     * Maximum players a single team can send.
     *
     * This is now actually reachable because blockbuster
     * packages can select more than 1-2 players.
     */
    protected int $maxPlayersPerTeam = 5;

    protected int $maxPicksPerTeam = 2;

    /*
     * Increased from 25%.
     *
     * This makes the generator less restrictive and allows
     * more realistic uneven-but-reasonable packages.
     */
    protected float $maxPackageDifferencePercentage = 50.0;

    /*
     * Chance that a generated proposal becomes a larger
     * blockbuster-style package.
     */
    protected int $blockbusterTradeChance = 35;

    /*
     * Number of distribution attempts for each proposal.
     */
    protected int $maxDistributionAttempts = 100;

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
    | Generate Trade Proposals
    |--------------------------------------------------------------------------
    */

    public function generateTradeProposals($isOffSeason)
    {
        return $this->generateTradeProposalsInternal(
            (bool) $isOffSeason
        );
    }


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
        | Get Teams
        |--------------------------------------------------------------------------
        */

        $teams = DB::table('teams')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        shuffle($teams);

        /*
        |--------------------------------------------------------------------------
        | Existing Trade Locks
        |--------------------------------------------------------------------------
        */

        $usedPlayers = $this->getUsedTradePlayers($seasonId);

        $usedPicks = $this->getPendingTradePicks();

        /*
        |--------------------------------------------------------------------------
        | Build Tradeable Assets
        |--------------------------------------------------------------------------
        */

        $assetsByTeam = [];

        foreach ($teams as $teamId) {

            $players = $this->findUnderperformingPlayers($teamId);

            $assets = [];

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
                    $this->tradeValuation
                    ->calculatePlayerValue($player);

                $assets[] = [
                    'type' => 'player',
                    'player' => $player,
                    'value' => (float) $player->composite_score,
                    'salary' => (float) ($player->salary ?? 0),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Draft Picks
            |--------------------------------------------------------------------------
            */

            $pickAssets =
                $this->getTradeableDraftPicks(
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
                    $this->tradeValuation
                    ->calculateDraftPickValue($pick);

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
                'message' =>
                'Not enough teams with tradeable assets.',
                'trades' => [],
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Proposals
        |--------------------------------------------------------------------------
        */

        $createdTrades = [];

        $attempts = 0;

        $maxAttempts = 300;

        while (
            count($createdTrades) <
            $this->maxGeneratedTrades
            &&
            $attempts < $maxAttempts
        ) {

            $attempts++;

            $availableTeams =
                array_keys($assetsByTeam);

            if (count($availableTeams) < 2) {
                break;
            }

            shuffle($availableTeams);

            /*
            |--------------------------------------------------------------------------
            | Select Number Of Teams
            |--------------------------------------------------------------------------
            */

            $teamCount = rand(
                2,
                min(
                    $this->maxTeamsPerTrade,
                    count($availableTeams)
                )
            );

            $selectedTeams =
                array_slice(
                    $availableTeams,
                    0,
                    $teamCount
                );

            /*
            |--------------------------------------------------------------------------
            | Blockbuster Decision
            |--------------------------------------------------------------------------
            */

            $isBlockbuster =
                rand(1, 100) <=
                $this->blockbusterTradeChance;

            /*
            |--------------------------------------------------------------------------
            | Select Assets For Each Team
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

                                if (
                                    $asset['type'] ===
                                    'player'
                                ) {
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

                $package =
                    $this->selectTradePackage(
                        $candidates,
                        $isBlockbuster
                    );

                if (empty($package)) {
                    continue 2;
                }

                $selectedAssets[$teamId] = $package;
            }

            if (
                count($selectedAssets) !==
                count($selectedTeams)
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Distribute Individual Assets
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | We no longer do:
            |
            | A -> B
            | B -> C
            | C -> A
            |
            | for the entire package.
            |
            | Each individual asset can go to a different team.
            |
            */

            $distribution =
                $this->buildAssetDistribution(
                    $selectedTeams,
                    $selectedAssets
                );

            if (!$distribution) {
                continue;
            }

            $routes =
                $distribution['routes'];

            $incomingByTeam =
                $distribution['incoming'];

            /*
            |--------------------------------------------------------------------------
            | Create Proposal
            |--------------------------------------------------------------------------
            */

            DB::beginTransaction();

            try {

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

                foreach ($routes as $route) {

                    $fromTeamId =
                        $route['from_team_id'];

                    $toTeamId =
                        $route['to_team_id'];

                    $asset =
                        $route['asset'];

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

                        $usedPicks[] =
                            (int) $pick->id;
                    }
                }

                if (empty($tradePlayerRows)) {

                    DB::rollBack();

                    continue;
                }

                DB::table('trade_players')
                    ->insert($tradePlayerRows);

                DB::commit();

                $createdTrades[] = [
                    'trade_proposal_id' =>
                    $tradeProposalId,

                    'team_count' =>
                    $teamCount,

                    'blockbuster' =>
                    $isBlockbuster,

                    'players' =>
                    $tradePlayerRows,
                ];
            } catch (\Throwable $e) {

                DB::rollBack();

                Log::error(
                    'Trade proposal generation failed.',
                    [
                        'error' =>
                        $e->getMessage(),
                    ]
                );
            }
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
    | Select Trade Package
    |--------------------------------------------------------------------------
    */

    private function selectTradePackage(
        array $candidates,
        bool $blockbuster
    ): array {

        $players =
            array_values(
                array_filter(
                    $candidates,
                    fn($asset) =>
                    $asset['type'] === 'player'
                )
            );

        $picks =
            array_values(
                array_filter(
                    $candidates,
                    fn($asset) =>
                    $asset['type'] === 'draft_pick'
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Sort By Value
        |--------------------------------------------------------------------------
        */

        usort(
            $players,
            fn($a, $b) =>
            $b['value'] <=> $a['value']
        );

        usort(
            $picks,
            fn($a, $b) =>
            $b['value'] <=> $a['value']
        );

        $selected = [];

        /*
        |--------------------------------------------------------------------------
        | Player Selection
        |--------------------------------------------------------------------------
        */

        if (!empty($players)) {

            $availablePlayers =
                count($players);

            if ($blockbuster) {

                /*
                 * Blockbuster:
                 *
                 * 2-5 players when available.
                 *
                 * Always consider the highest-value player.
                 */
                $minPlayers =
                    min(
                        2,
                        $availablePlayers
                    );

                $maxPlayers =
                    min(
                        $this->maxPlayersPerTeam,
                        $availablePlayers
                    );

                $playerCount =
                    rand(
                        $minPlayers,
                        $maxPlayers
                    );

                /*
                 * Always include the highest-value
                 * player for a genuine blockbuster
                 * when multiple players are available.
                 */
                $selected[] =
                    $players[0];

                $remainingPlayers =
                    array_slice(
                        $players,
                        1
                    );

                shuffle($remainingPlayers);

                $remainingCount =
                    max(
                        0,
                        $playerCount - 1
                    );

                if ($remainingCount > 0) {

                    $selected =
                        array_merge(
                            $selected,
                            array_slice(
                                $remainingPlayers,
                                0,
                                $remainingCount
                            )
                        );
                }
            } else {

                /*
                 * Normal trade:
                 * 1-2 players.
                 */
                $maxPlayers =
                    min(
                        2,
                        $availablePlayers,
                        $this->maxPlayersPerTeam
                    );

                $playerCount =
                    rand(
                        1,
                        $maxPlayers
                    );

                /*
                 * Prefer higher-value players,
                 * but keep some randomness.
                 */
                $playerPool =
                    array_slice(
                        $players,
                        0,
                        min(
                            count($players),
                            4
                        )
                    );

                shuffle($playerPool);

                $selected =
                    array_slice(
                        $playerPool,
                        0,
                        $playerCount
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Draft Pick Selection
        |--------------------------------------------------------------------------
        */

        if (!empty($picks)) {

            $pickChance =
                $blockbuster
                ? 65
                : 40;

            if (
                rand(1, 100) <=
                $pickChance
            ) {

                $maxPicks =
                    min(
                        $this->maxPicksPerTeam,
                        count($picks)
                    );

                if ($maxPicks > 0) {

                    $pickCount =
                        rand(
                            1,
                            $maxPicks
                        );

                    /*
                     * Don't always use the highest
                     * pick so generated trades vary.
                     */
                    $pickPool =
                        array_slice(
                            $picks,
                            0,
                            min(
                                count($picks),
                                4
                            )
                        );

                    shuffle($pickPool);

                    $selected =
                        array_merge(
                            $selected,
                            array_slice(
                                $pickPool,
                                0,
                                $pickCount
                            )
                        );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure Package Is Not Empty
        |--------------------------------------------------------------------------
        */

        if (empty($selected)) {

            $selected[] =
                $candidates[array_rand($candidates)];
        }

        return $selected;
    }

    /*
    |--------------------------------------------------------------------------
    | Build Individual Asset Distribution
    |--------------------------------------------------------------------------
    */

    private function buildAssetDistribution(
        array $selectedTeams,
        array $selectedAssets
    ): ?array {

        $teamCount =
            count($selectedTeams);

        $attempts =
            $teamCount >= 3
            ? $this->maxDistributionAttempts
            : 40;

        for (
            $attempt = 0;
            $attempt < $attempts;
            $attempt++
        ) {

            $routes = [];

            $incomingByTeam = [];

            foreach ($selectedTeams as $teamId) {
                $incomingByTeam[$teamId] = [];
            }

            /*
            |--------------------------------------------------------------------------
            | Route Every Asset Independently
            |--------------------------------------------------------------------------
            */

            foreach ($selectedTeams as $fromTeamId) {

                $assets =
                    $selectedAssets[$fromTeamId]
                    ?? [];

                if (empty($assets)) {
                    continue 2;
                }

                $otherTeams =
                    array_values(
                        array_filter(
                            $selectedTeams,
                            fn($teamId) =>
                            (int) $teamId !==
                                (int) $fromTeamId
                        )
                    );

                shuffle($otherTeams);

                /*
                |--------------------------------------------------------------------------
                | Spread Multiple Assets
                |--------------------------------------------------------------------------
                |
                | If a team has multiple assets in a 3+
                | team trade, intentionally spread the
                | first assets across different teams.
                |
                */

                foreach (
                    $assets as $index => $asset
                ) {

                    if (
                        $teamCount >= 3 &&
                        count($assets) >= 2 &&
                        $index < count($otherTeams)
                    ) {

                        $toTeamId =
                            $otherTeams[$index];
                    } else {

                        $toTeamId =
                            $otherTeams[array_rand($otherTeams)];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Safety
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $fromTeamId ===
                        (int) $toTeamId
                    ) {
                        continue 2;
                    }

                    $routes[] = [
                        'from_team_id' =>
                        (int) $fromTeamId,

                        'to_team_id' =>
                        (int) $toTeamId,

                        'asset' =>
                        $asset,
                    ];

                    $incomingByTeam[$toTeamId][] =
                        $asset;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Every Team Must Receive Something
            |--------------------------------------------------------------------------
            */

            foreach ($selectedTeams as $teamId) {

                if (
                    empty($incomingByTeam[$teamId])
                ) {
                    continue 2;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Complex Routing
            |--------------------------------------------------------------------------
            */

            if (
                $teamCount >= 3 &&
                !$this->hasComplexCrossRouting(
                    $routes,
                    $selectedTeams
                )
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Package Balance
            |--------------------------------------------------------------------------
            */

            if (
                !$this->validateDistributionBalance(
                    $selectedTeams,
                    $selectedAssets,
                    $incomingByTeam
                )
            ) {
                continue;
            }

            return [
                'routes' =>
                $routes,

                'incoming' =>
                $incomingByTeam,
            ];
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Complex Cross Routing
    |--------------------------------------------------------------------------
    */

    private function hasComplexCrossRouting(
        array $routes,
        array $selectedTeams
    ): bool {

        $destinationsBySource = [];

        $sourcesByDestination = [];

        foreach ($routes as $route) {

            $from =
                (int) $route['from_team_id'];

            $to =
                (int) $route['to_team_id'];

            $destinationsBySource[$from][] =
                $to;

            $sourcesByDestination[$to][] =
                $from;
        }

        /*
        |--------------------------------------------------------------------------
        | At Least One Team Sends To Multiple Teams
        |--------------------------------------------------------------------------
        */

        $sourceSplits = false;

        foreach ($selectedTeams as $teamId) {

            $destinations =
                array_unique(
                    $destinationsBySource[$teamId]
                        ?? []
                );

            if (count($destinations) >= 2) {

                $sourceSplits = true;

                break;
            }
        }

        if (!$sourceSplits) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | At Least One Team Receives From Multiple Teams
        |--------------------------------------------------------------------------
        */

        $destinationSplits = false;

        foreach ($selectedTeams as $teamId) {

            $sources =
                array_unique(
                    $sourcesByDestination[$teamId]
                        ?? []
                );

            if (count($sources) >= 2) {

                $destinationSplits = true;

                break;
            }
        }

        if (!$destinationSplits) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Reject Pure Round-Robin
        |--------------------------------------------------------------------------
        */

        $pureRoundRobin = true;

        foreach ($selectedTeams as $teamId) {

            $destinations =
                array_unique(
                    $destinationsBySource[$teamId]
                        ?? []
                );

            if (count($destinations) !== 1) {

                $pureRoundRobin = false;

                break;
            }
        }

        if ($pureRoundRobin) {

            foreach ($selectedTeams as $teamId) {

                $sources =
                    array_unique(
                        $sourcesByDestination[$teamId]
                            ?? []
                    );

                if (count($sources) !== 1) {

                    $pureRoundRobin = false;

                    break;
                }
            }
        }

        if ($pureRoundRobin) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Distribution Balance
    |--------------------------------------------------------------------------
    */

    private function validateDistributionBalance(
        array $selectedTeams,
        array $selectedAssets,
        array $incomingByTeam
    ): bool {

        foreach ($selectedTeams as $teamId) {

            $outgoing =
                $selectedAssets[$teamId]
                ?? [];

            $incoming =
                $incomingByTeam[$teamId]
                ?? [];

            if (
                empty($outgoing) ||
                empty($incoming)
            ) {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Existing Trade Valuation Rules
            |--------------------------------------------------------------------------
            */

            if (
                !$this->tradeValuation
                    ->isPackageBalanced(
                        $outgoing,
                        $incoming
                    )
            ) {
                return false;
            }

            $outgoingValue =
                $this->tradeValuation
                ->getPackageValue(
                    $outgoing
                );

            $incomingValue =
                $this->tradeValuation
                ->getPackageValue(
                    $incoming
                );

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
                ($difference / $baseValue) *
                100;

            /*
            |--------------------------------------------------------------------------
            | Relaxed 35% Limit
            |--------------------------------------------------------------------------
            */

            if (
                $differencePercentage >
                $this->maxPackageDifferencePercentage
            ) {
                return false;
            }
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Used Players
    |--------------------------------------------------------------------------
    */

    private function getUsedTradePlayers(
        int $seasonId
    ): array {

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
                fn($id) =>
                (int) $id
            )
            ->unique()
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Pending Picks
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
                fn($id) =>
                (int) $id
            )
            ->unique()
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Automated Trade Decision
    |--------------------------------------------------------------------------
    */

    public function automatedTradeDecision(
        $isOffSeason
    ) {

        $seasonId =
            $isOffSeason
            ? get_current_season_id() + 1
            : get_current_season_id();

        $proposals =
            DB::table('trade_proposals')
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
                | Use BOTH sending and receiving teams
                |--------------------------------------------------------------------------
                */

                $teams =
                    $tradePlayers
                    ->pluck('from_team_id')
                    ->merge(
                        $tradePlayers
                            ->pluck('to_team_id')
                    )
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
    | Validate Trade Assets
    |--------------------------------------------------------------------------
    */

    private function validateTradeAssets(
        $tradePlayers
    ): array {

        $seenPlayers = [];

        $seenPicks = [];

        $outgoingTeams = [];

        $incomingTeams = [];

        foreach ($tradePlayers as $tradePlayer) {

            $fromTeamId =
                (int) $tradePlayer->from_team_id;

            $toTeamId =
                (int) $tradePlayer->to_team_id;

            $outgoingTeams[$fromTeamId] = true;

            $incomingTeams[$toTeamId] = true;

            /*
            |--------------------------------------------------------------------------
            | Prevent Self Trade
            |--------------------------------------------------------------------------
            */

            if (
                $fromTeamId ===
                $toTeamId
            ) {
                return [
                    'valid' => false,
                    'reason' =>
                    'An asset cannot be traded to the same team.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Player
            |--------------------------------------------------------------------------
            */

            if (!empty($tradePlayer->player_id)) {

                $playerId =
                    (int) $tradePlayer->player_id;

                if (
                    in_array(
                        $playerId,
                        $seenPlayers,
                        true
                    )
                ) {
                    return [
                        'valid' => false,
                        'reason' =>
                        'The same player appears more than once in the trade.',
                    ];
                }

                $seenPlayers[] =
                    $playerId;

                $player =
                    DB::table('players')
                    ->where(
                        'id',
                        $playerId
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
                    $fromTeamId
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

            if (
                !empty($tradePlayer->draft_pick_right_id)
            ) {

                $pickId =
                    (int) $tradePlayer
                        ->draft_pick_right_id;

                if (
                    in_array(
                        $pickId,
                        $seenPicks,
                        true
                    )
                ) {
                    return [
                        'valid' => false,
                        'reason' =>
                        'The same draft pick appears more than once in the trade.',
                    ];
                }

                $seenPicks[] =
                    $pickId;

                $pick =
                    DB::table('draft_pick_rights')
                    ->where(
                        'id',
                        $pickId
                    )
                    ->first();

                if (!$pick) {

                    return [
                        'valid' => false,
                        'reason' =>
                        'Draft pick no longer exists.',
                    ];
                }

                if (
                    (int) $pick->current_owner_id !==
                    $fromTeamId
                ) {

                    return [
                        'valid' => false,
                        'reason' =>
                        'Draft pick is no longer owned by the original team.',
                    ];
                }

                if ((bool) $pick->is_used) {

                    return [
                        'valid' => false,
                        'reason' =>
                        'Draft pick has already been used.',
                    ];
                }

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
                        $pickId
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
        }

        /*
        |--------------------------------------------------------------------------
        | Every Participant Must Send And Receive
        |--------------------------------------------------------------------------
        */

        $allTeams =
            array_unique(
                array_merge(
                    array_keys($outgoingTeams),
                    array_keys($incomingTeams)
                )
            );

        foreach ($allTeams as $teamId) {

            if (
                !isset(
                    $outgoingTeams[$teamId]
                )
                ||
                !isset(
                    $incomingTeams[$teamId]
                )
            ) {

                return [
                    'valid' => false,
                    'reason' =>
                    'Every participating team must both send and receive an asset.',
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
    | Execute Trade
    |--------------------------------------------------------------------------
    */

    private function executeTrade(
        $proposal,
        $tradePlayers,
        bool $isOffSeason
    ): array {

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

        $tradeDetails = [];

        foreach ($tradePlayers as $tradePlayer) {

            $fromTeamId =
                $tradePlayer->from_team_id;

            $toTeamId =
                $tradePlayer->to_team_id;

            $routeKey =
                $fromTeamId .
                '_' .
                $toTeamId;

            if (
                !isset(
                    $tradeDetails[$routeKey]
                )
            ) {

                $tradeDetails[$routeKey] = [
                    'from_team_id' =>
                    $fromTeamId,

                    'to_team_id' =>
                    $toTeamId,

                    'players' => [],

                    'draft_picks' => [],
                ];
            }

            if (
                !empty($tradePlayer->player_id)
            ) {

                $tradeDetails[$routeKey]['players'][] = [
                    'id' =>
                    $tradePlayer->player_id,
                ];
            }

            if (
                !empty($tradePlayer->draft_pick_right_id)
            ) {

                $draftPick =
                    DB::table('draft_pick_rights')
                    ->where(
                        'id',
                        $tradePlayer
                            ->draft_pick_right_id
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
            array_values(
                $tradeDetails
            );

        /*
        |--------------------------------------------------------------------------
        | Transfer Players / Picks
        |--------------------------------------------------------------------------
        */

        foreach ($tradePlayers as $tradePlayer) {

            if (
                !empty($tradePlayer->player_id)
            ) {

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

            if (
                !empty($tradePlayer->draft_pick_right_id)
            ) {

                $updated =
                    DB::table('draft_pick_rights')
                    ->where(
                        'id',
                        $tradePlayer
                            ->draft_pick_right_id
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
        | Log Player Trades
        |--------------------------------------------------------------------------
        */

        foreach ($tradePlayers as $tradePlayer) {

            if (
                empty($tradePlayer->player_id)
            ) {
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
        | Approve Proposal
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
    | Reject Proposal
    |--------------------------------------------------------------------------
    */

    private function rejectTradeProposal(
        $proposalId
    ): void {

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
    | Trade Logging
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
    | Trade Description
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
                    ->flatMap(
                        function ($trade) {
                            return [
                                $trade['from_team_id'] ?? null,

                                $trade['to_team_id'] ?? null,
                            ];
                        }
                    )
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
                $teamNames[$teamFromId]
                ??
                'Unknown Team';

            $to =
                $teamNames[$teamToId]
                ??
                'Unknown Team';

            return
                "Traded {$player} ({$from}) to {$to}.";
        }

        $teams =
            collect($tradeDetails)
            ->flatMap(
                function ($trade) {

                    return [
                        $trade['from_team_id'] ?? null,

                        $trade['to_team_id'] ?? null,
                    ];
                }
            )
            ->filter()
            ->unique()
            ->values();

        $teamCount =
            $teams->count();

        $tradeLabel =
            match ($teamCount) {

                2 =>
                '2-team trade',

                3 =>
                '3-team trade',

                4 =>
                '4-team trade',

                default =>
                "{$teamCount}-team trade",
            };

        $teamSummaries = [];

        foreach ($tradeDetails as $trade) {

            $fromId =
                $trade['from_team_id']
                ?? null;

            $toId =
                $trade['to_team_id']
                ?? null;

            if (
                !$fromId ||
                !$toId
            ) {
                continue;
            }

            $fromName =
                $teamNames[$fromId]
                ??
                'Unknown Team';

            $toName =
                $teamNames[$toId]
                ??
                'Unknown Team';

            $assets = [];

            $playerIds =
                collect(
                    $trade['players']
                        ?? []
                )
                ->map(
                    function ($player) {

                        return is_array($player)
                            ? (
                                $player['id']
                                ?? null
                            )
                            : $player;
                    }
                )
                ->filter()
                ->values();

            if (
                $playerIds->isNotEmpty()
            ) {

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

                    if (
                        isset(
                            $players[$id]
                        )
                    ) {

                        $assets[] =
                            $players[$id];
                    }
                }
            }

            foreach (
                $trade['draft_picks']
                    ?? []
                as $pick
            ) {

                $season =
                    $pick['season_id']
                    ?? null;

                $round =
                    $pick['round']
                    ?? null;

                $pickNumber =
                    $pick['pick_number']
                    ?? null;

                $protections =
                    $pick['protections']
                    ?? null;

                if (
                    !$season ||
                    !$round
                ) {
                    continue;
                }

                $roundText =
                    match ((int) $round) {

                        1 =>
                        '1st',

                        2 =>
                        '2nd',

                        3 =>
                        '3rd',

                        default =>
                        $round . 'th',
                    };

                $pickText =
                    "Season {$season} " .
                    "{$roundText} Round Draft Pick";

                if (
                    $pickNumber !== null
                ) {

                    $pickText .=
                        " (#{$pickNumber})";
                }

                if (
                    !empty($protections)
                ) {

                    $pickText .=
                        " ({$protections})";
                }

                $assets[] =
                    $pickText;
            }

            foreach (
                $trade['draft_rights']
                    ?? []
                as $right
            ) {

                $season =
                    $right['season_id']
                    ?? null;

                if ($season) {

                    $assets[] =
                        "Season {$season} Draft Rights";
                }
            }

            foreach (
                $trade['other_assets']
                    ?? []
                as $asset
            ) {

                if (!empty($asset)) {
                    $assets[] = $asset;
                }
            }

            if (!empty($assets)) {

                $teamSummaries[] =
                    "{$fromName} sent " .
                    implode(
                        ', ',
                        $assets
                    ) .
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
            $teamNames[$teamFromId]
            ??
            'Unknown Team';

        $mainTo =
            $teamNames[$teamToId]
            ??
            'Unknown Team';

        $description =
            "Traded {$mainPlayer} " .
            "({$mainFrom}) to {$mainTo} " .
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
    | Find Underperforming Players
    |--------------------------------------------------------------------------
    */

    public function findUnderperformingPlayers(
        int $teamId
    ) {

        $seasonId =
            get_current_season_id();

        $coach =
            $this->coachDecisionService
            ->getTeamCoach(
                $teamId
            );

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

        if (
            $currentPlayers->isEmpty()
        ) {
            return collect();
        }

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
            ->keyBy(
                'player_id'
            );

        $tradeable =
            collect();

        foreach (
            $currentPlayers as $player
        ) {

            $previous =
                $previousStats->get(
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

            if (
                $previousScore <= 0
            ) {
                continue;
            }

            if (
                $currentScore >=
                $previousScore
            ) {
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

            if (
                $tradePressure < 15
            ) {
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

    /*
    |--------------------------------------------------------------------------
    | Tradeable Draft Picks
    |--------------------------------------------------------------------------
    */

    public function getTradeableDraftPicks(
        int $teamId,
        ?int $seasonId = null
    ) {

        $currentSeasonId =
            (int) get_current_season_id();

        $tradeSeasonId =
            $seasonId ??
            $currentSeasonId;

        $pendingPickIds =
            $this->getPendingTradePicks();

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

        if (
            !empty($pendingPickIds)
        ) {

            $query->whereNotIn(
                'id',
                $pendingPickIds
            );
        }

        if (
            $tradeSeasonId <= 0
        ) {
            return collect();
        }

        return $query
            ->orderBy(
                'season_id'
            )
            ->orderBy(
                'round'
            )
            ->orderBy(
                'pick_number'
            )
            ->get();
    }
}
