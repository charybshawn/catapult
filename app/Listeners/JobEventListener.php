<?php

namespace App\Listeners;

use App\Services\JobLogService;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobRetrying;
use Illuminate\Support\Facades\Log;

class JobEventListener
{
    protected JobLogService $jobLogService;

    public function __construct(JobLogService $jobLogService)
    {
        $this->jobLogService = $jobLogService;
    }

    /**
     * Handle job processing events.
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
        } catch (\Exception $e) {
            Log::error('Failed to log job processing event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job processed events.
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
        } catch (\Exception $e) {
            Log::error('Failed to log job processed event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failed events.
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
        } catch (\Exception $e) {
            Log::error('Failed to log job failed event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job exception events.
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
        } catch (\Exception $e) {
            Log::error('Failed to log job exception event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job retrying events.
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
        } catch (\Exception $e) {
            Log::error('Failed to log job retry event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract job result from event.
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
     * Determine if a job is critical.
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
     * Alert about critical job failure.
     */
    protected function alertCriticalJobFailure(string $jobClass, \Exception $exception): void
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
     * Calculate retry delay.
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
     * Register the listeners for the subscriber.
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