<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RetentionService;
use App\Models\Activity;
use App\Models\ActivityLogStatistic;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityLogMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:maintenance 
                            {--optimize : Optimize database tables}
                            {--archive : Archive old logs}
                            {--cleanup : Clean up orphaned records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform maintenance tasks on activity log system';

    protected RetentionService $retentionService;

    /**
     * Create a new command instance.
     */
    public function __construct(RetentionService $retentionService)
    {
        parent::__construct();
        $this->retentionService = $retentionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting activity log maintenance...');

        $tasks = [];

        // Archive old logs if requested or if archiving is enabled
        if ($this->option('archive') || config('activitylog.archive_enabled', false)) {
            $tasks[] = 'archive';
        }

        // Clean up orphaned records
        if ($this->option('cleanup') || !$this->options()) {
            $tasks[] = 'cleanup';
        }

        // Optimize tables
        if ($this->option('optimize') || !$this->options()) {
            $tasks[] = 'optimize';
        }

        // Generate monthly statistics
        if (!$this->options()) {
            $tasks[] = 'statistics';
        }

        foreach ($tasks as $task) {
            $this->performTask($task);
        }

        $this->info('Maintenance completed successfully!');

        // Log maintenance operation
        activity()
            ->causedByAnonymous()
            ->withProperties([
                'tasks' => $tasks,
                'completed_at' => now(),
            ])
            ->log('Activity log maintenance performed');

        return Command::SUCCESS;
    }

    /**
     * Perform a specific maintenance task
     */
    protected function performTask(string $task): void
    {
        switch ($task) {
            case 'archive':
                $this->archiveOldLogs();
                break;
            
            case 'cleanup':
                $this->cleanupOrphanedRecords();
                break;
            
            case 'optimize':
                $this->optimizeTables();
                break;
            
            case 'statistics':
                $this->generateMonthlyStatistics();
                break;
        }
    }

    /**
     * Archive old activity logs
     */
    protected function archiveOldLogs(): void
    {
        $this->info('Archiving old activity logs...');

        $archiveDays = config('activitylog.archive_after_days', 30);
        $cutoffDate = Carbon::now()->subDays($archiveDays);

        $count = Activity::where('created_at', '<', $cutoffDate)
            ->where('archived', false)
            ->count();

        if ($count === 0) {
            $this->info('No logs to archive.');
            return;
        }

        $this->info("Found {$count} logs to archive.");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $archived = 0;
        Activity::where('created_at', '<', $cutoffDate)
            ->where('archived', false)
            ->chunkById(1000, function ($activities) use (&$archived, $progressBar) {
                foreach ($activities as $activity) {
                    // Archive the activity (implementation depends on your archiving strategy)
                    $this->retentionService->archiveActivity($activity);
                    $archived++;
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine();

        $this->info("Archived {$archived} activity logs.");
    }

    /**
     * Clean up orphaned records
     */
    protected function cleanupOrphanedRecords(): void
    {
        $this->info('Cleaning up orphaned records...');

        // Find activities with missing causers
        $orphanedCausers = Activity::whereNotNull('causer_id')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'activity_log.causer_id')
                    ->where('activity_log.causer_type', 'App\\Models\\User');
            })
            ->count();

        if ($orphanedCausers > 0) {
            $this->info("Found {$orphanedCausers} activities with missing causers.");
            
            if ($this->confirm('Do you want to clean these up?')) {
                Activity::whereNotNull('causer_id')
                    ->whereNotExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('users')
                            ->whereColumn('users.id', 'activity_log.causer_id')
                            ->where('activity_log.causer_type', 'App\\Models\\User');
                    })
                    ->update([
                        'causer_id' => null,
                        'causer_type' => null,
                    ]);
                
                $this->info('Cleaned up orphaned causer references.');
            }
        }

        // Clean up old statistics
        $oldStats = ActivityLogStatistic::where('created_at', '<', Carbon::now()->subMonths(6))
            ->count();

        if ($oldStats > 0) {
            $this->info("Found {$oldStats} old statistics records.");
            
            if ($this->confirm('Do you want to remove statistics older than 6 months?')) {
                ActivityLogStatistic::where('created_at', '<', Carbon::now()->subMonths(6))
                    ->delete();
                
                $this->info('Removed old statistics.');
            }
        }
    }

    /**
     * Optimize database tables
     */
    protected function optimizeTables(): void
    {
        $this->info('Optimizing database tables...');

        $tables = [
            'activity_log',
            'activity_log_statistics',
            'activity_log_api_requests',
            'activity_log_background_jobs',
            'activity_log_bulk_operations',
            'activity_log_queries',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                try {
                    DB::statement("OPTIMIZE TABLE {$table}");
                    $this->info("Optimized table: {$table}");
                } catch (\Exception $e) {
                    $this->warn("Could not optimize table {$table}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Generate monthly statistics
     */
    protected function generateMonthlyStatistics(): void
    {
        $this->info('Generating monthly statistics...');

        $lastMonth = Carbon::now()->subMonth();
        
        // Check if statistics already exist for last month
        $exists = ActivityLogStatistic::where('period_type', 'month')
            ->where('period_start', $lastMonth->startOfMonth())
            ->exists();

        if ($exists) {
            $this->info('Monthly statistics already exist for ' . $lastMonth->format('F Y'));
            return;
        }

        $this->call('activitylog:stats', [
            '--period' => 'month',
            '--from' => $lastMonth->startOfMonth()->format('Y-m-d'),
            '--to' => $lastMonth->endOfMonth()->format('Y-m-d'),
            '--save' => true,
        ]);
    }
}