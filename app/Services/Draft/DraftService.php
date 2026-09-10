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
     * DRAFT PLAYERS
     * ============================================================
     */
    public function draftPlayers()
    {
        DB::beginTransaction();

        $draftResults = [];

        try {

            $latestSeasonId = get_current_season_id();

            $currentSeasonId = $latestSeasonId + 1;

            /*
             * Get draft order.
             */
            $draftOrder = DB::table('drafts')
                ->where(
                    'season_id',
                    $currentSeasonId
                )
                ->orderBy('round')
                ->orderBy('pick_number')
                ->get();

            if ($draftOrder->isEmpty()) {
                throw new \RuntimeException(
                    'No draft order exists. Generate the draft order first.'
                );
            }

            /*
             * ====================================================
             * ROOKIE POOL
             * ====================================================
             */

            $availablePlayers = collect(
                DB::table('players')
                    ->where('is_rookie', 1)
                    ->where('team_id', 0)
                    ->where('draft_id', $currentSeasonId)
                    ->where('is_drafted', 0)
                    ->orderByDesc('overall_rating')
                    ->orderBy('age')
                    ->get()
            );

            /*
             * We need enough players for every pick.
             *
             * The extra 20 allows undrafted players to remain.
             */
            $draftPlayerCountLimit =
                $draftOrder->count() + 20;

            if (
                $availablePlayers->count()
                <
                $draftPlayerCountLimit
            ) {
                throw new \RuntimeException(
                    'Not enough rookies available for the draft.'
                );
            }

            /*
             * Cache team position needs.
             */
            $teamPositionNeeds = [];

            foreach ($draftOrder as $pick) {

                $teamId = (int) $pick->team_id;

                if (!isset($teamPositionNeeds[$teamId])) {

                    $teamPositionNeeds[$teamId] =
                        $this->getTeamPositionNeeds(
                            $teamId
                        );
                }
            }

            /*
             * ====================================================
             * PROCESS EVERY PICK
             * ====================================================
             */

            foreach ($draftOrder as $pick) {

                if ($availablePlayers->isEmpty()) {
                    break;
                }

                $teamId = (int) $pick->team_id;

                /*
                 * Team making the selection.
                 */
                $team = DB::table('teams')
                    ->where('id', $teamId)
                    ->first();

                if (!$team) {
                    throw new \RuntimeException(
                        "Team {$teamId} does not exist."
                    );
                }

                /*
                 * =================================================
                 * COACH / POSITION SELECTION
                 * =================================================
                 */

                $neededPositions =
                    array_keys(
                        $teamPositionNeeds[$teamId] ?? []
                    );

                $coach =
                    $this->coachDecisionService
                    ->getTeamCoach($teamId);

                $candidatePlayers =
                    $availablePlayers
                    ->sortByDesc(function ($player) {

                        return (float) (
                            $player->overall_rating
                            ?? $player->overall
                            ?? 0
                        );
                    })
                    ->take(15)
                    ->values();

                $scoredCandidates =
                    $candidatePlayers
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

                $topCandidates =
                    $scoredCandidates
                    ->take(3)
                    ->values();

                $selectedCandidate =
                    $topCandidates->first();

                if (!$selectedCandidate) {
                    throw new \RuntimeException(
                        "No draft candidate available for pick {$pick->pick_number}."
                    );
                }

                /*
                 * Coach uncertainty between top candidates.
                 */
                if ($topCandidates->count() > 1) {

                    $coachQuality =
                        $this->coachDecisionService
                        ->getCoachQuality($coach);

                    $bestChance =
                        55 +
                        (($coachQuality - 50) * 0.40);

                    $bestChance = max(
                        40,
                        min(90, $bestChance)
                    );

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

                $selectedPlayer =
                    $selectedCandidate['player'];

                $selectedDraftScore =
                    $selectedCandidate['draft_score'];

                /*
                 * Remove from available pool immediately.
                 */
                $availablePlayers =
                    $availablePlayers
                    ->reject(function ($player) use ($selectedPlayer) {

                        return (int) $player->id
                            ===
                            (int) $selectedPlayer->id;
                    })
                    ->values();

                /*
                 * =================================================
                 * ROSTER MANAGEMENT
                 * =================================================
                 */

                $hasSpace =
                    $this->teamHasRosterSpace(
                        $teamId
                    );

                /*
                 * First round picks are valuable.
                 *
                 * We DO NOT allow a top first-round player to
                 * randomly disappear into free agency simply
                 * because the roster is full.
                 */
                $mustSign =
                    $pick->round == 1;

                $waivedPlayer = null;

                if (!$hasSpace) {

                    /*
                     * First try to create space intelligently.
                     */
                    $waivedPlayer =
                        $this->findPlayerToWaive(
                            $teamId,
                            $selectedPlayer,
                            $currentSeasonId,
                            $mustSign
                        );

                    if ($waivedPlayer) {

                        $this->waivePlayerForDraft(
                            $waivedPlayer,
                            $team,
                            $currentSeasonId
                        );

                        $hasSpace = true;
                    }
                }

                /*
                 * =================================================
                 * FINAL SIGNING DECISION
                 * =================================================
                 *
                 * First round:
                 *   MUST sign.
                 *
                 * Second round:
                 *   sign if space was created.
                 *
                 * If a second-round pick has no space and we
                 * cannot create space, the player remains
                 * undrafted/free agent.
                 */
                $finalTeamId =
                    $hasSpace
                    ? $teamId
                    : ($mustSign ? $teamId : 0);

                /*
                 * Safety:
                 *
                 * If a first-rounder somehow still has no space,
                 * force one more roster cut.
                 */
                if (
                    $mustSign
                    &&
                    !$this->teamHasRosterSpace($teamId)
                ) {

                    $forcedWaive =
                        $this->findPlayerToWaive(
                            $teamId,
                            $selectedPlayer,
                            $currentSeasonId,
                            true
                        );

                    if (!$forcedWaive) {

                        throw new \RuntimeException(
                            "Unable to create roster space for first-round pick {$pick->pick_number}."
                        );
                    }

                    $this->waivePlayerForDraft(
                        $forcedWaive,
                        $team,
                        $currentSeasonId
                    );

                    $finalTeamId = $teamId;
                    $hasSpace = true;
                }

                /*
                 * =================================================
                 * MARK PLAYER AS DRAFTED
                 * =================================================
                 */

                DB::table('players')
                    ->where('id', $selectedPlayer->id)
                    ->update([
                        'team_id' => $finalTeamId,
                        'drafted_team_id' => $teamId,
                        'is_drafted' => 1,
                        'draft_order' => $pick->pick_number,
                        'draft_status' => $pick->draft_status,

                        /*
                         * ContractService becomes the authority
                         * for actual contract years/salary.
                         */
                        'contract_years' => 0,
                    ]);

                /*
                 * Update draft row.
                 */
                DB::table('drafts')
                    ->where([
                        'season_id' => $currentSeasonId,
                        'round' => $pick->round,
                        'pick_number' => $pick->pick_number,
                    ])
                    ->update([
                        'player_id' => $selectedPlayer->id,
                        'team_id' => $teamId,
                    ]);

                /*
                 * =================================================
                 * DRAFT TRANSACTION
                 * =================================================
                 */

                DB::table('transactions')->insert([
                    'player_id' => $selectedPlayer->id,
                    'season_id' => $currentSeasonId,
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'draft',
                    'details' =>
                    "Drafted by {$team->name} in round {$pick->round}, pick {$pick->pick_number}.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /*
                 * =================================================
                 * ROOKIE CONTRACT
                 * =================================================
                 *
                 * FIRST ROUND:
                 * always signs.
                 *
                 * SECOND ROUND:
                 * signs only if roster space exists.
                 */
                $offer = null;

                if ($finalTeamId === $teamId) {

                    $offer =
                        $this->contractService
                        ->assignRookieContract(
                            $selectedPlayer,
                            $pick->round,
                            $pick->pick_number
                        );

                    /*
                     * Update players from the ContractService
                     * result.
                     */
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

                    /*
                     * player_contracts
                     */
                    DB::table('player_contracts')
                        ->insert([
                            'player_id' =>
                            $selectedPlayer->id,

                            'season_id' =>
                            $currentSeasonId,

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

                    /*
                     * Signed transaction.
                     */
                    DB::table('transactions')
                        ->insert([
                            'player_id' =>
                            $selectedPlayer->id,

                            'season_id' =>
                            $currentSeasonId,

                            'details' =>
                            $selectedPlayer->name .
                                " signed with " .
                                $team->name .
                                " for " .
                                $offer['years'] .
                                " years on a " .
                                $offer['contract_type'] .
                                " contract worth ₱" .
                                number_format(
                                    (float) $offer['salary'],
                                    2
                                ) .
                                '.',

                            'from_team_id' => 0,

                            'to_team_id' =>
                            $teamId,

                            'status' => 'signed',

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                /*
                 * =================================================
                 * CONSUME PICK RIGHT
                 * =================================================
                 */

                if (!empty($pick->draft_pick_right_id)) {

                    $this->draftPickRightsService
                        ->consumeDraftRight(
                            (int) $pick->draft_pick_right_id
                        );
                }

                /*
                 * =================================================
                 * RESULT
                 * =================================================
                 */

                $draftResults[] = [
                    'team_id' => $teamId,

                    'player_id' =>
                    $selectedPlayer->id,

                    'player_name' =>
                    $selectedPlayer->name,

                    'position' =>
                    $selectedPlayer->position,

                    'age' =>
                    $selectedPlayer->age,

                    'archetype' =>
                    $selectedPlayer->type,

                    'overall_rating' =>
                    $selectedPlayer->overall_rating,

                    'team_name' =>
                    $team->name,

                    'draft_id' =>
                    $currentSeasonId,

                    'draft_order' =>
                    $pick->pick_number,

                    'draft_status' =>
                    $pick->draft_status,

                    'round' =>
                    $pick->round,

                    'pick_number' =>
                    $pick->pick_number,

                    'draft_pick_right_id' =>
                    $pick->draft_pick_right_id,

                    'draft_score' =>
                    $selectedDraftScore,

                    'signed' =>
                    $finalTeamId === $teamId,

                    'contract_years' =>
                    $offer['years'] ?? 0,

                    'salary' =>
                    $offer['salary'] ?? 0,

                    'waived_player_id' =>
                    $waivedPlayer->id ?? null,

                    'waived_player_name' =>
                    $waivedPlayer->name ?? null,
                ];

                /*
                 * Update positional needs after drafting.
                 */
                $teamPositionNeeds[$teamId] =
                    $this->updateTeamPositionNeeds(
                        $teamPositionNeeds[$teamId] ?? [],
                        $selectedPlayer->position
                    );
            }

            /*
             * ====================================================
             * UNDRAFTED ROOKIES
             * ====================================================
             */

            DB::table('players')
                ->where('draft_id', $currentSeasonId)
                ->where('is_drafted', 0)
                ->update([
                    'team_id' => 0,
                    'contract_years' => 0,
                    'salary' => 0,
                    'draft_status' => 'Undrafted',
                    'is_rookie' => 1,
                ]);

            /*
             * ====================================================
             * SEASON STATUS
             * ====================================================
             */

            DB::table('seasons')
                ->where('id', $latestSeasonId)
                ->update([
                    'status' => config('timeline.draft'),
                ]);

            DB::commit();

            return response()->json([
                'error' => false,
                'season_id' => $currentSeasonId,
                'draft_results' => $draftResults,
                'message' =>
                'Draft completed successfully.',
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Drafting failed',
                [
                    'exception' => $e,
                ]
            );

            return response()->json([
                'error' => true,
                'message' => 'Drafting failed.',
                'error_message' => $e->getMessage(),
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
     * ROSTER SPACE
     * ============================================================
     */
    private function teamHasRosterSpace(int $teamId): bool
    {
        $count = DB::table('players')
            ->where('team_id', $teamId)
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
                'd.draft_pick_right_id'
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
