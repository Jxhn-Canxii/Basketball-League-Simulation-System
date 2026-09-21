<?php

namespace App\Services\Draft;

use Illuminate\Support\Facades\DB;

class DraftPickRightsService
{
    /**
     * ============================================================
     * ENSURE FUTURE DRAFT RIGHTS
     * ============================================================
     *
     * Creates the permanent draft assets for the next 3 draft
     * seasons.
     *
     * Example:
     *
     * Current season = 13
     *
     * Creates:
     *   Season 14 R1/R2
     *   Season 15 R1/R2
     *   Season 16 R1/R2
     *
     * It does NOT create Season 13 because that draft has already
     * occurred before the trade event.
     */
    public function ensureFutureDraftRights(int $currentSeasonId): void
    {
        $futureSeasons = [
            $currentSeasonId + 1,
            $currentSeasonId + 2,
            $currentSeasonId + 3,
            $currentSeasonId + 4,
            $currentSeasonId + 5,
        ];

        $teams = DB::table('teams')
            ->select('id')
            ->get();

        if ($teams->isEmpty()) {
            return;
        }

        foreach ($futureSeasons as $seasonId) {

            $draftRounds = $seasonId > 1 ? [1,2] : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];

            foreach ($teams as $team) {

                foreach ($draftRounds as $round) {

                    DB::table('draft_pick_rights')
                        ->insertOrIgnore([
                            'season_id' => $seasonId,
                            'round' => $round,

                            /*
                             * This never changes.
                             *
                             * It tells us which franchise originally
                             * owned this draft asset.
                             */
                            'original_team_id' => $team->id,

                            /*
                             * Initially the original owner owns it.
                             *
                             * If traded, this becomes the acquiring team.
                             */
                            'current_owner_id' => $team->id,

                            /*
                             * Future draft order is not known yet.
                             */
                            'pick_number' => null,

                            'is_traded' => 0,
                            'trade_proposal_id' => null,

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    /**
     * ============================================================
     * GET ORIGINAL PICK RIGHT
     * ============================================================
     *
     * Finds the permanent draft-right identity for:
     *
     * Season + Round + Original Team
     */
    public function getOriginalPickRight(
        int $seasonId,
        int $round,
        int $originalTeamId
    ) {
        return DB::table('draft_pick_rights')
            ->where('season_id', $seasonId)
            ->where('round', $round)
            ->where('original_team_id', $originalTeamId)
            ->first();
    }

    /**
     * ============================================================
     * GET TRADEABLE DRAFT PICKS
     * ============================================================
     *
     * Only the next 3 future draft seasons can be traded.
     *
     * Example:
     *
     * Current = 13
     *
     * Allowed:
     *   14
     *   15
     *   16
     *
     * Not allowed:
     *   13
     *   17+
     */
    public function getTradeableDraftPicks(int $teamId)
    {
        $currentSeasonId = (int) get_current_season_id();

        return DB::table('draft_pick_rights')
            ->where('current_owner_id', $teamId)
            ->whereBetween('season_id', [
                $currentSeasonId + 1,
                $currentSeasonId + 3,
            ])
            ->where('is_traded', 0)
            ->whereNull('trade_proposal_id')
            ->orderBy('season_id')
            ->orderBy('round')
            ->get();
    }

    /**
     * ============================================================
     * RESOLVE PICK OWNER
     * ============================================================
     *
     * The original team's standings determine the draft slot.
     *
     * The draft-right record determines who actually owns the
     * selection.
     */
    public function resolvePickOwner(
        object $pickRight,
        int $pickNumber
    ): int {
        /*
         * If there is no protection system affecting this pick,
         * the current owner makes the selection.
         */
        return (int) $pickRight->current_owner_id;
    }

    /**
     * ============================================================
     * CONSUME DRAFT RIGHT
     * ============================================================
     *
     * Once the actual draft occurs, this right can no longer be
     * offered as a future asset.
     */
    public function consumeDraftRight(int $draftPickRightId): void
    {
        DB::table('draft_pick_rights')
            ->where('id', $draftPickRightId)
            ->update([
                'is_used' => 1,
                'used_at' => now(),
                'updated_at' => now(),
            ]);
    }
}