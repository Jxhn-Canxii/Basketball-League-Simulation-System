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
            ->orderBy('season_awards.season_id','desc')
            ->orderBy('season_awards.id','desc')
            ->get();


        return response()->json([
            'message' => 'Season awards stored successfully.',
            'awards' => $awards
        ]);
    }

    public function getTeamAwards($request)
    {

        // Fetch awards along with player, team, and season names for the latest season
        $awards = DB::table('season_awards')
            ->leftJoin('players', 'season_awards.player_id', '=', 'players.id')
            ->leftJoin('teams', 'season_awards.team_id', '=', 'teams.id')
            ->leftJoin('seasons', 'season_awards.season_id', '=', 'seasons.id') // Join the seasons table
            ->where('season_awards.team_id', $request->team_id)
            ->select(
                'season_awards.*',
                'players.name as player_name',
                'teams.name as team_name',
                'seasons.name as season_name' // Select the season name
            )
            ->orderBy('season_awards.season_id','desc')
            ->orderBy('season_awards.id','desc')
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

        $query = DB::table('season_awards')
                ->leftJoin('players', 'season_awards.player_id', '=', 'players.id')
                ->leftJoin('teams', 'season_awards.team_id', '=', 'teams.id')
                ->leftJoin('teams as drafted_team', 'players.drafted_team_id', '=', 'drafted_team.id') // Fixed alias usage
                ->leftJoin('seasons', 'season_awards.season_id', '=', 'seasons.id') // Join the seasons table
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
                );

                // Fetch awards along with player and team names for the updated season
                if ($seasonId > 0) {
                    $query->where('season_awards.season_id', $seasonId);
                }
                if($awardsName != 0) {
                    $query->where('season_awards.award_name', $awardsName);
                } 

            $awards = $query->orderBy('season_awards.season_id','desc')
                            ->orderBy('teams.name','asc')
                            ->orderBy('season_awards.id','desc')
                            ->get();

        return response()->json([
            'message' => 'Team IDs in season awards updated successfully for season ' . $seasonId,
            'awards' => $awards
        ]);
    }

    public function storeSeasonAwards()
    {
        // Get the latest season ID
        $latestSeasonId = get_current_season_id() ?? 0;

        // Clear existing awards for the latest season
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
        $previousSeasonStats = DB::table('player_season_stats_archives')->where('season_id', $previousSeasonId)->pluck('eff', 'player_id');

        // Exclude rookies from the Most Improved Player award
        $nonRookies = DB::table('players')->where('is_rookie', false)->pluck('id');

        $mostImprovedPlayer = $eligiblePlayerStats->filter(function ($stats) use ($nonRookies) {
            return $nonRookies->contains($stats->player_id);
        })
            ->sortByDesc(function ($stats) use ($previousSeasonStats) {
                $previousPoints = $previousSeasonStats[$stats->player_id] ?? 0;
                return ($stats->eff - $previousPoints);
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
            return $stats->total_games_started <= ($stats->total_games * 0.25);
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

        $tripleCrown = DB::table('player_season_stats as ps')
                    ->select('ps.*')
                    ->join('players','players.id','=','ps.player_id')
                    ->join('playoff_series as c', function ($join) {
                        $join->on('c.winner_team_id', '=', 'ps.team_id')
                            ->on('c.season_id', '=', 'ps.season_id');
                    })
                    ->join('seasons as o', function ($join) {
                        $join->on('o.champion_id', '=', 'ps.team_id')
                            ->on('o.id', '=', 'ps.season_id');
                    })
                    ->join('seasons as f', function ($join) {
                        $join->on('f.finals_winner_id', '=', 'ps.team_id')
                            ->on('f.id', '=', 'ps.season_id');
                    })
                    ->where('c.round','semi_finals')
                    ->where('ps.season_id',$latestSeasonId)
                    ->where('ps.is_cut',0)
                    ->where('o.id',$latestSeasonId)
                    ->where('f.id',$latestSeasonId)
                    ->get();

        foreach($tripleCrown as $player){
            $awardName = 'Triple-Crown Champion';
            $description = 'A super-rare feat where both the team won Conference Champions, Overall National Champions, National Finals Champions in one season.';
            
            $this->insertAward($player, $awardName, $description, $latestSeasonId);
        }

        $this->insertAward($sixthManOfTheYear, 'Sixth Man of the Year', 'Best player coming off the bench', $latestSeasonId);

        // Only insert these awards if it's not season 1
        if ($latestSeasonId > 1) {
            // Most Improved Player (needs previous season stats)
            $this->insertAward($mostImprovedPlayer, 'Most Improved Player', 'Player with the highest increase in average points per game from the previous season', $latestSeasonId);

            // Rookie of the Year (not applicable in season 1)
            if ($rookieOfTheYear) {
                $this->insertAward($rookieOfTheYear, 'Rookie of the Season', 'Best rookie player of the season', $latestSeasonId);
            }
        }

        $this->insertAward($bestDefender, 'Best Defensive Player', 'Player with the highest combined average steals and blocks per game', $latestSeasonId);
        $this->insertAward($mvp, 'Best Overall Player', 'Player with the best overall performance score', $latestSeasonId);


        return response()->json([
            'message' => 'Season awards stored successfully.',
        ]);
    }

    public function endAwardsWindow(){
         // Update season status
        $latestSeasonId = get_current_season_id();

        $this->storeSeasonAwards();

        DB::table('seasons')->where('id', $latestSeasonId)->update(['status' => config('timeline.awards')]);

        return response()->json([
            'message' => 'Award window ended!',
        ]);

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

    public function insertAward($playerStats, $awardName, $awardDescription, $seasonId)
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
            
        });
    }

    public function selectAllStarsForConference($allStarGroup,$conferenceGroup)
    {
        // Get the latest season ID
        $latestSeasonId = get_current_season_id() ?? 0;

        // Get player stats from player_season_stats for the latest season
        $playerStats = DB::table('player_season_stats as pss')
            ->join('teams','teams.id','=','pss.team_id')
            ->join('players','players.id','=','pss.player_id')
            ->join('player_game_highs','player_game_highs.player_id','=','pss.player_id')
            ->select('players.is_rookie','players.is_injured','player_game_highs.all_star_count','players.position','players.draft_status','players.id as player_id','players.name as player_name','teams.city as team_city','teams.name as team_name','teams.primary_color as primary_color','teams.secondary_color as secondary_color','teams.id as team_id','teams.acronym as team_acronym','pss.*')
            ->join('conferences','conferences.id','=','teams.conference_id')
            ->where('pss.season_id', $latestSeasonId)
            ->where('players.is_injured', 0)
            ->whereIn('teams.conference_id',$conferenceGroup)
            ->get();

        $eligiblePlayerStats = $playerStats->filter(function ($stats) {
            return (int)$stats->total_games_played > 5 && !$stats->is_rookie && !$stats->is_injured;
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
        })->take(15)->values();

        // Filter out rookies and determine the Rookie of the Year award
        $rookies = $playerStats->filter(function ($stats) {
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
        })->take(15)->values();

        // Only insert these awards if it's not season 1
        if ($latestSeasonId > 1) {
        
            // Rookie of the Year (not applicable in season 1)
            if ($topRookies) {
                $rookieRankings = 0;
                foreach ($topRookies as $rookieStats) {
                    $rank = $rookieRankings++;
                    $role =  $rank >= 2 || $rank <= 5 ? 'starter' : 'reserve';
                    $role = $rank == 1 ? 'captain' : $role;

                    $this->insertAward($rookieStats,'all-rookie', 'All-Rookie '.$role.' of the '.$allStarGroup.' All-Rookie Select!',$latestSeasonId);

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
                $role =  $rank >= 2 || $rank <= 5 ? 'starter' : 'reserve';
                $role = $rank == 1 ? 'captain' : $role;

                $this->insertAward($allStarStats, 'all-star', 'All-star '.$role.' of the '.$allStarGroup.' All-stars.',$latestSeasonId);

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

                $this->updatePlayerAllStarCount($allStarStats->player_id);
            }

            $this->insertAllStarSchedule('all-star');

        }

        return response()->json([
            'message' => 'All Star stored successfully.',
        ]);
    }

    public function selectAllStarsForConferenceCandidate($allStarGroup,$conferenceGroup, $latestSeasonId)
    {
    
        $seasonStatsDB = $this->helper->getSeasonStatsDBName($latestSeasonId);

        $seasonStatus = $this->helper->seasonStatus($latestSeasonId);
        // Get player stats from player_season_stats for the latest season
        
        if($seasonStatus == 1){
            $playerStats = DB::table($seasonStatsDB.' as pss')
                ->join('teams','teams.id','=','pss.team_id')
                ->join('players','players.id','=','pss.player_id')
                ->join('player_game_highs','player_game_highs.player_id','=','pss.player_id')
                ->select('players.is_rookie','players.is_injured','player_game_highs.all_star_count','players.position','players.draft_status','players.id as player_id','players.name as player_name','teams.city as team_city','teams.name as team_name','teams.primary_color as primary_color','teams.secondary_color as secondary_color','teams.acronym as team_acronym','pss.*')
                ->join('conferences','conferences.id','=','teams.conference_id')
                ->where('pss.season_id', $latestSeasonId)
                ->where('players.is_injured', 0)
                ->whereIn('teams.conference_id',$conferenceGroup)
                ->get();
        }
        else{

            $playerStats = DB::table($seasonStatsDB.' as pss')
                ->join('teams','teams.id','=','pss.team_id')
                ->join('players','players.id','=','pss.player_id')
                ->join('player_game_highs','player_game_highs.player_id','=','pss.player_id')
                ->select('players.is_rookie','players.is_injured','player_game_highs.all_star_count','players.position','players.draft_status','players.id as player_id','players.name as player_name','teams.city as team_city','teams.name as team_name','teams.primary_color as primary_color','teams.secondary_color as secondary_color','teams.acronym as team_acronym','pss.*')
                ->join('conferences','conferences.id','=','teams.conference_id')
                ->join('season_awards','season_awards.player_id','=','pss.player_id')
                ->where('pss.season_id', $latestSeasonId)
                ->where('season_awards.season_id', $latestSeasonId)
                ->whereIn('season_awards.award_name',['all-star','all-rookie'])
                ->whereIn('teams.conference_id',$conferenceGroup)
                ->get();
        }

        $eligiblePlayerStats = $playerStats->filter(function ($stats) {
            return (int)$stats->total_games_played > 5 && !$stats->is_rookie && !$stats->is_injured;
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
        })->take(15)->values();

        // Filter out rookies and determine the Rookie of the Year award
        $rookies = $playerStats->filter(function ($stats) {
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
        })->take(15)->values();

        return [
            'message' => 'All Star Candidates for '.$allStarGroup,
            'all_rookie' => $topRookies,
            'all_star' => $allStars,
            'source' => $seasonStatsDB,
            'all_rookie_count' => count($topRookies),
            'all_star_count' =>  count($allStars),
        ];
    }

    public function updateAllStarCoach($seasonId,$conferenceGroup,$teamIds)
    {

        $bestCoach = DB::table('standings_view as sv')
            ->select('teams.coach_id','coaches.name as coach_name','coaches.coach_iq','teams.name as team_name','teams.city as team_city')
            ->join('teams','teams.id','=','sv.team_id')
            ->join('conferences','conferences.id','=','teams.conference_id')
            ->join('coaches','coaches.id','=','teams.coach_id')
            ->where('sv.season_id', $seasonId)
            ->whereIn('teams.conference_id', $conferenceGroup)
            ->orderBy('overall_rank','asc')
            ->limit(2)
            ->get();

        if($bestCoach){
            $teamId = -1; 
            foreach ($bestCoach as $coach) {
                # code...
                $teamId++;
             
                DB::table('teams')
                    ->where('id',$teamIds[$teamId])
                    ->update([
                        'coach_id' => $coach->coach_id
                    ]);

                DB::table('team_season_info')
                    ->where('team_id',$teamIds[$teamId])
                    ->where('season_id',$seasonId)
                    ->update([
                        'coach_id' => $coach->coach_id,
                        'coach_iq' => $coach->coach_iq,
                    ]);

                $teamInfo = DB::table('teams')
                            ->select('name','city')
                            ->where('id',$teamIds[$teamId])
                            ->first();

                //log tansactions
                DB::table('transactions')->insert([
                    'player_id' => 0,
                    'season_id' => $seasonId,
                    'details' => $coach->coach_name . ' has been appointed to coach the ' . $teamInfo->city.' '.$teamInfo->name,
                    'from_team_id' => 0,
                    'to_team_id' => $teamIds[$teamId],
                    'status' => 'appointed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);                
            }
        }
    
    }

    private function updatePlayerAllStarCount($playerId){

        $latestSeasonId = get_current_season_id();

        $award = DB::table('season_awards')
                ->where('award_name','all-star')
                ->where('player_id',$playerId)
                ->select(
                    'player_id',
                    DB::raw('COUNT(id) as all_star_count')
                )
                ->groupBy('player_id')
                ->first();
        
        DB::table('player_game_highs')->where('player_id',$playerId)->update(['all_star_count' => $award->all_star_count]);

    }
    
}

