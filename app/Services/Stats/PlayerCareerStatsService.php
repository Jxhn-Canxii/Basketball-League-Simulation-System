<?php

namespace App\Services\Stats;

use App\Models\PlayerGameStats;
use App\Models\Player;
use App\Services\Helper\HelperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PlayerCareerStatsService
{
    protected $helperService;
    
    public function __construct()
    {
        $this->helperService = new HelperService();
    }

    public function recordPlayerCareerHigh($playerGameStats, $gameData)
    {
        if (empty($playerGameStats)) {
            throw new \Exception("Player game stats are empty. Cannot update season stats.");
        }

        try {
            DB::beginTransaction();

            foreach ($playerGameStats as $stats) {

                $careerHighs = DB::table('player_game_highs')
                    ->where('player_id', $stats['player_id'])
                    ->first();

                $min = number_format($careerHighs->minutes ?? 0,'1');
                $points = $careerHighs->points ?? 0;
                $reb = $careerHighs->rebounds ?? 0;
                $ast = $careerHighs->assists ?? 0;
                $stl = $careerHighs->steals ?? 0;
                $blk = $careerHighs->blocks ?? 0;
                $to = $careerHighs->turnovers ?? 0;
                $fga = $careerHighs->field_goal_attempts ?? 0;
                $fgm = $careerHighs->field_goals_made ?? 0;
                $threepa = $careerHighs->three_point_attempts ?? 0;
                $threepm = $careerHighs->three_pointers_made ?? 0;
                $fta = $careerHighs->free_throw_attempts ?? 0;
                $ftm = $careerHighs->free_throws_made ?? 0;
                $twopa = $careerHighs->two_point_attempts ?? 0;
                $twopm = $careerHighs->two_pointers_made ?? 0;

                $careerHighData = [
                    'minutes' => $this->compareStats($stats,$gameData, $min, $stats['minutes'], 'minutes'),
                    'points' => $this->compareStats($stats,$gameData, $points, $stats['points'], 'points'),
                    'assists' => $this->compareStats($stats,$gameData, $ast, $stats['assists'], 'assists'),
                    'rebounds' => $this->compareStats($stats,$gameData, $reb, $stats['rebounds'], 'rebounds'),
                    'steals' => $this->compareStats($stats,$gameData, $stl, $stats['steals'], 'steals'),
                    'blocks' => $this->compareStats($stats,$gameData, $blk, $stats['blocks'], 'blocks'),
                    'turnovers' => $this->compareStats($stats,$gameData, $to, $stats['turnovers'], 'turnovers'),
                    'field_goal_attempts' => $this->compareStats($stats,$gameData, $fga, $stats['field_goal_attempts'], 'field_goal_attempts'),
                    'field_goals_made' => $this->compareStats($stats,$gameData, $fgm, $stats['field_goals_made'], 'field_goals_made'),
                    'three_point_attempts' => $this->compareStats($stats,$gameData, $threepa, $stats['three_point_attempts'], 'three_point_attempts'),
                    'three_pointers_made' => $this->compareStats($stats,$gameData, $threepm, $stats['three_pointers_made'], 'three_pointers_made'),
                    'free_throw_attempts' => $this->compareStats($stats,$gameData, $fta, $stats['free_throw_attempts'], 'free_throw_attempts'),
                    'free_throws_made' => $this->compareStats($stats,$gameData, $ftm, $stats['free_throws_made'], 'free_throws_made'),
                    'two_point_attempts' => $this->compareStats($stats,$gameData, $twopa, $stats['two_point_attempts'], 'two_point_attempts'),
                    'two_pointers_made' => $this->compareStats($stats,$gameData, $twopm, $stats['two_pointers_made'], 'two_pointers_made')
                ];

                // dd($careerHighData);

                DB::table('player_game_highs')->updateOrInsert(
                    [
                        'player_id' => $stats['player_id'],
                    ],
                    $careerHighData
                );

                DB::commit();

            }
        } catch (\Exception $e) {
            DB::rollBack();

            // Log error for debugging
            // Log::error("Error updating season stats: " . $e->getMessage());

            // Optionally, throw the error again to stop execution
            throw new \Exception("Failed to update career highs. Please check logs." . $e->getMessage());
        }
    }

    private function compareStats($stats,$gameData, $oldStats, $newStats, $type)
    {
        // dd($stats['minutes']  .'-'. $newStats);

        if($stats['minutes'] == 0){
            return $oldStats;
        }

        if ($newStats >= $oldStats && $newStats > 0) {
            $opponentTeamId = $stats['team_id'] != $gameData->away_team_id ? $gameData->away_team_id : $gameData->home_team_id;
            $opponentTeamName = $stats['team_id'] != $gameData->away_team_id ? $gameData->away_team_name : $gameData->home_team_name;
            $round = $this->helperService->roundFormatter($gameData->round);

            $details = $newStats == $oldStats && $oldStats != 0 ? 
                'Has matched his career high in ' . $type. '. ('.$newStats.')' : 
                'Has a career high in ' . $type.'. ('.$newStats.')';

            $details .= ' vs '.$opponentTeamName. ' in round #'.$round;

            DB::table('career_highlights')
                ->where('player_id', $stats['player_id'])
                ->where('type', $type)
                ->update(['status' => 'broken']);
            
            DB::table('career_highlights')->insert([
                'player_id' => $stats['player_id'],
                'season_id' => $stats['season_id'],
                'details' => $details,
                'game_id' => $stats['game_id'],
                'team_id' => $stats['team_id'],
                'vs_team_id' => $opponentTeamId,
                'value' => $newStats,
                'type' => $type,
                'status' => 'career-high',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $newStats;
        }

        return $oldStats;
    }
}
