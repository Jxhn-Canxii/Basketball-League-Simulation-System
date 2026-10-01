<?php

namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthCheckService
{
    /**
     * Run the complete system health check.
     *
     * IMPORTANT:
     * This service is READ-ONLY.
     * It does not create, update, delete, or write anything.
     */
    public function check(): array
    {
        $checks = [
            'application' => $this->checkApplication(),
            'database'    => $this->checkDatabase(),
            'tables'      => $this->checkTables(),
            'storage'     => $this->checkStorage(),
            'filesystem'  => $this->checkFilesystem(),
            'queue'       => $this->checkQueue(),
            'scheduler'   => $this->checkScheduler(),
            'season'      => $this->checkSeason(),
            'teams'       => $this->checkTeams(),
            'players'     => $this->checkPlayers(),
            'schedules'   => $this->checkSchedules(),
        ];

        $summary = [
            'healthy' => 0,
            'warning' => 0,
            'failed'  => 0,
        ];

        foreach ($checks as $check) {
            $status = $check['status'] ?? 'failed';

            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        $overallStatus = 'healthy';

        if ($summary['failed'] > 0) {
            $overallStatus = 'failed';
        } elseif ($summary['warning'] > 0) {
            $overallStatus = 'warning';
        }

        return [
            'status' => $overallStatus,

            'summary' => [
                'total'   => count($checks),
                'healthy' => $summary['healthy'],
                'warning' => $summary['warning'],
                'failed'  => $summary['failed'],
            ],

            'application' => [
                'name'        => config('app.name'),
                'environment' => app()->environment(),
                'debug'       => (bool) config('app.debug'),
                'laravel'     => app()->version(),
                'php'         => PHP_VERSION,
            ],

            'timestamp' => now()->toIso8601String(),

            'checks' => $checks,
        ];
    }

    /**
     * -------------------------------------------------------------
     * Application
     * -------------------------------------------------------------
     */
    protected function checkApplication(): array
    {
        try {
            return [
                'status'  => 'healthy',
                'message' => 'Application is running.',
                'details' => [
                    'environment' => app()->environment(),
                    'laravel'     => app()->version(),
                    'php'         => PHP_VERSION,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Application check failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Database
     * -------------------------------------------------------------
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);

            DB::select('SELECT 1');

            $responseTime = round(
                (microtime(true) - $start) * 1000,
                2
            );

            $connection = DB::connection();

            $databaseName = $connection
                ->getDatabaseName();

            $status = 'healthy';

            if ($responseTime > 500) {
                $status = 'warning';
            }

            if ($responseTime > 2000) {
                $status = 'failed';
            }

            return [
                'status'  => $status,
                'message' => $status === 'healthy'
                    ? 'Database connection is healthy.'
                    : 'Database response time is high.',

                'details' => [
                    'connection'   => config('database.default'),
                    'driver'       => $connection->getDriverName(),
                    'database'     => $databaseName,
                    'response_ms'  => $responseTime,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Database connection failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Required LIGA2 tables
     * -------------------------------------------------------------
     */
    protected function checkTables(): array
    {
        $tables = [
            'players',
            'teams',
            'coaches',
            'seasons',
            'schedules',
            'player_game_stats',
            'player_per_quarter_stats',
            'player_season_stats',
            'game_quarter_breakdown',
            'drafts',
            'draft_pick_rights',
            'trade_proposals',
            'trade_players',
        ];

        try {
            $missing = [];
            $existing = [];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $existing[] = $table;
                } else {
                    $missing[] = $table;
                }
            }

            if (!empty($missing)) {
                return [
                    'status'  => 'failed',
                    'message' => 'One or more required LIGA2 tables are missing.',
                    'details' => [
                        'required' => count($tables),
                        'existing' => count($existing),
                        'missing'  => $missing,
                    ],
                ];
            }

            return [
                'status'  => 'healthy',
                'message' => 'All required LIGA2 tables are available.',
                'details' => [
                    'required' => count($tables),
                    'existing' => count($existing),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Unable to inspect database tables.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Storage
     * -------------------------------------------------------------
     */
    protected function checkStorage(): array
    {
        try {
            $storagePath = storage_path();

            if (!is_dir($storagePath)) {
                return [
                    'status'  => 'failed',
                    'message' => 'Storage directory does not exist.',
                    'details' => [
                        'path' => $storagePath,
                    ],
                ];
            }

            if (!is_readable($storagePath)) {
                return [
                    'status'  => 'failed',
                    'message' => 'Storage directory is not readable.',
                    'details' => [
                        'path' => $storagePath,
                    ],
                ];
            }

            /*
             * Monitoring only:
             * We intentionally do NOT perform a write test.
             */
            return [
                'status'  => 'healthy',
                'message' => 'Storage directory is available.',
                'details' => [
                    'path'     => $storagePath,
                    'readable' => true,
                    'writable' => is_writable($storagePath),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Storage check failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Filesystem / Disk
     * -------------------------------------------------------------
     */
    protected function checkFilesystem(): array
    {
        try {
            $path = storage_path();

            $total = @disk_total_space($path);
            $free  = @disk_free_space($path);

            if ($total === false || $free === false) {
                return [
                    'status'  => 'warning',
                    'message' => 'Unable to determine disk usage.',
                ];
            }

            $used = $total - $free;

            $freePercentage = ($free / $total) * 100;

            $status = 'healthy';

            if ($freePercentage < 15) {
                $status = 'warning';
            }

            if ($freePercentage < 5) {
                $status = 'failed';
            }

            return [
                'status'  => $status,
                'message' => 'Filesystem capacity checked.',

                'details' => [
                    'total'          => $this->formatBytes($total),
                    'used'           => $this->formatBytes($used),
                    'free'           => $this->formatBytes($free),
                    'free_percentage' => round(
                        $freePercentage,
                        2
                    ),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Filesystem check failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Queue
     * -------------------------------------------------------------
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config(
                'queue.default'
            );

            if (empty($connection)) {
                return [
                    'status'  => 'warning',
                    'message' => 'Queue connection is not configured.',
                ];
            }

            return [
                'status'  => 'healthy',
                'message' => 'Queue configuration is available.',
                'details' => [
                    'connection' => $connection,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Queue check failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Scheduler
     * -------------------------------------------------------------
     *
     * This is configuration monitoring only.
     * We do not execute the scheduler.
     */
    protected function checkScheduler(): array
    {
        try {
            $scheduleConfigured = app()->bound(
                \Illuminate\Console\Scheduling\Schedule::class
            );

            return [
                'status' => $scheduleConfigured
                    ? 'healthy'
                    : 'warning',

                'message' => $scheduleConfigured
                    ? 'Scheduler is available.'
                    : 'Scheduler container is not currently bound.',

                'details' => [
                    'configured' => $scheduleConfigured,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'warning',
                'message' => 'Unable to inspect scheduler.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Current Season
     * -------------------------------------------------------------
     */
    protected function checkSeason(): array
    {
        try {
            if (!Schema::hasTable('seasons')) {
                return [
                    'status'  => 'failed',
                    'message' => 'Seasons table does not exist.',
                ];
            }

            $season = DB::table('seasons')
                ->orderByDesc('id')
                ->first();

            if (!$season) {
                return [
                    'status'  => 'warning',
                    'message' => 'No seasons found.',
                ];
            }

            return [
                'status'  => 'healthy',
                'message' => 'Season data is available.',
                'details' => [
                    'latest_season_id' => $season->id,
                    'season' => $season,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Season check failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Teams
     * -------------------------------------------------------------
     */
    protected function checkTeams(): array
    {
        try {
            if (!Schema::hasTable('teams')) {
                return [
                    'status'  => 'failed',
                    'message' => 'Teams table does not exist.',
                ];
            }

            $count = DB::table('teams')->count();

            if ($count === 0) {
                return [
                    'status'  => 'warning',
                    'message' => 'No teams found.',
                    'details' => [
                        'count' => 0,
                    ],
                ];
            }

            return [
                'status'  => 'healthy',
                'message' => 'Teams are available.',
                'details' => [
                    'count' => $count,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Teams check failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Players
     * -------------------------------------------------------------
     */
    protected function checkPlayers(): array
    {
        try {
            if (!Schema::hasTable('players')) {
                return [
                    'status'  => 'failed',
                    'message' => 'Players table does not exist.',
                ];
            }

            $totalPlayers = DB::table('players')
                ->count();

            $activePlayers = DB::table('players')
                ->where('is_active', 1)
                ->count();

            if ($totalPlayers === 0) {
                return [
                    'status'  => 'warning',
                    'message' => 'No players found.',
                    'details' => [
                        'total'  => 0,
                        'active' => 0,
                    ],
                ];
            }

            return [
                'status'  => 'healthy',
                'message' => 'Player data is available.',
                'details' => [
                    'total'  => $totalPlayers,
                    'active' => $activePlayers,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Players check failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Schedules / Games
     * -------------------------------------------------------------
     */
    protected function checkSchedules(): array
    {
        try {
            if (!Schema::hasTable('schedules')) {
                return [
                    'status'  => 'failed',
                    'message' => 'Schedules table does not exist.',
                ];
            }

            $total = DB::table('schedules')
                ->count();

            $completed = DB::table('schedules')
                ->where('status', 2)
                ->count();

            $pending = DB::table('schedules')
                ->where('status', 1)
                ->count();

            return [
                'status'  => 'healthy',
                'message' => 'Schedule data is available.',
                'details' => [
                    'total'     => $total,
                    'completed' => $completed,
                    'pending'   => $pending,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => 'failed',
                'message' => 'Schedule check failed.',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * -------------------------------------------------------------
     * Format Bytes
     * -------------------------------------------------------------
     */
    protected function formatBytes(
        int|float $bytes,
        int $precision = 2
    ): string {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $power = floor(
            log($bytes, 1024)
        );

        $power = min(
            $power,
            count($units) - 1
        );

        return round(
            $bytes / pow(1024, $power),
            $precision
        ) . ' ' . $units[$power];
    }
}
