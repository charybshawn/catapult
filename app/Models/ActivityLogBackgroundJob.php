<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLogBackgroundJob extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_log_background_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'activity_log_id',
        'job_id',
        'job_class',
        'queue_name',
        'status',
        'payload',
        'attempts',
        'max_attempts',
        'queued_at',
        'started_at',
        'completed_at',
        'failed_at',
        'execution_time_seconds',
        'memory_peak_mb',
        'exception_message',
        'exception_trace',
        'user_id',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'tags' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'execution_time_seconds' => 'float',
        'memory_peak_mb' => 'float',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
    ];

    /**
     * Get the activity log that owns the background job.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_log_id');
    }

    /**
     * Get the user who initiated the job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by job class.
     */
    public function scopeForJobClass($query, string $jobClass)
    {
        return $query->where('job_class', $jobClass);
    }

    /**
     * Scope to filter by queue name.
     */
    public function scopeOnQueue($query, string $queueName)
    {
        return $query->where('queue_name', $queueName);
    }

    /**
     * Scope to filter failed jobs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter pending jobs.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter jobs that are currently processing.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to filter jobs with tags.
     */
    public function scopeWithTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Mark the job as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the job as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'execution_time_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'memory_peak_mb' => memory_get_peak_usage(true) / 1024 / 1024,
        ]);
    }

    /**
     * Mark the job as failed.
     */
    public function markAsFailed(string $exceptionMessage = null, string $exceptionTrace = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'exception_message' => $exceptionMessage,
            'exception_trace' => $exceptionTrace,
            'execution_time_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'memory_peak_mb' => memory_get_peak_usage(true) / 1024 / 1024,
        ]);
    }

    /**
     * Mark the job as retrying.
     */
    public function markAsRetrying(): void
    {
        $this->update([
            'status' => 'retrying',
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Check if the job has exceeded max attempts.
     */
    public function hasExceededMaxAttempts(): bool
    {
        return $this->max_attempts && $this->attempts >= $this->max_attempts;
    }

    /**
     * Check if the job is complete.
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    /**
     * Check if the job is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the job failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get the wait time in seconds.
     */
    public function getWaitTimeAttribute(): ?float
    {
        if (!$this->started_at || !$this->queued_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->queued_at);
    }

    /**
     * Get a shortened job class name.
     */
    public function getShortJobClassAttribute(): string
    {
        return class_basename($this->job_class);
    }
}