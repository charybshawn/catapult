<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RetentionService;
use App\Models\Activity;
use Carbon\Carbon;

class ActivityLogPurge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:purge 
                            {--days= : Number of days to retain (overrides config)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge old activity log entries based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(RetentionService $retentionService): int
    {
        $days = $this->option('days') ?? config('activitylog.retention_days', 90);
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $cutoffDate = Carbon::now()->subDays($days);
        
        $query = Activity::where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->info('No activity logs to purge.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} activity logs older than {$days} days (before {$cutoffDate->format('Y-m-d')})");

        if ($dryRun) {
            $this->info('Dry run mode - no records will be deleted.');
            
            // Show sample of records that would be deleted
            $sample = $query->limit(10)->get();
            $this->table(
                ['ID', 'User', 'Description', 'Created At'],
                $sample->map(function ($activity) {
                    return [
                        $activity->id,
                        $activity->causer?->name ?? 'System',
                        $activity->description,
                        $activity->created_at->format('Y-m-d H:i:s'),
                    ];
                })
            );

            if ($count > 10) {
                $this->info("... and " . ($count - 10) . " more records");
            }

            return Command::SUCCESS;
        }

        if (!$force && !$this->confirm("Are you sure you want to delete {$count} activity logs?")) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info('Purging old activity logs...');
        
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        // Delete in chunks to avoid memory issues
        $deleted = 0;
        $query->chunkById(1000, function ($activities) use (&$deleted, $progressBar, $retentionService) {
            // Archive before deleting if enabled
            if (config('activitylog.archive_enabled', false)) {
                $retentionService->archiveActivities($activities);
            }

            $chunkCount = $activities->count();
            Activity::whereIn('id', $activities->pluck('id'))->delete();
            
            $deleted += $chunkCount;
            $progressBar->advance($chunkCount);
        });

        $progressBar->finish();
        $this->newLine();

        $this->info("Successfully purged {$deleted} activity logs.");

        // Log this maintenance operation
        activity()
            ->causedByAnonymous()
            ->withProperties([
                'operation' => 'purge',
                'records_deleted' => $deleted,
                'retention_days' => $days,
                'cutoff_date' => $cutoffDate->format('Y-m-d'),
            ])
            ->log('Activity logs purged');

        return Command::SUCCESS;
    }
}