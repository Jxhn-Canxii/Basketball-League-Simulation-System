<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use Illuminate\Http\Request;
use App\Http\Controllers\HelperController;
use Illuminate\Support\Facades\DB;

class StandingsController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperController();
    }
   
    public function seasonStandings(Request $request)
    {
        // Retrieve the season_id and conference_id from the request
        $seasonId = $request->season_id;
        $conferenceId = $request->conference_id;

        $latestSeasonId = get_current_season_id();

        // Determine the table to query based on season_id
        $table = ($seasonId == $latestSeasonId) ? 'standings_view' : 'standings_snapshots';

        $playerSeasonStatsDBName = $this->helper->getSeasonStatsDBName($seasonId);

        // Fetch the conference name from the conferences table
        $conference = DB::table('conferences')
            ->where('id', $conferenceId)
            ->first();

        // Fetch standings filtered by season_id and conference_id
        // $standings = DB::table($table)
        //     ->where('season_id', $seasonId)
        //     ->where('conference_id', $conferenceId)
        //     ->orderByDesc('wins') // Order by wins in descending order
        //     ->orderBy('conference_rank', 'asc') // If wins are tied, order by conference_rank ascending
        //     ->get();

        $standings = DB::table($table . ' as s')
            ->join('team_reputation_view as trv', function ($join) use ($seasonId) {
                $join->on('s.team_id', '=', 'trv.team_id');
            })
            ->where('s.season_id', $seasonId)
            ->where('s.conference_id', $conferenceId)
            ->orderByDesc('s.wins')
            ->orderBy('s.conference_rank', 'asc')
            ->select([
                's.*', // All columns from standings
                'trv.reputation_score',
                'trv.estimated_fans',
                'trv.streak_status',
                'trv.chemistry',
                'trv.wins_diff',
                'trv.rank_improvement',
                'trv.chemistry_diff',
                'trv.prev_wins',
                'trv.prev_rank',
                'trv.prev_chemistry'
            ])
            ->get();

        // Get all-time conference_rank = 1 and overall_rank = 1 counts per team
        $rankCounts = DB::table('standings_snapshots')
            ->where('conference_id', $conferenceId)
            ->select('team_id')
            ->selectRaw('SUM(conference_rank = 1) as conference_rank_1_count')
            ->selectRaw('SUM(conference_rank <= 3) as conference_top_3_count')
            ->selectRaw('SUM(overall_rank = 1) as overall_rank_1_count')
            ->groupBy('team_id')
            ->get()
            ->keyBy('team_id')
            ->toArray();

        // Add the all-time conference_rank = 1 and overall_rank = 1 counts to each team's standings
        $standings = $standings->map(function ($team) use ($rankCounts, $seasonId, $playerSeasonStatsDBName) {
            $team->conference_rank_count   = $rankCounts[$team->team_id]->conference_rank_1_count ?? 0;
            $team->conference_top_3_count  = $rankCounts[$team->team_id]->conference_top_3_count ?? 0;
            $team->overall_rank_count      = $rankCounts[$team->team_id]->overall_rank_1_count ?? 0;

             // Get rookies + stats for this team
            $rookies = DB::table('players as p')
                ->join($playerSeasonStatsDBName.' as ps', 'p.id', '=', 'ps.player_id')
                ->where('ps.team_id', $team->team_id)
                ->where('p.draft_id', $seasonId) // Only rookies drafted in this season
                ->where('ps.season_id', $seasonId)
                ->select('p.is_injured','p.name', 'ps.avg_points_per_game', 'ps.avg_assists_per_game', 'ps.avg_rebounds_per_game')
                ->orderByDesc('ps.eff')
                ->get();

            $topPlayers = DB::table('players as p')
                ->join($playerSeasonStatsDBName.' as ps', 'p.id', '=', 'ps.player_id')
                ->where('ps.team_id', $team->team_id)
                ->where('ps.season_id', $seasonId)
                ->where('ps.avg_points_per_game','>=',10)
                ->select('p.is_injured','p.name', 'ps.avg_points_per_game', 'ps.avg_assists_per_game', 'ps.avg_rebounds_per_game')
                ->orderByDesc('ps.eff')
                ->limit(3)
                ->get();
            
            $injuredPlayers = DB::table('players as p')
                ->join($playerSeasonStatsDBName.' as ps', 'p.id', '=', 'ps.player_id')
                ->where('ps.team_id', $team->team_id)
                ->where('ps.season_id', $seasonId)
                ->where('p.is_injured', true)
                ->select('p.is_injured','p.injury_type','p.injury_recovery_games','p.name', 'ps.avg_points_per_game', 'ps.avg_assists_per_game', 'ps.avg_rebounds_per_game')
                ->orderByDesc('ps.eff')
                ->get();

            $newPlayers = DB::table('players as p')
                ->join('player_season_stats_archives as ps', 'p.id', '=', 'ps.player_id')
                ->where('ps.team_id', $team->team_id) // Filter by the team
                ->where('p.team_id', $team->team_id) // Filter by the team
                ->where('ps.season_id', $seasonId) // Filter by the current season
                ->whereNotExists(function($query) use ($team, $seasonId) {
                    // Ensure the player hasn't played for this team in any previous season
                    $query->selectRaw(1)
                        ->from('player_season_stats_archives as ps2')
                        ->whereColumn('ps2.player_id', 'ps.player_id')
                        ->where('ps2.team_id', $team->team_id)
                        ->where('ps2.season_id', '<', $seasonId); // Check for previous seasons
                })
                ->groupBy('p.id') // Group by player
                ->havingRaw('COUNT(ps.id) = 1') // Ensure only players with 1 stat entry for this season and team
                ->distinct() // Ensure distinct players
                ->select('p.is_injured','p.name', 'ps.avg_points_per_game', 'ps.avg_assists_per_game', 'ps.avg_rebounds_per_game')
                ->orderByDesc('ps.eff') // Order by player efficiency
                ->get();

            // Format rookies as "Name (23.4ppg, 3.1apg, 5.2rpg)"
            $team->rookies = $rookies->map(function ($r) {
                $isInjured =  $r->is_injured ? 'x' : '';

                return sprintf(
                    "%s (%.1fppg, %.1fapg, %.1frpg) %s",
                    $r->name,
                    $r->avg_points_per_game,
                    $r->avg_assists_per_game,
                    $r->avg_rebounds_per_game,
                    $isInjured,
                );
            })->implode('%%');
            
            $team->new_players = $newPlayers->map(function ($r) {
                $isInjured =  $r->is_injured ? 'x' : '';
                
                return sprintf(
                    "%s (%.1fppg, %.1fapg, %.1frpg) %s",
                    $r->name,
                    $r->avg_points_per_game,
                    $r->avg_assists_per_game,
                    $r->avg_rebounds_per_game,
                    $isInjured,
                );
            })->implode('%%');

            $team->top_players = $topPlayers->map(function ($r) {
                $isInjured =  $r->is_injured ? 'x' : '';

                return sprintf(
                    "%s (%.1fppg, %.1fapg, %.1frpg) %s",
                    $r->name,
                    $r->avg_points_per_game,
                    $r->avg_assists_per_game,
                    $r->avg_rebounds_per_game,
                    $isInjured,
                );
            })->implode('%%');

            $team->injured_players = $injuredPlayers->map(function ($r) {
                $injuryType = str_replace('_',' ',$r->injury_type);

                return sprintf(
                    "%s - %s | eta %s days",
                    $r->name,
                    $injuryType,
                    $r->injury_recovery_games,
                );
            })->implode('%%');

            // Format best player as "Name (23.4ppg, 3.1apg, 5.2rpg)"
            // $team->best_player = $bestPlayer ? sprintf(
            //     "%s (%.1fppg, %.1fapg, %.1frpg)",
            //     $bestPlayer->name,
            //     $bestPlayer->avg_points_per_game,
            //     $bestPlayer->avg_assists_per_game,
            //     $bestPlayer->avg_rebounds_per_game
            // ) : null;

            return $team;
        });


        $latestNews = DB::table('game_news')
            ->select('id', 'game_id', 'season_id', 'round', 'title', 'content', 'created_at', 'updated_at')
            ->where('season_id', $seasonId)
            ->orderBy('id', 'desc')
            ->first();

        $isRoundsSimulatedForSeason = $this->helper->isRoundSimulated($seasonId,  0);

        // Return the standings along with the conference name
        return response()->json([
            'standings' => $standings,
            'conference_name' => $conference ? $conference->name : 'N/A', // Check if conference exists
            'latest_news' => $latestNews,
            'is_round_simulated' => false,
        ]);
    }
}
