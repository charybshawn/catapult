<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class JobLogService
{
    /**
     * Log job dispatch.
     */
    public function logJobDispatched(string $jobClass, array $payload = [], string $queue = null): string
    {
        $jobId = Str::uuid()->toString();
        
        activity('job_dispatched')
            ->withProperties([
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'queue' => $queue ?? config('queue.default'),
                'payload_size' => strlen(serialize($payload)),
                'payload_preview' => $this->getPayloadPreview($payload),
                'dispatched_at' => now()->toIso8601String(),
            ])
            ->log("Job dispatched: {$jobClass}");

        // Store job start time for duration calculation
        Cache::put("job_start_{$jobId}", microtime(true), 3600);

        return $jobId;
    }

    /**
     * Log job processing.
     */
    public function logJobProcessing(string $jobId, string $jobClass, int $attempt = 1): void
    {
        activity('job_processing')
            ->withProperties([
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'attempt' => $attempt,
                'worker_name' => gethostname(),
                'processing_started_at' => now()->toIso8601String(),
            ])
            ->log("Job processing: {$jobClass}");
    }

    /**
     * Log job completion.
     */
    public function logJobCompleted(string $jobId, string $jobClass, array $result = []): void
    {
        $startTime = Cache::pull("job_start_{$jobId}");
        $duration = $startTime ? microtime(true) - $startTime : null;

        activity('job_completed')
            ->withProperties([
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'duration' => $duration,
                'result_preview' => $this->getResultPreview($result),
                'completed_at' => now()->toIso8601String(),
            ])
            ->log("Job completed: {$jobClass}");

        // Update job statistics
        $this->updateJobStatistics($jobClass, 'completed', $duration);
    }

    /**
     * Log job failure.
     */
    public function logJobFailed(string $jobId, string $jobClass, Exception $exception, int $attempt = 1): void
    {
        $startTime = Cache::get("job_start_{$jobId}");
        $duration = $startTime ? microtime(true) - $startTime : null;

        activity('job_failed')
            ->withProperties([
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'attempt' => $attempt,
                'duration' => $duration,
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'failed_at' => now()->toIso8601String(),
            ])
            ->log("Job failed: {$jobClass}");

        // Update job statistics
        $this->updateJobStatistics($jobClass, 'failed', $duration);
    }

    /**
     * Log job retry.
     */
    public function logJobRetry(string $jobId, string $jobClass, int $attempt, int $delay = 0): void
    {
        activity('job_retry')
            ->withProperties([
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'attempt' => $attempt,
                'delay' => $delay,
                'retry_at' => now()->addSeconds($delay)->toIso8601String(),
            ])
            ->log("Job retry scheduled: {$jobClass}");
    }

    /**
     * Log job timeout.
     */
    public function logJobTimeout(string $jobId, string $jobClass, int $timeout): void
    {
        activity('job_timeout')
            ->withProperties([
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'timeout' => $timeout,
                'timed_out_at' => now()->toIso8601String(),
            ])
            ->log("Job timed out: {$jobClass}");

        // Update job statistics
        $this->updateJobStatistics($jobClass, 'timeout');
    }

    /**
     * Get payload preview.
     */
    protected function getPayloadPreview(array $payload, int $maxLength = 200): string
    {
        $json = json_encode($payload);
        return strlen($json) > $maxLength 
            ? substr($json, 0, $maxLength) . '...' 
            : $json;
    }

    /**
     * Get result preview.
     */
    protected function getResultPreview(array $result, int $maxLength = 200): string
    {
        $json = json_encode($result);
        return strlen($json) > $maxLength 
            ? substr($json, 0, $maxLength) . '...' 
            : $json;
    }

    /**
     * Update job statistics.
     */
    protected function updateJobStatistics(string $jobClass, string $status, float $duration = null): void
    {
        $key = "job_stats_{$jobClass}_" . now()->format('Y-m-d');
        $stats = Cache::get($key, [
            'total' => 0,
            'completed' => 0,
            'failed' => 0,
            'timeout' => 0,
            'total_duration' => 0,
            'min_duration' => null,
            'max_duration' => null,
        ]);

        $stats['total']++;
        $stats[$status] = ($stats[$status] ?? 0) + 1;

        if ($duration !== null) {
            $stats['total_duration'] += $duration;
            $stats['min_duration'] = $stats['min_duration'] === null 
                ? $duration 
                : min($stats['min_duration'], $duration);
            $stats['max_duration'] = $stats['max_duration'] === null 
                ? $duration 
                : max($stats['max_duration'], $duration);
        }

        Cache::put($key, $stats, 86400); // Store for 24 hours
    }

    /**
     * Get job statistics.
     */
    public function getJobStatistics(string $jobClass = null, Carbon $date = null): array
    {
        $date = $date ?? now();
        
        if ($jobClass) {
            $key = "job_stats_{$jobClass}_" . $date->format('Y-m-d');
            $stats = Cache::get($key, []);
            
            if (!empty($stats) && $stats['total'] > 0) {
                $stats['success_rate'] = ($stats['completed'] / $stats['total']) * 100;
                $stats['average_duration'] = $stats['total_duration'] / $stats['total'];
            }
            
            return $stats;
        }

        // Get all job classes from today's activities
        $jobClasses = activity()
            ->inLog('job_dispatched')
            ->whereDate('created_at', $date)
            ->get()
            ->pluck('properties.job_class')
            ->unique();

        $allStats = [];
        foreach ($jobClasses as $class) {
            $allStats[$class] = $this->getJobStatistics($class, $date);
        }

        return $allStats;
    }

    /**
     * Get queue statistics.
     */
    public function getQueueStatistics(string $queue = null): array
    {
        $query = activity()
            ->inLog('job_dispatched')
            ->where('created_at', '>=', now()->subHour());

        if ($queue) {
            $query->where('properties->queue', $queue);
        }

        $dispatched = $query->count();

        $completed = activity()
            ->inLog('job_completed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $failed = activity()
            ->inLog('job_failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $processing = $dispatched - $completed - $failed;

        return [
            'dispatched' => $dispatched,
            'completed' => $completed,
            'failed' => $failed,
            'processing' => max(0, $processing),
            'success_rate' => $dispatched > 0 ? ($completed / $dispatched) * 100 : 0,
        ];
    }

    /**
     * Get failed jobs.
     */
    public function getFailedJobs(int $limit = 50): array
    {
        return activity()
            ->inLog('job_failed')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'job_id' => $activity->properties['job_id'],
                    'job_class' => $activity->properties['job_class'],
                    'message' => $activity->properties['message'],
                    'attempt' => $activity->properties['attempt'],
                    'failed_at' => $activity->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get long-running jobs.
     */
    public function getLongRunningJobs(float $thresholdSeconds = 300): array
    {
        $runningJobs = [];
        $keys = Cache::get('job_start_*');

        foreach ($keys as $key) {
            $startTime = Cache::get($key);
            $duration = microtime(true) - $startTime;
            
            if ($duration > $thresholdSeconds) {
                $jobId = str_replace('job_start_', '', $key);
                $runningJobs[] = [
                    'job_id' => $jobId,
                    'duration' => $duration,
                    'started_at' => Carbon::createFromTimestamp($startTime),
                ];
            }
        }

        return $runningJobs;
    }
}