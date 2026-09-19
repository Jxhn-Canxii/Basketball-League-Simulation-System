<?php

namespace App\Services\Archive;

use App\Services\Helper\HelperService;
use App\Services\Team\TeamChemistryService;
use App\Services\Team\TeamStreakService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveToOtherDBService
{
    protected $chemistry;
    protected $helper;
    protected $streak;

    public function __construct(){
        $this->chemistry = new TeamChemistryService();
        $this->streak = new TeamStreakService();
        $this->helper = new HelperService();
    }

    public function pushArchivesToOtherDB()
    {
        /*
        |--------------------------------------------------------------------------
        | Drop all dynamic _batch_N tables
        |--------------------------------------------------------------------------
        */

            $databaseName = DB::getDatabaseName();
            $archiveDatabase = config('database.connections.archive.database');

            DB::connection('archive');

            // DB::table($archiveDatabase.'.test')
            //     ->insert([
            //         'name' => 'John',
            //         'age' => 29,
            //         'gender' => 'male',
            //     ]);
            // dd($connection);

            $batchTables = DB::table('information_schema.tables')
                ->where('table_schema', $databaseName)
                ->where(
                    'table_name',
                    'like',
                    '%batch_%'
                )
                ->pluck('table_name');

            //dd($batchTables);

            foreach ($batchTables as $tableName) {

                if (!Schema::hasTable($tableName)) {
                    DB::statement("CREATE TABLE $archiveDatabase.$tableName LIKE $tableName");
                }

                DB::statement("INSERT INTO $archiveDatabase.$tableName SELECT * FROM $tableName");
                
            }
    }

}