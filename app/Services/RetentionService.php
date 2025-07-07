<?php

namespace App\Services;

use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RetentionService
{
    protected int $retentionDays;
    protected bool $archiveEnabled;
    protected string $archivePath = 'activity-logs/archives';

    public function __construct(int $retentionDays = 90, bool $archiveEnabled = false)
    {
        $this->retentionDays = $retentionDays;
        $this->archiveEnabled = $archiveEnabled;
    }

    /**
     * Apply retention policy to activity logs
     */
    public function applyRetentionPolicy(): array
    {
        $cutoffDate = Carbon::now()->subDays($this->retentionDays);
        
        $query = Activity::where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            return [
                'deleted' => 0,
                'archived' => 0,
            ];
        }

        $archived = 0;
        $deleted = 0;

        // Process in chunks to avoid memory issues
        $query->chunkById(1000, function ($activities) use (&$archived, &$deleted) {
            if ($this->archiveEnabled) {
                $archived += $this->archiveActivities($activities);
            }

            // Delete the activities
            $ids = $activities->pluck('id');
            $deleted += Activity::whereIn('id', $ids)->delete();
        });

        return [
            'deleted' => $deleted,
            'archived' => $archived,
        ];
    }

    /**
     * Archive a collection of activities
     */
    public function archiveActivities(Collection $activities): int
    {
        if ($activities->isEmpty()) {
            return 0;
        }

        $date = now()->format('Y-m-d');
        $filename = "{$this->archivePath}/activities_{$date}_" . uniqid() . '.json';

        // Prepare data for archiving
        $data = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'causer_type' => $activity->causer_type,
                'causer_id' => $activity->causer_id,
                'properties' => $activity->properties,
                'event' => $activity->event,
                'batch_uuid' => $activity->batch_uuid,
                'created_at' => $activity->created_at->toIso8601String(),
                'updated_at' => $activity->updated_at->toIso8601String(),
            ];
        });

        // Store compressed JSON
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $compressed = gzencode($json, 9);
        
        Storage::put($filename . '.gz', $compressed);

        // Update activities as archived
        Activity::whereIn('id', $activities->pluck('id'))
            ->update(['archived' => true]);

        return $activities->count();
    }

    /**
     * Archive a single activity
     */
    public function archiveActivity(Activity $activity): bool
    {
        return $this->archiveActivities(collect([$activity])) === 1;
    }

    /**
     * Restore archived activities
     */
    public function restoreFromArchive(string $filename): int
    {
        if (!Storage::exists($filename)) {
            throw new \Exception("Archive file not found: {$filename}");
        }

        // Read and decompress the file
        $compressed = Storage::get($filename);
        $json = gzdecode($compressed);
        $data = json_decode($json, true);

        if (!$data) {
            throw new \Exception("Invalid archive file format");
        }

        $restored = 0;

        // Restore activities in chunks
        collect($data)->chunk(100)->each(function ($chunk) use (&$restored) {
            $activities = [];
            
            foreach ($chunk as $item) {
                $activities[] = [
                    'log_name' => $item['log_name'],
                    'description' => $item['description'],
                    'subject_type' => $item['subject_type'],
                    'subject_id' => $item['subject_id'],
                    'causer_type' => $item['causer_type'],
                    'causer_id' => $item['causer_id'],
                    'properties' => json_encode($item['properties']),
                    'event' => $item['event'],
                    'batch_uuid' => $item['batch_uuid'],
                    'archived' => false,
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];
            }

            $restored += Activity::insert($activities);
        });

        return $restored;
    }

    /**
     * Get archive statistics
     */
    public function getArchiveStatistics(): array
    {
        $files = Storage::files($this->archivePath);
        $totalSize = 0;
        $fileCount = 0;
        $oldestFile = null;
        $newestFile = null;

        foreach ($files as $file) {
            if (str_ends_with($file, '.gz')) {
                $fileCount++;
                $size = Storage::size($file);
                $totalSize += $size;
                $modified = Storage::lastModified($file);

                if (!$oldestFile || $modified < Storage::lastModified($oldestFile)) {
                    $oldestFile = $file;
                }

                if (!$newestFile || $modified > Storage::lastModified($newestFile)) {
                    $newestFile = $file;
                }
            }
        }

        return [
            'file_count' => $fileCount,
            'total_size' => $this->formatBytes($totalSize),
            'total_size_bytes' => $totalSize,
            'oldest_archive' => $oldestFile ? [
                'filename' => basename($oldestFile),
                'date' => Carbon::createFromTimestamp(Storage::lastModified($oldestFile))->toDateTimeString(),
            ] : null,
            'newest_archive' => $newestFile ? [
                'filename' => basename($newestFile),
                'date' => Carbon::createFromTimestamp(Storage::lastModified($newestFile))->toDateTimeString(),
            ] : null,
        ];
    }

    /**
     * Clean up old archive files
     */
    public function cleanupOldArchives(int $daysToKeep = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep)->timestamp;
        $files = Storage::files($this->archivePath);
        $deleted = 0;

        foreach ($files as $file) {
            if (str_ends_with($file, '.gz') && Storage::lastModified($file) < $cutoffDate) {
                Storage::delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get retention policy summary
     */
    public function getRetentionPolicySummary(): array
    {
        $cutoffDate = Carbon::now()->subDays($this->retentionDays);
        
        return [
            'retention_days' => $this->retentionDays,
            'cutoff_date' => $cutoffDate->toDateString(),
            'archive_enabled' => $this->archiveEnabled,
            'activities_to_purge' => Activity::where('created_at', '<', $cutoffDate)->count(),
            'archived_activities' => Activity::where('archived', true)->count(),
            'total_activities' => Activity::count(),
            'oldest_activity' => Activity::oldest()->first()?->created_at?->toDateString(),
            'newest_activity' => Activity::latest()->first()?->created_at?->toDateString(),
        ];
    }

    /**
     * Set retention days
     */
    public function setRetentionDays(int $days): void
    {
        $this->retentionDays = $days;
    }

    /**
     * Enable or disable archiving
     */
    public function setArchiveEnabled(bool $enabled): void
    {
        $this->archiveEnabled = $enabled;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}