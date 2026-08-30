<?php

namespace App\Http\Controllers;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class TeamStatsController extends Controller
{
    public function getActivePlayersSorted($teamId, $rolePriority, $round)
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

            $playerEfficiencies[] = [
                'player' => $player,
                'role' => $player->role,
                'total_eff' => $totalEff,
                'years_pro' => $yearsPro,
                'is_rookie' => $draft ? true : false,
                'draft_round' => $draft->round ?? null,
                'draft_pick' => $draft->pick_number ?? null,
                'role_rank' => array_search($player->role, $rolePriority) !== false
                    ? array_search($player->role, $rolePriority)
                    : PHP_INT_MAX,
            ];
        }

        // Sort by: total_eff DESC, years_pro DESC, role_priority ASC
        $sortedPlayers = collect($playerEfficiencies)->sort(function ($a, $b) {
            return $b['total_eff'] <=> $a['total_eff']
                ?: $b['years_pro'] <=> $a['years_pro']
                ?: $a['role_rank'] <=> $b['role_rank'];
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
            // Log the error message
            \Log::error('Error updating head-to-head matchup for team_id ' . $teamId . ' vs opponent_id ' . $opponentId . ': ' . $e->getMessage());

            // Return a structured error response
            return response()->json([
                'error' => 'Error updating head-to-head matchup: ' . $e->getMessage()
            ], 500); // Internal server error
        }
    }
    
    public function saveStandingsSnapshot()
    {
        try {
            $snapshots = DB::table('standings_view')
                ->select(
                    'team_id',
                    'team_name',
                    'team_city',
                    'team_acronym',
                    'primary_color',
                    'secondary_color',
                    'conference_id',
                    'conference_name',
                    'season_id',
                    'wins',
                    'losses',
                    'total_home_score',
                    'total_away_score',
                    'home_ppg',
                    'away_ppg',
                    'score_difference',
                    'conference_rank',
                    'overall_rank',
                    'is_defending_champion',
                    'chemistry',
                    'last_playoff_season_name',
                    'playoff_appearances',
                    'finals_appearances',
                    'conference_finals_appearances',
                    'conference_championships',
                    'championships',
                    'streak_status',
                    'last_5_games'
                )
                ->get();

            foreach ($snapshots as $snapshot) {
                DB::table('standings_snapshots')->updateOrInsert(
                    [
                        'team_id' => $snapshot->team_id,
                        'season_id' => $snapshot->season_id,
                    ],
                    (array) $snapshot
                );
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Standing Snapshot Error' . $e->getMessage(),
            ], 500);
        }
    }
}
