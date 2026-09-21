<?php

namespace App\Services\League;

ini_set('max_execution_time', 0); // 300 seconds = 5 minutes

use App\Services\Helper\HelperService;
use Illuminate\Support\Facades\DB;

class AwardsService
{

    protected $scheduleService;
    protected $helper;

    public function __construct(){
        $this->helper = new HelperService();
    }

    public function getSeasonAwards($request)
    {

        // Validate the incoming request
        $request->validate([
            'season_id' => 'required|exists:seasons,id',
        ]);
        // Fetch awards along with player, team, and season names for the latest season
        $awards = DB::table('season_awards')
            ->leftJoin('players', 'season_awards.player_id', '=', 'players.id')
            ->leftJoin('teams', 'season_awards.team_id', '=', 'teams.id')
            ->leftJoin('seasons', 'season_awards.season_id', '=', 'seasons.id') // Join the seasons table
            ->where('season_awards.season_id', $request->season_id)
            ->select(
                'season_awards.*',
                'players.name as player_name',
                'teams.name as team_name',
                'seasons.name as season_name' // Select the season name
            )
            ->get();


        return response()->json([
            'message' => 'Season awards stored successfully.',
            'awards' => $awards
        ]);
    }

    public function getawardnamesdropdown()
    {
        // Fetch distinct award names from the season_awards table
        $awardNames = DB::table('season_awards')
            ->select('award_name')
            ->distinct()
            ->get();

        // Pass the award names to the view
        return response()->json([
            'awardNames' => $awardNames
        ]);
    }

    public function filterawardsperseason($request)
    {
        // Assume season_id is passed in the request
        $seasonId = $request->input('season_id');
        $awardsName = $request->input('awards_name');
        // Fetch awards along with player and team names for the updated season
        if ($seasonId > 0) {
            $awards = DB::table('season_awards')
                ->leftJoin('players', 'season_awards.player_id', '=', 'players.id')
                ->leftJoin('teams', 'season_awards.team_id', '=', 'teams.id')
                ->leftJoin('teams as drafted_team', 'players.drafted_team_id', '=', 'drafted_team.id') // Fixed alias usage
                ->leftJoin('seasons', 'season_awards.season_id', '=', 'seasons.id') // Join the seasons table
                ->where('season_awards.season_id', $seasonId)
                ->select(
                    'season_awards.id',
                    'season_awards.player_id',
                    'players.name as player_name',
                    'players.draft_status as draft_status',
                    'drafted_team.acronym as drafted_team', // Fetch drafted team acronym
                    'teams.name as team_name',
                    'season_awards.award_name',
                    'season_awards.award_description',
                    'season_awards.season_id',
                    'season_awards.team_id',
                    'season_awards.created_at',
                    'seasons.name as season_name' // Select the season name
                )
                ->orderBy('season_awards.id', 'desc')  // Order by id in descending order
                ->get();
        } else {
            $awards = DB::table('season_awards')
                ->leftJoin('players', 'season_awards.player_id', '=', 'players.id')
                ->leftJoin('teams', 'season_awards.team_id', '=', 'teams.id')
                ->leftJoin('teams as drafted_team', 'players.drafted_team_id', '=', 'drafted_team.id') // Fixed alias usage
                ->leftJoin('seasons', 'season_awards.season_id', '=', 'seasons.id') // Join the seasons table
                ->where('season_awards.award_name', $awardsName)
                ->select(
                    'season_awards.id',
                    'season_awards.player_id',
                    'players.name as player_name',
                    'players.draft_status as draft_status',
                    'drafted_team.acronym as drafted_team', // Fetch drafted team acronym
                    'teams.name as team_name',
                    'season_awards.award_name',
                    'season_awards.award_description',
                    'season_awards.season_id',
                    'season_awards.team_id',
                    'season_awards.created_at',
                    'seasons.name as season_name' // Select the season name
                )
                ->orderBy('season_awards.id', 'desc')  // Order by id in descending order
                ->get();
        }


        return response()->json([
            'message' => 'Team IDs in season awards updated successfully for season ' . $seasonId,
            'awards' => $awards
        ]);
    }

