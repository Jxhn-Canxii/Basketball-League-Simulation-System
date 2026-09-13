<?php

namespace App\Services\Player;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;
use App\Services\League\RoundService;

class InjuryService
{
   protected $helper;
   protected $roundService;

    public function __construct()
    {
        $this->helper = new HelperService();
        $this->roundService = new RoundService();
    }

     public function getPlayerInjuryHistory($request)
    {
        $player_id = $request->input('player_id');

        if (!$player_id) {
            return response()->json(['error' => 'Player ID is required'], 400);
        }

        $injuryHistory = DB::table('injured_players_view as ipv')
            ->join('schedules_archives as s', 'ipv.game_id', '=', 's.game_id')
            ->join('teams as th', 's.home_id', '=', 'th.id')   // home team
            ->join('teams as ta', 's.away_id', '=', 'ta.id')   // away team
            ->join('teams as ti', 'ti.id', '=', 'ipv.team_id')   // team when injured
            ->where('ipv.player_id', $player_id)
            ->orderByDesc('ipv.game_id')
            ->select(
                'ipv.*',
                's.season_id',
                's.conference_id',
                's.round',
                's.series_id',
                's.home_id',
                'th.name as home_team',
                's.home_score',
                's.away_id',
                'ta.name as away_team',
                's.away_score',
                's.winner_id',
                's.status as game_status',
                's.game_number',
                'ti.primary_color',
                'ti.secondary_color'
            )
            ->get();

        $injuryLatestHistory = DB::table('injured_players_view as ipv')
            ->join('schedules as s', 'ipv.game_id', '=', 's.game_id')
            ->join('teams as th', 's.home_id', '=', 'th.id')   // home team
            ->join('teams as ta', 's.away_id', '=', 'ta.id')   // away team
            ->join('teams as ti', 'ti.id', '=', 'ipv.team_id')   // team when injured
            ->where('ipv.player_id', $player_id)
            ->orderByDesc('ipv.game_id')
            ->select(
                'ipv.*',
                's.season_id',
                's.conference_id',
                's.round',
                's.series_id',
                's.home_id',
                'th.name as home_team',
                's.home_score',
                's.away_id',
                'ta.name as away_team',
                's.away_score',
                's.winner_id',
                's.status as game_status',
                's.game_number',
                'ti.primary_color',
                'ti.secondary_color'
            )
            ->get();

        if ($injuryHistory->isEmpty() && $injuryLatestHistory->isEmpty()) {
            return response()->json(['message' => 'No injury history found for this player.'], 404);
        }

        // $allInjuryRecord = [];
        // Map results and add details using roundFormat()
        $injuryHistory[] = $injuryHistory->map(function ($item) {
            $roundLabel = $this->roundService->formatRound($item->round, $item->game_number);

            if ($item->team_id == $item->home_id) {
                $item->details = "Injury started in Season {$item->season_id}, {$roundLabel} vs {$item->away_team}";
            } else {
                $item->details = "Injury started in Season {$item->season_id}, {$roundLabel} vs {$item->home_team}";
            }

            return $item;
        });

        $injuryHistory = $injuryLatestHistory->map(function ($item) {
            $roundLabel = $this->roundService->formatRound($item->round, $item->game_number);

            if ($item->team_id == $item->home_id) {
                $item->details = "Injury started in Season {$item->season_id}, {$roundLabel} vs {$item->away_team}";
            } else {
                $item->details = "Injury started in Season {$item->season_id}, {$roundLabel} vs {$item->home_team}";
            }

            return $item;
        });

        return response()->json([
            'data' => $injuryHistory
        ]);
    }
   
}
