<?php

namespace App\Services\Team;

use Illuminate\Support\Facades\DB;

class TeamChemistryService
{
    public function getTeamChemistry($seasonId, $teamId)
    {
        return DB::table('team_season_info')
            ->where('season_id', $seasonId)
            ->where('team_id', $teamId)
            ->value('chemistry');
    }

    public function updateSeasonTeamChemistryBeforeGame($teamId)
    {
        $seasonId = get_current_season_id();

        $chemistryRow = DB::table('team_season_info')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->first();

        if (!$chemistryRow) {
            DB::table('team_season_info')->insert([
                'team_id' => $teamId,
                'season_id' => $seasonId,
                'chemistry' => 50, // default
            ]);
            $chemistry = 50;
        } else {
            $chemistry = $chemistryRow->chemistry;
        }

        $team = DB::table('teams')->where('id', $teamId)->first();
        if (!$team) return;

        $coachIQ = $chemistryRow->coach_iq;

        // 🎯 Last game outcome
        $lastGame = DB::table('schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->where('season_id', $seasonId)
            ->where('status', 2)
            ->orderByDesc('id')
            ->first();

        if ($lastGame) {
            $wonLastGame = $lastGame->winner_id === $teamId;
            $chemistry += $wonLastGame ? 2 : -2;
        }

        // 🎯 Coach IQ
        if ($coachIQ >= 90) $chemistry += 1;
        elseif ($coachIQ <= 65) $chemistry -= 1;

        // 🎯 Leadership
        $leaders = DB::table('players')
            ->where('team_id', $teamId)
            ->orderByDesc('leadership_rating')
            ->pluck('leadership_rating');

        if ($leaders->isNotEmpty()) {
            $avgLeadership = $leaders->avg();
            if ($avgLeadership >= 85) $chemistry += 2;
            elseif ($avgLeadership <= 60) $chemistry -= 2;
        }

        // 🎯 Season win percentage
        $seasonGames = DB::table('schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->where('season_id', $seasonId)
            ->where('status', 2)
            ->get();

        $totalGames = $seasonGames->count();
        $wins = $seasonGames->filter(fn($g) => $g->winner_id === $teamId)->count();

        if ($totalGames >= 5) {
            $winRate = $wins / $totalGames;
            if ($winRate >= 0.7) $chemistry += 2;
            elseif ($winRate <= 0.3) $chemistry -= 2;
        }

        // 🎯 Morale
        $moraleAvg = DB::table('players')
            ->where('team_id', $teamId)
            ->avg('morale');

        if (!is_null($moraleAvg)) {
            if ($moraleAvg >= 85) $chemistry += 2;
            elseif ($moraleAvg <= 60) $chemistry -= 2;
        }

        $injuredCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_injured', true) // assuming you track this
            ->count();

        if (!is_null($injuredCount)) {
            if ($injuredCount >= 3) $chemistry -= 3;
            elseif ($injuredCount === 1) $chemistry -= 1;
        }

        // 🧼 Clamp
        $chemistry = max(0, min(100, round($chemistry)));

        // ✅ Update
        DB::table('team_season_info')
            ->updateOrInsert(
                ['team_id' => $teamId, 'season_id' => $seasonId],
                ['chemistry' => $chemistry]
            );

        // $fatigueValue = max(0, min(10, round(100 - $chemistry)));
    }

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

        $previousSeasonPlayers = DB::table('player_season_stats_archives')
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
