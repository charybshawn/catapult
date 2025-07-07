<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityLogBulkOperation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_log_bulk_operations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'batch_uuid',
        'operation_type',
        'model_type',
        'total_records',
        'processed_records',
        'failed_records',
        'status',
        'parameters',
        'results',
        'error_message',
        'initiated_by',
        'started_at',
        'completed_at',
        'execution_time_seconds',
        'memory_peak_mb',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parameters' => 'array',
        'results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'execution_time_seconds' => 'float',
        'memory_peak_mb' => 'float',
    ];

    /**
     * Get the user who initiated the bulk operation.
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the activity logs associated with this bulk operation.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'batch_uuid', 'batch_uuid');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by operation type.
     */
    public function scopeByOperationType($query, string $type)
    {
        return $query->where('operation_type', $type);
    }

    /**
     * Scope to filter by model type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Mark the operation as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the operation as completed.
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
     * Mark the operation as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'execution_time_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'memory_peak_mb' => memory_get_peak_usage(true) / 1024 / 1024,
        ]);
    }

    /**
     * Increment processed records count.
     */
    public function incrementProcessed(int $count = 1): void
    {
        $this->increment('processed_records', $count);
    }

    /**
     * Increment failed records count.
     */
    public function incrementFailed(int $count = 1): void
    {
        $this->increment('failed_records', $count);
    }

    /**
     * Get the success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->processed_records == 0) {
            return 0;
        }

        return round((($this->processed_records - $this->failed_records) / $this->processed_records) * 100, 2);
    }

    /**
     * Check if the operation is complete.
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    /**
     * Check if the operation is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the operation failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed' || $this->failed_records > 0;
    }
}