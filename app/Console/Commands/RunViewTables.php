<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RunViewTables extends Command
{
    protected $signature = 'db:run-view-tables';

    protected $description = 'Run all SQL view files in database/view_tables';

    public function handle(): int
    {
        $path = database_path('view_tables');

        $files = File::glob($path . '/*.sql');

        sort($files);

        if (empty($files)) {
            $this->warn("No SQL view files found in: {$path}");
            return self::SUCCESS;
        }

        foreach ($files as $file) {
            $filename = basename($file);

            $this->info("Running: {$filename}");

            try {
                $query = File::get($file);

                DB::unprepared($query);

                $this->info("✓ {$filename}");
            } catch (\Throwable $e) {
                $this->error("✗ {$filename}");
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('All database views created successfully.');

        return self::SUCCESS;
    }
}