<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leagues;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LeaguesController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('Leagues/Index', [
            'status' => session('status'),
        ]);
    }
    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        Leagues::create($request->all());

        return redirect()->route('leagues.index');
    }
    public function list(Request $request)
    {
        // Retrieve search query from request
        $searchQuery = $request->search;

        // Query builder for leagues
        $query = Leagues::query();

        // Apply search filter if search query is provided
        if ($searchQuery) {
            $query->where('name', 'like', '%' . $searchQuery . '%');
        }

        // Exclude league with ID 2
        $query->where('id', '!=', 2);

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
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $team = Leagues::findOrFail($request->id);
        $team->update($request->all());

        return redirect()->route('leagues.index');
    }

    // Remove the specified resource from storage.
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);
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
            'head_to_head',
            'injury_histories',
            'players',
            'player_game_stats',
            'coaches',
            'player_playoff_appearances',
            'player_ratings',
            'player_season_stats',
            'player_season_stats_archives',
            'player_season_playoff_stats_archives',
            'player_season_playoff_stats',
            'playoff_series',
            'player_series_appearances',
            'game_news',
            'standings_snapshots',
            'schedules',
            'seasons',
            'season_awards',
            'storylines',
            'streak',
            'trade_logs',
            'team_season_info',
            'trade_proposals',
            'trade_players',
            'transactions',
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
        | Drop all dynamic player_game_stats_batch_N tables
        |--------------------------------------------------------------------------
        */

            $databaseName = DB::getDatabaseName();

            $batchTables = DB::table('information_schema.tables')
                ->where('table_schema', $databaseName)
                ->where(
                    'table_name',
                    'like',
                    'player_game_stats_batch_%'
                )
                ->pluck('table_name');

            foreach ($batchTables as $tableName) {

                /*
             * Extra safety:
             * only allow the expected table-name pattern.
             */
                if (
                    preg_match(
                        '/^player_game_stats_batch_[0-9]+$/',
                        $tableName
                    )
                ) {
                    DB::statement(
                        'DROP TABLE IF EXISTS `' .
                            str_replace('`', '``', $tableName) .
                            '`'
                    );
                }
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
