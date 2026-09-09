<?php

namespace App\Services\Transaction;

use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class TransactionsService
{

    protected $helper;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->helper = new HelperService();
    }

    public function getRecentNonTransferTransactions()
    {
        $transactions = DB::table('transactions')
            ->select(
                'transactions.id',
                'transactions.player_id',
                'players.name as player_name',
                'players.role as player_role',
                'players.position as position',
                'players.overall_rating as overall_rating',
                'players.draft_status as draft_status',
                'players.draft_id as draft_season_id',
                DB::raw("CASE WHEN players.drafted_team_id = 0 THEN 'Undrafted' ELSE drafted_team.acronym END as drafted_team_abbre"),
                'players.age as age',
                'transactions.season_id',
                'seasons.name as season_name',
                'transactions.details',
                'transactions.from_team_id',
                DB::raw("CASE WHEN transactions.from_team_id = 0 THEN 'Free Agent' ELSE from_teams.name END as from_team_name"),
                DB::raw("CASE WHEN transactions.from_team_id = 0 THEN 'None' ELSE from_teams.city END as from_team_city"),
                'transactions.to_team_id',
                DB::raw("CASE WHEN transactions.to_team_id = 0 THEN 'Free Agent' ELSE to_teams.name END as to_team_name"),
                DB::raw("CASE WHEN transactions.to_team_id = 0 THEN 'None' ELSE to_teams.city END as to_team_city"),
                'transactions.status',
                'transactions.created_at',
                'transactions.updated_at',
                // Add award information
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT award_name SEPARATOR ', ') 
                    FROM season_awards 
                    WHERE season_awards.player_id = transactions.player_id) as awards"),
                // Check if player is Finals MVP
                DB::raw("CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM seasons 
                        WHERE seasons.finals_mvp_id = transactions.player_id
                    ) THEN 'Finals MVP'
                    ELSE NULL 
                END as finals_mvp_status"),
                // Get Finals MVP seasons
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT seasons.name) 
                    FROM seasons 
                    WHERE seasons.finals_mvp_id = transactions.player_id) as finals_mvp_seasons")
            )
            ->join('players', 'transactions.player_id', '=', 'players.id')
            ->join('seasons', 'transactions.season_id', '=', 'seasons.id')
            ->leftJoin('teams as drafted_team', 'players.drafted_team_id', '=', 'drafted_team.id')
            ->leftJoin('teams as from_teams', 'transactions.from_team_id', '=', 'from_teams.id')
            ->leftJoin('teams as to_teams', 'transactions.to_team_id', '=', 'to_teams.id')
            ->whereNotIn('transactions.status', ['transfer', 'role change'])
            ->orderBy('transactions.id', 'desc')
            ->limit(6)
            ->get();

        // Format the response data
        $transactions = $transactions->map(function ($transaction) {
            $awardsInfo = [];

            // Add regular awards if any
            if ($transaction->awards) {
                $awardsInfo[] = $transaction->awards;
            }

            // Add Finals MVP information if applicable
            if ($transaction->finals_mvp_status) {
                $awardsInfo[] = "Finals MVP (" . $transaction->finals_mvp_seasons . ")";
            }

            // Add awards information to transaction
            $transaction->awards_info = !empty($awardsInfo) ? implode(', ', $awardsInfo) : null;

            // Remove raw fields
            unset($transaction->awards);
            unset($transaction->finals_mvp_status);
            unset($transaction->finals_mvp_seasons);

            return $transaction;
        });

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    public function getRecentTransferTransactions()
    {
        $latestSeasonId = get_current_season_id() ?? 1;

        $transactions = DB::table('transactions')
            ->select(
                'transactions.id',
                'transactions.player_id',
                'players.name as player_name',
                'players.role as player_role',
                'players.position as position',
                'players.overall_rating as overall_rating',
                'players.draft_status as draft_status',
                'players.draft_id as draft_season_id',
                DB::raw("CASE WHEN players.drafted_team_id = 0 THEN 'Undrafted' ELSE drafted_team.acronym END as drafted_team_abbre"),
                'players.age as age',
                'transactions.season_id',
                'seasons.name as season_name',
                'transactions.details',
                'transactions.from_team_id',
                DB::raw("CASE WHEN transactions.from_team_id = 0 THEN 'Free Agent' ELSE from_teams.name END as from_team_name"),
                DB::raw("CASE WHEN transactions.from_team_id = 0 THEN 'None' ELSE from_teams.city END as from_team_city"),
                'transactions.to_team_id',
                DB::raw("CASE WHEN transactions.to_team_id = 0 THEN 'Free Agent' ELSE to_teams.name END as to_team_name"),
                DB::raw("CASE WHEN transactions.to_team_id = 0 THEN 'None' ELSE to_teams.city END as to_team_city"),
                DB::raw("CASE WHEN players.team_id = 0 THEN 'Free Agent' ELSE current_team.name END as current_team_name"),
                DB::raw("CASE WHEN players.team_id = 0 THEN 'Free Agent' ELSE current_team.city END as current_team_city"),
                'transactions.status',
                'transactions.created_at',
                'transactions.updated_at',
                // Add award information
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT award_name SEPARATOR ', ') 
                    FROM season_awards 
                    WHERE season_awards.player_id = transactions.player_id) as awards"),
                // Check if player is Finals MVP
                DB::raw("CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM seasons 
                        WHERE seasons.finals_mvp_id = transactions.player_id
                    ) THEN 'Finals MVP'
                    ELSE NULL 
                END as finals_mvp_status"),
                // Get Finals MVP seasons
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT seasons.name) 
                    FROM seasons 
                    WHERE seasons.finals_mvp_id = transactions.player_id) as finals_mvp_seasons")
            )
            ->join('players', 'transactions.player_id', '=', 'players.id')
            ->join('seasons', 'transactions.season_id', '=', 'seasons.id')
            ->leftJoin('teams as current_team', 'players.team_id', '=', 'current_team.id')
            ->leftJoin('teams as drafted_team', 'players.drafted_team_id', '=', 'drafted_team.id')
            ->leftJoin('teams as from_teams', 'transactions.from_team_id', '=', 'from_teams.id')
            ->leftJoin('teams as to_teams', 'transactions.to_team_id', '=', 'to_teams.id')
            ->where('transactions.season_id', $latestSeasonId)
            ->whereNotIn('transactions.status', ['star player change', 'role change'])
            ->orderBy('transactions.id', 'desc')
            ->limit(6)
            ->get();

        // Format the response data
        $transactions = $transactions->map(function ($transaction) {
            $awardsInfo = [];

            // Add regular awards if any
            if ($transaction->awards) {
                $awardsInfo[] = $transaction->awards;
            }

            // Add Finals MVP information if applicable
            if ($transaction->finals_mvp_status) {
                $awardsInfo[] = "Finals MVP (" . $transaction->finals_mvp_seasons . ")";
            }

            // Add awards information to transaction
            $transaction->awards_info = !empty($awardsInfo) ? implode(', ', $awardsInfo) : null;

            // Remove raw fields
            unset($transaction->awards);
            unset($transaction->finals_mvp_status);
            unset($transaction->finals_mvp_seasons);

            return $transaction;
        });

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    public function getSeasonTransferTransactions($request)
    {
        $perPage = $request->input('itemsperpage', 10); // Number of items per page
        $currentPage = $request->input('page_num', 1); // Current page number
        $search = $request->input('search', ''); // Search term

        $transactions = DB::table('transactions')
            ->select(
                'transactions.id',
                'transactions.player_id',
                'players.name as player_name',
                'players.role as player_role',
                'players.position as position',
                'players.overall_rating as overall_rating',
                'players.draft_status as draft_status',
                'players.draft_id as draft_season_id',
                DB::raw("CASE WHEN players.drafted_team_id = 0 THEN 'Undrafted' ELSE drafted_team.acronym END as drafted_team_abbre"),
                'players.age as age',
                'transactions.season_id',
                'seasons.name as season_name',
                'transactions.details',
                'transactions.from_team_id',
                DB::raw("CASE WHEN transactions.from_team_id = 0 THEN 'Free Agent' ELSE from_teams.name END as from_team_name"),
                DB::raw("CASE WHEN transactions.from_team_id = 0 THEN 'None' ELSE from_teams.city END as from_team_city"),
                'transactions.to_team_id',
                DB::raw("CASE WHEN transactions.to_team_id = 0 THEN 'Free Agent' ELSE to_teams.name END as to_team_name"),
                DB::raw("CASE WHEN transactions.to_team_id = 0 THEN 'None' ELSE to_teams.city END as to_team_city"),
                DB::raw("CASE WHEN players.team_id = 0 THEN 'Free Agent' ELSE current_team.name END as current_team_name"),
                DB::raw("CASE WHEN players.team_id = 0 THEN 'Free Agent' ELSE current_team.city END as current_team_city"),
                'transactions.status',
                'transactions.created_at',
                'transactions.updated_at',
                // Add award information
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT award_name SEPARATOR ', ') 
                    FROM season_awards 
                    WHERE season_awards.player_id = transactions.player_id) as awards"),
                // Check if player is Finals MVP
                DB::raw("CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM seasons 
                        WHERE seasons.finals_mvp_id = transactions.player_id
                    ) THEN 'Finals MVP'
                    ELSE NULL 
                END as finals_mvp_status"),
                // Get Finals MVP seasons
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT seasons.name) 
                    FROM seasons 
                    WHERE seasons.finals_mvp_id = transactions.player_id) as finals_mvp_seasons")
            )
            ->join('players', 'transactions.player_id', '=', 'players.id')
            ->join('seasons', 'transactions.season_id', '=', 'seasons.id')
            ->leftJoin('teams as current_team', 'players.team_id', '=', 'current_team.id')
            ->leftJoin('teams as drafted_team', 'players.drafted_team_id', '=', 'drafted_team.id')
            ->leftJoin('teams as from_teams', 'transactions.from_team_id', '=', 'from_teams.id')
            ->leftJoin('teams as to_teams', 'transactions.to_team_id', '=', 'to_teams.id')
            ->whereNotIn('transactions.status', ['star player change', 'role change']);

        // Apply search filter if provided
        if($search) {
            $transactions->where('players.name', 'like', "%{$search}%");
        }

         // Get the total number of records
        $totalItems = DB::table('transactions')
            ->count();

        // Calculate the offset for the query
        $offset = ($currentPage - 1) * $perPage;

        // Fetch the paginated data
        $transactions = $transactions->offset($offset)
            ->orderBy('transactions.id', 'desc')
            ->limit($perPage)
            ->get();

        // Calculate total pages
        $totalPages = (int) ceil($totalItems / $perPage);

        // Format the response data
        $transactions = $transactions->map(function ($transaction) {
            $awardsInfo = [];

            // Add regular awards if any
            if ($transaction->awards) {
                $awardsInfo[] = $transaction->awards;
            }

            // Add Finals MVP information if applicable
            if ($transaction->finals_mvp_status) {
                $awardsInfo[] = "Finals MVP (" . $transaction->finals_mvp_seasons . ")";
            }

            // Add awards information to transaction
            $transaction->awards_info = !empty($awardsInfo) ? implode(', ', $awardsInfo) : null;

            // Remove raw fields
            unset($transaction->awards);
            unset($transaction->finals_mvp_status);
            unset($transaction->finals_mvp_seasons);

            return $transaction;
        });

        return response()->json([
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $totalItems,
            'search' => $search,
            'transactions' => $transactions,
        ]);
    }

    public function getTransactions($request)
    {
        $seasonId = $request->season_id;
        $teamId = $request->team_id;
        $type = $request->type; // 'normal' or 'notable'
        $perPage = $request->get('itemsperpage', 10); // Default items per page is 10
        $page = $request->get('page_num', 1); // Default to page 1

        $statsDBName = $this->helper->getSeasonStatsDBName($seasonId);
        // Build the initial query with necessary joins
        $query = DB::table('transactions as t')
            ->leftJoin('season_awards as sa', function ($join) {
                $join->on('sa.player_id', '=', 't.player_id');
            })
            ->leftJoin('seasons as s', 's.id', '=', 't.season_id')
            ->leftJoin('players as p', 'p.id', '=', 't.player_id')
            ->leftJoin('teams as from_team', 'from_team.id', '=', 't.from_team_id')
            ->leftJoin('teams as to_team', 'to_team.id', '=', 't.to_team_id')
            ->leftJoin('teams as award_team', 'award_team.id', '=', 'sa.team_id') // Join teams for the awards
            ->leftJoin($statsDBName.' as ps', function ($join) {
                $join->on('ps.player_id', '=', 't.player_id')
                    ->on('ps.season_id', '=', 't.season_id');
            })
            ->select(
                't.id',
                't.player_id',
                't.season_id',
                't.details',
                't.from_team_id',
                't.to_team_id',
                't.status',
                'p.name as player_name',
                'p.is_active as is_active',
                'from_team.name as from_team_name',
                'to_team.name as to_team_name',
                'p.role', // Fetch player's role
                DB::raw("CASE
                WHEN sa.player_id IS NOT NULL  /* Player has an award */
                    OR s.finals_mvp_id = t.player_id  /* Player is Finals MVP */
                    OR p.role = 'star player'  /* Player is a star player */
                THEN 'notable'
                ELSE 'normal'
                END AS transaction_type"),
                DB::raw("GROUP_CONCAT(DISTINCT CONCAT(sa.award_name, ' (Season: ', sa.season_id, ', Team: ', IFNULL(award_team.name, 'N/A'), ')') ORDER BY sa.season_id ASC) AS player_awards"),
                DB::raw("(SELECT CONCAT('Finals MVP (Season ', s.id, ', Team: ', t.name, ')')
                FROM seasons AS s
                LEFT JOIN teams AS t ON t.id = s.finals_winner_id
                WHERE s.finals_mvp_id = p.id
                LIMIT 1) AS finals_mvp"),
                DB::raw("(SELECT CONCAT('Finals Winner (Season ', s.id, ', Team: ', winner_team.name, ')')
                FROM seasons AS s
                LEFT JOIN teams AS winner_team ON winner_team.id = s.finals_winner_id
                WHERE s.finals_winner_id = p.id
                LIMIT 1) AS player_finals_winner"),
                DB::raw("CASE
                    WHEN s.finals_mvp_id = p.id THEN 1
                    ELSE 0
                END as is_finals_mvp"),
                // Fetch player career championships (all seasons they were champions)
                DB::raw("MAX(CASE WHEN t.status = 'retired' THEN 1 ELSE 0 END) AS is_retired"),  // Check if the player has any 'retired' status
                's.finals_winner_name'  // Add finals_winner_name from the seasons table
            )
            ->whereNotIn('t.status', ['draft', 'released']); // Filter out 'draft' and 'released' transactions

        // Apply filters for 'normal' or 'notable' transaction type based on the CASE logic
        if ($type) {
            $query->whereRaw("
                (sa.player_id IS NOT NULL
                OR s.finals_mvp_id = t.player_id
                OR p.role = 'star player') = ?
            ", [$type === 'notable' ? 1 : 0]);
        }

        // Apply season_id filter if provided (if you want transactions from a specific season)
        if ($seasonId) {
            $query->where('t.season_id', $seasonId);
        }

        // Apply team_id filter if provided (filter by from_team_id or to_team_id)
        if ($teamId) {
            $query->where(function ($subQuery) use ($teamId) {
                $subQuery->where('t.from_team_id', $teamId)
                    ->orWhere('t.to_team_id', $teamId);
            });
        }

        // Add GROUP BY clause to ensure proper grouping for each transaction and player
        $query->groupBy(
            't.id',
            't.player_id',
            't.season_id',
            'sa.player_id',
            't.details',
            't.from_team_id',
            't.to_team_id',
            't.status',
            'p.name',
            'p.is_active',
            'from_team.name',
            'to_team.name',
            'p.role',
            's.id',
            'p.id',
            's.finals_mvp_id',
            'award_team.name',
            's.champion_id',       // Added champion_id in GROUP BY
            's.finals_winner_id',   // Added finals_winner_id in GROUP BY
            's.finals_winner_name'  // Added finals_winner_name in GROUP BY
        );

        // Fetch all transactions without pagination
        $transactions = $query->get();


        foreach ($transactions as $transaction) {
            $playerId = $transaction->player_id;
            $championships = DB::table('seasons')
                ->join('player_game_stats', 'seasons.id', '=', 'player_game_stats.season_id')
                ->join('schedules_archives as schedules', 'player_game_stats.game_id', '=', 'schedules.game_id')
                ->join('teams', 'player_game_stats.team_id', '=', 'teams.id')
                ->select('seasons.id as season_id', 'teams.name as championship_team', 'seasons.name as championship_season')
                ->where('player_game_stats.player_id',  $playerId)
                ->where('schedules.round', 'finals')
                ->whereColumn('seasons.id', 'player_game_stats.season_id')
                ->whereExists(function ($query) use ($playerId) {
                    $query->select(DB::raw(1))
                        ->from('schedules_archives as s')
                        ->join('player_game_stats as pg', 's.game_id', '=', 'pg.game_id')
                        ->where('pg.team_id', '=', DB::raw('player_game_stats.team_id'))
                        ->where('s.round', 'finals')
                        ->where('pg.player_id',  $playerId)
                        ->whereColumn('pg.season_id', 'player_game_stats.season_id')
                        ->where(function ($q) {
                            $q->where(function ($q) {
                                $q->whereColumn('s.home_id', 'player_game_stats.team_id')
                                    ->whereColumn('s.home_score', '>', 's.away_score');
                            })
                                ->orWhere(function ($q) {
                                    $q->whereColumn('s.away_id', 'player_game_stats.team_id')
                                        ->whereColumn('s.away_score', '>', 's.home_score');
                                });
                        });
                })
                ->groupBy('seasons.id', 'teams.name', 'seasons.name') // Group by season_id, championship team and season name
                ->get();

            // Convert the championships to a comma-separated string
            $championshipsFormatted = $championships->map(function ($championship) {
                return "{$championship->championship_season} Champion ({$championship->championship_team})";
            })->implode(', ');
            // Assign the championship data to the transaction, if available
            $transaction->player_career_championships = $championshipsFormatted;

            // If the player is retired, set their status as 'retired'
            if ($transaction->is_retired) {
                $transaction->status = 'retired';
            }

            // Remove the temporary 'is_retired' field from the response
            unset($transaction->is_retired);
        }


        // Return the data with total_items set to 0 (no pagination)
        return response()->json([
            'data' => $transactions,  // The actual data for all transactions
            'current_page' => 1,      // Page 1 (since we're not paginating)
            'total_items' => 0,       // Set total_items to 0 as requested
            'total_pages' => 1,       // One page since no pagination
            'per_page' => count($transactions),  // The number of items fetched
        ]);
    }

}