    public function selectAllStarsForConference($allStarGroup,$conferenceGroup)
    {
        // Get the latest season ID
        $latestSeasonId = get_current_season_id() ?? 0;

        // Get player stats from player_season_stats for the latest season
        $playerStats = DB::table('player_season_stats as pss')
            ->join('teams','teams.id','=','pss.team_id')
            ->join('conferences','conferences.id','=','teams.conference_id')
            ->where('pss.season_id', $latestSeasonId)
            ->whereIn('conferences.id',$conferenceGroup)
            ->get();

        $eligiblePlayerStats = $playerStats->filter(function ($stats) {
            return (int)$stats->total_games_played >= 0.50 * (int)$stats->total_games;
        });

        // Calculate MVP by sorting the players based on the weighted stats and returning the top player
        $allStars = $eligiblePlayerStats->sort(function ($a, $b) {
            $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;

            $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;

            return $bStats <=> $aStats;
        })->limit(15)->get();

        // Filter out rookies and determine the Rookie of the Year award
        $rookies = $eligiblePlayerStats->filter(function ($stats) {
            return DB::table('players')
                ->where('id', $stats->player_id)
                ->where('draft_id', $stats->season_id)
                ->exists();
        });

        // Filter rookies who have played at least 75% of games for Rookie of the Year
        $topRookies = $rookies->sort(function ($a, $b) {
            $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;

            $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;

            return $bStats <=> $aStats;
        })->limit(15)->get();

        // Only insert these awards if it's not season 1
        if ($latestSeasonId > 1) {
        
            // Rookie of the Year (not applicable in season 1)
            if ($topRookies) {
                $rookieRankings = 0;
                foreach ($topRookies as $rookieStats) {
                    $rank = $rookieRankings++;
                    $role = $rank <= 5 ? 'starter' : 'reserve';

                    $this->insertAward($rookieStats, 
                    'all-rookie', 'All-Rookie '.$role.' of the '.$allStarGroup.' All-Rookie Select!', 
                    $latestSeasonId);

                    DB::table('transactions')->insert([
                        'player_id' => $rookieStats->player_id,
                        'season_id' => $latestSeasonId,
                        'details' => 'has been selected All-Rookie '.$role.' of the '.$allStarGroup.' All-rookie Selection.',
                        'from_team_id' => $rookieStats->team_id,
                        'to_team_id' => $rookieStats->team_id,
                        'status' => 'selected',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->insertAllStarSchedule('all-rookie');
            }
        }

        if ($allStars) {
            $allStarRankings = 0;
            foreach ($allStars as $allStarStats) {
                $rank = $allStarRankings++;
                $role = $rank <= 5 ? 'starter' : 'reserve';

                $this->insertAward($allStarStats, 
                'all-star', 'All-star '.$role.' of the '.$allStarGroup.' All-stars.', 
                $latestSeasonId);

                DB::table('transactions')->insert([
                    'player_id' => $allStarStats->player_id,
                    'season_id' => $latestSeasonId,
                    'details' => 'has been selected All-star '.$role.' of the '.$allStarGroup.' All-stars.',
                    'from_team_id' => $allStarStats->team_id,
                    'to_team_id' => $allStarStats->team_id,
                    'status' => 'selected',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->insertAllStarSchedule('all-star');

        }

        return response()->json([
            'message' => 'All Star stored successfully.',
        ]);
    }

    public function storeSeasonAwards()
    {
        // Get the latest season ID
        $latestSeasonId = get_current_season_id() ?? 0;

        // Clear existing awards for the latest season
        DB::table('season_awards')->where('season_id', $latestSeasonId)->delete();

        // Get player stats from player_season_stats for the latest season
        $playerStats = DB::table('player_season_stats')
            ->where('season_id', $latestSeasonId)
            ->get();

        // Filter eligible players (must have played at least 75% of the total games)
        $eligiblePlayerStats = $playerStats->filter(function ($stats) {
            return (int)$stats->total_games_played >= 0.75 * (int)$stats->total_games;
        });


        // Determine the top performers based on different metrics
        $topScorer = $eligiblePlayerStats->sortByDesc('avg_points_per_game')->first();
        $topRebounder = $eligiblePlayerStats->sortByDesc('avg_rebounds_per_game')->first();
        $topPlaymaker = $eligiblePlayerStats->sortByDesc('avg_assists_per_game')->first();
        $topStealer = $eligiblePlayerStats->sortByDesc('avg_steals_per_game')->first();
        $topBlocker = $eligiblePlayerStats->sortByDesc('avg_blocks_per_game')->first();
        $bestDefender = $eligiblePlayerStats->sortByDesc(function ($stats) {
            return $stats->avg_steals_per_game + $stats->avg_blocks_per_game;
        })->first();

        // Top 5 Offensive Players (Top 5 players based on avg points per game)
        $topOffensivePlayers = $eligiblePlayerStats->sortByDesc('avg_points_per_game')->take(5);

        // Top 5 Defensive Players (Top 5 players based on combined avg steals and blocks per game)
        $topDefensivePlayers = $eligiblePlayerStats->sortByDesc(function ($stats) {
            return $stats->avg_steals_per_game + $stats->avg_blocks_per_game;
        })->take(5);

        // Get previous season's stats for comparison
        $previousSeasonId = get_previous_season_id() ?? 0;
        $previousSeasonStats = DB::table('player_season_stats_archives')->where('season_id', $previousSeasonId)->pluck('avg_points_per_game', 'player_id');

        // Exclude rookies from the Most Improved Player award
        $nonRookies = DB::table('players')->where('is_rookie', false)->pluck('id');

        $mostImprovedPlayer = $eligiblePlayerStats->filter(function ($stats) use ($nonRookies) {
            return $nonRookies->contains($stats->player_id);
        })
            ->sortByDesc(function ($stats) use ($previousSeasonStats) {
                $previousPoints = $previousSeasonStats[$stats->player_id] ?? 0;
                return ($stats->avg_points_per_game - $previousPoints);
            })
            ->first();

        // Calculate MVP by sorting the players based on the weighted stats and returning the top player
        $mvp = $eligiblePlayerStats->sort(function ($a, $b) {
            $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;

            $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;

            return $bStats <=> $aStats;
        })->first();

        // Filter out rookies and determine the Rookie of the Year award
        $rookies = $eligiblePlayerStats->filter(function ($stats) {
            return DB::table('players')
                ->where('id', $stats->player_id)
                ->where('draft_id', $stats->season_id)
                ->exists();
        });

        // Filter rookies who have played at least 75% of games for Rookie of the Year
        $rookieOfTheYear = $rookies->filter(function ($stats) {
            return $stats->total_games_played >= 0.75 * $stats->total_games;
        })->sort(function ($a, $b) {
            $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;

            $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;

            return $bStats <=> $aStats;
        })->first();

        // Determine the 6th Man of the Year award
        $rolePlayers = $eligiblePlayerStats->filter(function ($stats) {
            return $stats->role !== 'star player' && $stats->role !== 'all star' && $stats->role !== 'starter';
        });

        $sixthManOfTheYear = $rolePlayers->sort(function ($a, $b) {
            $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;
            $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;
            return $bStats <=> $aStats;
        })->first();

        // Add these new awards before the insert awards section
        // Iron Man Award
        $ironMan = $eligiblePlayerStats->sortByDesc('total_minutes_played')->first();

        // Most Efficient Player (highest FG%)
        $mostEfficient = $eligiblePlayerStats->filter(function ($stats) {
            return $stats->total_field_goal_attempts > 200; // Minimum attempts threshold
        })->sortByDesc(function ($stats) {
            return ($stats->total_field_goals_made / $stats->total_field_goal_attempts) * 100;
        })->first();

        // Free Throw King (highest FT%)
        $freeThrowKing = $eligiblePlayerStats->filter(function ($stats) {
            return $stats->total_free_throw_attempts > 100; // Minimum attempts threshold
        })->sortByDesc(function ($stats) {
            return ($stats->total_free_throws_made / $stats->total_free_throw_attempts) * 100;
        })->first();

        // Three-Point Specialist (most 3pts made)
        $threePointKing = $eligiblePlayerStats->filter(function ($stats) {
            return $stats->total_three_point_attempts > 100; // Minimum attempts threshold
        })->sortByDesc('total_three_pointers_made')->first();

        // Double-Double Machine (most double-doubles)
        $doubleDoubleMachine = $eligiblePlayerStats->sortByDesc(function ($stats) {
            // Calculate approximate double-doubles based on averages
            $pointsDouble = $stats->avg_points_per_game >= 10 ? 1 : 0;
            $reboundsDouble = $stats->avg_rebounds_per_game >= 10 ? 1 : 0;
            $assistsDouble = $stats->avg_assists_per_game >= 10 ? 1 : 0;
            return $pointsDouble + $reboundsDouble + $assistsDouble;
        })->first();

        // Insert core awards that are always given
        $this->insertAward($topScorer, 'Top Scorer', 'Player with the highest average points per game', $latestSeasonId);
        $this->insertAward($topRebounder, 'Top Rebounder', 'Player with the highest average rebounds per game', $latestSeasonId);
        $this->insertAward($topPlaymaker, 'Top Playmaker', 'Player with the highest average assists per game', $latestSeasonId);
        $this->insertAward($topStealer, 'Top Stealer', 'Player with the highest average steals per game', $latestSeasonId);
        $this->insertAward($topBlocker, 'Top Blocker', 'Player with the highest average blocks per game', $latestSeasonId);
        $this->insertAward($bestDefender, 'Best Defensive Player', 'Player with the highest combined average steals and blocks per game', $latestSeasonId);
        $this->insertAward($mvp, 'Best Overall Player', 'Player with the best overall performance score', $latestSeasonId);

        // Only insert these awards if it's not season 1
        if ($latestSeasonId > 1) {
            // Most Improved Player (needs previous season stats)
            $this->insertAward($mostImprovedPlayer, 'Most Improved Player', 'Player with the highest increase in average points per game from the previous season', $latestSeasonId);

            // Rookie of the Year (not applicable in season 1)
            if ($rookieOfTheYear) {
                $this->insertAward($rookieOfTheYear, 'Rookie of the Season', 'Best rookie player of the season', $latestSeasonId);
            }

            // Sixth Man of the Year
            if ($sixthManOfTheYear) {
                $this->insertAward($sixthManOfTheYear, 'Sixth Man of the Year', 'Best player coming off the bench', $latestSeasonId);
            }
        }

        // Insert Top 5 Players awards (these are given every season)
        $counter = 1;
        foreach ($topOffensivePlayers as $player) {
            $this->insertAward($player, 'Top ' . $counter . ' Offensive Player', 'Player ranked ' . $counter . ' in average points per game', $latestSeasonId);
            $counter++;
        }

        $counter = 1;
        foreach ($topDefensivePlayers as $player) {
            $this->insertAward($player, 'Top ' . $counter . ' Defensive Player', 'Player ranked ' . $counter . ' in combined average steals and blocks per game', $latestSeasonId);
            $counter++;
        }

        // Add these to your existing awards insertion section
        $this->insertAward($ironMan, 'Iron Man of the Year', 'Player with most minutes played in the season', $latestSeasonId);
        $this->insertAward($mostEfficient, 'Most Efficient Player', 'Player with highest field goal percentage (min. 200 attempts)', $latestSeasonId);
        $this->insertAward($freeThrowKing, 'Free Throw King', 'Player with highest free throw percentage (min. 100 attempts)', $latestSeasonId);
        $this->insertAward($threePointKing, 'Three-Point Specialist', 'Player with most three-pointers made (min. 100 attempts)', $latestSeasonId);
        $this->insertAward($doubleDoubleMachine, 'Double-Double Machine', 'Player with most double-double combinations', $latestSeasonId);

        // Add these new award calculations before the insertion section
        // Advanced Statistical Awards
        $advancedAwards = [
            // Most Complete Player (Triple-Double Machine)
            'Triple-Double Machine' => $eligiblePlayerStats->filter(function ($stats) {
                return $stats->avg_points_per_game >= 10 &&
                    $stats->avg_rebounds_per_game >= 10 &&
                    $stats->avg_assists_per_game >= 10;
            })->first(),

            // Best Shooter (True Shooting Percentage Leader)
            'Shooting Efficiency Leader' => $eligiblePlayerStats
                ->filter(function ($stats) {
                    return $stats->total_field_goal_attempts >= 300;
                })
                ->sortByDesc('ts_percent')
                ->first(),

            // Most Efficient Player (PER Leader)
            'Player Efficiency Leader' => $eligiblePlayerStats
                ->sortByDesc('per')
                ->first(),

            // Perfect Attendance Award
            'Perfect Attendance Award' => $eligiblePlayerStats
                ->filter(function ($stats) {
                    return $stats->total_games_played === $stats->total_games;
                })
                ->sortByDesc('total_minutes_played')
                ->first(),

            // Best All-Around Player (Based on EFF)
            'Most Versatile Player' => $eligiblePlayerStats
                ->sortByDesc('eff')
                ->first(),

            // Game Leaders Awards
            'Points Game Leader' => $eligiblePlayerStats
                ->filter(function ($stats) {
                    return $stats->points_game_leader > 0;
                })
                ->sortByDesc('points_game_leader')
                ->first(),

            'Rebounds Game Leader' => $eligiblePlayerStats
                ->filter(function ($stats) {
                    return $stats->rebounds_game_leader > 0;
                })
                ->sortByDesc('rebounds_game_leader')
                ->first(),

            'Assists Game Leader' => $eligiblePlayerStats
                ->filter(function ($stats) {
                    return $stats->assists_game_leader > 0;
                })
                ->sortByDesc('assists_game_leader')
                ->first(),

            'Blocks Game Leader' => $eligiblePlayerStats
                ->filter(function ($stats) {
                    return $stats->blocks_game_leader > 0;
                })
                ->sortByDesc('blocks_game_leader')
                ->first(),

            'Steals Game Leader' => $eligiblePlayerStats
                ->filter(function ($stats) {
                    return $stats->steals_game_leader > 0;
                })
                ->sortByDesc('steals_game_leader')
                ->first(),
        ];

        // Insert advanced awards
        foreach ($advancedAwards as $awardName => $player) {
            if ($player) {
                $description = match ($awardName) {
                    'Triple-Double Machine' => 'Player averaging triple-double for the season',
                    'Shooting Efficiency Leader' => 'Player with highest true shooting percentage (min. 300 attempts)',
                    'Player Efficiency Leader' => 'Player with highest Player Efficiency Rating (PER)',
                    'Perfect Attendance Award' => 'Player who played all games with most minutes',
                    'Most Versatile Player' => 'Player with highest efficiency rating',
                    'Points Game Leader' => 'Player with most points in a single game',
                    'Rebounds Game Leader' => 'Player with most rebounds in a single game',
                    'Assists Game Leader' => 'Player with most assists in a single game',
                    'Blocks Game Leader' => 'Player with most blocks in a single game',
                    'Steals Game Leader' => 'Player with most steals in a single game',
                    default => 'Outstanding statistical achievement'
                };

                $this->insertAward($player, $awardName, $description, $latestSeasonId);
            }
        }

        // Update season status
        DB::table('seasons')->where('id', $latestSeasonId)->update(['status' => config('timeline.awards')]);

        // Fetch awards along with player, team names, and team_id for the latest season
        $awards = DB::table('season_awards')
            ->leftJoin('players', 'season_awards.player_id', '=', 'players.id')
            ->leftJoin('teams', 'players.team_id', '=', 'teams.id')
            ->where('season_awards.season_id', $latestSeasonId)
            ->select(
                'season_awards.*',
                'players.name as player_name',
                'teams.name as team_name',
                'teams.id as team_id'
            )
            ->get();

        return response()->json([
            'message' => 'Season awards stored successfully.',
            'awards' => $awards
        ]);
    }

    public function storeSeasonAwardsAuto($request)
    {
        try {
            // Get the latest season ID
            $latestSeasonId = $request->season_id;

            // Clear existing awards for the latest season
            DB::table('season_awards')->where('season_id', $latestSeasonId)->delete();

            // Get player stats from player_season_stats for the latest season
            $playerStats = DB::table('player_season_stats')
                ->where('season_id', $latestSeasonId)
                ->get();

            // Get total number of games played in the season
            $eligiblePlayerStats = $playerStats->filter(function ($stats) use ($latestSeasonId) {
                // Check if the player has played at least 75% of the total games
                $totalGamesInSeason = $stats->total_games;
                return $stats->total_games_played >= 0.75 * $totalGamesInSeason;
            });

            // Determine the top performers based on different metrics
            $topScorer = $eligiblePlayerStats->sortByDesc('avg_points_per_game')->first();
            $topRebounder = $eligiblePlayerStats->sortByDesc('avg_rebounds_per_game')->first();
            $topPlaymaker = $eligiblePlayerStats->sortByDesc('avg_assists_per_game')->first();
            $topStealer = $eligiblePlayerStats->sortByDesc('avg_steals_per_game')->first();
            $topBlocker = $eligiblePlayerStats->sortByDesc('avg_blocks_per_game')->first();
            $bestDefender = $eligiblePlayerStats->sortByDesc(function ($stats) {
                return $stats->avg_steals_per_game + $stats->avg_blocks_per_game;
            })->first();

            // Top 5 Offensive Players
            $topOffensivePlayers = $eligiblePlayerStats->sortByDesc('avg_points_per_game')->take(5);

            // Top 5 Defensive Players
            $topDefensivePlayers = $eligiblePlayerStats->sortByDesc(function ($stats) {
                return $stats->avg_steals_per_game + $stats->avg_blocks_per_game;
            })->take(5);

            // Get previous season's stats for comparison
            $previousSeasonId = get_previous_season_id();
            $previousSeasonStats = DB::table('player_season_stats_archives')->where('season_id', $previousSeasonId)->pluck('avg_points_per_game', 'player_id');

            // Exclude rookies from the Most Improved Player award
            $nonRookies = DB::table('players')->where('is_rookie', false)->pluck('id');

            $mostImprovedPlayer = $eligiblePlayerStats->filter(function ($stats) use ($nonRookies) {
                return $nonRookies->contains($stats->player_id);
            })
                ->sortByDesc(function ($stats) use ($previousSeasonStats) {
                    $previousPoints = $previousSeasonStats[$stats->player_id] ?? 0;
                    return ($stats->avg_points_per_game - $previousPoints);
                })
                ->first();

            // Calculate MVP by sorting the players based on the weighted stats and returning the top player
            $mvp = $eligiblePlayerStats->filter(function ($stats) {
                return $stats->total_games_played >= 0.75 * $stats->total_games; // Ensure MVP has played 75% of games
            })->sort(function ($a, $b) {
                $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                    $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                    $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;

                $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                    $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                    $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;

                return $bStats <=> $aStats;
            })->first();

            // Filter out rookies and determine the Rookie of the Year award
            $rookies = $eligiblePlayerStats->filter(function ($stats) {
                // Check if the player is a rookie by comparing the draft_id and season_id
                return DB::table('players')
                    ->where('id', $stats->player_id)          // Match the player_id
                    ->where('draft_id', $stats->season_id)    // Check if draft_id matches season_id
                    ->exists();  // Return true if a record is found (i.e., player is a rookie)
            });

            // Filter rookies who have played at least 75% of games for Rookie of the Year
            $rookieOfTheYear = $rookies->filter(function ($stats) {
                return $stats->total_games_played >= 0.75 * $stats->total_games;
            })->sort(function ($a, $b) {
                $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                    $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                    $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;

                $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                    $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                    $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;

                return $bStats <=> $aStats;
            })->first();

            // Determine the 6th Man of the Year award
            $rolePlayers = $eligiblePlayerStats->filter(function ($stats) {
                return $stats->role !== 'star player' && $stats->role !== 'all star' && $stats->role !== 'starter';
            });

            $sixthManOfTheYear = $rolePlayers->sort(function ($a, $b) {
                $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                    $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                    $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;
                $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                    $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                    $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;
                return $bStats <=> $aStats;
            })->first();

            // Add these new awards before the insert awards section
            // Iron Man Award
            $ironMan = $eligiblePlayerStats->sortByDesc('total_minutes_played')->first();

            // Most Efficient Player (highest FG%)
            $mostEfficient = $eligiblePlayerStats->filter(function ($stats) {
                return $stats->total_field_goal_attempts > 200; // Minimum attempts threshold
            })->sortByDesc(function ($stats) {
                return ($stats->total_field_goals_made / $stats->total_field_goal_attempts) * 100;
            })->first();

            // Free Throw King (highest FT%)
            $freeThrowKing = $eligiblePlayerStats->filter(function ($stats) {
                return $stats->total_free_throw_attempts > 100; // Minimum attempts threshold
            })->sortByDesc(function ($stats) {
                return ($stats->total_free_throws_made / $stats->total_free_throw_attempts) * 100;
            })->first();

            // Three-Point Specialist (most 3pts made)
            $threePointKing = $eligiblePlayerStats->filter(function ($stats) {
                return $stats->total_three_point_attempts > 100; // Minimum attempts threshold
            })->sortByDesc('total_three_pointers_made')->first();

            // Double-Double Machine (most double-doubles)
            $doubleDoubleMachine = $eligiblePlayerStats->sortByDesc(function ($stats) {
                // Calculate approximate double-doubles based on averages
                $pointsDouble = $stats->avg_points_per_game >= 10 ? 1 : 0;
                $reboundsDouble = $stats->avg_rebounds_per_game >= 10 ? 1 : 0;
                $assistsDouble = $stats->avg_assists_per_game >= 10 ? 1 : 0;
                return $pointsDouble + $reboundsDouble + $assistsDouble;
            })->first();

            // Insert awards into season_awards table if not already present
            $this->insertAward($topScorer, 'Top Scorer', 'Player with the highest average points per game', $latestSeasonId);
            $this->insertAward($topRebounder, 'Top Rebounder', 'Player with the highest average rebounds per game', $latestSeasonId);
            $this->insertAward($topPlaymaker, 'Top Playmaker', 'Player with the highest average assists per game', $latestSeasonId);
            $this->insertAward($topStealer, 'Top Stealer', 'Player with the highest average steals per game', $latestSeasonId);
            $this->insertAward($topBlocker, 'Top Blocker', 'Player with the highest average blocks per game', $latestSeasonId);
            $this->insertAward($bestDefender, 'Best Defensive Player', 'Player with the highest combined average steals and blocks per game', $latestSeasonId);
            $this->insertAward($mvp, 'Best Overall Player', 'Player with the best overall performance score', $latestSeasonId);
            $this->insertAward($mostImprovedPlayer, 'Most Improved Player', 'Player with the highest increase in average points per game from the previous season', $latestSeasonId);

            // Insert the Rookie of the Season award
            if ($rookieOfTheYear &&  $latestSeasonId > 1) {
                $this->insertAward($rookieOfTheYear, 'Rookie of the Season', 'Best rookie player of the season', $latestSeasonId);
            }

            // Insert the 6th Man of the Year award
            if ($sixthManOfTheYear &&  $latestSeasonId > 1) {
                $this->insertAward($sixthManOfTheYear, '6th Man of the Year', 'Best player coming off the bench', $latestSeasonId);
            }

            // Insert Top 5 Offensive Players awards
            $counter = 1;
            foreach ($topOffensivePlayers as $player) {
                if ($counter > 5) break;
                $this->insertAward($player, 'Top ' . $counter . ' Offensive Player', 'Player ranked ' . $counter . ' in average points per game', $latestSeasonId);
                $counter++;
            }

            // Insert Top 5 Defensive Players awards
            $counter = 1;
            foreach ($topDefensivePlayers as $player) {
                if ($counter > 5) break;
                $this->insertAward($player, 'Top ' . $counter . ' Defensive Player', 'Player ranked ' . $counter . ' in combined average steals and blocks per game', $latestSeasonId);
                $counter++;
            }

            // Add these to your existing awards insertion section
            $this->insertAward($ironMan, 'Iron Man of the Year', 'Player with most minutes played in the season', $latestSeasonId);
            $this->insertAward($mostEfficient, 'Most Efficient Player', 'Player with highest field goal percentage (min. 200 attempts)', $latestSeasonId);
            $this->insertAward($freeThrowKing, 'Free Throw King', 'Player with highest free throw percentage (min. 100 attempts)', $latestSeasonId);
            $this->insertAward($threePointKing, 'Three-Point Specialist', 'Player with most three-pointers made (min. 100 attempts)', $latestSeasonId);
            $this->insertAward($doubleDoubleMachine, 'Double-Double Machine', 'Player with most double-double combinations', $latestSeasonId);

            // Add these new award calculations before the insertion section
            // Advanced Statistical Awards
            $advancedAwards = [
                // Most Complete Player (Triple-Double Machine)
                'Triple-Double Machine' => $eligiblePlayerStats->filter(function ($stats) {
                    return $stats->avg_points_per_game >= 10 &&
                        $stats->avg_rebounds_per_game >= 10 &&
                        $stats->avg_assists_per_game >= 10;
                })->first(),

                // Best Shooter (True Shooting Percentage Leader)
                'Shooting Efficiency Leader' => $eligiblePlayerStats
                    ->filter(function ($stats) {
                        return $stats->total_field_goal_attempts >= 300;
                    })
                    ->sortByDesc('ts_percent')
                    ->first(),

                // Most Efficient Player (PER Leader)
                'Player Efficiency Leader' => $eligiblePlayerStats
                    ->sortByDesc('per')
                    ->first(),

                // Best All-Around Player (Based on EFF)
                'Most Versatile Player' => $eligiblePlayerStats
                    ->sortByDesc('eff')
                    ->first(),

                // Game Leaders Awards
                'Points Game Leader' => $eligiblePlayerStats
                    ->filter(function ($stats) {
                        return $stats->points_game_leader > 0;
                    })
                    ->sortByDesc('points_game_leader')
                    ->first(),

                'Rebounds Game Leader' => $eligiblePlayerStats
                    ->filter(function ($stats) {
                        return $stats->rebounds_game_leader > 0;
                    })
                    ->sortByDesc('rebounds_game_leader')
                    ->first(),

                'Assists Game Leader' => $eligiblePlayerStats
                    ->filter(function ($stats) {
                        return $stats->assists_game_leader > 0;
                    })
                    ->sortByDesc('assists_game_leader')
                    ->first(),

                'Blocks Game Leader' => $eligiblePlayerStats
                    ->filter(function ($stats) {
                        return $stats->blocks_game_leader > 0;
                    })
                    ->sortByDesc('blocks_game_leader')
                    ->first(),

                'Steals Game Leader' => $eligiblePlayerStats
                    ->filter(function ($stats) {
                        return $stats->steals_game_leader > 0;
                    })
                    ->sortByDesc('steals_game_leader')
                    ->first(),
            ];

            // Insert advanced awards
            foreach ($advancedAwards as $awardName => $player) {
                if ($player) {
                    $description = match ($awardName) {
                        'Triple-Double Machine' => 'Player averaging triple-double for the season',
                        'Shooting Efficiency Leader' => 'Player with highest true shooting percentage (min. 300 attempts)',
                        'Player Efficiency Leader' => 'Player with highest Player Efficiency Rating (PER)',
                        'Most Versatile Player' => 'Player with highest efficiency rating',
                        'Points Game Leader' => 'Player with most points in a single game',
                        'Rebounds Game Leader' => 'Player with most rebounds in a single game',
                        'Assists Game Leader' => 'Player with most assists in a single game',
                        'Blocks Game Leader' => 'Player with most blocks in a single game',
                        'Steals Game Leader' => 'Player with most steals in a single game',
                        default => 'Outstanding statistical achievement'
                    };

                    $this->insertAward($player, $awardName, $description, $latestSeasonId);
                }
            }

            // Update season status
            DB::table('seasons')->where('id', $latestSeasonId)->update(['status' => 12]);

            // Fetch awards along with player, team names, and team_id for the latest season
            $awards = DB::table('season_awards')
                ->leftJoin('players', 'season_awards.player_id', '=', 'players.id')
                ->leftJoin('teams', 'players.team_id', '=', 'teams.id')
                ->where('season_awards.season_id', $latestSeasonId)
                ->select(
                    'season_awards.*',
                    'players.name as player_name',
                    'teams.name as team_name',
                    'teams.id as team_id' // Include team_id in the select clause
                )
                ->get();

            return response()->json([
                'message' => 'Season awards stored successfully.',
                'awards' => $awards,
                'season_id' =>  $latestSeasonId,
            ]);
        } catch (\Exception $e) {
            // Return an error response
            return response()->json([
                'message' => 'An error occurred while storing the season awards.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFinalsMVPList()
    {
        // Fetch data from the view table directly
        $mvpList = DB::table('finals_mvp_with_stats')  // Assuming this is the name of the view
            ->select(
                'player_id',
                'player_name',
                'player_role',
                'current_team_names',
                'mvp_winning_team_names',
                'awards_won',
                'is_active',
                'total_games',
                'total_games_played',
                'avg_minutes_per_game',
                'avg_points_per_game',
                'avg_rebounds_per_game',
                'avg_assists_per_game',
                'avg_steals_per_game',
                'avg_blocks_per_game',
                'avg_turnovers_per_game',
                'avg_fouls_per_game',
                'total_points',
                'total_rebounds',
                'total_assists',
                'total_steals',
                'total_blocks',
                'total_turnovers',
                'total_fouls',
                'stats_created_at',
                'stats_updated_at'
            )
            ->where('player_id', '!=', null)
            ->orderByDesc('stats_created_at')  // Ensure it's ordered by most recent stats
            ->get();

        // Return the data as a JSON response
        return response()->json($mvpList);
    }

    private function insertAward($playerStats, $awardName, $awardDescription, $seasonId)
    {
        if ($playerStats) {
            try {
                DB::beginTransaction();

                $conferenceId = $this->helper->getTeamConferenceId($playerStats->team_id);

                // Insert award
                DB::table('season_awards')->updateOrInsert(
                    [
                        'player_id' => $playerStats->player_id,
                        'team_id' => $playerStats->team_id,
                        'conference_id' => $conferenceId,
                        'season_id' => $seasonId,
                        'award_name' => $awardName,
                    ],
                    [
                        'award_description' => $awardDescription,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Update player contract and add transaction record
                // $this->upsertCurrentSeasonStoryline();
                // $this->processAwardContractExtension($playerStats, $awardName, $seasonId);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }
    }

    public function insertAllStarSchedule($round)
    {
        $seasonId = get_current_season_id();
        $scheduleTable = $this->helper->getScheduleDBName($seasonId);
        $game_id = 'S'.$seasonId.'-'.$round;
        $pair = ($round == 'all-star') ? [1,2] : [3,4];

        $homeTeamAssigned = $seasonId % 2 == 0 ? $pair[0] : $pair[1];
        $awayTeamAssigned = $seasonId % 2 == 0 ? $pair[1] : $pair[0];
                
        $schedule[] = [
            'home_id' => $homeTeamAssigned,
            'conference_id' => $round,
            'game_id' => $game_id,
            'away_id' =>$awayTeamAssigned,
            'season_id' => $seasonId,
            'round' => $round,
            'home_score' => 0,
            'away_score' => 0,
            'winner_id' => 0,
            // Add more fields as needed, such as date and time
        ];

        // Start a database transaction
        DB::transaction(function () use ($scheduleTable, $seasonId, $round, $schedule) {
            // Filter out any matches with duplicate game_id values that already exist in the database
            $existingGameIds = DB::table($scheduleTable)
                ->whereIn('game_id', array_column($schedule, 'game_id'))
                ->pluck('game_id')
                ->toArray();

            // If any game_ids already exist, throw an exception or return an error response
            if (!empty($existingGameIds)) {
                $existingGameIdsStr = implode(', ', $existingGameIds);
                return response()->json([
                    'success' => false,
                    'message' => "Duplicate game_id(s) found: {$existingGameIdsStr}. No schedules were inserted.",
                ], 500);
                // throw new \Exception("Duplicate game_id(s) found: {$existingGameIdsStr}. No schedules were inserted.");
            }

            // Insert schedule entries into the database
            DB::table('schedules')->insert($schedule);

            
            // Prepare player game stats entries
            $playerGameStats = [];
            $northAllStarsConference = [1,2];
            $southAllStarsConference = [2,3];

            foreach ($schedule as $match) {
                // Fetch players for home and away teams
                $homeTeam = $seasonId % 2 == 0 ? $northAllStarsConference : $southAllStarsConference;
                $awayTeam = $seasonId % 2 == 0 ? $southAllStarsConference : $southAllStarsConference;
                
                $homeTeamPlayers = DB::table('season_awards')
                    ->where('award_name',$round)
                    ->whereIn('conference_id',$homeTeam)
                    ->where('season_id',$seasonId)
                    ->get();

                $awayTeamPlayers = DB::table('season_awards')
                    ->where('award_name',$round)
                    ->whereIn('conference_id',$awayTeam)
                    ->where('season_id',$seasonId)
                    ->get();

                // Create player game stats entries for home team players
                foreach ($homeTeamPlayers as $player) {
                    $playerGameStats[] = [
                        'player_id' => $player->player_id,
                        'season_id' => $seasonId,
                        'game_id' => $match['game_id'],
                        'team_id' => $match['home_id'],
                        'points' => 0,
                        'rebounds' => 0,
                        'assists' => 0,
                        'steals' => 0,
                        'blocks' => 0,
                        'turnovers' => 0,
                        'fouls' => 0,
                    ];
                }

                // Create player game stats entries for away team players
                foreach ($awayTeamPlayers as $player) {
                    $playerGameStats[] = [
                        'player_id' => $player->player_id,
                        'season_id' => $seasonId,
                        'game_id' => $match['game_id'],
                        'team_id' => $match['away_id'],
                        'points' => 0,
                        'rebounds' => 0,
                        'assists' => 0,
                        'steals' => 0,
                        'blocks' => 0,
                        'turnovers' => 0,
                        'fouls' => 0,
                    ];
                }
            }

            // Insert player game stats entries into the database
            if (!empty($playerGameStats)) {
                DB::table('player_game_stats')->insert($playerGameStats);
            }
        });
    }

    public function updateAllStarCoach($seasonId)
    {

        $bestCoach = DB::table('standings_view as sv')
            ->select('teams.coach_id','coaches.name as coach_name','teams.name as team_name','teams.city as team_city')
            ->join('teams','teams.id','=','sv.team_id')
            ->join('conferences','conferences.id','=','teams.conference_id')
            ->join('coaches','coaches.id','=','teams.coach_id')
            ->where('sv.season_id', $seasonId)
            ->orderBy('overall_rank','asc')
            ->limit(4)
            ->get();

        if($bestCoach){
            $teamId = 0;
            foreach ($bestCoach as $coach) {
                # code...
                $teamId++;

                DB::table('teams')
                    ->where('id',$teamId)
                    ->update([
                        'coach_id' => $coach->coach_id
                    ]);

                $teamInfo = DB::table('teams')
                                ->select('name','city')
                                ->where('team_id',$teamId)
                                ->first();

                //log tansactions
                DB::table('transactions')->insert([
                    'player_id' => 0,
                    'season_id' => $seasonId,
                    'details' => $coach->coach_name . ' has been appointed to coach the ' . $teamInfo->city.' '.$teamInfo->name,
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'appointed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if($teamId > 4){
                    break;
                }
                
            }
        }
       
    }
    
}

