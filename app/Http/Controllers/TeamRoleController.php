<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeamRoleController extends Controller
{
    public function __construct(){

    }

    public function updateTeamRolesBasedOnStats($teamId, $round)
    {
        if (!$teamId) return false;

        DB::beginTransaction();
        try {
            $seasonId = get_current_season_id();
            $previousSeasonId = $seasonId - 1;

            $playersRaw = DB::table('players')
                ->where('contract_years', '>', 0)
                ->where('is_injured', false)
                ->where('team_id', $teamId)
                ->select('id', 'role', 'position')
                ->get();

            $playerEfficiencies = [];

            foreach ($playersRaw as $player) {
                $playerId = $player->id;

                $yearsPro = DB::table('player_season_stats_archives')
                    ->where('player_id', $playerId)
                    ->distinct('season_id')
                    ->count('season_id') + 1;

                // Current season totals
                $currentEff = DB::table('player_season_stats')
                    ->where('season_id', $seasonId)
                    ->where('player_id', $playerId)
                    ->sum('eff');

                $gamesPlayed = DB::table('player_game_stats')
                    ->where('season_id', $seasonId)
                    ->where('player_id', $playerId)
                    ->count();

                // PER approximation (EFF per game)
                $per = $gamesPlayed > 0 ? ($currentEff / $gamesPlayed) : 0;

                // Total EFF including last 5 games of previous season (early season buffer)
                $totalEff = $currentEff;
                if ($round <= 5) {
                    $lastFiveGames = DB::table('player_game_stats')
                        ->where('season_id', $previousSeasonId)
                        ->where('player_id', $playerId)
                        ->orderByDesc('id')
                        ->limit(5)
                        ->pluck('eff');
                    $totalEff += $lastFiveGames->sum();
                }

                // Draft info
                $draft = DB::table('drafts')
                    ->where('player_id', $playerId)
                    ->where('season_id', $seasonId)
                    ->first();

                // Hybrid role score: PER weighted higher than EFF/game
                $roleScore = ($per * 0.6) + (($totalEff / max(1, $gamesPlayed)) * 0.4);

                // Positional weights
                $positionWeights = [
                    'PG' => fn($score) => $score * 1.05,
                    'SG' => fn($score) => $score * 1.03,
                    'SF' => fn($score) => $score,
                    'PF' => fn($score) => $score * 1.02,
                    'C'  => fn($score) => $score * 1.06,
                ];

                $mainPosition = explode('/', $player->position)[0] ?? 'SF';
                $weightedScore = isset($positionWeights[$mainPosition])
                    ? $positionWeights[$mainPosition]($roleScore)
                    : $roleScore;

                $playerEfficiencies[] = [
                    'player_id' => $playerId,
                    'position' => $player->position,
                    'role' => $player->role,
                    'role_score' => $weightedScore,
                    'years_pro' => $yearsPro,
                    'is_rookie' => $draft ? true : false,
                    'draft_round' => $draft->round ?? null,
                    'draft_pick' => $draft->pick_number ?? null,
                ];
            }

            // Sort by score first, then years pro
            $players = collect($playerEfficiencies)->sort(function ($a, $b) {
                return $b['role_score'] <=> $a['role_score'] ?: $b['years_pro'] <=> $a['years_pro'];
            })->values();

            // Build starting five
            $positions = ['PG', 'SG', 'SF', 'PF', 'C'];
            $startingFive = [];
            $usedPlayerIds = [];

            foreach ($positions as $neededPosition) {
                foreach ($players as $player) {
                    if (in_array($player['player_id'], $usedPlayerIds)) continue;
                    $playerPositions = explode('/', strtoupper($player['position']));
                    if (in_array($neededPosition, $playerPositions)) {
                        $startingFive[] = $player;
                        $usedPlayerIds[] = $player['player_id'];
                        break;
                    }
                }
            }

            // Add high-performing rookie to starting five if missing
            foreach ($players as $player) {
                if (count($startingFive) >= 5) break;
                if (!in_array($player['player_id'], $usedPlayerIds)) {
                    if ($player['is_rookie'] && $player['draft_pick'] && $player['draft_pick'] <= 5) {
                        $startingFive[] = $player;
                        $usedPlayerIds[] = $player['player_id'];
                    }
                }
            }

            // Fill to 5 if still lacking
            foreach ($players as $player) {
                if (count($startingFive) >= 5) break;
                if (!in_array($player['player_id'], $usedPlayerIds)) {
                    $startingFive[] = $player;
                    $usedPlayerIds[] = $player['player_id'];
                }
            }

            // Assign roles
            usort($startingFive, function ($a, $b) {
                return $b['role_score'] <=> $a['role_score'] ?: $b['years_pro'] <=> $a['years_pro'];
            });

            $newRoles = [];
            $newRoles[$startingFive[0]['player_id']] = 'star player';
            $roleOrder = ['all star', 'all star', 'starter', 'starter'];
            foreach (array_slice($startingFive, 1) as $i => $player) {
                $newRoles[$player['player_id']] = $roleOrder[$i];
            }

            // Role players
            $rolePlayerCount = 0;
            foreach ($players as $player) {
                if (in_array($player['player_id'], $usedPlayerIds)) continue;
                if ($rolePlayerCount < 5 || ($player['is_rookie'] && $player['draft_pick'] && $player['draft_pick'] <= 10)) {
                    $newRoles[$player['player_id']] = 'role player';
                    $usedPlayerIds[] = $player['player_id'];
                    $rolePlayerCount++;
                }
            }

            // Remaining bench
            foreach ($players as $player) {
                if (!in_array($player['player_id'], $usedPlayerIds)) {
                    $newRoles[$player['player_id']] = 'bench';
                }
            }

            // Save role changes
            foreach ($newRoles as $playerId => $newRole) {
                $currentRole = collect($playersRaw)->firstWhere('id', $playerId)->role;
                if ($currentRole !== $newRole) {
                    $status = $newRole === 'star player' ? 'star player change' : 'role change';
                    $roundName = is_numeric($round) ? "Round $round" : "Playoffs";

                    DB::table('transactions')->insert([
                        'player_id' => $playerId,
                        'season_id' => $seasonId,
                        'details' => "Has moved from $currentRole to $newRole for the upcoming games. $roundName",
                        'from_team_id' => $teamId,
                        'to_team_id' => $teamId,
                        'status' => $status,
                    ]);
                }

                DB::table('players')->where('id', $playerId)->update(['role' => $newRole]);

                DB::table('player_season_stats')
                    ->where('player_id', $playerId)
                    ->where('season_id', $seasonId)
                    ->where('team_id', $teamId)
                    ->update(['role' => $newRole]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updating team $teamId roles: " . $e->getMessage());
            return false;
        }
    }
}
