<?php

namespace App\Services\Team;

use App\Models\Player;
use Illuminate\Support\Facades\DB;

class TeamStatsService
{
    public function prepareFinalRoster($teamId)
    {
        $seasonId = get_current_season_id();
        $previousSeasonId = get_previous_season_id(); // You must implement this

        $rolePriority = [
            'star player' => 1,
            'all star' => 2,
            'starter' => 2,
            'role player' => 5,
            'bench' => 5,
        ];

        $players = Player::where('team_id', $teamId)
            ->where('is_active', 1)
            ->get();

        $playerEfficiencies = [];

        foreach ($players as $player) {
            $playerId = $player->id;
            $role = $player->role;
            $isInjured = $player->is_injured;

            // Years pro = distinct seasons
            $yearsPro = DB::table('player_season_stats_archives')
                ->where('player_id', $playerId)
                ->distinct('season_id')
                ->count('season_id') + 1;

            // Current season efficiency sum
            $currentEff = DB::table('player_season_stats')
                ->where('season_id', $seasonId)
                ->where('player_id', $playerId)
                ->sum('eff') ?? 0;

            // Last 5 games from previous season (only if early season)
            $lastFiveGamesEff = DB::table('player_game_stats')
                ->where('season_id', $previousSeasonId)
                ->where('player_id', $playerId)
                ->orderByDesc('id')
                ->limit(5)
                ->sum('eff') ?? 0;

            $totalEff = $currentEff + $lastFiveGamesEff;

            // Draft info
            $draft = DB::table('drafts')
                ->where('player_id', $playerId)
                ->where('season_id', $seasonId)
                ->first();

            $playerEfficiencies[] = [
                'player' => $player,
                'player_id' => $playerId,
                'role' => $role,
                'total_eff' => $totalEff,
                'years_pro' => $yearsPro,
                'is_rookie' => $draft ? true : false,
                'draft_round' => $draft->round ?? null,
                'draft_pick' => $draft->pick_number ?? null,
                'is_injured' => $isInjured ?? 0,
                'role_rank' => array_search($player->role, $rolePriority) !== false
                    ? array_search($player->role, $rolePriority)
                    : PHP_INT_MAX,
            ];
        }

        // Sort by: total_eff DESC, years_pro DESC, role_priority ASC
        $sortedPlayers = collect($playerEfficiencies)->sort(function ($a, $b) {
            return $a['is_injured'] <=> $b['is_injured']
                ?: $b['total_eff'] <=> $a['total_eff']
                ?: $b['years_pro'] <=> $a['years_pro']
                ?: $a['role_rank'] <=> $b['role_rank'];
        })->pluck('player')->values();

        $sortedPlayers->slice(1, 12)->each(function ($playerStat) {

                $newFatigue = $this->fatigueAdjustment($playerStat->morale,$playerStat->fatigue);

                Player::where('id', $playerStat->id)->update(['is_reserved' => false,'fatigue' => $newFatigue]);    
        });

        foreach ($sortedPlayers->slice(12, 15) as $playerStat) {
                $newFatigue = $playerStat->is_injured == 1 ? $playerStat->fatigue - 5 : 0;

                Player::where('id', $playerStat->id)->update(['is_reserved' => true,'fatigue' => $newFatigue, 'role' => 'bench' ]);

                DB::table('player_season_stats')
                    ->where('id', $playerStat->id)
                    ->where('team_id', $teamId)
                    ->where('season_id', $seasonId)
                    ->update(['role' => 'bench' ]);   
        }

    }

