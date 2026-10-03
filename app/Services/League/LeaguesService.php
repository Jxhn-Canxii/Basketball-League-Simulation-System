<?php

namespace App\Services\League;

use App\Models\Leagues;
use Illuminate\Support\Facades\DB;

class LeaguesService
{
   
    public function add($request)
    {
       
        Leagues::create($request->all());

        return redirect()->route('leagues.index');
    }

    public function list($request)
    {
        // Retrieve search query from request
        $searchQuery = $request->search;

        // Query builder for leagues
        $query = Leagues::query();

        // Apply search filter if search query is provided
        if ($searchQuery) {
            $query->where('name', 'like', '%' . $searchQuery . '%');
        }

        // Get total count of records before pagination
        $totalCount = $query->count();

        // Set the number of records to display per page
        $perPage = 10;

        // Calculate the total number of pages
        $totalPages = ceil($totalCount / $perPage);

        // Get the current page from the request, default to 1 if not provided
        $currentPage = $request->page_num;

        // Calculate the offset for pagination
        $offset = ($currentPage - 1) * $perPage;

        // Retrieve leagues data with pagination
        $leagues = $query->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'leagues' => $leagues,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'total_count' => $totalCount,
        ]);
    }

    // Update the specified resource in storage.
    public function update($request)
    {
    
        $team = Leagues::findOrFail($request->id);
        $team->update($request->all());

        return redirect()->route('leagues.index');
    }

    // Remove the specified resource from storage.
    public function delete($request)
    {
        
        $team = Leagues::findOrFail($request->id);
        $team->delete();

        return redirect()->route('leagues.index');
    }

    public function dropdown()
    {
        $leagues = Leagues::all(['id', 'name']); // Fetch only id and name columns
        return response()->json($leagues);
    }

    public function resetLeague()
    {
        $tables = [
            'drafts',
            'draft_pick_rights',
            'play_by_play',
            'salary_caps',
            'head_to_head',
            'injury_histories',
            'players',
            'player_game_stats',
            'player_per_quarter_stats',
            'game_quarter_breakdown',
            'player_playoff_appearances',
            'player_ratings',
            'player_season_stats',
            'player_season_stats_archives',
            'player_season_playoff_stats_archives',
            'player_season_playoff_stats',
            'playoff_series',
            'player_game_highs',
            'career_highlights',
            'playoff_series_archives',
            'player_series_appearances',
            'game_news',
            'standings_snapshots',
            'schedules_archives',
            'schedule_view_snapshots',
            'schedules',
            'seasons',
            'season_awards',
            'storylines',
            'streak',
            'team_season_info',
            'trade_logs',
            'trade_proposals',
            'trade_players',
            'role_change_transactions',
            'transactions',
            'player_contracts',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {

            /*
        |--------------------------------------------------------------------------
        | Truncate normal tables
        |--------------------------------------------------------------------------
        */

            foreach ($tables as $table) {

                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Reset Team Data
        |--------------------------------------------------------------------------
        */

        DB::statement(
            'UPDATE teams
            LEFT JOIN coaches ON teams.id = coaches.team_id
            SET teams.coach_id = COALESCE(coaches.id, 0)'
        );

        DB::statement(
            'UPDATE coaches SET 
            career_wins = 0,
            contract_years = 0,
            career_losses = 0,
            experience_years = 0,
            team_id = 0,
            winning_percentage = 0'
        );

        DB::statement('UPDATE `teams` SET `coach_id`=0');

        /*
        |--------------------------------------------------------------------------
        | Drop all dynamic _batch_N tables
        |--------------------------------------------------------------------------
        */

            $databaseName = DB::getDatabaseName();

            $batchTables = DB::table('information_schema.tables')
                ->where('table_schema', $databaseName)
                ->where(
                    'table_name',
                    'like',
                    '%batch_%'
                )
                ->pluck('table_name');

            // dd($batchTables);

            foreach ($batchTables as $tableName) {

                DB::statement("DROP TABLE IF EXISTS $tableName");
                
            }
        } finally {

            /*
        |--------------------------------------------------------------------------
        | Always re-enable foreign key checks
        |--------------------------------------------------------------------------
        */

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        return response()->json([
            'message' => 'Tables reset successfully.',
            'batch_tables_removed' => $batchTables->count(),
        ]);
    }
}
