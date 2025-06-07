<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seasons;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DraftController extends Controller
{
    public function index()
    {
        return Inertia::render('Draft/Index', [
            'status' => session('status'),
        ]);
    }
    /**
     * Store aggregated stats of a player's performance for a season in the player_season_stats table.
     * If 'is_last' is true, update the latest season's status to 9.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function draftOrder()
    {
        $latestSeasonId = get_current_season_id();
        $currentSeasonId = $latestSeasonId + 1;

        // === Check if draft already exists ===
        $existingDraft = DB::table('drafts')
            ->join('teams', 'drafts.team_id', '=', 'teams.id')
            ->where('drafts.season_id', $currentSeasonId)
            ->select(
                'drafts.round',
                'drafts.pick_number as pick',
                'teams.name as team_name',
                'drafts.team_id',
                'drafts.draft_status',
                'drafts.player_id'
            )
            ->orderBy('drafts.round')
            ->orderBy('drafts.pick_number')
            ->get();


        if ($existingDraft->isNotEmpty()) {
            return response()->json([
                'season_id' => $currentSeasonId,
                'draft_order' => $existingDraft,
                'message' => 'Draft already exists for this season.',
            ]);
        }

        // === Step 1: Get all teams for the latest season (ordered worst to best) ===
        $allTeams = DB::table('standings_view')
            ->select('team_id', 'team_name', 'wins', 'losses', 'overall_rank')
            ->where('season_id', $latestSeasonId)
            ->orderBy('overall_rank', 'desc')
            ->get();

        // === Step 2: Separate lottery teams (bottom 14) and rest ===
        $lotteryTeams = $allTeams->take(14);
        $nonLotteryTeams = $allTeams->slice(14);

        // === Step 3: Define draft lottery odds (NBA-style) ===
        $lotteryOdds = [140, 140, 140, 125, 105, 90, 75, 60, 45, 30, 20, 15, 10, 5];

        // === Step 4: Lottery function (weighted random draw) ===
        function weightedRandom(array $items)
        {
            $rand = mt_rand(1, array_sum(array_column($items, 'weight')));
            foreach ($items as $item) {
                if ($rand <= $item['weight']) {
                    return $item;
                }
                $rand -= $item['weight'];
            }
        }

        // === Step 5: Build weighted pool for lottery draw ===
        $weightedPool = [];
        foreach ($lotteryTeams as $i => $team) {
            $weightedPool[] = [
                'team' => $team,
                'weight' => $lotteryOdds[$i],
            ];
        }

        // === Step 6: Simulate top 4 lottery picks ===
        $topPicks = [];
        $selectedTeamIds = [];
        while (count($topPicks) < 4) {
            $winner = weightedRandom($weightedPool);
            $teamId = $winner['team']->team_id;
            if (!in_array($teamId, $selectedTeamIds)) {
                $topPicks[] = $winner['team'];
                $selectedTeamIds[] = $teamId;
            }
        }

        // === Step 7: Remaining lottery teams (ordered by rank, excluding top 4) ===
        $remainingLotteryTeams = $lotteryTeams->filter(function ($team) use ($selectedTeamIds) {
            return !in_array($team->team_id, $selectedTeamIds);
        });

        // === Step 8: Merge full round 1 order: top 4 + remaining lottery + rest ===
        $firstRoundOrder = array_merge(
            $topPicks,
            $remainingLotteryTeams->values()->all(),
            $nonLotteryTeams->values()->all()
        );

        // === Step 9: Build full draft (2 rounds) and insert into DB ===
        $twoRoundDraftOrder = [];

        foreach ([1, 2] as $round) {
            foreach ($firstRoundOrder as $pickIndex => $team) {
                $pickNumber = $pickIndex + 1;
                $draftStatus = "S{$currentSeasonId} R{$round} P{$pickNumber}";

                $twoRoundDraftOrder[] = [
                    'round' => $round,
                    'pick' => $pickNumber,
                    'team_id' => $team->team_id,
                    'team_name' => $team->team_name,
                    'wins' => $team->wins,
                    'losses' => $team->losses,
                    'overall_rank' => $team->overall_rank,
                    'draft_status' => $draftStatus,
                ];

                DB::table('drafts')->insert([
                    'team_id' => $team->team_id,
                    'player_id' => 0,
                    'season_id' => $currentSeasonId,
                    'round' => $round,
                    'pick_number' => $pickNumber,
                    'draft_status' => $draftStatus,
                ]);
            }
        }

        return response()->json([
            'season_id' => $currentSeasonId,
            'draft_order' => $twoRoundDraftOrder,
            'message' => 'Draft successfully generated.',
        ]);
    }

    public function draftPlayers()
    {
        DB::beginTransaction();
        $draftResults = [];
        
        try {
            $latestSeasonId = get_current_season_id();
            $currentSeasonId = $latestSeasonId + 1;
        
            $teamCount = DB::table('teams')->count();
        
            // Get draft order already set in the `drafts` table
            $draftOrder = DB::table('drafts')
                ->where('season_id', $currentSeasonId)
                ->orderBy('round')
                ->orderBy('pick_number')
                ->get();

            $draftPlayerCountLimit = count($draftOrder);
            
            $draftPlayerCountLimit = (float) $draftPlayerCountLimit + 20; // Limit to 60 players for the draft
            // Get rookie players eligible for drafting
            $availablePlayers = collect(DB::table('players')
                ->where('is_rookie', 1)
                ->where('team_id', 0)
                ->where('draft_id', $currentSeasonId)
                ->where('is_drafted', 0)
                ->orderBy('overall_rating', 'desc')
                ->get());

            // $availablePlayers = $topPlayers->shuffle(); // Shuffle to randomize player selection


    
            // If there aren't enough rookies for the draft, return an error
            if ($availablePlayers->count() < $draftPlayerCountLimit) {
                return response()->json([
                    'error' => true,
                    'message' => 'Not enough rookies available for the draft.',
                ], 400);
            }
    
            // Track team position needs (e.g., PG, SG, C, etc.)
            $teamPositionNeeds = [];
            foreach ($draftOrder as $pick) {
                $teamId = $pick->team_id;
                $team = DB::table('teams')->where('id', $teamId)->first();
                $teamPositionNeeds[$teamId] = $this->getTeamPositionNeeds($teamId); // Fetch team’s position needs
            }
        
            // Draft players
            foreach ($draftOrder as $pick) {
                if ($availablePlayers->isEmpty()) break;
        
                $teamId = $pick->team_id;
                $team = DB::table('teams')->where('id', $teamId)->first();
    
                // Get the team’s needed position from the position needs array
                $neededPositions = array_keys($teamPositionNeeds[$teamId] ?? []);

                $selectedPlayer = $availablePlayers->first(function ($player) use ($neededPositions) {
                    foreach ($neededPositions as $pos) {
                        if (strpos($player->position, $pos) !== false) {
                            return true;
                        }
                    }
                    return false;
                });

                // If no player matched need, get best available
                if (!$selectedPlayer) {
                    $selectedPlayer = $availablePlayers->shift();
                } else {
                    // Remove the selected player manually from availablePlayers
                    $availablePlayers = $availablePlayers->reject(fn($p) => $p->id === $selectedPlayer->id)->values();
                }


    
                // If no player matches the exact need, select the best available player
                if (!$selectedPlayer) {
                    $selectedPlayer = $availablePlayers->shift();
                }
    
                // Random contract assignment based on round and pick number
                $contractYears = $pick->round === 1
                    ? ($pick->pick_number <= 10 ? rand(3, 5) : rand(1, 4))
                    : rand(1, 2);
    
                $teamHasSpace = DB::table('players')
                    ->where('team_id', $teamId)
                    ->count() < 15;
    
                $playerTeamId = $teamHasSpace ? $teamId : 0;
                $contract = $teamHasSpace ? $contractYears : 0;
    
                // Update player information
                DB::table('players')->where('id', $selectedPlayer->id)->update([
                    'team_id' => $playerTeamId,
                    'drafted_team_id' => $teamId,
                    'is_drafted' => 1,
                    'draft_order' => $pick->pick_number,
                    'draft_status' => $pick->draft_status,
                    'contract_years' => $contract,
                ]);
    
                // Update draft table with the selected player
                DB::table('drafts')->where([
                    'season_id' => $currentSeasonId,
                    'round' => $pick->round,
                    'pick_number' => $pick->pick_number,
                ])->update([
                    'player_id' => $selectedPlayer->id,
                ]);
    
                // Log the draft transaction
                DB::table('transactions')->insert([
                    'player_id' => $selectedPlayer->id,
                    'season_id' => $currentSeasonId,
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'draft',
                    'details' => "Drafted by {$team->name} in round {$pick->round}, pick {$pick->pick_number}",
                ]);
    
                // If the team has space, sign the player to a rookie contract
                if ($teamHasSpace) {
                    DB::table('transactions')->insert([
                        'player_id' => $selectedPlayer->id,
                        'season_id' => $currentSeasonId,
                        'from_team_id' => 0,
                        'to_team_id' => $teamId,
                        'status' => 'signed',
                        'details' => "Signed by {$team->name} to rookie contract ({$contract} years)",
                    ]);
                }
    
                // Track the draft results
                $draftResults[] = [
                    'team_id' => $teamId,
                    'player_id' => $selectedPlayer->id,
                    'player_name' => $selectedPlayer->name,
                    'position' => $selectedPlayer->position,
                    'age' => $selectedPlayer->age,
                    'archetype' => $selectedPlayer->type,
                    'overall_rating' => $selectedPlayer->overall_rating,
                    'team_name' => $team->name,
                    'draft_id' => $currentSeasonId,
                    'draft_order' => $pick->pick_number,
                    'draft_status' => $pick->draft_status,
                    'round' => $pick->round,
                    'pick_number' => $pick->pick_number,
                ];
    
                // Update the team’s position needs after drafting the player
                $teamPositionNeeds[$teamId] = $this->updateTeamPositionNeeds($teamPositionNeeds[$teamId], $selectedPlayer->position);
            }
        
            // Update remaining rookies that were undrafted
            DB::table('players')
                ->where('draft_id', $currentSeasonId)
                ->where('is_drafted', 0)
                ->update([
                    'team_id' => 0,
                    'contract_years' => 0,
                    'draft_status' => 'Undrafted',
                    'is_rookie' => 1,
                ]);
        
            // Update season status to post-draft
            DB::table('seasons')
                ->where('id', $latestSeasonId)
                ->update(['status' => config('timeline.draft')]);
        
            DB::commit();
        
            return response()->json([
                'error' => false,
                'season_id' => $currentSeasonId,
                'draft_results' => $draftResults,
                'message' => 'Draft completed successfully.',
            ], 200);
        
        } catch (\Exception $e) {
            DB::rollBack();
        
            \Log::error('Drafting failed', ['exception' => $e]);
        
            return response()->json([
                'error' => true,
                'message' => 'Drafting failed.',
                'error_message' => $e->getMessage(),
            ], 500);
        }
    }
    
    private function getTeamPositionNeeds($teamId)
    {
        // Minimum required count for each main position
        $required = [
            'PG' => 3,
            'SG' => 3,
            'SF' => 3,
            'PF' => 3,
            'C'  => 3,
        ];
    
        // Initialize counters
        $positionCount = [
            'PG' => 0,
            'SG' => 0,
            'SF' => 0,
            'PF' => 0,
            'C'  => 0,
        ];
    
        // Fetch players on the team
        $roster = DB::table('players')->where('team_id', $teamId)->get();
    
        foreach ($roster as $player) {
            $positions = explode('/', $player->position);
            foreach ($positions as $pos) {
                if (isset($positionCount[$pos])) {
                    $positionCount[$pos]++;
                }
            }
        }
    
        // Find unmet needs
        $needs = [];
        foreach ($required as $pos => $minCount) {
            if ($positionCount[$pos] < $minCount) {
                $needs[$pos] = $minCount - $positionCount[$pos];
            }
        }
    
        return $needs; // returns ['PG' => 1, 'C' => 2] etc.
    }
    

    private function updateTeamPositionNeeds($currentNeeds, $playerPosition)
    {
        $positions = explode('/', $playerPosition);

        foreach ($positions as $position) {
            if (isset($currentNeeds[$position])) {
                $currentNeeds[$position]--;

                if ($currentNeeds[$position] <= 0) {
                    unset($currentNeeds[$position]);
                }
            }
        }

        return $currentNeeds;
    }

    
    public function rookieDraftees(Request $request)
    {
        // Get pagination parameters from the request
        $perPage = $request->input('itemsperpage', 10); // Number of items per page
        $currentPage = $request->input('page_num', 1); // Current page number
        $search = $request->input('search', ''); // Search term

        // Calculate the offset for the query
        $offset = ($currentPage - 1) * $perPage;

        // Define role priorities
        $rolePriorities = [
            'star player' => 1,
            'all star' => 2,
            'starter' => 2,
            'role player' => 5,
            'bench' => 5,
        ];

        // Build the query with optional search filter
        $query = Player::select('*')
            ->where('contract_years', 0)
            ->where('is_active', 1)
            ->where('is_rookie', 1);

        // Apply search filter if provided
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Add role priority sorting
        $query->orderByRaw(
            "FIELD(role, 'star player','all star', 'starter', 'role player', 'bench')"
        );

        // Get total number of records
        $total = $query->count();

        // Fetch the paginated data
        $freeAgents = $query->offset($offset)
            ->limit($perPage)
            ->get();

        // Calculate total pages
        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'rookies' => $freeAgents,
        ]);
    }

    public function draftResultsPerSeason(Request $request)
    {
        // Get the latest season_id from the request
        $latestSeasonId = $request->season_id;


        // If you want to include team names and player names, join the relevant tables
        $draftResultsWithNames = DB::table('drafts')
            ->join('teams', 'drafts.team_id', '=', 'teams.id')
            ->join('players', 'drafts.player_id', '=', 'players.id')
            ->select(
                'players.type as archetype',
                'players.age',
                'players.overall_rating',
                'players.position',
                'drafts.team_id',
                'teams.name as team_name',
                'drafts.player_id',
                'players.name as player_name',
                'drafts.season_id',
                'drafts.round',
                'drafts.pick_number',
                'drafts.draft_status'
            )
            ->where('players.draft_id', $latestSeasonId)
            ->get();

        // Extract player IDs from the draft results to create the rank group
        $rankGroupPlayerIds = $draftResultsWithNames->pluck('player_id');

        // Determine if the season_id is the current season
        $currentSeasonId = DB::table('seasons')->orderBy('id', 'desc')->value('id');

        // Fetch player stats and calculate ranks only for the players drafted in the latest season
        $playerStats = collect();
        if ($latestSeasonId == $currentSeasonId) {
            // Get player stats from the player_game_stats table for the current season, filtered by rank group and draft_id
            $playerGameStats = DB::table('player_game_stats')
                ->join('players', 'player_game_stats.player_id', '=', 'players.id')
                ->where('players.draft_id', $currentSeasonId)
                ->whereIn('player_game_stats.player_id', $rankGroupPlayerIds) // Filter by rank group
                ->select(
                    'player_game_stats.player_id',
                    DB::raw('COUNT(CASE WHEN minutes > 0 THEN 1 END) as total_games_played'),
                    DB::raw('AVG(player_game_stats.points) as avg_points_per_game'),
                    DB::raw('AVG(player_game_stats.rebounds) as avg_rebounds_per_game'),
                    DB::raw('AVG(player_game_stats.assists) as avg_assists_per_game'),
                    DB::raw('AVG(player_game_stats.steals) as avg_steals_per_game'),
                    DB::raw('AVG(player_game_stats.blocks) as avg_blocks_per_game'),
                    DB::raw('AVG(player_game_stats.turnovers) as avg_turnovers_per_game'),
                    DB::raw('AVG(player_game_stats.minutes) as avg_minutes_played')
                )
                ->groupBy('player_game_stats.player_id')
                ->get();
            $playerStats = $playerGameStats;
        } else {
            // Get player stats from the player_season_stats table for the previous season, filtered by rank group and draft_id
            $playerSeasonStats = DB::table('player_season_stats')
                ->join('players', 'player_season_stats.player_id', '=', 'players.id')
                ->where('players.draft_id', $latestSeasonId)
                ->whereIn('player_season_stats.player_id', $rankGroupPlayerIds) // Filter by rank group
                ->select(
                    'player_season_stats.player_id',
                    'player_season_stats.avg_points_per_game',
                    'player_season_stats.avg_rebounds_per_game',
                    'player_season_stats.avg_assists_per_game',
                    'player_season_stats.avg_steals_per_game',
                    'player_season_stats.avg_blocks_per_game',
                    'player_season_stats.avg_turnovers_per_game',
                    'player_season_stats.total_games_played',
                    'player_season_stats.avg_minutes_per_game as avg_minutes_played'
                )
                ->get();
            $playerStats = $playerSeasonStats;
        }

        // Assign ranks based on the custom formula
        $rankedPlayers = $playerStats->filter(function ($player) {
            // Only include players who have played at least one game and have minutes played > 0
            return $player->total_games_played > 0 && $player->avg_minutes_played > 0;
        })->sort(function ($a, $b) {
            // Calculate ranking scores for player A
            $aStats = $a->avg_points_per_game * 1.0 + $a->avg_rebounds_per_game * 1.2 +
                      $a->avg_assists_per_game * 1.5 + $a->avg_steals_per_game * 2.0 +
                      $a->avg_blocks_per_game * 2.0 - $a->avg_turnovers_per_game * 1.5;

            // Calculate ranking scores for player B
            $bStats = $b->avg_points_per_game * 1.0 + $b->avg_rebounds_per_game * 1.2 +
                      $b->avg_assists_per_game * 1.5 + $b->avg_steals_per_game * 2.0 +
                      $b->avg_blocks_per_game * 2.0 - $b->avg_turnovers_per_game * 1.5;

            // Factor in total games played and minutes played for ranking score
            // Example: Multiply score by total games played and average minutes played to give it weight
            $aFinalScore = $aStats * $a->total_games_played * $a->avg_minutes_played;
            $bFinalScore = $bStats * $b->total_games_played * $b->avg_minutes_played;

            return $bFinalScore <=> $aFinalScore;
        })->values(); // Re-index after sorting

        // Add ranks to each player
        $rankedPlayers = $rankedPlayers->map(function ($stats, $index) {
            $stats->rank = $index + 1; // Add rank starting from 1
            return $stats;
        });

        // Merge ranks into the draft results
        $draftResultsWithNamesAndRanks = $draftResultsWithNames->map(function ($draft) use ($rankedPlayers) {
            $playerRank = $rankedPlayers->firstWhere('player_id', $draft->player_id);
            $draft->rank = $playerRank->rank ?? null; // Add rank if found, otherwise null
            return $draft;
        });

        // Return the draft results as a JSON response
        return response()->json([
            'season_id' => $latestSeasonId,
            'draft_results' => $draftResultsWithNamesAndRanks,
        ]);
    }

    public function draftResults()
    {
        // Get the latest season_id from the standings_view
        $latestSeasonId = get_current_season_id();

        // Fetch draft results for the latest season from the drafts table
        $draftResults = DB::table('drafts')
            ->select('team_id', 'player_id', 'season_id', 'round', 'pick_number', 'draft_status')
            ->where('season_id', $latestSeasonId + 1) // Assuming the new season is the next one
            ->get();

        // If you want to include team names and player names, you can join the relevant tables
        $draftResultsWithNames = DB::table('drafts')
            ->join('teams', 'drafts.team_id', '=', 'teams.id')
            ->join('players', 'drafts.player_id', '=', 'players.id')
            ->select(
                'drafts.team_id',
                'teams.name as team_name',
                'drafts.player_id',
                'players.name as player_name',
                'players.age',
                'players.position',
                'players.type as archetype',
                'players.overall_rating',
                'drafts.season_id',
                'drafts.round',
                'drafts.pick_number',
                'drafts.draft_status'
            )
            ->where('drafts.season_id', $latestSeasonId + 1)
            ->get();

        // Return the draft results as a JSON response
        return response()->json([
            'season_id' => $latestSeasonId + 1, // Return the new season id
            'draft_results' => $draftResultsWithNames,
        ]);
    }
    private function updatePlayerPlayoffAppearances()
    {
        // $seasonId = $request->season_id;
        $seasonId = get_current_season_id();
        // Retrieve player playoff statistics for the given season
        $playerData = DB::table('players AS p')
            ->leftJoin('player_game_stats AS pg', 'p.id', '=', 'pg.player_id')
            ->leftJoin('schedules AS s', 'pg.game_id', '=', 's.game_id')
            ->leftJoin('teams AS t', 'pg.team_id', '=', 't.id')
            ->leftJoin('teams AS t2', 'p.team_id', '=', 't2.id')
            ->leftJoin(DB::raw('(SELECT DISTINCT player_id, season_id FROM player_game_stats) AS all_s'), 'all_s.player_id', '=', 'p.id')
            ->leftJoin('player_season_stats AS pss', 'pss.player_id', '=', 'p.id') // Join with player_season_stats to count distinct season_id
            ->leftJoin('seasons AS ss', 'ss.id', '=', 'all_s.season_id') // Join with player_season_stats to count distinct season_id
            ->where('all_s.season_id', $seasonId)  // Filter by season_id
            ->whereIn('s.round', [
                'play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals',
                'round_of_32', 'round_of_16', 'quarter_finals', 'semi_finals',
                'interconference_semi_finals', 'finals'
            ])
            ->where('s.season_id', $seasonId) // Ensure we're filtering by the correct season in the schedules table
            ->select([
                'p.id AS player_id',
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "play_ins_elims_round_1" THEN s.game_id END) AS play_ins_elims_round_1_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "play_ins_elims_round_2" THEN s.game_id END) AS play_ins_elims_round_2_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "play_ins_finals" THEN s.game_id END) AS play_ins_finals_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "round_of_32" THEN s.game_id END) AS round_of_32_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "round_of_16" THEN s.game_id END) AS round_of_16_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "quarter_finals" THEN s.game_id END) AS quarter_finals_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "semi_finals" THEN s.game_id END) AS semi_finals_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "interconference_semi_finals" THEN s.game_id END) AS interconference_semi_finals_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "finals" THEN s.game_id END) AS finals_appearances'),
                DB::raw('COUNT(DISTINCT s.game_id) AS total_playoff_appearances'),
                DB::raw('COUNT(DISTINCT CASE WHEN s.round IN ("play_ins_elims_round_1", "play_ins_elims_round_2", "play_ins_finals", "round_of_32", "round_of_16", "quarter_finals", "semi_finals", "interconference_semi_finals", "finals") THEN s.season_id END) AS seasons_played_in_playoffs'),
                
                // Counting distinct seasons from player_season_stats
                DB::raw('COUNT(DISTINCT pss.season_id) AS total_seasons_played'),
                
                // Championship check: Compare pg.team_id with finals_winner_id in the finals round
                DB::raw('COUNT(DISTINCT CASE WHEN s.round = "finals" AND pg.team_id = ss.finals_winner_id THEN s.game_id END) AS championships_won')
            ])
            ->groupBy('p.id', 'all_s.season_id') // Group by both player and season to avoid over-counting
            ->get();

    
        // Insert or update the data for each player in the player_playoff_appearances table
        foreach ($playerData as $data) {
            DB::table('player_playoff_appearances')->updateOrInsert(
                [
                    'player_id' => $data->player_id,
                ],
                [
                    'play_ins_elims_round_1_appearances' => DB::raw("IFNULL(play_ins_elims_round_1_appearances, 0) + {$data->play_ins_elims_round_1_appearances}"),
                    'play_ins_elims_round_2_appearances' => DB::raw("IFNULL(play_ins_elims_round_2_appearances, 0) + {$data->play_ins_elims_round_2_appearances}"),
                    'play_ins_finals_appearances' => DB::raw("IFNULL(play_ins_finals_appearances, 0) + {$data->play_ins_finals_appearances}"),
                    'round_of_32_appearances' => DB::raw("IFNULL(round_of_32_appearances, 0) + {$data->round_of_32_appearances}"),
                    'round_of_16_appearances' => DB::raw("IFNULL(round_of_16_appearances, 0) + {$data->round_of_16_appearances}"),
                    'quarter_finals_appearances' => DB::raw("IFNULL(quarter_finals_appearances, 0) + {$data->quarter_finals_appearances}"),
                    'semi_finals_appearances' => DB::raw("IFNULL(semi_finals_appearances, 0) + {$data->semi_finals_appearances}"),
                    'interconference_semi_finals_appearances' => DB::raw("IFNULL(interconference_semi_finals_appearances, 0) + {$data->interconference_semi_finals_appearances}"),
                    'finals_appearances' => DB::raw("IFNULL(finals_appearances, 0) + {$data->finals_appearances}"),
                    'total_playoff_appearances' => DB::raw("IFNULL(total_playoff_appearances, 0) + {$data->total_playoff_appearances}"),
                    'seasons_played_in_playoffs' => DB::raw("IFNULL(seasons_played_in_playoffs, 0) + {$data->seasons_played_in_playoffs}"),
                    'total_seasons_played' => $data->total_seasons_played,
                    'championships_won' => DB::raw("IFNULL(championships_won, 0) + {$data->championships_won}")
                ]
            );
        }

        return true;
    }
    
    private function determineContractYears($role)
    {
        switch ($role) {
            case 'star player':
                return rand(3, 7);
            case 'all star':
                    return rand(3, 5);
            case 'starter':
                return rand(1, 5);
            case 'role player':
                return rand(1, 3);
            case 'bench':
                return rand(1, 2);
            default:
                return 1;
        }
    }
}
