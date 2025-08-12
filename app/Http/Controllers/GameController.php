<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Models\Schedules;
use App\Models\Seasons;
use App\Models\Teams;
use App\Models\PlayerGameStats;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GameController extends Controller
{

    public function getBoxScore(Request $request)
    {
        // Validate the request
        $request->validate([
            'game_id' => 'required|string',
            'show_stats'  => 'required|boolean',
        ]);

        $show_stats = $request->show_stats;
        $game_id = $request->game_id; // Fetch game details from the schedule_view table and join with teams table
        $game = \DB::table('schedule_view')
            ->join('teams as away_team', 'schedule_view.away_id', '=', 'away_team.id') // Join for away team
            ->join('teams as home_team', 'schedule_view.home_id', '=', 'home_team.id') // Join for home team
            ->where('schedule_view.game_id', $game_id)
            ->select(
                'schedule_view.*', // Select all columns from schedule_view
                'away_team.description as away_description',
                'away_team.sponsor as away_sponsor',
                'away_team.primary_color as away_primary_color',
                'away_team.secondary_color as away_secondary_color',
                'away_team.city as away_city',
                'home_team.description as home_description',
                'home_team.sponsor as home_sponsor',
                'home_team.primary_color as home_primary_color',
                'home_team.secondary_color as home_secondary_color',
                'home_team.city as home_city',
            )
            ->first();

        if (!$game) {
            return response()->json([
                'message' => 'Game not found',
            ], 404);
        }

        $playerStats = \DB::table('player_game_stats')
            ->where('player_game_stats.game_id', $game_id)
            ->leftJoin('players as p', 'player_game_stats.player_id', '=', 'p.id') // Alias for players table
            ->leftJoin('teams as drafted_team', 'drafted_team.id', '=', 'p.drafted_team_id') // Alias for drafted teams
            ->leftJoin('teams as t', 'player_game_stats.team_id', '=', 't.id') // Alias for teams table
            ->leftJoin('player_season_stats as pss', function ($join) {
                $join->on('pss.player_id', '=', 'p.id')
                    ->on('pss.season_id', '=', 'player_game_stats.season_id'); // Join player_season_stats on player_id and season_id
            })
            ->leftJoin('season_awards as sa', 'sa.player_id', '=', 'p.id')
            ->select(
                'player_game_stats.player_id',
                'p.name as player_name',
                'p.is_rookie',
                'p.is_injured',
                'p.draft_status',
                'p.draft_id',
                'p.age',
                'drafted_team.acronym as drafted_team_acro',
                'player_game_stats.team_id',
                'player_game_stats.game_id',
                'player_game_stats.season_id as game_season_id',
                'pss.season_id as stats_season_id',
                't.name as team_name',
                'player_game_stats.role as player_role',
                'p.position as position',
                'player_game_stats.points',
                'player_game_stats.assists',
                'player_game_stats.rebounds',
                'player_game_stats.steals',
                'player_game_stats.blocks',
                'player_game_stats.turnovers',
                'player_game_stats.fouls',
                'player_game_stats.minutes',

                'player_game_stats.field_goal_attempts',
                'player_game_stats.field_goals_made',
                'player_game_stats.two_point_attempts',
                'player_game_stats.two_pointers_made',
                'player_game_stats.three_point_attempts',
                'player_game_stats.three_pointers_made',
                'player_game_stats.free_throw_attempts',
                'player_game_stats.free_throws_made',
                'player_game_stats.per',
                'player_game_stats.ts_percent',
                'player_game_stats.eff',

                // Determine if the player is the Finals MVP for this season
                DB::raw("CASE WHEN p.id = (SELECT finals_mvp_id FROM seasons WHERE seasons.finals_mvp_id = p.id LIMIT 1) THEN 1 ELSE 0 END as is_finals_mvp"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Best Defensive Player') THEN 1 ELSE 0 END) AS is_defensive_poy"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Sixth Man of the Year') THEN 1 ELSE 0 END) AS is_sixth_man"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Rookie of the Season') THEN 1 ELSE 0 END) AS is_rookie_poy"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Most Improved Player') THEN 1 ELSE 0 END) AS is_most_improved"),
                DB::raw("(SELECT CASE WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.player_id = p.id AND sa.award_name = 'Best Overall Player') THEN 1 ELSE 0 END) AS is_season_mvp"),
                // Return the string for Finals MVP
                // DB::raw("COALESCE(
                //     (SELECT CONCAT('Finals MVP (Season ', s.id, ') by ',
                //         CASE
                //             WHEN pgs.team_id = s.finals_winner_id AND s.finals_winner_score > s.finals_loser_score THEN th.name
                //             WHEN pgs.team_id = s.finals_loser_id AND s.finals_loser_score > s.finals_winner_score THEN ta.name
                //             ELSE 'No Winner'
                //         END)
                //     FROM seasons s
                //     LEFT JOIN teams th ON th.id = s.finals_winner_id  -- Join for winner team name
                //     LEFT JOIN teams ta ON ta.id = s.finals_loser_id  -- Join for loser team name
                //     JOIN player_game_stats pgs ON pgs.season_id = s.id AND pgs.player_id = p.id  -- Join to get the player’s game stats
                //     WHERE s.finals_mvp_id = p.id
                //     LIMIT 1), '') as finals_mvp"),

                // DB::raw("COALESCE(
                //     (SELECT CONCAT('Championship Won (Season ', s.id, ') by ',
                //                    CASE
                //                        WHEN pgs.team_id = s.finals_winner_id AND s.finals_winner_score > s.finals_loser_score THEN th.name
                //                        WHEN pgs.team_id = s.finals_loser_id AND s.finals_loser_score > s.finals_winner_score THEN ta.name
                //                    END)
                //      FROM seasons s
                //      JOIN player_game_stats pgs ON pgs.season_id = s.id AND pgs.player_id = p.id
                //      LEFT JOIN teams th ON th.id = s.finals_winner_id  -- Join for home team name
                //      LEFT JOIN teams ta ON ta.id = s.finals_loser_id  -- Join for away team name
                //      WHERE (
                //          (pgs.team_id = s.finals_winner_id AND s.finals_winner_score > s.finals_loser_score)  -- Home team wins
                //          OR
                //          (pgs.team_id = s.finals_loser_id AND s.finals_loser_score > s.finals_winner_score)  -- Away team wins
                //      )
                //      LIMIT 1), '') as championship_won"),
            )
            ->groupBy(
                'player_game_stats.player_id',
                'p.id',
                'p.name',
                'p.is_rookie',
                'p.is_injured',
                'p.draft_status',
                'p.draft_id',
                'p.age',
                'drafted_team.acronym',
                'player_game_stats.team_id',
                'player_game_stats.game_id',
                'player_game_stats.season_id',
                'pss.season_id',
                't.name',
                'player_game_stats.role',
                'p.position',
                'player_game_stats.points',
                'player_game_stats.assists',
                'player_game_stats.rebounds',
                'player_game_stats.steals',
                'player_game_stats.blocks',
                'player_game_stats.turnovers',
                'player_game_stats.fouls',
                'player_game_stats.minutes',
                'player_game_stats.field_goal_attempts',
                'player_game_stats.field_goals_made',
                'player_game_stats.two_point_attempts',
                'player_game_stats.two_pointers_made',
                'player_game_stats.three_point_attempts',
                'player_game_stats.three_pointers_made',
                'player_game_stats.free_throw_attempts',
                'player_game_stats.free_throws_made',
                'player_game_stats.per',
                'player_game_stats.ts_percent',
                'player_game_stats.eff'
            )
            ->get()
            ->keyBy('player_id');



        // Fetch all players that might be relevant to the game (ignoring team_id here)
        // Get all player stats with calculated percentages
        $playerStats = $playerStats->map(function ($stat) {
            // Calculate shooting percentages
            $stat->field_goal_percent = $stat->field_goal_attempts > 0
                ? round(($stat->field_goals_made / $stat->field_goal_attempts) * 100, 1)
                : 0;

            $stat->three_point_percent = $stat->three_point_attempts > 0
                ? round(($stat->three_pointers_made / $stat->three_point_attempts) * 100, 1)
                : 0;

            $stat->free_throw_percent = $stat->free_throw_attempts > 0
                ? round(($stat->free_throws_made / $stat->free_throw_attempts) * 100, 1)
                : 0;

            return $stat;
        });

        // Calculate stat leaders with qualification thresholds
        $statLeaders = [
            'points' => $playerStats->sortByDesc('points')->first(),
            'assists' => $playerStats->sortByDesc('assists')->first(),
            'rebounds' => $playerStats->sortByDesc('rebounds')->first(),
            'steals' => $playerStats->sortByDesc('steals')->first(),
            'blocks' => $playerStats->sortByDesc('blocks')->first(),
            'field_goal_percent' => $playerStats->filter(function ($stat) {
                return $stat->field_goal_attempts >= 5;
            })->sortByDesc('field_goal_percent')->first(),
            'three_pointers' => [
                'made' => $playerStats->sortByDesc('three_pointers_made')->first(),
                'percent' => $playerStats->filter(function ($stat) {
                    return $stat->three_point_attempts >= 3;
                })->sortByDesc('three_point_percent')->first()
            ],
            'free_throw_percent' => $playerStats->filter(function ($stat) {
                return $stat->free_throw_attempts >= 2;
            })->sortByDesc('free_throw_percent')->first(),
            'advanced' => [
                'per' => $playerStats->sortByDesc('per')->first(),
                'ts_percent' => $playerStats->sortByDesc('ts_percent')->first(),
                'eff' => $playerStats->sortByDesc('eff')->first()
            ]
        ];

        // Add defensive stats leader (combination of steals and blocks)
        $statLeaders['defensive'] = $playerStats->sortByDesc(function ($stat) {
            return $stat->steals + $stat->blocks;
        })->first();

        // Add efficiency leader (PTS + REB + AST)
        $statLeaders['efficiency'] = $playerStats->sortByDesc(function ($stat) {
            return $stat->points + $stat->rebounds + $stat->assists;
        })->first();

        $randomSeasonStatsLeaders = $this->getStatsLeaders($game->season_id);
        // Determine the winning team
        $winningTeamId = $game->winner_id;

        // Filter player stats for the winning team
        $winningTeamPlayersStats = $playerStats->filter(function ($stat) use ($winningTeamId) {
            return $stat->team_id == $winningTeamId;
        });

        // Determine the best player of the winning team
        // $bestWinningTeamPlayer = $winningTeamPlayersStats->sort(function ($a, $b) {
        //     $aStats = $a->points + $a->assists + $a->rebounds + $a->steals + $a->blocks;
        //     $bStats = $b->points + $b->assists + $b->rebounds + $b->steals + $b->blocks;
        //     return $bStats <=> $aStats;
        // })->first();

        $bestWinningTeamPlayer = $winningTeamPlayersStats
            ->sortByDesc('eff')
            ->first();



        // Fetch player details for the best player of the winning team if exists
        $bestWinningTeamPlayerDetails = $bestWinningTeamPlayer ? [
            'game_id' => $bestWinningTeamPlayer->game_id,
            'name' => $bestWinningTeamPlayer->player_name,
            'team' => $bestWinningTeamPlayer->team_name,
            'age' => $bestWinningTeamPlayer->age,
            'position' => $bestWinningTeamPlayer->position,
            'points' => $bestWinningTeamPlayer->points,
            'assists' => $bestWinningTeamPlayer->assists,
            'rebounds' => $bestWinningTeamPlayer->rebounds,
            'steals' => $bestWinningTeamPlayer->steals,
            'blocks' => $bestWinningTeamPlayer->blocks,
            'turnovers' => $bestWinningTeamPlayer->turnovers,
            'fouls' => $bestWinningTeamPlayer->fouls,
            'role' => $bestWinningTeamPlayer->player_role,
            'minutes' => $bestWinningTeamPlayer->minutes,
            'draft_id' => $bestWinningTeamPlayer->draft_id,
            'draft_status' => $bestWinningTeamPlayer->draft_status,
            'drafted_team_acro' => $bestWinningTeamPlayer->drafted_team_acro,
            'awards' => $bestWinningTeamPlayer->awards ?? null,
            'finals_mvp' => $bestWinningTeamPlayer->finals_mvp ?? null,
            'championship_won' => $bestWinningTeamPlayer->championship_won ?? null,
            'is_finals_mvp' => $bestWinningTeamPlayer->is_finals_mvp,
            'is_season_mvp' => $bestWinningTeamPlayer->is_season_mvp,
            'is_defensive_poy' => $bestWinningTeamPlayer->is_defensive_poy,
            'is_rookie_poy' => $bestWinningTeamPlayer->is_rookie_poy,
            'is_most_improved' => $bestWinningTeamPlayer->is_most_improved,
        ] : null;

        $homeTeamStreak = $this->getTeamStreak($game->home_id, $game->id);
        $awayTeamStreak = $this->getTeamStreak($game->away_id, $game->id);

        // Query to get head-to-head record
        $headToHeadRecord = $this->getHeadToHeadRecord($game->home_id, $game->away_id);

        $homeTeamPlayersArray = [];
        $awayTeamPlayersArray = [];

        if ($show_stats) {
            // Fetch all players that might be relevant to the game (ignoring team_id here)
            $players = \DB::table('players')
                ->whereIn('id', $playerStats->pluck('player_id')->toArray())
                ->get()
                ->keyBy('id');

            // Split player stats into home and away teams, using the game team IDs
            $homeTeamPlayers = $players->filter(function ($player) use ($game, $playerStats) {
                $playerStat = $playerStats->get($player->id);
                return $playerStat && $playerStat->team_id == $game->home_id;
            });

            $awayTeamPlayers = $players->filter(function ($player) use ($game, $playerStats) {
                $playerStat = $playerStats->get($player->id);
                return $playerStat && $playerStat->team_id == $game->away_id;
            });

            $homeTeamPlayersArray = $homeTeamPlayers->map(function ($player) use ($playerStats) {
                $stats = $playerStats->get($player->id);

                // Field goal percentage, 2-point percentage, 3-point percentage, and free throw percentage calculation
                $fgPercentage = $stats && $stats->field_goal_attempts ? ($stats->field_goals_made / $stats->field_goal_attempts) * 100 : 0;
                $twoPPercentage = $stats && $stats->two_point_attempts ? ($stats->two_pointers_made / $stats->two_point_attempts) * 100 : 0;
                $threePPercentage = $stats && $stats->three_point_attempts ? ($stats->three_pointers_made / $stats->three_point_attempts) * 100 : 0;
                $ftPercentage = $stats && $stats->free_throw_attempts ? ($stats->free_throws_made / $stats->free_throw_attempts) * 100 : 0;

                // Calculate a composite score based on various stats (weights are customizable)
                $compositeScore = (
                    ($stats->points * 1) +
                    ($stats->assists * 1.5) +
                    ($stats->rebounds * 1.2) +
                    ($stats->steals * 2) +
                    ($stats->blocks * 2) -
                    ($stats->turnovers * 1) -
                    ($stats->fouls * 0.5)
                );

                return [
                    'player_id' => $player->id,
                    'team_id' => $player->team_id,
                    'name' => $player->name,
                    'position' => $player->position,
                    'role' => $player->role,
                    'fatigue' => $player->fatigue,
                    'is_rookie' => $player->is_rookie,
                    'is_injured' => $player->is_injured,
                    'points' => $stats ? $stats->points : 0,
                    'assists' => $stats ? $stats->assists : 0,
                    'rebounds' => $stats ? $stats->rebounds : 0,
                    'steals' => $stats ? $stats->steals : 0,
                    'blocks' => $stats ? $stats->blocks : 0,
                    'turnovers' => $stats ? $stats->turnovers : 0,
                    'fouls' => $stats ? $stats->fouls : 0,
                    'minutes' => $stats ? $stats->minutes : 'DNP',
                    'field_goal_attempts' => $stats ? $stats->field_goal_attempts : 0,
                    'field_goals_made' => $stats ? $stats->field_goals_made : 0,
                    'two_point_attempts' => $stats ? $stats->two_point_attempts : 0,
                    'two_pointers_made' => $stats ? $stats->two_pointers_made : 0,
                    'three_point_attempts' => $stats ? $stats->three_point_attempts : 0,
                    'three_pointers_made' => $stats ? $stats->three_pointers_made : 0,
                    'free_throw_attempts' => $stats ? $stats->free_throw_attempts : 0,
                    'free_throws_made' => $stats ? $stats->free_throws_made : 0,
                    'field_goal_percentage' => $fgPercentage,
                    'two_point_percentage' => $twoPPercentage,
                    'three_point_percentage' => $threePPercentage,
                    'free_throw_percentage' => $ftPercentage,
                    'per' => $stats ? $stats->per : null,  // Player efficiency rating
                    'ts_percent' => $stats ? $stats->ts_percent : null,  // True shooting percentage
                    'efficiency' => $stats ? $stats->eff : null,  // Efficiency
                    'composite_score' => $compositeScore  // Add composite score to the array
                ];
            })->sortByDesc('composite_score')->values()->toArray();  // Sort by composite score in descending order

            // Convert away team player stats to an array, including those with no recorded stats
            $awayTeamPlayersArray = $awayTeamPlayers->map(function ($player) use ($playerStats) {
                $stats = $playerStats->get($player->id);

                // Field goal percentage, 2-point percentage, 3-point percentage, and free throw percentage calculation
                $fgPercentage = $stats && $stats->field_goal_attempts ? ($stats->field_goals_made / $stats->field_goal_attempts) * 100 : 0;
                $twoPPercentage = $stats && $stats->two_point_attempts ? ($stats->two_pointers_made / $stats->two_point_attempts) * 100 : 0;
                $threePPercentage = $stats && $stats->three_point_attempts ? ($stats->three_pointers_made / $stats->three_point_attempts) * 100 : 0;
                $ftPercentage = $stats && $stats->free_throw_attempts ? ($stats->free_throws_made / $stats->free_throw_attempts) * 100 : 0;

                // Calculate a composite score based on various stats (weights are customizable)
                $compositeScore = (
                    ($stats->points * 1) +
                    ($stats->assists * 1.5) +
                    ($stats->rebounds * 1.2) +
                    ($stats->steals * 2) +
                    ($stats->blocks * 2) -
                    ($stats->turnovers * 1) -
                    ($stats->fouls * 0.5)
                );

                return [
                    'player_id' => $player->id,
                    'team_id' => $player->team_id,
                    'name' => $player->name,
                    'position' => $player->position,
                    'role' => $player->role,
                    'fatigue' => $player->fatigue,
                    'is_rookie' => $player->is_rookie,
                    'is_injured' => $player->is_injured,
                    'points' => $stats ? $stats->points : 0,
                    'assists' => $stats ? $stats->assists : 0,
                    'rebounds' => $stats ? $stats->rebounds : 0,
                    'steals' => $stats ? $stats->steals : 0,
                    'blocks' => $stats ? $stats->blocks : 0,
                    'turnovers' => $stats ? $stats->turnovers : 0,
                    'fouls' => $stats ? $stats->fouls : 0,
                    'minutes' => $stats ? $stats->minutes : 'DNP',
                    'field_goal_attempts' => $stats ? $stats->field_goal_attempts : 0,
                    'field_goals_made' => $stats ? $stats->field_goals_made : 0,
                    'two_point_attempts' => $stats ? $stats->two_point_attempts : 0,
                    'two_pointers_made' => $stats ? $stats->two_pointers_made : 0,
                    'three_point_attempts' => $stats ? $stats->three_point_attempts : 0,
                    'three_pointers_made' => $stats ? $stats->three_pointers_made : 0,
                    'free_throw_attempts' => $stats ? $stats->free_throw_attempts : 0,
                    'free_throws_made' => $stats ? $stats->free_throws_made : 0,
                    'field_goal_percentage' => $fgPercentage,
                    'two_point_percentage' => $twoPPercentage,
                    'three_point_percentage' => $threePPercentage,
                    'free_throw_percentage' => $ftPercentage,
                    'per' => $stats ? $stats->per : null,
                    'ts_percent' => $stats ? $stats->ts_percent : null,
                    'efficiency' => $stats ? $stats->eff : null,
                    'composite_score' => $compositeScore  // Add composite score to the array
                ];
            })->sortByDesc('composite_score')->values()->toArray();  // Sort by composite score in descending order
        }
        $homeTeamRatings = $this->getTeamRatingsPerSeason($game->season_id, $game->home_id);
        $awayTeamRatings = $this->getTeamRatingsPerSeason($game->season_id, $game->away_id);


        $injury = $this->getIngameInjury($game->id);

        $seasonData = DB::table('seasons')
            ->join('leagues', 'seasons.league_id', '=', 'leagues.id')
            ->where('seasons.id', $game->season_id)
            ->select('seasons.name as season_name', 'leagues.id as league_id', 'leagues.name as league_name')
            ->first();

        $seasonName = $seasonData->season_name;
        $leagueName = $seasonData->league_name;

        // Format data for box score
        $boxScore = [
            'game_id' => $game->game_id,
            'round' => $game->round,
            'injury' => $injury,
            'league_leaders' => $randomSeasonStatsLeaders,
            'home_team' => [
                'team_id' => $game->home_id, // Use the correct field from your query
                'name' => $game->home_team_name,
                'city' => $game->home_city,
                'score' => $game->home_score,
                'primary_color' => $game->home_primary_color, // Add primary color
                'secondary_color' => $game->home_secondary_color, // Add secondary color
                'description' => $game->home_description, // Add secondary color
                'sponsor' => $game->home_sponsor, // Add secondary color
                'streak' => $homeTeamStreak,
                'ratings' => $homeTeamRatings,
            ],
            'away_team' => [
                'team_id' => $game->away_id, // Use the correct field from your query
                'name' => $game->away_team_name,
                'city' => $game->away_city,
                'score' => $game->away_score,
                'primary_color' => $game->away_primary_color, // Add primary color
                'secondary_color' => $game->away_secondary_color, // Add secondary color
                'description' => $game->away_description, // Add secondary color
                'sponsor' => $game->away_sponsor, // Add secondary color
                'streak' => $awayTeamStreak,
                'ratings' => $awayTeamRatings,
            ],
            'player_stats' => [
                'home' => $homeTeamPlayersArray,
                'away' => $awayTeamPlayersArray,
            ],
            'head_to_head_record' => $headToHeadRecord,
            'stat_leaders' => $statLeaders,
            'best_player' => $bestWinningTeamPlayerDetails,
            'total_players_played' => $playerStats->count(),
            'season_name' =>  $seasonName,
            'league_name' =>  $leagueName,
        ];

        return response()->json([
            'box_score' => $boxScore,
        ]);
    }
    private function getStatsLeaders($seasonId)
    {
        // List of possible stat types to randomize (both averages and totals)
        $statTypes = [
            'avg_points_per_game' => 'points per game',
            'avg_rebounds_per_game' => 'rebounds per game',
            'avg_assists_per_game' => 'assists per game',
            'avg_steals_per_game' => 'steals per game',
            'avg_blocks_per_game' => 'blocks per game',
            'avg_turnovers_per_game' => 'turnovers per game',
            'avg_fouls_per_game' => 'fouls',
            'total_points' => 'total points',
            'total_rebounds' => 'total rebounds',
            'total_assists' => 'total assists',
            'total_steals' => 'total steals',
            'total_blocks' => 'total blocks',
            'total_turnovers' => 'total turnovers',
            'total_fouls' => 'total fouls'
        ];

        // Randomly pick a stat type (this now includes total stats as well)
        $randomStatKey = array_rand($statTypes);
        $statType = $statTypes[$randomStatKey]; // Get the readable stat name (e.g., 'points', 'steals')

        // Step 1: Retrieve the overall leader with the highest stat for the season
        $overallLeader = DB::table('player_season_stats')
            ->select(
                'player_season_stats.id',
                'player_season_stats.player_id',
                'player_season_stats.team_id',
                'player_season_stats.season_id',
                'player_season_stats.role',
                'player_season_stats.total_games',
                'player_season_stats.total_games_played',
                'player_season_stats.avg_minutes_per_game',
                'player_season_stats.avg_points_per_game',
                'player_season_stats.avg_rebounds_per_game',
                'player_season_stats.avg_assists_per_game',
                'player_season_stats.avg_steals_per_game',
                'player_season_stats.avg_blocks_per_game',
                'player_season_stats.avg_turnovers_per_game',
                'player_season_stats.avg_fouls_per_game',
                'player_season_stats.total_points',       // Add total stats here
                'player_season_stats.total_rebounds',     // Add total stats here
                'player_season_stats.total_assists',      // Add total stats here
                'player_season_stats.total_steals',       // Add total stats here
                'player_season_stats.total_blocks',       // Add total stats here
                'player_season_stats.total_turnovers',    // Add total stats here
                'player_season_stats.total_fouls',        // Add total stats here
                'players.is_rookie',
                'players.draft_status',
                'players.name as player_name',
                'teams.name as team_name'
            )
            ->join('players', 'player_season_stats.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats.team_id', '=', 'teams.id')
            ->where('player_season_stats.season_id', $seasonId)
            ->orderByDesc($randomStatKey) // Order by the randomized stat key (which could be a total or avg stat)
            ->limit(1)
            ->first();

        // Step 2: Retrieve the rookie leader with the highest stat for the season
        $rookieLeader = DB::table('player_season_stats')
            ->select(
                'player_season_stats.id',
                'player_season_stats.player_id',
                'player_season_stats.team_id',
                'player_season_stats.season_id',
                'player_season_stats.role',
                'player_season_stats.total_games',
                'player_season_stats.total_games_played',
                'player_season_stats.avg_minutes_per_game',
                'player_season_stats.avg_points_per_game',
                'player_season_stats.avg_rebounds_per_game',
                'player_season_stats.avg_assists_per_game',
                'player_season_stats.avg_steals_per_game',
                'player_season_stats.avg_blocks_per_game',
                'player_season_stats.avg_turnovers_per_game',
                'player_season_stats.avg_fouls_per_game',
                'player_season_stats.total_points',       // Add total stats here
                'player_season_stats.total_rebounds',     // Add total stats here
                'player_season_stats.total_assists',      // Add total stats here
                'player_season_stats.total_steals',       // Add total stats here
                'player_season_stats.total_blocks',       // Add total stats here
                'player_season_stats.total_turnovers',    // Add total stats here
                'player_season_stats.total_fouls',        // Add total stats here
                'players.is_rookie',
                'players.draft_status',
                'players.name as player_name',
                'teams.name as team_name'
            )
            ->join('players', 'player_season_stats.player_id', '=', 'players.id')
            ->join('teams', 'player_season_stats.team_id', '=', 'teams.id')
            ->where('players.is_rookie', true)
            ->where('player_season_stats.season_id', $seasonId)
            ->orderByDesc($randomStatKey) // Order by the randomized stat key (which could be a total or avg stat)
            ->limit(1)
            ->first();

        // Step 3: Handle case where either leader could be null
        $leaders = collect([$overallLeader, $rookieLeader])->filter(); // Filter out nulls

        if ($leaders->isEmpty()) {
            return null; // No valid leaders found
        }

        // Randomly select the leader
        $selectedLeader = $leaders->random();

        // Step 4: Fetch the selected stat value dynamically
        $statValue = $selectedLeader ? $selectedLeader->{$randomStatKey} : 0;

        // Build the message
        $message =  ($selectedLeader->is_rookie ? "Rookie" : "Overall") . " Season Leader in " . ucfirst($statType);

        // Prepare the response data
        $responseData = [
            'player_name' => $selectedLeader->player_name,
            'team_name' => $selectedLeader->team_name,
            'draft_status' => $selectedLeader->draft_status,
            'stat_type' => $statType,
            'stat_value' => $statValue,
            'message' => $message,
        ];

        return $responseData;
    }

    private function getIngameInjury($gameId)
    {
        return DB::table('injured_players_view as i')
            ->select('i.*', 'p.role', 'p.position')
            ->join('players as p', 'i.player_id', '=', 'p.id') // Join with player_ratings table
            ->where('game_id', $gameId)
            ->get();
    }
    private function getTeamRatingsPerSeason($seasonId, $teamId)
    {
        // Get team ratings for the given season using the player_ratings table
        $teamRatings = \DB::table('player_season_stats') // Changed table to player_season_stats
            ->join('players as p', 'player_season_stats.player_id', '=', 'p.id') // Join with player_ratings table
            ->join('teams as t', 'player_season_stats.team_id', '=', 't.id')
            ->select(
                't.id as team_id',
                't.name as team_name',
                'player_season_stats.season_id',
                \DB::raw('ROUND(AVG(p.defense_rating)) as defense_rating'),
                \DB::raw('ROUND(AVG(p.shooting_rating)) as offense_rating'), // Assuming offense is shooting_rating
                \DB::raw('ROUND(AVG(p.passing_rating)) as passing_rating'),
                \DB::raw('ROUND(AVG(p.rebounding_rating)) as rebounding_rating')
            )
            ->where('player_season_stats.season_id', $seasonId)  // Filter by season
            ->where('player_season_stats.team_id', $teamId)  // Filter by team_id (optional)
            ->groupBy('t.id', 't.name', 'player_season_stats.season_id')
            ->get();

        if ($teamRatings->isEmpty()) {
            return response()->json(['message' => 'No team ratings found for this season', 'season_id' => $seasonId], 404);
        }

        return $teamRatings[0];
    }



    /**
     * Function to get team streak
     */
    private function getTeamStreak($teamId, $game_id)
    {
        // Query to calculate the team's current winning or losing streak
        $streak = \DB::table('schedule_view')
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->where('status', 2)
            ->where('id', '<=', $game_id) // Get records with id less than or equal to game_id
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
        $streakResult = $currentStreak > 0 ? ($isWinningStreak ? 'W' . $currentStreak : 'L' . $currentStreak) : 'N0';

        return $streakResult;
    }

    /**
     * Function to get head-to-head record
     */
    private function getHeadToHeadRecord($homeTeamId, $awayTeamId)
    {
        // Fetch the head-to-head matchup from the head_to_head_matchups table using homeTeamId as team_id
        $headToHead = \DB::table('head_to_head')
            ->where('team_id', $homeTeamId)
            ->where('opponent_id', $awayTeamId)
            ->first(); // We expect at most one record, so using first()

        if (!$headToHead) {
            return [
                'home_team_wins' => 0,
                'away_team_wins' => 0,
                'match_count' => 0,
                'draws' => 0,
            ];
        }

        // Return the head-to-head record from the perspective of the home team
        return [
            'home_team_wins' => $headToHead->wins,
            'away_team_wins' => $headToHead->losses,
            'match_count' => $headToHead->losses + $headToHead->wins,
            'draws' => $headToHead->draws,
        ];
    }
}
