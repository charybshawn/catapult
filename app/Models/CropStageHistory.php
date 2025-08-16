<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\CropTimeCalculator;

class CropStageHistory extends Model
{
    use HasFactory;

    protected $table = 'crop_stage_history';

    protected $fillable = [
        'crop_id',
        'crop_batch_id',
        'stage_id',
        'entered_at',
        'exited_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'exited_at' => 'datetime',
    ];

    /**
     * Get the crop this history belongs to
     */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    /**
     * Get the crop batch this history belongs to
     */
    public function cropBatch(): BelongsTo
    {
        return $this->belongsTo(CropBatch::class);
    }

    /**
     * Get the stage for this history entry
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(CropStage::class, 'stage_id');
    }

    /**
     * Get the user who created this history entry
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->entered_at) {
            return null;
        }

        $endTime = $this->exited_at ?: now();
        return $this->entered_at->diffInMinutes($endTime);
    }

    /**
     * Get human-readable duration display
     */
    public function getDurationDisplayAttribute(): ?string
    {
        $minutes = $this->duration_minutes;
        
        if ($minutes === null) {
            return null;
        }

        return app(CropTimeCalculator::class)->formatTimeDisplay($minutes);
    }

    /**
     * Check if this stage is currently active (no exit time)
     */
    public function getIsActiveAttribute(): bool
    {
        return is_null($this->exited_at);
    }

    /**
     * Scope to get active stage entries
     */
    public function scopeActive($query)
    {
        return $query->whereNull('exited_at');
    }

    /**
     * Scope to get completed stage entries
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('exited_at');
    }

    /**
     * Scope to get history for a specific crop
     */
    public function scopeForCrop($query, $cropId)
    {
        return $query->where('crop_id', $cropId);
    }

    /**
     * Scope to get history for a specific batch
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('crop_batch_id', $batchId);
    }
}