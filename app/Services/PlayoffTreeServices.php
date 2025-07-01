<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PlayoffTreeService
{
    public static function getPlayoffRoundStructure($start, $type)
    {
        // Define constants if using them
        $rounds = [
            'round_of_16' => 'round_of_16',
            'quarter_finals' => 'quarter_finals',
            'semi_finals' => 'semi_finals',
            'interconference_semi_finals' => 'interconference_semi_finals',
            'finals' => 'finals',
            'play_ins_elims_round_1' => 'play_ins_elims_round_1',
            'play_ins_elims_round_2' => 'play_ins_elims_round_2',
            'play_ins_finals' => 'play_ins_finals',
        ];

        if ($start == 8) {
            return [
                5 => [$rounds['quarter_finals']],
                6 => $type == 2
                    ? [$rounds['semi_finals']]
                    : [$rounds['quarter_finals'], $rounds['semi_finals']],
                7 => $type == 2
                    ? [$rounds['interconference_semi_finals']]
                    : [$rounds['quarter_finals'], $rounds['semi_finals'], $rounds['interconference_semi_finals']],
                8 => [$rounds['quarter_finals'], $rounds['semi_finals'], $rounds['interconference_semi_finals'], $rounds['finals']],
            ];
        }

        if ($start == 16) {
            return [
                1 => [$rounds['play_ins_elims_round_1'], $rounds['play_ins_elims_round_2'], $rounds['play_ins_finals']],
                2 => [$rounds['play_ins_elims_round_1'], $rounds['play_ins_elims_round_2'], $rounds['play_ins_finals']],
                3 => [$rounds['play_ins_elims_round_1'], $rounds['play_ins_elims_round_2'], $rounds['play_ins_finals'], $rounds['round_of_16']],
                4 => [$rounds['play_ins_elims_round_1'], $rounds['play_ins_elims_round_2'], $rounds['play_ins_finals'], $rounds['round_of_16'], $rounds['quarter_finals']],
                5 => [$rounds['play_ins_elims_round_1'], $rounds['play_ins_elims_round_2'], $rounds['play_ins_finals'], $rounds['round_of_16'], $rounds['quarter_finals'], $rounds['semi_finals']],
                6 => [$rounds['play_ins_elims_round_1'], $rounds['play_ins_elims_round_2'], $rounds['play_ins_finals'], $rounds['round_of_16'], $rounds['quarter_finals'], $rounds['semi_finals'], $rounds['finals']],
                7 => [$rounds['play_ins_elims_round_1'], $rounds['play_ins_elims_round_2'], $rounds['play_ins_finals'], $rounds['round_of_16'], $rounds['quarter_finals'], $rounds['semi_finals'], $rounds['interconference_semi_finals'], $rounds['finals']],
                8 => [$rounds['play_ins_elims_round_1'], $rounds['play_ins_elims_round_2'], $rounds['play_ins_finals'], $rounds['round_of_16'], $rounds['quarter_finals'], $rounds['semi_finals'], $rounds['interconference_semi_finals'], $rounds['finals']],
            ];
        }

        return [];
    }

    public static function buildPlayoffTree($seasonId, $status, $type, $start)
    {
        $status = min($status, 8);
        $roundStructure = self::getPlayoffRoundStructure($start, $type);
        $currentRounds = $roundStructure[$status] ?? [];

        $tree = [];

        foreach ($currentRounds as $round) {
            $playoffSchedule = DB::table('schedules')
                ->select('game_id', 'home_id', 'away_id', 'home_score', 'away_score', 'round', 'id')
                ->where('season_id', $seasonId)
                ->where('round', $round)
                ->orderBy('round', 'asc')
                ->get();

            $teamIds = $playoffSchedule->pluck('home_id')
                        ->merge($playoffSchedule->pluck('away_id'))
                        ->unique();

            $standingsData = DB::table('standings_view')
                ->whereIn('team_id', $teamIds)
                ->where('season_id', $seasonId)
                ->get()
                ->keyBy('team_id');

            $tree[$round] = [];

            foreach ($playoffSchedule as $game) {
                $home = $standingsData[$game->home_id] ?? DB::table('teams')->where('id', $game->home_id)->first();
                $away = $standingsData[$game->away_id] ?? DB::table('teams')->where('id', $game->away_id)->first();

                $tree[$round][] = [
                    'id' => $game->id,
                    'game_id' => $game->game_id,
                    'home_team' => [
                        'id' => $game->home_id,
                        'name' => $home->name ?? 'Unknown',
                        'home_score' => $game->home_score,
                        'conference' => $home->conference_name ?? null,
                        'conference_rank' => $home->conference_rank ?? null,
                        'overall_rank' => $home->overall_rank ?? null,
                        'primary_color' => $home->primary_color ?? '000000',
                        'secondary_color' => $home->secondary_color ?? '000000',
                    ],
                    'away_team' => [
                        'id' => $game->away_id,
                        'name' => $away->name ?? 'Unknown',
                        'away_score' => $game->away_score,
                        'conference' => $away->conference_name ?? null,
                        'conference_rank' => $away->conference_rank ?? null,
                        'overall_rank' => $away->overall_rank ?? null,
                        'primary_color' => $away->primary_color ?? '000000',
                        'secondary_color' => $away->secondary_color ?? '000000',
                    ],
                    'winner' => $game->home_score > $game->away_score ? $game->home_id : ($game->home_score < $game->away_score ? $game->away_id : 0),
                    'season_id' => $seasonId
                ];
            }
        }

        return $tree;
    }
}