    public function getActivePlayersSorted($teamId, $gameId, $rolePriority, $round)
    {
        $seasonId = get_current_season_id();
        $previousSeasonId = get_previous_season_id(); // You must implement this

        $players = Player::where('team_id', $teamId)
            ->where('is_active', 1)
            ->get();

        $playerEfficiencies = [];

        foreach ($players as $player) {
            $playerId = $player->id;
            $role = $player->role;

            // Years pro = distinct seasons
            $yearsPro = DB::table('player_season_stats_archives')
                ->where('player_id', $playerId)
                ->distinct('season_id')
                ->count('season_id') + 1;

            // Current season efficiency sum
            $currentEff = DB::table('player_season_stats')
                ->where('season_id', $seasonId)
                ->where('player_id', $playerId)
                ->sum('eff') ?? 0;

            // Last 5 games from previous season (only if early season)
            $lastFiveGamesEff = DB::table('player_game_stats')
                ->where('season_id', $previousSeasonId)
                ->where('player_id', $playerId)
                ->orderByDesc('id')
                ->limit(5)
                ->sum('eff') ?? 0;

            $totalEff = $currentEff + $lastFiveGamesEff;

            // Draft info
            $draft = DB::table('drafts')
                ->where('player_id', $playerId)
                ->where('season_id', $seasonId)
                ->first();

            $playerFouls = DB::table('player_per_quarter_stats')
                ->where('player_id', $playerId)
                ->where('game_id', $gameId)
                ->sum('fouls');

            $isFouledOut = ($playerFouls >= 5) ? 1 : 0;

            $player->is_fouled_out = $isFouledOut;
            $player->last_quarter_fouls = $playerFouls;
            
            $playerEfficiencies[] = [
                'player' => $player,
                'role' => $player->role,
                'total_eff' => $totalEff,
                'years_pro' => $yearsPro,
                'is_rookie' => $draft ? true : false,
                'draft_round' => $draft->round ?? null,
                'draft_pick' => $draft->pick_number ?? null,
                'is_fouled_out' =>  $isFouledOut,
                'fatigue' =>  $player->fatigue,
                'role_rank' => array_search($player->role, $rolePriority) !== false
                    ? array_search($player->role, $rolePriority)
                    : PHP_INT_MAX,
            ];
        }

        // Sort by: total_eff DESC, role_priority ASC,fatigue ASC,years_pro DESC,
        $sortedPlayers = collect($playerEfficiencies)->sort(function ($a, $b) {
            return $b['total_eff'] <=> $a['total_eff']
                ?: $a['role_rank'] <=> $b['role_rank']
                ?: $a['fatigue'] <=> $b['fatigue']
                ?: $b['years_pro'] <=> $a['years_pro'];
        })->pluck('player')->values();

        return $sortedPlayers;
    }
   
    public function getTeamChemistry($seasonId, $teamId)
    {
        return DB::table('team_season_info')
            ->where('season_id', $seasonId)
            ->where('team_id', $teamId)
            ->value('chemistry');
    }
    // Helper methods for stat calculation
    public function updateHeadToHeadResults($gameId)
    {
        // Fetch the game details from the schedules table
        $game = DB::table('schedules')
            ->where('id', $gameId)
            ->where('status', 2) // Ensure the game is completed
            ->first();

        if (!$game) {
            return response()->json([
                'error' => 'Game not found or not completed for game_id: ' . $gameId
            ], 404); // Game not found or not completed
        }

        // Determine the outcome of the game
        $teamWins = $game->home_score > $game->away_score ? 1 : 0;
        $opponentWins = $game->away_score > $game->home_score ? 1 : 0;
        $draws = $game->home_score == $game->away_score ? 1 : 0;

        // Update for the team's perspective (home vs away)
        $this->updateHeadToHeadMatchup($game->home_id, $game->away_id, $teamWins, $opponentWins, $draws);

        // Update for the opponent's perspective (away vs home)
        $this->updateHeadToHeadMatchup($game->away_id, $game->home_id, $opponentWins, $teamWins, $draws);

        return response()->json([
            'message' => 'Successfully updated head-to-head matchups for game_id: ' . $gameId
        ], 200); // Success
    }

    private function updateHeadToHeadMatchup($teamId, $opponentId, $teamWins, $opponentWins, $draws)
    {
        try {
            // Check if this matchup already exists in the head_to_head table
            $matchup = DB::table('head_to_head')
                ->where('team_id', $teamId)
                ->where('opponent_id', $opponentId)
                ->first();

            if ($matchup) {
                // If matchup exists, update the match count and win/loss records
                DB::table('head_to_head')
                    ->where('team_id', $teamId)
                    ->where('opponent_id', $opponentId)
                    ->update([
                        'wins' => $matchup->wins + $teamWins,
                        'losses' => $matchup->losses + $opponentWins,
                        'draws' => $matchup->draws + $draws,
                    ]);
            } else {
                // If matchup does not exist, insert a new record
                DB::table('head_to_head')
                    ->insert([
                        'team_id' => $teamId,
                        'opponent_id' => $opponentId,
                        'wins' => $teamWins,
                        'losses' => $opponentWins,
                        'draws' => $draws,
                    ]);
            }

            // Return true if successful
            return true;
        } catch (\Exception $e) {
            // Return a structured error response
            return response()->json([
                'error' => 'Error updating head-to-head matchup: ' . $e->getMessage()
            ], 500); // Internal server error
        }
    }

    private function fatigueAdjustment($morale,$fatigue){

        $moraleFactor = $this->moraleFactor($morale);

        $newFatigue = min(0,($fatigue - ($fatigue * $moraleFactor)));

        return $newFatigue;
    }

    private function moraleFactor(int $morale){

            switch ($morale) {
                case $morale > 90:
                    return 0.50;
                    break;
                case $morale > 80 && $morale < 90:
                    return 0.40;
                    break;
                case $morale > 70 && $morale < 80:
                    return 0.30;
                    break;
                case $morale > 60 && $morale < 70:
                    return 0.20;
                    break;
                case $morale < 50:
                    return 0.10;
                    break;
                default:
                    return 0.05;
                    break;
            }
    }
}
