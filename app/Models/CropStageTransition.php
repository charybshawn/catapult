<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropStageTransition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'crop_batch_id',
        'crop_count',
        'from_stage_id',
        'to_stage_id',
        'transition_at',
        'recorded_at',
        'user_id',
        'user_name',
        'reason',
        'metadata',
        'validation_warnings',
        'succeeded_count',
        'failed_count',
        'failed_crops',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transition_at' => 'datetime',
        'recorded_at' => 'datetime',
        'metadata' => 'array',
        'validation_warnings' => 'array',
        'failed_crops' => 'array',
    ];

    /**
     * Get the batch associated with this transition.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(CropBatch::class, 'crop_batch_id');
    }

    /**
     * Get the from stage.
     */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(CropStage::class, 'from_stage_id');
    }

    /**
     * Get the to stage.
     */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(CropStage::class, 'to_stage_id');
    }

    /**
     * Get the user who performed the transition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get transitions for a specific batch.
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('crop_batch_id', $batchId);
    }

    /**
     * Scope to get transitions by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent transitions.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('transition_at', '>=', now()->subDays($days));
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->succeeded_count + $this->failed_count;
        return $total > 0 ? round(($this->succeeded_count / $total) * 100, 2) : 100;
    }

    /**
     * Check if this was a bulk operation.
     */
    public function getIsBulkAttribute(): bool
    {
        return in_array($this->type, ['bulk_advance', 'bulk_revert']);
    }

    /**
     * Check if this was a reversion.
     */
    public function getIsReversionAttribute(): bool
    {
        return in_array($this->type, ['revert', 'bulk_revert']);
    }
}