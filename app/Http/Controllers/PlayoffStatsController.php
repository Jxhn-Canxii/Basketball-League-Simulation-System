<?php

namespace App\Http\Controllers;
use App\Models\PlayerGameStats;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PlayoffStatsController extends Controller
{
    public function updatePlayoffQualifiedFlags()
    {
        $seasonId = get_current_season_id();

        // Step 1: Get all team IDs in the top 10 of their conference for this season
        $qualifiedTeamIds = DB::table('standings_view')
            ->where('season_id', $seasonId)
            ->where('conference_rank', '<=', 10)
            ->pluck('team_id')
            ->toArray();

        // Step 2: Set is_playoff_qualified = 1 for qualified teams
        DB::table('team_season_info')
            ->where('season_id', $seasonId)
            ->whereIn('team_id', $qualifiedTeamIds)
            ->update(['is_playoff_qualified' => 1]);

        // Step 3 (optional): Set is_playoff_qualified = 0 for all others
        DB::table('team_season_info')
            ->where('season_id', $seasonId)
            ->whereNotIn('team_id', $qualifiedTeamIds)
            ->update(['is_playoff_qualified' => 0]);
    }
    // Helper methods for stat calculation

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

    public function updateFinalsBonusContract($teamId, $seasonId, $teamName)
    {
        // Retrieve all active players for the specified team
        $players = Player::where('is_active', 1)
            ->where('team_id', $teamId)
            ->where('is_injured', 0)  // Exclude injured players
            ->get();

        foreach ($players as $player) {
            // Determine the additional contract years based on the player's role
            $additionalContractYears = 0;
            if ($player->role == 'star player') {
                $additionalContractYears = rand(2, 3);  // 2 to 3 years for star players
            }
            if ($player->role == 'all star') {
                $additionalContractYears = rand(1, 3);  // 1 to 3 years for all star players
            }
            if ($player->role == 'starter') {
                $additionalContractYears = rand(0, 2);  // 1 to 3 years for all star players
            }

            //only core players will have a bonus
            if ($additionalContractYears > 0) {
                // Update the player's contract years
                $player->contract_years += $additionalContractYears;
                $player->save();

                // Insert transaction log
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => 'Re-signed with ' . $teamName . ' for a contract extension(Finals Bonus) of ' . $additionalContractYears . ' years',
                    'from_team_id' => $player->team_id,
                    'to_team_id' => $player->team_id,
                    'status' => 'contract extension',
                ]);
            }
        }
    }

    // Method to handle semi-finals logic
    public function updateConferenceChampions($gameData, $winnerId)
    {
        // Determine the conference based on home or away conference name
        $conferenceName = $gameData->home_conference_name;

        // Define the columns to update
        $columnsToUpdate = [];

        // Determine the winner's team name based on the winner ID
        $winnerName = null;
        if ($gameData->home_team_id === $winnerId) {
            $winnerName = $gameData->home_team_name;
        } elseif ($gameData->away_team_id === $winnerId) {
            $winnerName = $gameData->away_team_name;
        }

        // Check the conference and set the champion ID and name columns
        switch ($conferenceName) {
            case 'Luzon':
                $columnsToUpdate = [
                    'east_champion_id' => $winnerId,
                    'east_champion_name' => $winnerName,
                ];
                break;
            case 'NCR':
                $columnsToUpdate = [
                    'west_champion_id' => $winnerId,
                    'west_champion_name' => $winnerName,
                ];
                break;
            case 'Visayas':
                $columnsToUpdate = [
                    'north_champion_id' => $winnerId,
                    'north_champion_name' => $winnerName,
                ];
                break;
            case 'Mindanao':
                $columnsToUpdate = [
                    'south_champion_id' => $winnerId,
                    'south_champion_name' => $winnerName,
                ];
                break;
        }

        // Update the seasons table with the determined columns
        DB::table('seasons')
            ->where('id', $gameData->season_id)
            ->update($columnsToUpdate);
    }

    public function updateSeriesConferenceChampions($gameData)
    {
        // Get series info including home and away team IDs and names, plus conference info
        $series = DB::table('playoff_series as ps')
            ->join('teams as home_team', 'ps.home_team_id', '=', 'home_team.id')
            ->join('teams as away_team', 'ps.away_team_id', '=', 'away_team.id')
            ->join('conferences as home_conf', 'home_team.conference_id', '=', 'home_conf.id')
            ->join('conferences as away_conf', 'away_team.conference_id', '=', 'away_conf.id')
            ->where('ps.series_id', $gameData->series_id)
            ->where('ps.status', 2)
            ->select(
                'ps.*',
                'home_team.name as home_team_name',
                'home_conf.name as home_conference',
                'away_team.name as away_team_name',
                'away_conf.name as away_conference'
            )
            ->first();


        if (!$series) {
            return; // Series not found
        }

        // Determine the conference based on home or away conference name from the series table
        // Assuming the conference relevant to this update is the home team's conference
        $conferenceName = $series->home_conference;
        $winnerId = $series->winner_team_id;
        // Determine winner's name from the series table based on winnerId
        $winnerName = null;
        if ($series->home_team_id === $winnerId) {
            $winnerName = $series->home_team_name;
        } elseif ($series->away_team_id === $winnerId) {
            $winnerName = $series->away_team_name;
        }

        // Prepare columns to update in the seasons table based on conference
        $columnsToUpdate = [];

        switch ($conferenceName) {
            case 'Luzon':
                $columnsToUpdate = [
                    'east_champion_id' => $winnerId,
                    'east_champion_name' => $winnerName,
                ];
                break;
            case 'NCR':
                $columnsToUpdate = [
                    'west_champion_id' => $winnerId,
                    'west_champion_name' => $winnerName,
                ];
                break;
            case 'Visayas':
                $columnsToUpdate = [
                    'north_champion_id' => $winnerId,
                    'north_champion_name' => $winnerName,
                ];
                break;
            case 'Mindanao':
                $columnsToUpdate = [
                    'south_champion_id' => $winnerId,
                    'south_champion_name' => $winnerName,
                ];
                break;
        }

        // Update the seasons table with the champion info for the conference
        if (!empty($columnsToUpdate)) {
            DB::table('seasons')
                ->where('id', $gameData->season_id)
                ->update($columnsToUpdate);
        }
    }

    // Method to handle finals logic
    public function updateFinalsWinner($gameData, $winnerId, $homeScore, $awayScore)
    {
        // 1. Get all finals game IDs for this season
        $finalsGameIds = DB::table('schedules')
            ->where('season_id', $gameData->season_id)
            ->where('round', 'finals') // adjust if your finals round name is different
            ->pluck('id');

        // 2. Calculate averages for all players in the winning team across finals
        $mvpPlayer = PlayerGameStats::join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->whereIn('player_game_stats.game_id', $finalsGameIds)
            ->where('player_game_stats.team_id', $winnerId)
            ->select(
                'player_game_stats.player_id',
                'players.name as mvp_name',
                DB::raw('AVG(player_game_stats.eff) as avg_eff'),
                DB::raw('AVG(player_game_stats.points) as avg_points'),
                DB::raw('AVG(player_game_stats.assists) as avg_assists'),
                DB::raw('AVG(player_game_stats.rebounds) as avg_rebounds')
            )
            ->groupBy('player_game_stats.player_id', 'players.name')
            ->orderByDesc('avg_eff')
            ->first();

        // 3. Get MVP details
        $finalsMVP = $mvpPlayer ? $mvpPlayer->mvp_name : '';
        $finalsMVPId = $mvpPlayer ? $mvpPlayer->player_id : '';
        $homeTeamWins = $gameData->home_team_id === $winnerId;

        // 4. Update season record with finals results
        DB::table('seasons')
            ->where('id', $gameData->season_id)
            ->update([
                'finals_winner_id'    => $winnerId,
                'finals_loser_id'     => $homeTeamWins ? $gameData->away_team_id : $gameData->home_team_id,
                'finals_winner_name'  => $homeTeamWins ? $gameData->home_team_name : $gameData->away_team_name,
                'finals_loser_name'   => $homeTeamWins ? $gameData->away_team_name : $gameData->home_team_name,
                'finals_winner_score' => $homeTeamWins ? $homeScore : $awayScore,
                'finals_loser_score'  => $homeTeamWins ? $awayScore : $homeScore,
                'finals_mvp'          => $finalsMVP,
                'finals_mvp_id'       => $finalsMVPId,
            ]);
    }

    public function updateSeriesFinalsWinner($gameData)
    {
        // Get series info including home and away team names and wins
        $series = DB::table('playoff_series as ps')
            ->join('teams as home_team', 'ps.home_team_id', '=', 'home_team.id')
            ->join('teams as away_team', 'ps.away_team_id', '=', 'away_team.id')
            ->where('ps.series_id', $gameData->series_id)
            ->select(
                'ps.*',
                'home_team.name as home_team_name',
                'away_team.name as away_team_name'
            )
            ->where('ps.status', 2)
            ->first();

        if (!$series) {
            return; // Series not found
        }

        // Finals game IDs in this season involving the two teams
        $finalsGameIds = DB::table('schedules')
            ->where('round', 'finals')
            ->where('season_id', $gameData->season_id)
            ->where(function ($query) use ($series) {
                $query->where('home_id', $series->home_team_id)
                    ->orWhere('away_id', $series->away_team_id);
            })
            ->pluck('game_id')
            ->toArray();

        // Calculate MVP based on eff average for winner team in finals
        $mvpPlayer = PlayerGameStats::join('players', 'player_game_stats.player_id', '=', 'players.id')
            ->whereIn('player_game_stats.game_id', $finalsGameIds)
            ->where('player_game_stats.team_id', $series->winner_team_id)
            ->select(
                'player_game_stats.player_id',
                'players.name as mvp_name',
                DB::raw('AVG(player_game_stats.eff) as avg_eff')
            )
            ->groupBy('player_game_stats.player_id', 'players.name')
            ->orderByDesc('avg_eff')
            ->first();

        $finalsMVP = $mvpPlayer ? $mvpPlayer->mvp_name : null;
        $finalsMVPId = $mvpPlayer ? $mvpPlayer->player_id : null;

        // Determine if winner is home or away to assign names correctly
        $homeTeamWins = $series->winner_team_id == $series->home_team_id;

        // Update seasons table with finals results using winner_team_id, loser_team_id, and wins
        DB::table('seasons')
            ->where('id', $gameData->season_id)
            ->update([
                'finals_winner_id'    => $series->winner_team_id,
                'finals_loser_id'     => $series->loser_team_id,
                'finals_winner_name'  => $homeTeamWins ? $series->home_team_name : $series->away_team_name,
                'finals_loser_name'   => $homeTeamWins ? $series->away_team_name : $series->home_team_name,
                'finals_winner_score' => $homeTeamWins ? $series->home_wins : $series->away_wins,
                'finals_loser_score'  => $homeTeamWins ? $series->away_wins : $series->home_wins,
                'finals_mvp'          => $finalsMVP,
                'finals_mvp_id'       => $finalsMVPId,
            ]);
    }

    public function updateFinalsMVPBonusContract($winnerId, $seasonId, $finalsMVPId)
    {
        $extensionYears = 3; // Number of years to extend the contract for the Finals MVP
        $awardName = 'Finals MVP'; // Name of the award
        // Add years to player's contract
        DB::table('players')
            ->where('id', $finalsMVPId)
            ->update([
                'contract_years' => DB::raw("contract_years + $extensionYears"),
                'updated_at' => now()
            ]);

        // Record contract extension transaction
        DB::table('transactions')->insert([
            'player_id' => $finalsMVPId,
            'season_id' => $seasonId,
            'details' => "Contract extended by {$extensionYears} year(s) for winning {$awardName}",
            'from_team_id' => $winnerId,
            'to_team_id' => $winnerId,
            'status' => 'extension',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function updatePlayoffAppearancesForGame($gameData)
    {
        $homeId = $gameData->home_team_id;
        $awayId = $gameData->away_team_id;
        $seasonId = $gameData->season_id;
        $round = $gameData->round;

        // Generate a unique series identifier (sorted to ensure consistency)
        $teamIds = [$homeId, $awayId];
        sort($teamIds); // Sort to ensure consistent series ID regardless of home/away order
        $seriesIdentifier = implode('_', [$seasonId, $round, $teamIds[0], $teamIds[1]]);

        // Get unique player IDs from both teams
        $playerIds = DB::table('players')
            ->whereIn('team_id', [$homeId, $awayId])
            ->pluck('id')
            ->unique();

        foreach ($playerIds as $playerId) {
            $this->updatePlayerPlayoffAppearance($playerId, $gameData, $seriesIdentifier);
        }
    }

    private function updatePlayerPlayoffAppearance($playerId, $gameData, $seriesIdentifier)
    {
        if (!$playerId || !$gameData || !$seriesIdentifier) {
            \Log::error("Invalid input: playerId=$playerId, gameData=" . json_encode($gameData) . ", seriesIdentifier=$seriesIdentifier");
            return;
        }

        $seasonId = $gameData->season_id;
        $round = $gameData->round;

        // ✅ Winner team comes directly from schedules (single-elims assumption)
        $winnerTeamId = $gameData->winner_id;

        // Fetch player's team_id
        $playerTeamId = DB::table('players')->where('id', $playerId)->value('team_id');
        if (!$playerTeamId) {
            \Log::error("No team_id found for player_id: $playerId");
            return;
        }

        $roundColumnMap = [
            'play_ins_elims_round_1' => 'play_ins_elims_round_1_appearances',
            'play_ins_elims_round_2' => 'play_ins_elims_round_2_appearances',
            'play_ins_finals' => 'play_ins_finals_appearances',
            'round_of_32' => 'round_of_32_appearances',
            'round_of_16' => 'round_of_16_appearances',
            'quarter_finals' => 'quarter_finals_appearances',
            'semi_finals' => 'semi_finals_appearances',
            'interconference_semi_finals' => 'interconference_semi_finals_appearances',
            'finals' => 'finals_appearances',
        ];

        if (!isset($roundColumnMap[$round])) {
            \Log::warning("Invalid playoff round: $round");
            return; // Not a tracked playoff round
        }

        $columnToIncrement = $roundColumnMap[$round];

        // Use a transaction for database consistency
        DB::transaction(function () use ($playerId, $columnToIncrement, $round, $playerTeamId, $winnerTeamId, $seriesIdentifier, $gameData) {

            // Check if the player has already been credited for this series
            $existingAppearance = DB::table('player_series_appearances')
                ->where('player_id', $playerId)
                ->where('series_identifier', $seriesIdentifier)
                ->exists();

            if ($existingAppearance) {
                \Log::info("Player $playerId already credited for series $seriesIdentifier");
                return; // Skip if appearance already recorded
            }

            // Record the series appearance
            DB::table('player_series_appearances')->insert([
                'player_id' => $playerId,
                'series_identifier' => $seriesIdentifier,
                'season_id' => $gameData->season_id,
                'round' => $round,
                'created_at' => now(),
            ]);

            // Ensure player record exists in player_playoff_appearances
            DB::table('player_playoff_appearances')->updateOrInsert(
                ['player_id' => $playerId],
                []
            );

            // Increment specific round appearance
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->increment($columnToIncrement);

            // Increment total playoff appearances
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->increment('total_playoff_appearances');

            // ✅ Handle championship win in finals (single-elims assumption)
            if ($round === 'finals' && $winnerTeamId && $playerTeamId == $winnerTeamId) {
                \Log::info("Incrementing championships_won for player $playerId, team $playerTeamId won");
                DB::table('player_playoff_appearances')
                    ->where('player_id', $playerId)
                    ->increment('championships_won');
            } else {
                \Log::info("Championship not incremented: round=$round, playerTeamId=$playerTeamId, winnerTeamId=$winnerTeamId");
            }
        });
    }

    public function updatePlayoffSeriesAppearancesForGame($gameData)
    {
        $homeId = $gameData->home_team_id;
        $awayId = $gameData->away_team_id;
        $seasonId = $gameData->season_id;
        $round = $gameData->round;

        // Generate a unique series identifier (sorted to ensure consistency)
        $teamIds = [$homeId, $awayId];
        sort($teamIds); // Sort to ensure consistent series ID regardless of home/away order
        $seriesIdentifier = implode('_', [$seasonId, $round, $teamIds[0], $teamIds[1]]);

        // Get unique player IDs from both teams
        $playerIds = DB::table('players')
            ->whereIn('team_id', [$homeId, $awayId])
            ->pluck('id')
            ->unique();

        foreach ($playerIds as $playerId) {
            $this->updatePlayerPlayoffSeriesAppearance($playerId, $gameData, $seriesIdentifier);
        }
    }

    public function updatePlayerPlayoffSeriesAppearance($playerId, $gameData, $seriesIdentifier)
    {
        if (!$playerId || !$gameData || !$seriesIdentifier) {
            return;
        }

        $seasonId = $gameData->season_id;
        $round = $gameData->round;
        $winnerId = $gameData->winner_id;

        // Fetch player's team_id
        $playerTeamId = DB::table('players')->where('id', $playerId)->value('team_id');
        if (!$playerTeamId) {
            return;
        }

        $roundColumnMap = [
            'play_ins_elims_round_1' => 'play_ins_elims_round_1_appearances',
            'play_ins_elims_round_2' => 'play_ins_elims_round_2_appearances',
            'play_ins_finals' => 'play_ins_finals_appearances',
            'round_of_32' => 'round_of_32_appearances',
            'round_of_16' => 'round_of_16_appearances',
            'quarter_finals' => 'quarter_finals_appearances',
            'semi_finals' => 'semi_finals_appearances',
            'interconference_semi_finals' => 'interconference_semi_finals_appearances',
            'finals' => 'finals_appearances',
        ];

        if (!isset($roundColumnMap[$round])) {
            return; // Not a tracked playoff round
        }

        $columnToIncrement = $roundColumnMap[$round];

        DB::transaction(function () use ($playerId, $columnToIncrement, $round, $playerTeamId, $winnerId, $seriesIdentifier, $gameData) {
            // Check if already recorded
            $existingAppearance = DB::table('player_series_appearances')
                ->where('player_id', $playerId)
                ->where('series_identifier', $seriesIdentifier)
                ->exists();

            // Championship win condition: finals + winner + completed series
            if ($round === 'finals' && $playerTeamId) {
                // Get the series data
                $series = DB::table('playoff_series')
                    ->where('series_id', $gameData->series_id)
                    ->where('status', 2) // finished
                    ->first();

                // Check if the player's team is the series winner
                if ($series && $series->winner_team_id == $playerTeamId) {
                    DB::table('player_playoff_appearances')
                        ->where('player_id', $playerId)
                        ->increment('championships_won');
                }
            }

            if ($existingAppearance) {
                return;
            }

            // Record the series appearance
            DB::table('player_series_appearances')->insert([
                'player_id' => $playerId,
                'series_identifier' => $seriesIdentifier,
                'season_id' => $gameData->season_id,
                'round' => $round,
                'created_at' => now(),
            ]);

            // Ensure record exists in player_playoff_appearances
            DB::table('player_playoff_appearances')->updateOrInsert(
                ['player_id' => $playerId],
                []
            );

            // Increment round appearance
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->increment($columnToIncrement);

            // Increment total playoff appearances
            DB::table('player_playoff_appearances')
                ->where('player_id', $playerId)
                ->increment('total_playoff_appearances');
        });
    }

    public function updateSeriesAndSchedule($gameData, $winnerId)
    {
        // Fetch the series
        $series = DB::table('playoff_series')
            ->where('series_id', $gameData->series_id)
            ->first();

        if (!$series) {
            throw new \Exception("Series not found for series_id: {$gameData->series_id}");
        }

        if ($series->status == 2) {
            return false; // Series already completed, stop here
        }

        // Update wins based on the winner
        $updateData = [
            'home_wins' => $series->home_team_id == $winnerId ? $series->home_wins + 1 : $series->home_wins,
            'away_wins' => $series->away_team_id == $winnerId ? $series->away_wins + 1 : $series->away_wins,
            'updated_at' => Carbon::now(),
            'status' => 1, // Default to "in progress"
        ];

        // Check if the series is completed
        if (
            $updateData['home_wins'] >= $series->best_of ||
            $updateData['away_wins'] >= $series->best_of
        ) {
            $updateData['status'] = 2; // Mark as completed

            if ($updateData['home_wins'] >= $series->best_of) {
                $updateData['winner_team_id'] = $series->home_team_id;
                $updateData['loser_team_id'] = $series->away_team_id;
            } else {
                $updateData['winner_team_id'] = $series->away_team_id;
                $updateData['loser_team_id'] = $series->home_team_id;
            }
        }

        // Update playoff_series table
        DB::table('playoff_series')
            ->where('series_id', $gameData->series_id)
            ->update($updateData);
    }
}
