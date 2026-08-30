<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TeamStreakController extends Controller
{

    public static function updateTeamStreaks($gameId)
    {
        $game = DB::table('schedules')
            ->where('id', $gameId)
            ->where('status', 2)
            ->orderBy('id', 'asc') // Ensure chronological order
            ->first();

        if (!$game) {
            return;
        }

        $winnerId = $game->winner_id;
        $homeStreak = self::getTeamStreak($game->home_id, $gameId);
        $awayStreak = self::getTeamStreak($game->away_id, $gameId);

        $homeData =  self::formatStreakData($game->home_id, $gameId, $homeStreak);
        $awayData =  self::formatStreakData($game->away_id, $gameId, $awayStreak);

        // return response()->json([
        //     'away' => $awayData,
        //     'home' => $homeData,
        // ]);

        try {
            if($homeData){
                // DB::table('streak')->where('team_id', $game->home_id)->update($homeData);
                DB::table('streak')->updateOrInsert(
                    [
                        'team_id' => $game->home_id,
                    ],
                    $homeData
                );
            }
            if($awayData){
                // DB::table('streak')->where('team_id', $game->away_id)->update($awayData);
                DB::table('streak')->updateOrInsert(
                    [
                        'team_id' => $game->away_id,
                    ],
                    $awayData
                );
            }
        } catch (\Exception $e) {

        }
    }

    private static function formatStreakData($teamId, $gameId, $activeStreak){
    
        $currentStreak = DB::table('streak')
            ->where('team_id', $teamId) // Get records with id less than or equal to game_id
            ->orderBy('id', 'desc') // Assuming game_id is the chronological identifier
            ->first();
        
        if (!$currentStreak) {
            return false;
        }

        $streakStatus = $activeStreak['status'];
        $streakCount = $activeStreak['streak'];
        $streakStart = $activeStreak['streak'];

        $winningStreak = $currentStreak->best_winning_streak;
        $losingStreak = $currentStreak->best_losing_streak;

        if(($streakStatus == 'W' && $streakCount < $winningStreak)){
            return false;
        }

        if(($streakStatus == 'L' && $streakCount < $losingStreak)){
            return false;
        }

        $newWinningStreak = ($streakStatus == 'W' && $streakCount > $winningStreak) ? $streakCount : $winningStreak;
        $newLosingStreak = ($streakStatus == 'L' && $streakCount > $losingStreak) ? $streakCount : $losingStreak;

        $winningStreakStartId = ($streakStatus == 'W' && $streakCount > $winningStreak) ? $gameId : $currentStreak->best_winning_streak_start_id;
        $winningStreakEndId = 0;

        $losingStreakStartId = ($streakStatus == 'L' && $streakCount > $losingStreak) ? $gameId : $currentStreak->best_losing_streak_start_id;
        $losingStreakEndId = 0;

        return [
            'best_winning_streak' => $newWinningStreak,
            'best_losing_streak' => $newLosingStreak,
            'best_winning_streak_start_id' => $winningStreakStartId,
            'best_winning_streak_end_id' =>  $winningStreakEndId,
            'best_losing_streak_start_id' => $losingStreakStartId,
            'best_losing_streak_end_id' =>  $losingStreakEndId,
            'created_at' => $streakStatus,
            'updated_at' => now(),
        ];
    }

    private static function getTeamStreak($teamId, $game_id)
    {
        $currentStreak = DB::table('streak')
            ->where('team_id', $teamId) // Get records with id less than or equal to game_id
            ->orderBy('id', 'desc') // Assuming game_id is the chronological identifier
            ->first();
        
        if (!$currentStreak) {
            return;
        }

        $winningStreak = $currentStreak->best_winning_streak;
        $losingStreak = $currentStreak->best_losing_streak;

        $searchingLimit = ($winningStreak >= $losingStreak) ? $winningStreak + 1 :  $losingStreak + 1;
        // Query to calculate the team's current winning or losing streak
        $streak = DB::table('schedule_view')
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->where('status', 2)
            ->where('id', '<=', $game_id) // Get records with id less than or equal to game_id
            ->limit($searchingLimit)
            ->orderBy('id', 'desc') // Assuming game_id is the chronological identifier
            ->get();

        // Logic to determine streak type (winning or losing)
        $currentStreak = 0;
        $isWinningStreak = null;

        foreach ($streak as $game) {
            // Get the scores for the team and the opponent
            $teamScore = $game->home_id == $teamId ? $game->home_score : $game->away_score;
            $opponentScore = $game->home_id == $teamId ? $game->away_score : $game->home_score;

            // Determine win or loss
            if ($teamScore > $opponentScore) {
                // If it's a win
                if ($isWinningStreak === false) {
                    break; // Break if streak direction changes
                }
                $isWinningStreak = true; // Set streak type to winning
                $currentStreak++; // Increment the winning streak
            } else {
                // If it's a loss
                if ($isWinningStreak === true) {
                    break; // Break if streak direction changes
                }
                $isWinningStreak = false; // Set streak type to losing
                $currentStreak++; // Increment the losing streak
            }
        }

        // Determine the current streak output
        $streakResult = $currentStreak > 0 ? ($isWinningStreak ? 'W' : 'L') : 'N0';

        $firstRow = $streak->first();
        $lastRow = $streak->last();
        $startGameId = $isWinningStreak ? $firstRow->id : $lastRow->id;

        return [
            'status' => $streakResult,
            'streak' => $currentStreak,
            'start' => $startGameId,
        ];
    }

    public function insertTeamStreak(){

        $activeTeams = DB::table('teams')
            ->select('teams.id', 'teams.name')
            ->groupBy('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();
        
        foreach($activeTeams as $team){

            $data = [
                'best_winning_streak' => 0,
                'best_winning_streak_start_id' => 0,
                'best_winning_streak_end_id' => 0,
                'best_losing_streak' => 0,
                'best_losing_streak_start_id' => 0,
                'best_losing_streak_end_id' => 0,
            ];

            DB::table('streak')->updateOrInsert(
                [
                    'team_id' => $team->id,
                ],
                $data
            );
        }

    }

    public function newTeamStreak($teamId){

            $streakExists = DB::table('streak')
                ->where('team_id', $teamId)
                ->exists();

            if($streakExists) return true;

            $data = [
                'team_id' => $teamId,
                'best_winning_streak' => 0,
                'best_winning_streak_start_id' => 0,
                'best_winning_streak_end_id' => 0,
                'best_losing_streak' => 0,
                'best_losing_streak_start_id' => 0,
                'best_losing_streak_end_id' => 0,
            ];

            DB::table('streak')->insert($data);
    }
}
