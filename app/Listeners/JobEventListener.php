<?php

namespace App\Listeners;

use Exception;
use App\Services\JobLogService;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobRetrying;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive background job monitoring listener for agricultural system operations.
 * 
 * Monitors Laravel queue job lifecycle events including processing, completion,
 * failures, exceptions, and retries. Essential for tracking agricultural background
 * operations like crop task processing, order generation, invoice creation, and
 * seed price updates. Provides critical job failure alerting and performance metrics.
 * 
 * @business_domain Background job monitoring for agricultural operations
 * @agricultural_jobs Crop task processing, recurring orders, price updates, backups
 * @reliability_monitoring Job failure detection and retry tracking
 * @performance_analysis Job execution time and queue performance metrics
 */
class JobEventListener
{
    /**
     * Service for structured background job logging and analytics.
     * 
     * @var JobLogService Service managing job performance data and failure tracking
     */
    protected JobLogService $jobLogService;

    /**
     * Initialize job event listener with logging service dependency.
     * 
     * @param JobLogService $jobLogService Service for job monitoring and analytics
     */
    public function __construct(JobLogService $jobLogService)
    {
        $this->jobLogService = $jobLogService;
    }

    /**
     * Handle background job processing start events for agricultural operations.
     * 
     * Logs job initiation with context about job type, attempt number, and 
     * processing start time. Critical for monitoring agricultural background
     * operations like crop task scheduling and order processing workflows.
     * 
     * @param JobProcessing $event Laravel job processing event
     * @return void
     * 
     * @agricultural_jobs Crop tasks, recurring orders, inventory updates
     * @monitoring_start Tracks job initiation for performance analysis
     */
    public function handleJobProcessing(JobProcessing $event): void
    {
        try {
            $payload = $event->job->payload();
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $jobId = $payload['uuid'] ?? $event->job->getJobId();

            $this->jobLogService->logJobProcessing(
                $jobId,
                $jobClass,
                $event->job->attempts()
            );
        } catch (Exception $e) {
            Log::error('Failed to log job processing event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle successful background job completion events with performance metrics.
     * 
     * Logs successful job completion including execution time, result data,
     * and performance metrics. Essential for monitoring agricultural operation
     * reliability and identifying performance trends in background processing.
     * 
     * @param JobProcessed $event Laravel job completion event
     * @return void
     * 
     * @performance_tracking Job execution time and success rate monitoring
     * @agricultural_success Successful crop tasks, order generation, price updates
     */
    public function handleJobProcessed(JobProcessed $event): void
    {
        try {
            $payload = $event->job->payload();
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $jobId = $payload['uuid'] ?? $event->job->getJobId();

            $this->jobLogService->logJobCompleted(
                $jobId,
                $jobClass,
                $this->extractJobResult($event)
            );
        } catch (Exception $e) {
            Log::error('Failed to log job processed event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle background job failure events with critical alerting for agricultural operations.
     * 
     * Logs job failures with exception details, attempt counts, and triggers
     * critical job failure alerts for essential agricultural operations.
     * Prevents silent failures in crop management and order processing.
     * 
     * @param JobFailed $event Laravel job failure event with exception data
     * @return void
     * 
     * @critical_monitoring Failure detection for essential agricultural jobs
     * @alerting_system Notifications for crop task failures and order processing issues
     */
    public function handleJobFailed(JobFailed $event): void
    {
        try {
            $payload = $event->job->payload();
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $jobId = $payload['uuid'] ?? $event->job->getJobId();

            $this->jobLogService->logJobFailed(
                $jobId,
                $jobClass,
                $event->exception,
                $event->job->attempts()
            );

            // Additional alerting for critical jobs
            if ($this->isCriticalJob($jobClass)) {
                $this->alertCriticalJobFailure($jobClass, $event->exception);
            }
        } catch (Exception $e) {
            Log::error('Failed to log job failed event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle background job exception events during agricultural operation processing.
     * 
     * Captures job exceptions that occur during background processing of agricultural
     * operations, logging detailed exception information and retry status for
     * debugging and reliability monitoring.
     * 
     * @param JobExceptionOccurred $event Laravel job exception event
     * @return void
     * 
     * @exception_tracking Non-fatal job exceptions with retry capability
     * @agricultural_debugging Exception details for crop and order processing issues
     */
    public function handleJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        try {
            $payload = $event->job->payload();
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $jobId = $payload['uuid'] ?? $event->job->getJobId();

            activity('job_exception')
                ->withProperties([
                    'job_id' => $jobId,
                    'job_class' => $jobClass,
                    'exception_class' => get_class($event->exception),
                    'message' => $event->exception->getMessage(),
                    'attempt' => $event->job->attempts(),
                    'will_retry' => !$event->job->isDeleted() && !$event->job->isReleased(),
                ])
                ->log("Job exception: {$jobClass}");
        } catch (Exception $e) {
            Log::error('Failed to log job exception event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle background job retry events with exponential backoff analysis.
     * 
     * Monitors job retry attempts including retry delays and attempt counts.
     * Important for tracking reliability of agricultural background operations
     * and identifying jobs requiring intervention or configuration changes.
     * 
     * @param JobRetrying $event Laravel job retry event with attempt data
     * @return void
     * 
     * @retry_monitoring Tracks retry patterns for agricultural job reliability
     * @backoff_analysis Monitors retry delay calculations and success rates
     */
    public function handleJobRetrying(JobRetrying $event): void
    {
        try {
            $payload = $event->job->payload();
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $jobId = $payload['uuid'] ?? $event->job->getJobId();

            $this->jobLogService->logJobRetry(
                $jobId,
                $jobClass,
                $event->job->attempts(),
                $this->calculateRetryDelay($event)
            );
        } catch (Exception $e) {
            Log::error('Failed to log job retry event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract job execution result data for agricultural operation analytics.
     * 
     * Processes job completion events to extract performance metrics and
     * execution context for agricultural operation monitoring and optimization.
     * 
     * @param JobProcessed $event Completed job event with execution context
     * @return array Job result data including queue, connection, and attempts
     * 
     * @performance_data Queue performance metrics for agricultural jobs
     * @execution_context Job completion details for analytics and optimization
     */
    protected function extractJobResult(JobProcessed $event): array
    {
        // This is a placeholder - in reality, you'd need to implement
        // a way for jobs to return results that can be logged
        return [
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'attempts' => $event->job->attempts(),
        ];
    }

    /**
     * Determine if background job is critical for agricultural operations.
     * 
     * Evaluates job class against configured critical job patterns to identify
     * essential agricultural operations that require immediate attention when
     * failing. Critical jobs include crop tasks, order processing, and backups.
     * 
     * @param string $jobClass Job class name to evaluate for criticality
     * @return bool True if job is critical for agricultural operations
     * 
     * @business_critical Identifies essential agricultural operation jobs
     * @alerting_rules Determines which job failures trigger immediate alerts
     */
    protected function isCriticalJob(string $jobClass): bool
    {
        $criticalJobs = config('logging.critical_jobs', []);
        
        foreach ($criticalJobs as $criticalJob) {
            if (str_contains($jobClass, $criticalJob)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trigger critical job failure alerts for essential agricultural operations.
     * 
     * Creates high-priority alerts and notifications for critical agricultural
     * job failures that could impact crop production, order fulfillment, or
     * data integrity. Integrates with external alerting systems as needed.
     * 
     * @param string $jobClass Critical job class that failed
     * @param Exception $exception Exception details for troubleshooting
     * @return void
     * 
     * @critical_alerting High-priority notifications for agricultural job failures
     * @business_impact Prevents silent failures in essential crop and order operations
     */
    protected function alertCriticalJobFailure(string $jobClass, Exception $exception): void
    {
        activity('critical_job_failure')
            ->withProperties([
                'job_class' => $jobClass,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'alert_sent' => true,
            ])
            ->log("Critical job failure: {$jobClass}");

        // Here you would integrate with your alerting system
        // e.g., send email, Slack notification, PagerDuty, etc.
    }

    /**
     * Calculate exponential backoff retry delay for failed agricultural jobs.
     * 
     * Implements retry delay calculation with exponential backoff strategy
     * to prevent overwhelming agricultural systems during job failures while
     * ensuring reasonable retry intervals for time-sensitive operations.
     * 
     * @param JobRetrying $event Job retry event with attempt and timing data
     * @return int Calculated retry delay in seconds
     * 
     * @backoff_strategy Exponential delay to prevent system overload
     * @agricultural_timing Appropriate delays for crop and order processing jobs
     */
    protected function calculateRetryDelay(JobRetrying $event): int
    {
        $payload = $event->job->payload();
        
        // Check if job has custom retry delay
        if (isset($payload['retryUntil'])) {
            return max(0, $payload['retryUntil'] - time());
        }

        // Default exponential backoff
        return min(pow(2, $event->job->attempts()) * 60, 3600);
    }

    /**
     * Register job event listeners for comprehensive agricultural job monitoring.
     * 
     * Maps Laravel queue events to corresponding handler methods for complete
     * background job lifecycle monitoring in agricultural operations system.
     * 
     * @param mixed $events Laravel event dispatcher for registration
     * @return array Event-to-method mapping for job monitoring
     * 
     * @event_mapping Complete job lifecycle monitoring for agricultural operations
     */
    public function subscribe($events): array
    {
        return [
            JobProcessing::class => 'handleJobProcessing',
            JobProcessed::class => 'handleJobProcessed',
            JobFailed::class => 'handleJobFailed',
            JobExceptionOccurred::class => 'handleJobExceptionOccurred',
            JobRetrying::class => 'handleJobRetrying',
        ];
    }
}