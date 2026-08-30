<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 1200); // 300 seconds = 5 minutes

use Illuminate\Support\Facades\DB;
use App\Models\Teams;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RecordsController extends Controller
{
    public function index()
    {
        return Inertia::render('Records/Index', [
            'status' => session('status'),
        ]);
    }
    public function champions(Request $request)
    {
        // Retrieve pagination parameters from the request
        $perPage = $request->input('per_page', 10); // Default per page to 10 if not provided
        $page = $request->input('page_num', 1); // Default page to 1 if not provided

        $offset = ($page - 1) * $perPage;

        // Query to count championships, runner-ups, and last finals appearance of each team
        $teamStatsQuery = DB::table('teams')
            ->select(
                'teams.id',
                'teams.name',
                'teams.acronym',
                'teams.primary_color',
                'teams.secondary_color',
                'conferences.name as conference_name', // Add conference name to select
                DB::raw('COUNT(DISTINCT CASE WHEN seasons.finals_winner_id = teams.id THEN seasons.id END) AS championships'),
                DB::raw('COUNT(DISTINCT CASE WHEN seasons.finals_loser_id = teams.id THEN seasons.id END) AS runnerups'),
                DB::raw('MAX(CASE WHEN seasons.finals_winner_id = teams.id OR seasons.finals_loser_id = teams.id THEN seasons.name ELSE NULL END) AS last_finals_appearance')
            )
            ->leftJoin('seasons', function ($join) {
                $join->on('teams.id', '=', 'seasons.finals_winner_id')
                    ->orWhere('teams.id', '=', 'seasons.finals_loser_id');
            })
            ->leftJoin('conferences', 'teams.conference_id', '=', 'conferences.id') // Join conferences table
            ->groupBy('teams.id', 'teams.name', 'teams.acronym', 'teams.primary_color', 'teams.secondary_color', 'conferences.name') // Group by team columns and conference name
            ->havingRaw('COALESCE(championships, 0) > 0 OR COALESCE(runnerups, 0) > 0'); // Filter teams with at least one championship or runner-up


        // Count total number of records
        $totalCount = $teamStatsQuery->get()->count();

        // Fetch paginated team statistics
        $teamStats = $teamStatsQuery->orderByDesc('championships')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        // Cache the response
        $response = [
            'data' => $teamStats,
            'current_page' => $page,
            'total_pages' => ceil($totalCount / $perPage),
            'total' => $totalCount,
        ];

        // Return the response
        return response()->json($response);
    }

    public function recent(Request $request)
    {
        // Query to fetch the recent entries from schedule_view where either home_score or away_score is greater than 0
        $recentSchedule = DB::table('schedule_view')
            ->where('home_score', '>', 0)
            ->orWhere('away_score', '>', 0)
            ->orderBy('id', 'desc') // Order by id in descending order to get the latest entries first
            ->take(12) // Retrieve only the latest 10 entries
            ->get();

        return response()->json([
            'data' => $recentSchedule,
        ]);
    }
    public function getRivalries()
    {
        $results = DB::table('head_to_head')
            ->join('teams as team', 'team.id', '=', 'head_to_head.team_id') // Join to get team's name
            ->join('teams as opponent', 'opponent.id', '=', 'head_to_head.opponent_id') // Join to get opponent's name
            ->select(
                'head_to_head.team_id',
                'team.name as team_name', // Fetch team name
                'head_to_head.opponent_id',
                'opponent.name as opponent_name', // Fetch opponent name
                'head_to_head.wins',
                'head_to_head.losses',
                DB::raw('(head_to_head.wins + head_to_head.losses) as total_games') // Calculate total games played
            )
            ->orderByDesc('total_games') // Sort by total games (wins + losses) in descending order
            ->limit(10) // Get only the top 5 records
            ->get();

        $records = [];

        foreach ($results as $record) {
            $records[] = [
                'team_id' => $record->team_id,
                'team_name' => $record->team_name, // Include team name
                'opponent_id' => $record->opponent_id,
                'opponent_name' => $record->opponent_name, // Include opponent name
                'wins' => $record->wins,
                'losses' => $record->losses,
                'total_games' => $record->total_games, // Show total games played
                'home_id' => $record->team_name,
                'away_id' => $record->opponent_name,
            ];
        }
        return response()->json([
            'data' => $records,
        ]);
    }
    public function playoffAppearances()
    {
        $teams = DB::table('schedules')
            ->select(
                'teams.name as team_name',
                'conferences.name as conference_name',
                DB::raw('COUNT(DISTINCT CONCAT(schedules.season_id, schedules.round)) as playoff_appearances')
            )
            ->join('teams', function ($join) {
                $join->on('schedules.home_id', '=', 'teams.id')
                    ->orOn('schedules.away_id', '=', 'teams.id');
            })
            ->join('conferences', 'teams.conference_id', '=', 'conferences.id')
            ->whereIn('schedules.round', config('playoffs'))
            ->groupBy('teams.name', 'conferences.name')
            ->orderBy('playoff_appearances', 'desc')
            ->limit(16)
            ->get();

        return response()->json([
            'data' => $teams,
        ]);
    }

    public function topScorerTeams(Request $request)
    {
        // Extracting per_page, current_page, and sort_by from request
        $perPage = $request->input('per_page', 10); // Default per page to 10 if not provided
        $page = $request->input('page_num', 1); // Default page to 1 if not provided
        $sortBy = $request->input('sort_by', 'total_points'); // Default sort by total points if not provided

        // Valid sort fields to prevent SQL injection
        $validSortFields = [
            'total_points',
            'total_rebounds',
            'total_assists',
            'total_steals',
            'total_blocks',
            'total_turnovers',
            'total_fouls',
        ];

        // Ensure sort_by is valid
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'total_points';
        }

        // Calculating the offset to skip records
        $offset = ($page - 1) * $perPage;

        // Query to fetch teams and aggregate player stats per team
        $scoreAlltime = DB::table('player_season_stats_archives as player_season_stats')
            ->select(
                'teams.name',
                'teams.primary_color',
                'teams.secondary_color',
                'conferences.name as conference',
                DB::raw('SUM(player_season_stats.total_points) as total_points'),
                DB::raw('SUM(player_season_stats.total_rebounds) as total_rebounds'),
                DB::raw('SUM(player_season_stats.total_assists) as total_assists'),
                DB::raw('SUM(player_season_stats.total_steals) as total_steals'),
                DB::raw('SUM(player_season_stats.total_blocks) as total_blocks'),
                DB::raw('SUM(player_season_stats.total_turnovers) as total_turnovers'),
                DB::raw('SUM(player_season_stats.total_fouls) as total_fouls')
            )
            ->leftJoin('players', 'player_season_stats.player_id', '=', 'players.id')
            ->leftJoin('teams', 'player_season_stats.team_id', '=', 'teams.id')
            ->leftJoin('conferences', 'teams.conference_id', '=', 'conferences.id')
            ->groupBy('teams.id', 'teams.name', 'teams.primary_color', 'teams.secondary_color', 'conferences.name')
            ->orderBy($sortBy, 'desc') // Sort dynamically based on the requested stat
            ->skip($offset)
            ->take($perPage)
            ->get();

        // Count total records
        $totalCount = DB::table('teams')->count();

        // Calculate total pages
        $totalPages = ceil($totalCount / $perPage);

        // Create the response array
        $response = [
            'data' => $scoreAlltime,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $totalCount,
        ];

        return response()->json($response);
    }

    public function statsLeaders(Request $request)
    {
        // Extract parameters from request with default values
        $perPage = max(1, (int) $request->input('itemsperpage', 10)); // Default items per page to 10
        $page = max(1, (int) $request->input('page_num', 1)); // Default page number to 1
        $sortBy = $request->input('sort_by', 'total_points'); // Default sort column to 'total_points'

        // Ensure valid sort column
        $validSortColumns = [
            'total_points',
            'total_rebounds',
            'total_assists',
            'total_steals',
            'total_blocks',
            'total_turnovers',
            'total_fouls'
        ];
        if (!in_array($sortBy, $validSortColumns)) {
            return response()->json([
                'error' => 'Invalid sort_by parameter. Valid options are: ' . implode(', ', $validSortColumns)
            ], 400);
        }

        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;

        // Query to fetch statistics from player_season_stats
        $statsAlltime = DB::table('player_season_stats_archives')
            ->select(
                'players.id as player_id',
                'players.name as player_name',
                'players.is_active as is_active',
                'teams.secondary_color',
                'teams.primary_color',
                'teams.name as team_name',
                DB::raw("SUM(player_season_stats_archives.$sortBy) as total_stat") // Aggregate the chosen stat for each player
            )
            ->leftJoin('players', 'player_season_stats_archives.player_id', '=', 'players.id') // Join players
            ->leftJoin('teams', 'teams.id', '=', 'players.team_id') // Join players
            ->groupBy('players.id', 'players.name', 'teams.name')                                  // Group by player_id and player_name
            ->orderBy('total_stat', 'desc')                                          // Order by total_stat in descending order
            ->skip($offset)                                                          // Offset for pagination
            ->take($perPage)                                                         // Limit results per page
            ->get()
            ->map(function ($item, $index) use ($offset) {
                // Add rank to each item based on the pagination offset
                $item->rank = $offset + $index + 1;
                return $item;
            });



        // Count total unique players with stats
        $totalCount = DB::table('player_season_stats_archives')
            ->select('player_id')
            ->distinct()
            ->count();

        // Calculate total pages
        $totalPages = ceil($totalCount / $perPage);

        // Create the response array
        $response = [
            'data' => $statsAlltime,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $totalCount,
            'sort_by' => $sortBy,
        ];

        return response()->json($response);
    }

    public function winningestTeams(Request $request)
    {
        // Extracting per_page and current_page from request
        $perPage = $request->input('per_page', 10); // Default per page to 10 if not provided
        $page = $request->input('page_num', 1); // Default page to 1 if not provided

        // Calculating the offset to skip records
        $offset = ($page - 1) * $perPage;

        // Query to fetch team statistics
        $teamsStats = DB::table('standings_snapshots')
            ->select(
                'teams.id as team_id',
                'teams.name',
                'teams.primary_color',
                'teams.secondary_color',
                'conferences.name as conference',
                DB::raw('SUM(wins) as total_wins'),
                DB::raw('SUM(losses) as total_losses'),
                DB::raw('IFNULL((SUM(wins) / NULLIF(SUM(wins) + SUM(losses), 0)) * 100, 0) as win_rate')
            )
            ->leftJoin('teams', 'standings_snapshots.team_id', '=', 'teams.id')
            ->leftJoin('conferences', 'teams.conference_id', '=', 'conferences.id')
            ->groupBy('teams.id', 'teams.name', 'conferences.name', 'teams.primary_color', 'teams.secondary_color')
            ->orderBy('total_wins', 'desc') // Sort by total wins in descending order
            ->skip($offset)
            ->take($perPage)
            ->get();

        // Count total records (number of distinct teams in standings_snapshots)
        $totalCount = DB::table('standings_snapshots')
            ->distinct('team_id')
            ->count('team_id');

        // Calculate total pages
        $totalPages = ceil($totalCount / $perPage);

        // Query to get best and worst seasons for each team (commented out but updated)
        // $teamSeasons = DB::table('standings_snapshots')
        //     ->select(
        //         'standings_snapshots.team_id',
        //         'standings_snapshots.season_id',
        //         'seasons.name as season_name',
        //         DB::raw('SUM(wins) as total_wins'),
        //         DB::raw('SUM(losses) as total_losses'),
        //         DB::raw('IFNULL((SUM(wins) / NULLIF(SUM(wins) + SUM(losses), 0)) * 100, 0) as win_rate')
        //     )
        //     ->leftJoin('seasons', 'standings_snapshots.season_id', '=', 'seasons.id')
        //     ->where('seasons.status', 8)
        //     ->groupBy('standings_snapshots.team_id', 'standings_snapshots.season_id', 'seasons.name')
        //     ->orderBy('standings_snapshots.team_id', 'asc');

        // // Determine best and worst seasons per team
        // $bestSeasons = $teamSeasons->clone()->orderBy('win_rate', 'desc')->get()->groupBy('team_id')->map(function ($seasons) {
        //     return $seasons->first(); // Best winning season (highest percentage) for each team
        // });

        // $worstSeasons = $teamSeasons->clone()->orderBy('win_rate', 'asc')->get()->groupBy('team_id')->map(function ($seasons) {
        //     return $seasons->first(); // Worst winning season (lowest percentage) for each team
        // });

        // // Combine team stats with best and worst seasons
        // $teamsWithSeasons = $teamsStats->map(function ($team) use ($bestSeasons, $worstSeasons) {
        //     $bestSeason = $bestSeasons->get($team->team_id);
        //     $worstSeason = $worstSeasons->get($team->team_id);

        //     return [
        //         'team_name' => $team->name,
        //         'conference' => $team->conference,
        //         'total_wins' => $team->total_wins,
        //         'total_losses' => $team->total_losses,
        //         'win_rate' => $team->win_rate,
        //         'best_season' => $bestSeason ? $bestSeason->season_name : 'N/A',
        //         'best_win_loss' => $bestSeason ? $bestSeason->total_wins . "-" . $bestSeason->total_losses : 'N/A',
        //         'worst_season' => $worstSeason ? $worstSeason->season_name : 'N/A',
        //         'worst_win_loss' => $worstSeason ? $worstSeason->total_wins . "-" . $worstSeason->total_losses : 'N/A',
        //     ];
        // });

        // Create the response array
        $response = [
            'data' => $teamsStats, // Use $teamsWithSeasons if uncommenting the best/worst seasons logic
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $totalCount,
        ];

        return response()->json($response);
    }

    public function updatePlayerPlayoffAppearances(Request $request)
    {
        $playerData = DB::table('players AS p')
            ->leftJoin('player_series_appearances AS psa', 'p.id', '=', 'psa.player_id')
            ->leftJoin('seasons AS ss', 'ss.id', '=', 'psa.season_id')
            ->leftJoin('player_season_stats AS pss', 'pss.player_id', '=', 'p.id')
            ->leftJoin('teams AS t', 'pss.team_id', '=', 't.id')
            ->whereIn('psa.round', [
                'play_ins_elims_round_1',
                'play_ins_elims_round_2',
                'play_ins_finals',
                'round_of_32',
                'round_of_16',
                'quarter_finals',
                'semi_finals',
                'interconference_semi_finals',
                'finals'
            ])
            ->select([
                'p.id AS player_id',
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "play_ins_elims_round_1" THEN psa.series_identifier END) AS play_ins_elims_round_1_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "play_ins_elims_round_2" THEN psa.series_identifier END) AS play_ins_elims_round_2_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "play_ins_finals" THEN psa.series_identifier END) AS play_ins_finals_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "round_of_32" THEN psa.series_identifier END) AS round_of_32_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "round_of_16" THEN psa.series_identifier END) AS round_of_16_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "quarter_finals" THEN psa.series_identifier END) AS quarter_finals_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "semi_finals" THEN psa.series_identifier END) AS semi_finals_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "interconference_semi_finals" THEN psa.series_identifier END) AS interconference_semi_finals_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "finals" THEN psa.series_identifier END) AS finals_appearances'),
                DB::raw('COUNT(DISTINCT psa.series_identifier) AS total_playoff_appearances'),
                DB::raw('COUNT(DISTINCT psa.season_id) AS seasons_played_in_playoffs'),
                DB::raw('COUNT(DISTINCT pss.season_id) AS total_seasons_played'),
                DB::raw('COUNT(DISTINCT CASE WHEN psa.round = "finals" AND t.id = ss.finals_winner_id THEN psa.season_id END) AS championships_won')
            ])
            ->groupBy('p.id')
            ->get();

        DB::transaction(function () use ($playerData) {
            foreach ($playerData as $data) {
                DB::table('player_playoff_appearances')->updateOrInsert(
                    ['player_id' => $data->player_id],
                    [
                        'play_ins_elims_round_1_appearances' => $data->play_ins_elims_round_1_appearances,
                        'play_ins_elims_round_2_appearances' => $data->play_ins_elims_round_2_appearances,
                        'play_ins_finals_appearances' => $data->play_ins_finals_appearances,
                        'round_of_32_appearances' => $data->round_of_32_appearances,
                        'round_of_16_appearances' => $data->round_of_16_appearances,
                        'quarter_finals_appearances' => $data->quarter_finals_appearances,
                        'semi_finals_appearances' => $data->semi_finals_appearances,
                        'interconference_semi_finals_appearances' => $data->interconference_semi_finals_appearances,
                        'finals_appearances' => $data->finals_appearances,
                        'total_playoff_appearances' => $data->total_playoff_appearances,
                        'seasons_played_in_playoffs' => $data->seasons_played_in_playoffs,
                        'total_seasons_played' => $data->total_seasons_played,
                        'championships_won' => $data->championships_won
                    ]
                );
            }
        });

        return response()->json(['message' => 'Playoff appearances updated for all players across all seasons based on series data.']);
    }
}
