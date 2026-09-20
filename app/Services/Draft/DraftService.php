<?php

namespace App\Services\Draft;

use Illuminate\Http\Request;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Helper\HelperService;
use App\Services\Coach\CoachDecisionService;
use App\Services\Contract\ContractService;

class DraftService
{
    protected $helper;
    protected $coachDecisionService;
    protected $contractService;
    protected $draftPickRightsService;

    public function __construct()
    {
        $this->helper = new HelperService();

        $this->coachDecisionService = new CoachDecisionService();

        $this->contractService = new ContractService();

        $this->draftPickRightsService = new DraftPickRightsService();
    }

    /**
     * ============================================================
     * DRAFT ORDER
     * ============================================================
     *
     * The important concept here is:
     *
     * standings determine ORIGINAL TEAM SLOT
     *
     * draft_pick_rights determine CURRENT OWNER
     *
     * Therefore:
     *
     * Team A can generate Pick #3,
     * but Team B can actually make the selection.
     */
    public function draftOrderPioneer()
    {
        DB::beginTransaction();

        try {
            $currentSeasonId = 1;
            $latestSeasonId = 0;

            /*
             * Don't regenerate an existing draft.
             */
            $existingDraft = DB::table('drafts')
                ->join(
                    'teams',
                    'drafts.team_id',
                    '=',
                    'teams.id'
                )
                ->where(
                    'drafts.season_id',
                    $currentSeasonId
                )
                ->select(
                    'drafts.id',
                    'drafts.draft_pick_right_id',
                    'drafts.round',
                    'drafts.pick_number as pick',
                    'teams.name as team_name',
                    'drafts.team_id',
                    'drafts.draft_status',
                    'drafts.player_id'
                )
                ->orderBy('drafts.round')
                ->orderBy('drafts.pick_number')
                ->get();

            if ($existingDraft->isNotEmpty()) {

                DB::commit();

                return response()->json([
                    'season_id' => $currentSeasonId,
                    'draft_order' => $existingDraft,
                    'message' => 'Draft already exists for this season.',
                ]);
            }

            /*
             * Make sure every franchise has a draft-right row.
             */
            $this->draftPickRightsService
                ->ensureFutureDraftRights($latestSeasonId);

            /*
             * ====================================================
             * STANDINGS
             * ====================================================
             */

            $allTeams = DB::table('teams')
                ->select(
                    'teams.id as team_id',
                    'teams.name as team_name',
                )
                ->orderBy(
                    'market_size',
                    'desc'
                )
                ->get();

            if ($allTeams->isEmpty()) {
                throw new \RuntimeException(
                    'No standings found for the previous season.'
                );
            }

            /*
             * ====================================================
             * LOTTERY
             * ====================================================
             */

            $lotteryTeams = $allTeams->take(14);

            $nonLotteryTeams = $allTeams->slice(14);

            $lotteryOdds = [
                140,
                140,
                140,
                125,
                105,
                90,
                75,
                60,
                45,
                30,
                20,
                15,
                10,
                5,
            ];

            $weightedPool = [];

            foreach ($lotteryTeams as $i => $team) {
                $weightedPool[] = [
                    'team' => $team,
                    'weight' => $lotteryOdds[$i] ?? 1,
                ];
            }

            $topPicks = [];
            $selectedTeamIds = [];

            while (count($topPicks) < min(4, count($weightedPool))) {

                $winner = $this->weightedRandom($weightedPool);

                $teamId = (int) $winner['team']->team_id;

                if (!in_array($teamId, $selectedTeamIds, true)) {

                    $topPicks[] = $winner['team'];

                    $selectedTeamIds[] = $teamId;
                }
            }

            $remainingLotteryTeams = $lotteryTeams
                ->filter(function ($team) use ($selectedTeamIds) {
                    return !in_array(
                        (int) $team->team_id,
                        $selectedTeamIds,
                        true
                    );
                })
                ->values()
                ->all();

            $firstRoundOrder = array_merge(
                $topPicks,
                $remainingLotteryTeams,
                $nonLotteryTeams->values()->all()
            );

            /*
             * ====================================================
             * CREATE ACTUAL DRAFT
             * ====================================================
             */

            $draftOutput = [];
            $draftRounds  = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];

            foreach ($draftRounds as $round) {

                foreach ($firstRoundOrder as $pickIndex => $originalTeam) {

                    $pickNumber = $pickIndex + 1;

                    $originalTeamId = (int) $originalTeam->team_id;

                    /*
                     * Find the permanent draft-right identity.
                     */
                    $pickRight =
                        $this->draftPickRightsService
                        ->getOriginalPickRight(
                            $currentSeasonId,
                            $round,
                            $originalTeamId
                        );

                    if (!$pickRight) {
                        throw new \RuntimeException(
                            "Missing draft pick right for team {$originalTeamId}, round {$round}."
                        );
                    }

                    /*
                     * Protection is resolved using the slot generated
                     * by the ORIGINAL team's standings.
                     */
                    $currentOwnerId =
                        $this->draftPickRightsService
                        ->resolvePickOwner(
                            $pickRight,
                            $pickNumber
                        );

                    $currentOwner =
                        DB::table('teams')
                        ->where('id', $currentOwnerId)
                        ->first();

                    if (!$currentOwner) {
                        throw new \RuntimeException(
                            "Current owner {$currentOwnerId} does not exist."
                        );
                    }

                    $draftStatus =
                        "S{$currentSeasonId} R{$round} P{$pickNumber}";

                    /*
                     * Draft row now points to the permanent pick right.
                     */
                    DB::table('drafts')->insert([
                        'original_team_id' => $originalTeamId,
                        'team_id' => $currentOwnerId,
                        'player_id' => 0,
                        'draft_pick_right_id' => $pickRight->id,
                        'season_id' => $currentSeasonId,
                        'round' => $round,
                        'pick_number' => $pickNumber,
                        'draft_status' => $draftStatus,
                    ]);

                    $draftOutput[] = [
                        'round' => $round,
                        'pick' => $pickNumber,

                        /*
                         * Original team is useful for explaining
                         * where the pick came from.
                         */
                        'original_team_id' => $originalTeamId,
                        'original_team_name' => $originalTeam->team_name,

                        /*
                         * Current owner is the team actually making
                         * the selection.
                         */
                        'team_id' => $currentOwnerId,
                        'team_name' => $currentOwner->name,

                        'draft_pick_right_id' => $pickRight->id,
                        'draft_status' => $draftStatus,
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'season_id' => $currentSeasonId,
                'draft_order' => $draftOutput,
                'message' => 'Draft successfully generated.',
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Draft order generation failed',
                [
                    'exception' => $e,
                ]
            );

            return response()->json([
                'error' => true,
                'message' => 'Draft order generation failed.',
                'error_message' => $e->getMessage(),
            ], 500);
        }
    }

    public function draftOrder()
    {
        DB::beginTransaction();

        try {
            $latestSeasonId = get_current_season_id();
            $currentSeasonId = $latestSeasonId + 1;

            /*
             * Don't regenerate an existing draft.
             */
            $existingDraft = DB::table('drafts')
                ->join(
                    'teams',
                    'drafts.team_id',
                    '=',
                    'teams.id'
                )
                ->where(
                    'drafts.season_id',
                    $currentSeasonId
                )
                ->select(
                    'drafts.id',
                    'drafts.draft_pick_right_id',
                    'drafts.round',
                    'drafts.pick_number as pick',
                    'teams.name as team_name',
                    'drafts.team_id',
                    'drafts.draft_status',
                    'drafts.player_id'
                )
                ->orderBy('drafts.round')
                ->orderBy('drafts.pick_number')
                ->get();

            if ($existingDraft->isNotEmpty()) {

                DB::commit();

                return response()->json([
                    'season_id' => $currentSeasonId,
                    'draft_order' => $existingDraft,
                    'message' => 'Draft already exists for this season.',
                ]);
            }

            /*
             * Make sure every franchise has a draft-right row.
             */
            $this->draftPickRightsService
                ->ensureFutureDraftRights($latestSeasonId);

            /*
             * ====================================================
             * STANDINGS
             * ====================================================
             */

            $allTeams = DB::table('standings_view')
                ->select(
                    'team_id',
                    'team_name',
                    'wins',
                    'losses',
                    'overall_rank'
                )
                ->where(
                    'season_id',
                    $latestSeasonId
                )
                ->orderBy(
                    'overall_rank',
                    'desc'
                )
                ->get();

            if ($allTeams->isEmpty()) {
                throw new \RuntimeException(
                    'No standings found for the previous season.'
                );
            }

            /*
             * ====================================================
             * LOTTERY
             * ====================================================
             */

            $lotteryTeams = $allTeams->take(14);

            $nonLotteryTeams = $allTeams->slice(14);

            $lotteryOdds = [
                140,
                140,
                140,
                125,
                105,
                90,
                75,
                60,
                45,
                30,
                20,
                15,
                10,
                5,
            ];

            $weightedPool = [];

            foreach ($lotteryTeams as $i => $team) {
                $weightedPool[] = [
                    'team' => $team,
                    'weight' => $lotteryOdds[$i] ?? 1,
                ];
            }

            $topPicks = [];
            $selectedTeamIds = [];

            while (count($topPicks) < min(4, count($weightedPool))) {

                $winner = $this->weightedRandom($weightedPool);

                $teamId = (int) $winner['team']->team_id;

                if (!in_array($teamId, $selectedTeamIds, true)) {

                    $topPicks[] = $winner['team'];

                    $selectedTeamIds[] = $teamId;
                }
            }

            $remainingLotteryTeams = $lotteryTeams
                ->filter(function ($team) use ($selectedTeamIds) {
                    return !in_array(
                        (int) $team->team_id,
                        $selectedTeamIds,
                        true
                    );
                })
                ->values()
                ->all();

            $firstRoundOrder = array_merge(
                $topPicks,
                $remainingLotteryTeams,
                $nonLotteryTeams->values()->all()
            );

            /*
             * ====================================================
             * CREATE ACTUAL DRAFT
             * ====================================================
             */

            $draftOutput = [];

            foreach ([1, 2] as $round) {

                foreach ($firstRoundOrder as $pickIndex => $originalTeam) {

                    $pickNumber = $pickIndex + 1;

                    $originalTeamId = (int) $originalTeam->team_id;

                    /*
                     * Find the permanent draft-right identity.
                     */
                    $pickRight =
                        $this->draftPickRightsService
                        ->getOriginalPickRight(
                            $currentSeasonId,
                            $round,
                            $originalTeamId
                        );

                    if (!$pickRight) {
                        throw new \RuntimeException(
                            "Missing draft pick right for team {$originalTeamId}, round {$round}."
                        );
                    }

                    /*
                     * Protection is resolved using the slot generated
                     * by the ORIGINAL team's standings.
                     */
                    $currentOwnerId =
                        $this->draftPickRightsService
                        ->resolvePickOwner(
                            $pickRight,
                            $pickNumber
                        );

                    $currentOwner =
                        DB::table('teams')
                        ->where('id', $currentOwnerId)
                        ->first();

                    if (!$currentOwner) {
                        throw new \RuntimeException(
                            "Current owner {$currentOwnerId} does not exist."
                        );
                    }

                    $draftStatus =
                        "S{$currentSeasonId} R{$round} P{$pickNumber}";

                    /*
                     * Draft row now points to the permanent pick right.
                     */
                    DB::table('drafts')->insert([
                        'original_team_id' => $originalTeamId,
                        'team_id' => $currentOwnerId,
                        'player_id' => 0,
                        'draft_pick_right_id' => $pickRight->id,
                        'season_id' => $currentSeasonId,
                        'round' => $round,
                        'pick_number' => $pickNumber,
                        'draft_status' => $draftStatus,
                    ]);

                    $draftOutput[] = [
                        'round' => $round,
                        'pick' => $pickNumber,

                        /*
                         * Original team is useful for explaining
                         * where the pick came from.
                         */
                        'original_team_id' => $originalTeamId,
                        'original_team_name' => $originalTeam->team_name,

                        /*
                         * Current owner is the team actually making
                         * the selection.
                         */
                        'team_id' => $currentOwnerId,
                        'team_name' => $currentOwner->name,

                        'draft_pick_right_id' => $pickRight->id,
                        'draft_status' => $draftStatus,
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'season_id' => $currentSeasonId,
                'draft_order' => $draftOutput,
                'message' => 'Draft successfully generated.',
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Draft order generation failed',
                [
                    'exception' => $e,
                ]
            );

            return response()->json([
                'error' => true,
                'message' => 'Draft order generation failed.',
                'error_message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * ============================================================
     * DRAFT ONE PICK
     * ============================================================
     *
     * Processes EXACTLY ONE draft pick.
     *
     * Example:
     *
     * /draft/decision/4/round1/pick1
     *
     * Then the frontend calls:
     *
     * /draft/decision/4/round1/pick2
     *
     * etc.
     *
     * The backend NEVER loops through the entire draft.
     */
    public function draftDecision(
        int $seasonId,
        int $round,
        int $pickNumber
    ) {
        try {

            $result = DB::transaction(function () use ($seasonId,$round,$pickNumber)
            {

                /**
                 * ==================================================
                 * GET THE EXACT PICK
                 * ==================================================
                 *
                 * lockForUpdate() prevents two requests from
                 * processing the same pick simultaneously.
                 */
                $pick = DB::table('drafts as d')
                    ->leftJoin('draft_pick_rights as dpr','d.draft_pick_right_id','=','dpr.id')
                    ->select('d.*','dpr.current_owner_id')
                    ->where('d.season_id', $seasonId)
                    ->where('d.round', $round)
                    ->where('d.pick_number', $pickNumber)
                    ->lockForUpdate()
                    ->first();

                if (!$pick) {
                    throw new \RuntimeException(
                        "Draft pick does not exist: "
                            . "Season {$seasonId}, "
                            . "Round {$round}, "
                            . "Pick {$pickNumber}."
                    );
                }

                /**
                 * ==================================================
                 * PREVENT DOUBLE DRAFTING
                 * ==================================================
                 */
                if (!empty($pick->player_id) && (int) $pick->player_id !== 0) {

                    $existingPlayer = DB::table('players')
                        ->where('id', $pick->player_id)
                        ->first();

                    return $this->buildAlreadyProcessedPickResponse(
                        $pick,
                        $existingPlayer
                    );
                }

                /**
                 * ==================================================
                 * CURRENT OWNER
                 * ==================================================
                 *
                 * drafts.team_id already contains the current
                 * owner resolved by draftOrder().
                 *
                 * This is extremely important because:
                 *
                 * ORIGINAL TEAM != CURRENT OWNER
                 *
                 * Example:
                 *
                 * Pick #3 originally belonged to Team A.
                 * Team A traded the pick to Team B.
                 *
                 * Team B is the team making the selection.
                 */
                $teamId = $pick->current_owner_id == 0 ? $pick->team_id : $pick->current_owner_id;

                $team = DB::table('teams')
                    ->where('id', $teamId)
                    ->first();

                if (!$team) {
                    throw new \RuntimeException(
                        "Draft team {$teamId} does not exist."
                    );
                }

                /**
                 * ==================================================
                 * ORIGINAL PICK RIGHT
                 * ==================================================
                 */
                $pickRight = null;

                if (!empty($pick->draft_pick_right_id)) {

                    $pickRight = DB::table('draft_pick_rights')
                        ->where(
                            'id',
                            $pick->draft_pick_right_id
                        )
                        ->first();
                }

                /**
                 * ==================================================
                 * AVAILABLE ROOKIES
                 * ==================================================
                 *
                 * Fresh query on every pick.
                 *
                 * We don't keep an in-memory rookie collection
                 * because every request is a separate draft event.
                 */
                $availablePlayers = DB::table('players')
                    ->where('is_rookie', 1)
                    ->where('team_id', 0)
                    ->where('draft_id', $seasonId)
                    ->where('is_drafted', 0)
                    ->orderByDesc('potential_rating')
                    ->orderByDesc('overall_rating')
                    ->orderBy('age')
                    ->get();

                if ($availablePlayers->isEmpty()) {
                    throw new \RuntimeException(
                        "No available rookies remain for "
                            . "Round {$round}, Pick {$pickNumber}."
                    );
                }

                /**
                 * ==================================================
                 * POSITION NEEDS
                 * ==================================================
                 *
                 * Recalculate from the database.
                 *
                 * This is safer than keeping cached needs between
                 * HTTP requests.
                 */
                $positionNeeds = $this->getTeamPositionNeeds(
                    $teamId
                );

                $neededPositions = array_keys(
                    $positionNeeds
                );

                /**
                 * ==================================================
                 * GET COACH
                 * ==================================================
                 */
                $coach = $this->coachDecisionService->getTeamCoach($teamId);

                /**
                 * ==================================================
                 * CANDIDATES
                 * ==================================================
                 *
                 * We evaluate the best 15 available prospects,
                 * exactly like your existing system.
                 */
                $candidatePlayers = $availablePlayers
                    ->sortByDesc(function ($player) {

                        return (float) (
                            $player->overall_rating
                            ??
                            $player->overall
                            ??
                            0
                        );
                    })
                    ->take(15)
                    ->values();

                /**
                 * ==================================================
                 * SCORE CANDIDATES
                 * ==================================================
                 */
                $scoredCandidates = $candidatePlayers
                    ->map(function ($player) use (
                        $coach,
                        $neededPositions
                    ) {

                        $draftScore =
                            $this->calculateCoachDraftScore(
                                $coach,
                                $player,
                                $neededPositions
                            );

                        return [
                            'player' => $player,
                            'draft_score' => $draftScore,
                        ];
                    })
                    ->sortByDesc('draft_score')
                    ->values();

                $topCandidates = $scoredCandidates
                    ->take(3)
                    ->values();

                if ($topCandidates->isEmpty()) {

                    throw new \RuntimeException(
                        "No draft candidates available for "
                            . "Round {$round}, Pick {$pickNumber}."
                    );
                }

                /**
                 * ==================================================
                 * COACH DECISION
                 * ==================================================
                 */
                $decision = $this->makeDraftDecision(
                    $team,
                    $coach,
                    $topCandidates,
                    $positionNeeds,
                    $pick
                );

                $selectedCandidate = $decision['candidate'];

                $selectedPlayer = $selectedCandidate['player'];

                $selectedDraftScore = (float) $selectedCandidate['draft_score'];

                $decisionMakerType = $decision['decision_maker_type'];

                $decisionMakerName = $decision['decision_maker_name'];

                $decisionReason = $decision['decision_maker_reason'];

                $decisionFactors = $decision['decision_factors'];

                $decisionScore = $decision['draft_score'];

                /**
                 * ==================================================
                 * ROSTER MANAGEMENT
                 * ==================================================
                 */
                $hasSpace = $this->teamHasRosterSpace($teamId);

                $mustSign = ((int) $round === 1);

                $waivedPlayer = null;

                if (!$hasSpace) {

                    $waivedPlayer =
                        $this->findPlayerToWaive(
                            $teamId,
                            $selectedPlayer,
                            $seasonId,
                            $mustSign
                        );

                    if ($waivedPlayer) {

                        $this->waivePlayerForDraft(
                            $waivedPlayer,
                            $team,
                            $seasonId
                        );

                        $hasSpace = true;
                    }
                }

                /**
                 * ==================================================
                 * FINAL SIGNING DECISION
                 * ==================================================
                 */
                $finalTeamId = $hasSpace ? $teamId : ($mustSign ? $teamId : 0);

                /**
                 * ==================================================
                 * FIRST ROUND SAFETY
                 * ==================================================
                 *
                 * A first-round pick must sign.
                 *
                 * If the normal waiver candidate failed to create
                 * space, force a roster cut.
                 */
                if ($mustSign && !$this->teamHasRosterSpace($teamId)) {

                    $forcedWaive = $this->findPlayerToWaive($teamId,$selectedPlayer,$seasonId,true);

                    if (!$forcedWaive) {

                        throw new \RuntimeException(
                            "Unable to create roster space for "
                                . "first-round pick {$pickNumber}."
                        );
                    }

                    $this->waivePlayerForDraft($forcedWaive,$team,$seasonId);

                    $finalTeamId = $teamId;
                    $hasSpace = true;

                    $waivedPlayer = $forcedWaive;
                }

                /**
                 * ==================================================
                 * MARK PLAYER AS DRAFTED
                 * ==================================================
                 */
                DB::table('players')
                    ->where('id', $selectedPlayer->id)
                    ->update([
                        'team_id' => $finalTeamId,
                        'drafted_team_id' => $teamId,
                        'is_drafted' => 1,
                        'draft_order' => $pickNumber,
                        'draft_status' => $pick->draft_status,
                        'contract_years' => 0,
                    ]);

                /**
                 * ==================================================
                 * DRAFT TRANSACTION
                 * ==================================================
                 */
                DB::table('transactions')->insert([
                    'player_id' => $selectedPlayer->id,
                    'season_id' => $seasonId,
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'draft',
                    'details' =>
                    "Drafted by {$team->name} "
                        . "in round {$round}, "
                        . "pick {$pickNumber}.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /**
                 * ==================================================
                 * ROOKIE CONTRACT
                 * ==================================================
                 */
                $offer = null;

                if ($finalTeamId === $teamId) {

                    $offer =
                        $this->contractService
                        ->assignRookieContract(
                            $selectedPlayer,
                            $round,
                            $pickNumber
                        );

                    DB::table('players')
                        ->where('id', $selectedPlayer->id)
                        ->update([
                            'team_id' => $teamId,
                            'contract_years' =>
                            $offer['years'],
                            'salary' =>
                            $offer['salary'],
                            'contract_type' =>
                            $offer['contract_type'],
                            'player_option' =>
                            $offer['player_option'] ?? 0,
                            'team_option' =>
                            $offer['team_option'] ?? 0,
                            'no_trade_clause' =>
                            $offer['no_trade_clause'] ?? 0,
                        ]);

                    DB::table('player_contracts')
                        ->insert([
                            'player_id' =>
                            $selectedPlayer->id,

                            'season_id' =>
                            $seasonId,

                            'team_id' =>
                            $teamId,

                            'salary' =>
                            $offer['salary'],

                            'contract_years' =>
                            $offer['years'],

                            'contract_type' =>
                            $offer['contract_type'],

                            'player_option' =>
                            $offer['player_option'] ?? false,

                            'team_option' =>
                            $offer['team_option'] ?? false,

                            'no_trade_clause' =>
                            $offer['no_trade_clause'] ?? false,

                            'status' => 'signed',

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    DB::table('transactions')
                        ->insert([
                            'player_id' =>
                            $selectedPlayer->id,

                            'season_id' =>
                            $seasonId,

                            'details' =>
                            $selectedPlayer->name
                                . " signed with "
                                . $team->name
                                . " for "
                                . $offer['years']
                                . " years on a "
                                . $offer['contract_type']
                                . " contract worth ₱"
                                . number_format(
                                    (float) $offer['salary'],
                                    2
                                )
                                . '.',

                            'from_team_id' => 0,

                            'to_team_id' =>
                            $teamId,

                            'status' => 'signed',

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                /**
                 * ==================================================
                 * SAVE DECISION TO DRAFT
                 * ==================================================
                 */
                DB::table('drafts')
                    ->where('id', $pick->id)
                    ->update([
                        'player_id' =>
                        $selectedPlayer->id,

                        'team_id' =>
                        $teamId,

                        'decision_maker_type' =>
                        $decisionMakerType,

                        'decision_maker_name' =>
                        $decisionMakerName,

                        'decision_maker_reason' =>
                        $decisionReason,

                        'decision_factors' =>
                        json_encode(
                            $decisionFactors
                        ),

                        'updated_at' => now(),
                    ]);

                /**
                 * ==================================================
                 * CONSUME PICK RIGHT
                 * ==================================================
                 */
                if (!empty($pick->draft_pick_right_id)) {

                    $this->draftPickRightsService
                        ->consumeDraftRight(
                            (int) $pick->draft_pick_right_id
                        );
                }

                /**
                 * ==================================================
                 * FIND NEXT PICK
                 * ==================================================
                 *
                 * This automatically moves:
                 *
                 * R1 P30 -> R2 P1
                 */
                $nextPick = DB::table('drafts')
                    ->join('teams as original','drafts.original_team_id','=','original.id')
                    ->join('draft_pick_rights','draft_pick_rights.id','=','drafts.draft_pick_right_id')
                    ->join('teams as current_owner','current_owner.id','=','drafts.team_id')
                    ->select(
                        'drafts.*',
                        'original.name as original_team_name',
                        'original.acronym as original_team_acronym',
                        'current_owner.name as team_name',
                        'current_owner.id as current_team_id'
                    )
                    ->where('drafts.season_id', $seasonId)
                    ->where(function ($query) use (
                        $round,
                        $pickNumber
                    ) {

                        $query
                            ->where('drafts.round', '>', $round)
                            ->orWhere(function ($q) use (
                                $round,
                                $pickNumber
                            ) {

                                $q->where(
                                    'drafts.round',
                                    $round
                                )
                                    ->where(
                                        'drafts.pick_number',
                                        '>',
                                        $pickNumber
                                    );
                            });
                    })
                    ->orderBy('drafts.round')
                    ->orderBy('drafts.pick_number')
                    ->first();

                /**
                 * ==================================================
                 * DETERMINE COMPLETION
                 * ==================================================
                 */
                $isComplete = !$nextPick;

                /**
                 * ==================================================
                 * SEASON STATUS
                 * ==================================================
                 *
                 * Only change the season status after the FINAL
                 * pick has actually been processed.
                 */
                if ($isComplete) {

                    DB::table('players')
                        ->where('draft_id', $seasonId)
                        ->where('is_drafted', 0)
                        ->update([
                            'team_id' => 0,
                            'contract_years' => 0,
                            'salary' => 0,
                            'draft_status' => 'Undrafted',
                            'is_rookie' => 1,
                        ]);

                    $latestSeasonId = get_current_season_id();

                    DB::table('seasons')
                        ->where('id', $latestSeasonId)
                        ->update([
                            'status' =>
                            config('timeline.draft'),
                        ]);
                }

                /**
                 * ==================================================
                 * RESPONSE DATA
                 * ==================================================
                 */
                return [
                    'season_id' => $seasonId,

                    'round' => $round,

                    'pick_number' =>
                    $pickNumber,

                    'draft_status' =>
                    $pick->draft_status,

                    'team' => [
                        'id' => $team->id,
                        'name' => $team->name,
                    ],

                    'original_team' => [
                        'id' =>
                        $pickRight->original_team_id
                            ?? null,
                    ],

                    'current_owner' => [
                        'id' => $team->id,
                        'name' => $team->name,
                    ],

                    'player' => [
                        'id' =>
                        $selectedPlayer->id,

                        'name' =>
                        $selectedPlayer->name,

                        'position' =>
                        $selectedPlayer->position,

                        'age' =>
                        $selectedPlayer->age,

                        'overall_rating' =>
                        $selectedPlayer->overall_rating,

                        'archetype' =>
                        $selectedPlayer->type,
                    ],

                    'decision' => [
                        'maker_type' =>
                        $decisionMakerType,

                        'maker_name' =>
                        $decisionMakerName,

                        'reason' =>
                        $decisionReason,

                        'factors' =>
                        $decisionFactors,

                        'draft_score' =>
                        $selectedDraftScore,
                    ],

                    'signing' => [
                        'signed' =>
                        $finalTeamId === $teamId,

                        'team_id' =>
                        $finalTeamId,

                        'contract_years' =>
                        $offer['years'] ?? 0,

                        'salary' =>
                        $offer['salary'] ?? 0,

                        'contract_type' =>
                        $offer['contract_type'] ?? null,
                    ],

                    'waiver' => [
                        'waived' =>
                        $waivedPlayer !== null,

                        'player_id' =>
                        $waivedPlayer->id ?? null,

                        'player_name' =>
                        $waivedPlayer->name ?? null,
                    ],

                    'next_pick' => $nextPick
                        ? [
                            'round' =>
                            (int) $nextPick->round,

                            'pick_number' =>
                            (int) $nextPick->pick_number,

                            'draft_status' =>
                            $nextPick->draft_status,

                            'team_id' =>
                            (int) $nextPick->current_team_id ? $nextPick->current_team_id : $nextPick->team_id,

                            'original_team_id' =>
                            (int) $nextPick->team_id,

                            'team_name' =>
                            $nextPick->original_team_id == $nextPick->team_id ? 
                                $nextPick->team_name : 
                                $nextPick->team_name.' via ('.$nextPick->original_team_acronym.')',

                            'team_data' =>
                            $nextPick,
                        ]
                        : null,

                    'is_complete' =>
                    $isComplete,
                ];
            });

            return response()->json([
                'error' => false,
                'data' => $result,
                'message' =>
                $result['is_complete']
                    ? 'Draft completed successfully.'
                    : "Pick {$pickNumber} completed.",
            ]);
        } catch (\Throwable $e) {

            Log::error(
                'Single draft pick failed',
                [
                    'season_id' => $seasonId,
                    'round' => $round,
                    'pick_number' => $pickNumber,
                    'exception' => $e,
                ]
            );

            return response()->json([
                'error' => true,
                'message' =>
                'Draft pick failed.',
                'error_message' =>
                $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ============================================================
     * FIND PLAYER TO WAIVE
     * ============================================================
     */
    private function findPlayerToWaive(
        int $teamId,
        object $selectedPlayer,
        int $seasonId,
        bool $mustCreateSpace = false
    ) {
        /*
         * IMPORTANT:
         *
         * We look at the PREVIOUS completed season.
         *
         * Your old code incorrectly used:
         *
         * $previousSeasonId = $currentSeasonId;
         */
        $previousSeasonId =
            $seasonId - 1;

        /*
         * First attempt:
         * matching position + weak contract/performance.
         */
        $candidate = DB::table('players as p')
            ->leftJoin(
                'player_season_stats as stats',
                function ($join) use ($previousSeasonId) {

                    $join->on(
                        'p.id',
                        '=',
                        'stats.player_id'
                    );

                    $join->where(
                        'stats.season_id',
                        '=',
                        $previousSeasonId
                    );
                }
            )
            ->where(
                'p.team_id',
                $teamId
            )
            ->where(
                'p.id',
                '!=',
                $selectedPlayer->id
            )
            ->where(function ($q) use ($selectedPlayer) {

                $q->where(
                    'p.position',
                    'like',
                    '%' . $selectedPlayer->position . '%'
                );
            })
            ->where(function ($q) {

                $q->where(
                    'p.contract_years',
                    '<=',
                    1
                )
                    ->orWhere(
                        'stats.eff',
                        '<',
                        10
                    );
            })
            ->select(
                'p.id',
                'p.name',
                'p.position',
                'p.contract_years',
                'p.salary',
                'stats.eff'
            )
            ->orderByRaw(
                'CASE
                    WHEN p.contract_years <= 0 THEN 0
                    WHEN stats.eff IS NULL THEN 1
                    ELSE 2
                 END'
            )
            ->orderBy('stats.eff', 'asc')
            ->first();

        if ($candidate) {
            return $candidate;
        }

        /*
         * If a first-rounder MUST be signed, use a broader
         * fallback.
         *
         * We still try to remove the least valuable player.
         */
        if ($mustCreateSpace) {

            return DB::table('players as p')
                ->leftJoin(
                    'player_season_stats as stats',
                    function ($join) use ($previousSeasonId) {

                        $join->on(
                            'p.id',
                            '=',
                            'stats.player_id'
                        );

                        $join->where(
                            'stats.season_id',
                            '=',
                            $previousSeasonId
                        );
                    }
                )
                ->where(
                    'p.team_id',
                    $teamId
                )
                ->where(
                    'p.id',
                    '!=',
                    $selectedPlayer->id
                )
                ->select(
                    'p.id',
                    'p.name',
                    'p.position',
                    'p.contract_years',
                    'p.salary',
                    'stats.eff'
                )
                ->orderByRaw(
                    'COALESCE(stats.eff, 0) ASC'
                )
                ->orderBy(
                    'p.contract_years',
                    'asc'
                )
                ->orderBy(
                    'p.salary',
                    'asc'
                )
                ->first();
        }

        return null;
    }

    /**
     * ============================================================
     * WAIVE PLAYER FOR DRAFT
     * ============================================================
     */
    private function waivePlayerForDraft(
        object $player,
        object $team,
        int $seasonId
    ): void {

        DB::table('players')
            ->where('id', $player->id)
            ->update([
                'team_id' => 0,
                'contract_years' => 0,
                'salary' => 0,
                'contract_type' => null,
                'player_option' => 0,
                'team_option' => 0,
                'no_trade_clause' => 0,
            ]);

        /*
         * Terminate previous contract if one exists.
         */
        DB::table('player_contracts')
            ->where('player_id', $player->id)
            ->where('season_id', $seasonId - 1)
            ->where('status', 'signed')
            ->update([
                'status' => 'terminated',
                'updated_at' => now(),
            ]);

        DB::table('transactions')
            ->insert([
                'player_id' => $player->id,
                'season_id' => $seasonId,
                'from_team_id' => $team->id,
                'to_team_id' => 0,
                'status' => 'waived',
                'details' =>
                "Waived by {$team->name} to make roster space for a draft pick.",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }


    /**
     * ============================================================
     * MAKE DRAFT DECISION
     * ============================================================
     *
     * Selects exactly one player from the top candidates and
     * generates the explanation for the selection.
     */
    private function makeDraftDecision(
        object $team,
        $coach,
        $topCandidates,
        array $neededPositions,
        object $pick
    ): array {

        if ($topCandidates->isEmpty()) {

            throw new \RuntimeException(
                "No candidates available for pick "
                    . $pick->pick_number
                    . "."
            );
        }

        /**
         * ========================================================
         * DECISION MAKER
         * ========================================================
         *
         * Your current system has CoachDecisionService, so the
         * coach is the decision maker.
         *
         * You can later plug a GM service into this without
         * changing the draft engine.
         */
        $decisionMakerType = 'coach';

        $decisionMakerName =
            $coach->name
            ??
            'Head Coach';

        /**
         * ========================================================
         * COACH UNCERTAINTY
         * ========================================================
         */
        $selectedCandidate =
            $topCandidates->first();

        if ($topCandidates->count() > 1) {

            $coachQuality =
                $this->coachDecisionService
                ->getCoachQuality($coach);

            $bestChance =
                55
                +
                (($coachQuality - 50) * 0.40);

            $bestChance =
                max(
                    40,
                    min(
                        90,
                        $bestChance
                    )
                );

            /**
             * Coach does not always take the #1
             * statistically-scored prospect.
             */
            if (
                !$this->coachDecisionService
                    ->randomDecision($bestChance)
            ) {

                $selectedCandidate =
                    $topCandidates
                    ->slice(1)
                    ->random();
            }
        }

        /**
         * ========================================================
         * PLAYER
         * ========================================================
         */
        $selectedPlayer =
            $selectedCandidate['player'];

        $draftScore =
            (float) $selectedCandidate['draft_score'];

        /**
         * ========================================================
         * BUILD REASON
         * ========================================================
         */
        $reasonData =
            $this->buildDraftDecisionReason(
                $selectedPlayer,
                $topCandidates,
                $neededPositions,
                $draftScore,
                $pick
            );

        return [
            'candidate' =>
            $selectedCandidate,

            'decision_maker_type' =>
            $decisionMakerType,

            'decision_maker_name' =>
            $decisionMakerName,

            'decision_maker_reason' =>
            $reasonData['reason'],

            'decision_factors' =>
            $reasonData['factors'],

            'draft_score' => $selectedCandidate['draft_score'],
        ];
    }

    /**
     * ============================================================
     * ALREADY PROCESSED PICK
     * ============================================================
     */
    private function buildAlreadyProcessedPickResponse(
        object $pick,
        $player
    ): array {

        $team = DB::table('teams')
            ->where('id', $pick->team_id)
            ->first();

        $nextPick = DB::table('drafts')
            ->where('season_id', $pick->season_id)
            ->where(function ($query) use ($pick) {

                $query
                    ->where(
                        'round',
                        '>',
                        $pick->round
                    )
                    ->orWhere(function ($q) use ($pick) {

                        $q->where(
                            'round',
                            $pick->round
                        )
                            ->where(
                                'pick_number',
                                '>',
                                $pick->pick_number
                            );
                    });
            })
            ->orderBy('round')
            ->orderBy('pick_number')
            ->first();

        return [
            'season_id' =>
            (int) $pick->season_id,

            'round' =>
            (int) $pick->round,

            'pick_number' =>
            (int) $pick->pick_number,

            'draft_status' =>
            $pick->draft_status,

            'already_processed' =>
            true,

            'team' => [
                'id' =>
                $team->id ?? $pick->team_id,

                'name' =>
                $team->name ?? 'Unknown Team',
            ],

            'player' => $player
                ? [
                    'id' =>
                    $player->id,

                    'name' =>
                    $player->name,

                    'position' =>
                    $player->position,

                    'age' =>
                    $player->age,

                    'overall_rating' =>
                    $player->overall_rating,

                    'archetype' =>
                    $player->type,
                ]
                : null,

            'decision' => [
                'maker_type' =>
                $pick->decision_maker_type,

                'maker_name' =>
                $pick->decision_maker_name,

                'reason' =>
                $pick->decision_maker_reason,

                'factors' =>
                $pick->decision_factors
                    ? json_decode(
                        $pick->decision_factors,
                        true
                    )
                    : [],
            ],

            'next_pick' => $nextPick
                ? [
                    'round' =>
                    (int) $nextPick->round,

                    'pick_number' =>
                    (int) $nextPick->pick_number,

                    'draft_status' =>
                    $nextPick->draft_status,

                    'team_id' =>
                    (int) $nextPick->team_id,

                    'team_name' =>
                    (int) $nextPick->team_name,
                ]
                : null,

            'is_complete' =>
            $nextPick === null,
        ];
    }
    /**
     * ============================================================
     * BUILD DRAFT DECISION REASON
     * ============================================================
     */
    private function buildDraftDecisionReason(
        object $player,
        $topCandidates,
        array $neededPositions,
        float $draftScore,
        object $pick
    ): array {
        $overall = (float) ($player->overall_rating ?? $player->overall ?? 0);

        $position = strtoupper(trim((string) $player->position));

        /*
        * --------------------------------------------------------
        * DETERMINE POSITION FIT
        * --------------------------------------------------------
        */
        $positionFit = false;

        foreach (array_keys($neededPositions) as $neededPosition) {

            if (
                str_contains(
                    $position,
                    strtoupper($neededPosition)
                )
            ) {
                $positionFit = true;
                break;
            }
        }

        /*
        * --------------------------------------------------------
        * COMPARE AGAINST TOP ALTERNATIVES
        * --------------------------------------------------------
        */
        $bestAlternative = null;

        foreach ($topCandidates as $candidate) {

            $candidatePlayer = $candidate['player'];

            if (
                (int) $candidatePlayer->id ===
                (int) $player->id
            ) {
                continue;
            }

            if (!$bestAlternative) {
                $bestAlternative = $candidate;
                continue;
            }

            if (
                (float) $candidate['draft_score'] >
                (float) $bestAlternative['draft_score']
            ) {
                $bestAlternative = $candidate;
            }
        }

        /*
        * --------------------------------------------------------
        * SCORE DIFFERENCE
        * --------------------------------------------------------
        */
        $scoreDifference = 0;

        if ($bestAlternative) {

            $scoreDifference =
                $draftScore -
                (float) $bestAlternative['draft_score'];
        }

        /*
        * --------------------------------------------------------
        * DECISION FACTORS
        * --------------------------------------------------------
        */
        $factors = [];

        if ($positionFit) {
            $factors[] = 'position_need';
        }

        if ($overall >= 85) {
            $factors[] = 'elite_talent';
        } elseif ($overall >= 78) {
            $factors[] = 'strong_talent';
        }

        if ($scoreDifference >= 3) {
            $factors[] = 'best_fit';
        } elseif ($scoreDifference >= 1) {
            $factors[] = 'draft_value';
        }

        if (empty($factors)) {
            $factors[] = 'best_available';
        }

        /*
        * --------------------------------------------------------
        * GENERATE REASON
        * --------------------------------------------------------
        */
        $reason = $this->generateDraftReason(
            $player,
            $positionFit,
            $overall,
            $scoreDifference,
            $factors
        );

        return [
            'reason' => $reason,
            'factors' => $factors,
        ];
    }


    /**
     * ============================================================
     * GENERATE DRAFT REASON
     * ============================================================
     */
    private function generateDraftReason(
        object $player,
        bool $positionFit,
        float $overall,
        float $scoreDifference,
        array $factors
    ): string {
        $name = $player->name ?? 'the player';

        $position = strtoupper(trim((string) $player->position));

        /*
            * --------------------------------------------------------
            * POSITION NEED + ELITE TALENT
            * --------------------------------------------------------
            */
        if ($positionFit && in_array('elite_talent', $factors, true)) {
            return
                "We had a need at {$position}, and {$name} "
                . "was too talented to pass up. His overall ability "
                . "gives us both immediate impact and long-term value.";
        }

        /*
            * --------------------------------------------------------
            * POSITION NEED + BEST FIT
            * --------------------------------------------------------
            */
        if ($positionFit && in_array('best_fit', $factors, true)) {
            return
                "We wanted help at {$position}, and {$name} "
                . "was the best fit among the players available. "
                . "His skill set matches what we need from this roster.";
        }

        /*
            * --------------------------------------------------------
            * POSITION NEED + VALUE
            * --------------------------------------------------------
            */
        if ($positionFit && in_array('draft_value', $factors, true)) {
            return
                "Addressing {$position} was important for us, "
                . "and {$name} offered excellent value at this point "
                . "in the draft.";
        }

        /*
            * --------------------------------------------------------
            * ELITE TALENT
            * --------------------------------------------------------
            */
        if (in_array('elite_talent', $factors, true)) {
            return
                "{$name} was simply too good to pass up. "
                . "His talent gives us another high-level piece "
                . "to build around.";
        }

        /*
            * --------------------------------------------------------
            * STRONG TALENT
            * --------------------------------------------------------
            */
        if (in_array('strong_talent', $factors, true)) {
            return
                "We liked {$name}'s combination of talent and upside. "
                . "He gives us a strong player without forcing us "
                . "to reach for a specific position.";
        }

        /*
            * --------------------------------------------------------
            * BEST FIT
            * --------------------------------------------------------
            */
        if (in_array('best_fit', $factors, true)) {
            return
                "After evaluating the remaining prospects, "
                . "{$name} stood out as the best overall fit for "
                . "what we're trying to build.";
        }

        /*
            * --------------------------------------------------------
            * DRAFT VALUE
            * --------------------------------------------------------
            */
        if (in_array('draft_value', $factors, true)) {
            return
                "We felt {$name} represented excellent value at "
                . "this point in the draft. The combination of "
                . "talent and fit made the decision easier.";
        }

        /*
            * --------------------------------------------------------
            * FALLBACK
            * --------------------------------------------------------
            */
        return
            "We believe {$name} was the best player available "
            . "for us at this point in the draft.";
    }

    /**
     * ============================================================
     * ROSTER SPACE
     * ============================================================
     */
    private function teamHasRosterSpace(int $teamId): bool
    {
        $count = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', 1)
            ->count();

        return $count < 15;
    }

    /**
     * ============================================================
     * LOTTERY RANDOM
     * ============================================================
     */
    private function weightedRandom(array $items)
    {
        $totalWeight =
            array_sum(
                array_column(
                    $items,
                    'weight'
                )
            );

        if ($totalWeight <= 0) {
            return $items[array_rand($items)];
        }

        $rand =
            mt_rand(
                1,
                $totalWeight
            );

        foreach ($items as $item) {

            if ($rand <= $item['weight']) {
                return $item;
            }

            $rand -= $item['weight'];
        }

        return end($items);
    }

    /**
     * ============================================================
     * POSITION NEEDS
     * ============================================================
     */
    private function getTeamPositionNeeds($teamId)
    {
        $required = [
            'PG' => 3,
            'SG' => 3,
            'SF' => 3,
            'PF' => 3,
            'C' => 3,
        ];

        $positionCount = [
            'PG' => 0,
            'SG' => 0,
            'SF' => 0,
            'PF' => 0,
            'C' => 0,
        ];

        $roster = DB::table('players')
            ->where('team_id', $teamId)
            ->get();

        foreach ($roster as $player) {

            $positions =
                explode(
                    '/',
                    strtoupper(
                        (string) $player->position
                    )
                );

            foreach ($positions as $pos) {

                $pos = trim($pos);

                if (isset($positionCount[$pos])) {
                    $positionCount[$pos]++;
                }
            }
        }

        $needs = [];

        foreach ($required as $pos => $minCount) {

            if ($positionCount[$pos] < $minCount) {

                $needs[$pos] =
                    $minCount -
                    $positionCount[$pos];
            }
        }

        return $needs;
    }

    /**
     * ============================================================
     * UPDATE POSITION NEEDS
     * ============================================================
     */
    private function updateTeamPositionNeeds(
        $currentNeeds,
        $playerPosition
    ) {
        $positions =
            explode(
                '/',
                strtoupper(
                    (string) $playerPosition
                )
            );

        foreach ($positions as $position) {

            $position = trim($position);

            if (isset($currentNeeds[$position])) {

                $currentNeeds[$position]--;

                if ($currentNeeds[$position] <= 0) {
                    unset(
                        $currentNeeds[$position]
                    );
                }
            }
        }

        return $currentNeeds;
    }

    /**
     * ============================================================
     * COACH DRAFT SCORE
     * ============================================================
     */
    private function calculateCoachDraftScore(
        $coach,
        $player,
        array $positionNeeds
    ): float {

        $baseScore = (float) (
            $player->overall_rating
            ?? $player->overall
            ?? 0
        );

        return (float)
        $this->coachDecisionService
            ->getDraftScore(
                $coach,
                $player,
                $baseScore,
                $positionNeeds
            );
    }

    /**
     * ============================================================
     * ROOKIE DRAFTEES
     * ============================================================
     */
    public function rookieDraftees(Request $request)
    {
        $perPage =
            max(
                1,
                (int) $request->input(
                    'itemsperpage',
                    10
                )
            );

        $currentPage =
            max(
                1,
                (int) $request->input(
                    'page_num',
                    1
                )
            );

        $search =
            trim(
                (string) $request->input(
                    'search',
                    ''
                )
            );

        $offset =
            ($currentPage - 1) *
            $perPage;

        $query =
            Player::query()
            ->where(
                'contract_years',
                0
            )
            ->where(
                'is_active',
                1
            )
            ->where(
                'is_rookie',
                1
            );

        if ($search !== '') {

            $query->where(
                'name',
                'like',
                "%{$search}%"
            );
        }

        $query->orderByRaw(
            "FIELD(
                role,
                'star player',
                'all star',
                'starter',
                'role player',
                'bench'
            )"
        );

        $total = $query->count();

        $freeAgents =
            $query
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $totalPages =
            (int) ceil(
                $total / $perPage
            );

        return response()->json([
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'rookies' => $freeAgents,
        ]);
    }

    /**
     * ============================================================
     * DRAFT RESULTS
     * ============================================================
     */
    public function draftResults()
    {
        $latestSeasonId =
            get_current_season_id();

        $draftSeasonId =
            $latestSeasonId + 1;

        $draftResults =
            DB::table('drafts as d')
            ->join(
                'teams',
                'd.team_id',
                '=',
                'teams.id'
            )
            ->join(
                'players',
                'd.player_id',
                '=',
                'players.id'
            )
            ->leftJoin(
                'draft_pick_rights as pr',
                'd.draft_pick_right_id',
                '=',
                'pr.id'
            )
            ->select(
                'd.team_id',
                'teams.name as team_name',
                'd.player_id',
                'players.name as player_name',
                'players.age',
                'players.position',
                'players.type as archetype',
                'players.overall_rating',
                'd.season_id',
                'd.round',
                'd.pick_number',
                'd.draft_status',
                'd.draft_pick_right_id',
                'pr.original_team_id',
                'pr.current_owner_id'
            )
            ->where(
                'd.season_id',
                $draftSeasonId
            )
            ->orderBy('d.round')
            ->orderBy('d.pick_number')
            ->get();

        return response()->json([
            'season_id' => $draftSeasonId,
            'draft_results' => $draftResults,
        ]);
    }

    /**
     * ============================================================
     * DRAFT RESULTS PER SEASON
     * ============================================================
     *
     * Kept close to your existing endpoint so your frontend
     * doesn't need a complete rewrite.
     */
    public function draftResultsPerSeason(Request $request)
    {
        $latestSeasonId =
            (int) $request->season_id;

        $currentSeasonId =
            get_current_season_id();

        $playerSeasonStatsTable =
            $this->helper
            ->getSeasonStatsDBName(
                $latestSeasonId
            );

        $draftResultsWithNames =
            DB::table('drafts as d')
            ->join(
                'teams',
                'd.team_id',
                '=',
                'teams.id'
            )
            ->join(
                'teams as ot',
                'd.original_team_id',
                '=',
                'ot.id'
            )
            ->join(
                'players',
                'd.player_id',
                '=',
                'players.id'
            )
            ->leftJoin(
                $playerSeasonStatsTable . ' as player_season_stats',
                'players.id',
                '=',
                'player_season_stats.player_id'
            )
            ->leftJoin(
                'teams as signed_team',
                'signed_team.id',
                '=',
                'player_season_stats.team_id'
            )
            ->select(
                'players.type as archetype',
                'players.age',
                'players.overall_rating',
                'players.position',
                'd.team_id',
                'ot.name as original_team_name',
                'ot.acronym as original_team_acronym',
                'teams.acronym as team_acronym',
                'teams.name as team_name',
                'teams.id as drafted_team_id',
                'signed_team.name as signed_team_name',
                'signed_team.id as signed_team_id',
                'd.player_id',
                'players.name as player_name',
                'd.season_id',
                'd.round',
                'd.pick_number',
                'd.draft_status',
                'd.draft_pick_right_id',
                'd.decision_maker_type',
                'd.decision_maker_name',
                'd.decision_maker_reason',
            )
            ->where(
                'players.draft_id',
                $latestSeasonId
            )
            ->orderBy('d.round')
            ->orderBy('d.pick_number')
            ->groupBy('players.id')
            ->get();

        $rankGroupPlayerIds =
            $draftResultsWithNames
            ->pluck('player_id');

        $playerStats =
            collect();

        if (
            $latestSeasonId ==
            $currentSeasonId
        ) {

            $playerStats =
                DB::table('player_game_stats')
                ->join(
                    'players',
                    'player_game_stats.player_id',
                    '=',
                    'players.id'
                )
                ->where(
                    'players.draft_id',
                    $currentSeasonId
                )
                ->whereIn(
                    'player_game_stats.player_id',
                    $rankGroupPlayerIds
                )
                ->select(
                    'player_game_stats.player_id',

                    DB::raw(
                        'COUNT(
                                CASE
                                    WHEN minutes > 0
                                    THEN 1
                                END
                            ) as total_games_played'
                    ),

                    DB::raw(
                        'AVG(player_game_stats.points)
                            as avg_points_per_game'
                    ),

                    DB::raw(
                        'AVG(player_game_stats.rebounds)
                            as avg_rebounds_per_game'
                    ),

                    DB::raw(
                        'AVG(player_game_stats.assists)
                            as avg_assists_per_game'
                    ),

                    DB::raw(
                        'AVG(player_game_stats.steals)
                            as avg_steals_per_game'
                    ),

                    DB::raw(
                        'AVG(player_game_stats.blocks)
                            as avg_blocks_per_game'
                    ),

                    DB::raw(
                        'AVG(player_game_stats.turnovers)
                            as avg_turnovers_per_game'
                    ),

                    DB::raw(
                        'AVG(player_game_stats.minutes)
                            as avg_minutes_played'
                    )
                )
                ->groupBy(
                    'player_game_stats.player_id'
                )
                ->get();
        } else {

            $playerStats =
                DB::table(
                    $playerSeasonStatsTable .
                        ' as player_season_stats'
                )
                ->join(
                    'players',
                    'player_season_stats.player_id',
                    '=',
                    'players.id'
                )
                ->where(
                    'players.draft_id',
                    $latestSeasonId
                )
                ->whereIn(
                    'player_season_stats.player_id',
                    $rankGroupPlayerIds
                )
                ->select(
                    'player_season_stats.player_id',
                    'player_season_stats.avg_points_per_game',
                    'player_season_stats.avg_rebounds_per_game',
                    'player_season_stats.avg_assists_per_game',
                    'player_season_stats.avg_steals_per_game',
                    'player_season_stats.avg_blocks_per_game',
                    'player_season_stats.avg_turnovers_per_game',
                    'player_season_stats.total_games_played',
                    'player_season_stats.avg_minutes_per_game as avg_minutes_played'
                )
                ->get();
        }

        $rankedPlayers =
            $playerStats
            ->filter(function ($player) {

                return
                    $player->total_games_played > 0
                    &&
                    $player->avg_minutes_played > 0;
            })
            ->sort(function ($a, $b) {

                $aStats =
                    ($a->avg_points_per_game * 1.0)
                    +
                    ($a->avg_rebounds_per_game * 1.2)
                    +
                    ($a->avg_assists_per_game * 1.5)
                    +
                    ($a->avg_steals_per_game * 2.0)
                    +
                    ($a->avg_blocks_per_game * 2.0)
                    -
                    ($a->avg_turnovers_per_game * 1.5);

                $bStats =
                    ($b->avg_points_per_game * 1.0)
                    +
                    ($b->avg_rebounds_per_game * 1.2)
                    +
                    ($b->avg_assists_per_game * 1.5)
                    +
                    ($b->avg_steals_per_game * 2.0)
                    +
                    ($b->avg_blocks_per_game * 2.0)
                    -
                    ($b->avg_turnovers_per_game * 1.5);

                $aFinalScore =
                    $aStats
                    *
                    $a->total_games_played
                    *
                    $a->avg_minutes_played;

                $bFinalScore =
                    $bStats
                    *
                    $b->total_games_played
                    *
                    $b->avg_minutes_played;

                return
                    $bFinalScore
                    <=>
                    $aFinalScore;
            })
            ->values();

        $rankedPlayers =
            $rankedPlayers->map(
                function ($stats, $index) {

                    $stats->rank =
                        $index + 1;

                    return $stats;
                }
            );

        $draftResultsWithNamesAndRanks =
            $draftResultsWithNames
            ->map(
                function ($draft) use (
                    $rankedPlayers
                ) {

                    $playerRank =
                        $rankedPlayers
                        ->firstWhere(
                            'player_id',
                            $draft->player_id
                        );

                    $draft->rank =
                        $playerRank->rank
                        ?? null;

                    return $draft;
                }
            );

        return response()->json([
            'season_id' => $latestSeasonId,
            'draft_results' =>
            $draftResultsWithNamesAndRanks,
        ]);
    }
}
