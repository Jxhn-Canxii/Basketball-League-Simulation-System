<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TeamChemistryController extends Controller
{
    public function getChemistryCalculation($teamId, $latestSeasonId, $previousSeasonId)
    {
        $retentionRate = $this->calculateRetentionRate($teamId, $latestSeasonId, $previousSeasonId) ?? 0;

        $teamCoach = DB::table('teams')
            ->leftJoin('coaches', 'coaches.team_id', '=', 'teams.id')
            ->select(
                'coaches.experience_years as experience',
                'coaches.coach_iq as iq',
                'coaches.name as coach_name',
                'teams.name as team_name'
            )
            ->where('teams.id', $teamId)
            ->first();

        $teamAvgAge = DB::table('players')
            ->where('team_id', $teamId)
            ->avg('age') ?? 0;

        $avgLeadership = DB::table('players')
            ->where('team_id', $teamId)
            ->avg('leadership_rating') ?? 0;

        $ambitionAvg = DB::table('players')
            ->where('team_id', $teamId)
            ->avg('ambition_rating') ?? 0;
        
        $avgOverallRating = DB::table('players')
            ->where('team_id', $teamId)
            ->avg('overall_rating') ?? 0;

        // Normalize values (0–1 scale)
        $coachExperience = isset($teamCoach->experience)
            ? min(30, $teamCoach->experience)
            : 0;

        $coachIq = isset($teamCoach->iq)
            ? min(100, $teamCoach->iq)
            : 0;

        $teamName = isset($teamCoach->team_name)
            ? $teamCoach->team_name
            : 'none';

        $coachName = isset($teamCoach->coach_name)
            ? $teamCoach->coach_name
            : 'none';

        $normalizedAge = ceil(($teamAvgAge / 30) * 100);

        $normalizedCoachExp = ceil(($coachExperience / 15) * 100);

        // Final chemistry score
        $chemistryScore = (
            ($coachIq * 0.20) + 
            ($ambitionAvg * 0.10) +
            ($avgLeadership * 0.10) + 
            ($avgOverallRating * 0.20) +
            ($retentionRate * 0.20) +
            ($normalizedCoachExp * 0.15) +
            ($normalizedAge * 0.15) 
        );

        // Force range 1–100
        $chemistryScore = max(1, min(100, round($chemistryScore)));

        $chemistryData =  [
            'team_id' => $teamId,
            'team_name' => $teamName,
            'average_age' => ceil($teamAvgAge),
            'normalized_age' => $normalizedAge,
            'coach_name' => $coachName,
            'coach_experience' => round($coachExperience, 2),
            'normalized_coach_experience' => $normalizedCoachExp,
            'coach_iq' => ceil($coachIq),
            'retention_rate' => round($retentionRate, 2),
            'avg_leadership_rating' => ceil($avgLeadership),
            'avg_ambition' => ceil($ambitionAvg),
            'avg_overall_rating' => ceil($avgOverallRating),
            'chemistry_score' => $chemistryScore,
        ];

        return $chemistryData;

    }

    private function calculateRetentionRate($teamId,$latestSeasonId,$previousSeasonId){

        $previousSeasonPlayers = DB::table('player_season_stats')
                    ->where('team_id', $teamId)
                    ->where('season_id', $previousSeasonId)
                    ->pluck('player_id');

        $currentSeasonPlayers = DB::table('player_season_stats')
                    ->where('team_id', $teamId)
                    ->where('season_id', $latestSeasonId)
                    ->pluck('player_id');

        $retainedPlayers = $previousSeasonPlayers->intersect($currentSeasonPlayers)->count();
        $totalPlayers = $previousSeasonPlayers->count();

        if($totalPlayers == 0){
            return 0.0;
        }

        $retentionRate = ($retainedPlayers / $totalPlayers) * 100;

        return $retentionRate;

    }
}
