<?php

namespace App\Services\Draft;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class DraftPickRightsService
{
    /**
     * Create the initial draft rights for a season.
     *
     * One row represents one franchise's original pick right.
     *
     * Example:
     * Team A 2028 Round 1
     *
     * original_team_id = A
     * current_owner_id = A
     */
    public function createInitialDraftRights(int $seasonId): void
    {
        $teams = DB::table('teams')
            ->select('id')
            ->orderBy('id')
            ->get();

        foreach ($teams as $team) {
            foreach ([1, 2] as $round) {
                $exists = DB::table('draft_pick_rights')
                    ->where('season_id', $seasonId)
                    ->where('round', $round)
                    ->where('original_team_id', $team->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('draft_pick_rights')->insert([
                    'season_id' => $seasonId,
                    'round' => $round,
                    'original_team_id' => $team->id,
                    'current_owner_id' => $team->id,
                    'is_traded' => 0,
                    'trade_proposal_id' => null,
                    'protections' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get all draft rights owned by a team.
     */
    public function getTeamDraftRights(
        int $teamId,
        ?int $seasonId = null
    ) {
        $seasonId ??= get_current_season_id() + 1;

        return DB::table('draft_pick_rights')
            ->where('season_id', $seasonId)
            ->where('current_owner_id', $teamId)
            ->orderBy('round')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get one original pick right.
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
     * Transfer a draft right from one team to another.
     */
    public function transferDraftRight(
        int $draftPickRightId,
        int $fromTeamId,
        int $toTeamId,
        ?int $tradeProposalId = null
    ): bool {
        $pick = DB::table('draft_pick_rights')
            ->where('id', $draftPickRightId)
            ->lockForUpdate()
            ->first();

        if (!$pick) {
            throw new RuntimeException(
                "Draft pick right {$draftPickRightId} does not exist."
            );
        }

        if ((int) $pick->current_owner_id !== $fromTeamId) {
            throw new RuntimeException(
                "Draft pick right {$draftPickRightId} is not owned by team {$fromTeamId}."
            );
        }

        if ($fromTeamId === $toTeamId) {
            throw new RuntimeException(
                'A draft pick cannot be transferred to the same team.'
            );
        }

        DB::table('draft_pick_rights')
            ->where('id', $draftPickRightId)
            ->update([
                'current_owner_id' => $toTeamId,
                'is_traded' => 1,
                'trade_proposal_id' => $tradeProposalId,
                'updated_at' => now(),
            ]);

        return true;
    }

    /**
     * Validate that a team still owns a pick.
     */
    public function validateOwnership(
        int $draftPickRightId,
        int $teamId
    ): bool {
        return DB::table('draft_pick_rights')
            ->where('id', $draftPickRightId)
            ->where('current_owner_id', $teamId)
            ->exists();
    }

    /**
     * Determine who receives a pick after protection rules.
     *
     * Currently supports:
     * Top-5
     * Top 5
     * top-5
     * Top-10
     * Top 10
     * Lottery protected
     * None
     *
     * Without a rollover column/schema, a protected pick simply
     * remains with the original owner for this draft.
     */
    public function resolvePickOwner(
        object $pickRight,
        int $pickNumber
    ): int {
        $protection = trim((string) ($pickRight->protections ?? ''));

        if ($protection === '' || strtolower($protection) === 'none') {
            return (int) $pickRight->current_owner_id;
        }

        $normalized = strtolower(
            str_replace([' ', '_'], '', $protection)
        );

        /*
         * Top-5 / top5
         */
        if (
            str_contains($normalized, 'top-5') ||
            str_contains($normalized, 'top5')
        ) {
            if ($pickNumber <= 5) {
                return (int) $pickRight->original_team_id;
            }

            return (int) $pickRight->current_owner_id;
        }

        /*
         * Top-10 / top10
         */
        if (
            str_contains($normalized, 'top-10') ||
            str_contains($normalized, 'top10')
        ) {
            if ($pickNumber <= 10) {
                return (int) $pickRight->original_team_id;
            }

            return (int) $pickRight->current_owner_id;
        }

        /*
         * Lottery protected.
         *
         * Lottery picks are positions 1-14.
         */
        if (str_contains($normalized, 'lottery')) {
            if ($pickNumber <= 14) {
                return (int) $pickRight->original_team_id;
            }

            return (int) $pickRight->current_owner_id;
        }

        return (int) $pickRight->current_owner_id;
    }

    /**
     * Mark a pick as consumed after the draft.
     *
     * We don't delete the row because the row is the historical
     * identity of the draft right.
     */
    public function consumeDraftRight(int $draftPickRightId): void
    {
        DB::table('draft_pick_rights')
            ->where('id', $draftPickRightId)
            ->update([
                'updated_at' => now(),
            ]);
    }

    /**
     * Get a human-readable description.
     */
    public function getPickDescription(object $pick): string
    {
        return sprintf(
            '%s Round %d Pick Right - Season %d',
            $pick->original_team_id,
            $pick->round,
            $pick->season_id
        );
    }
}