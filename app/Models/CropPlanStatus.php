<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropPlanStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the crop plans for this status.
     */
    public function cropPlans(): HasMany
    {
        return $this->hasMany(CropPlan::class, 'status_id');
    }

    /**
     * Get options for select fields (active statuses only).
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get all active crop plan statuses.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find crop plan status by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is a draft status.
     */
    public function isDraft(): bool
    {
        return $this->code === 'draft';
    }

    /**
     * Check if this is an active status.
     */
    public function isActive(): bool
    {
        return $this->code === 'active';
    }

    /**
     * Check if this is a completed status.
     */
    public function isCompleted(): bool
    {
        return $this->code === 'completed';
    }

    /**
     * Check if this is a cancelled status.
     */
    public function isCancelled(): bool
    {
        return $this->code === 'cancelled';
    }

    /**
     * Check if this status indicates the plan is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->isActive();
    }

    /**
     * Check if this status is final (no further changes allowed).
     */
    public function isFinal(): bool
    {
        return in_array($this->code, ['completed', 'cancelled']);
    }

    /**
     * Check if this status allows modifications.
     */
    public function allowsModifications(): bool
    {
        return !$this->isFinal();
    }

    /**
     * Check if crops can be created from this plan status.
     */
    public function allowsCropCreation(): bool
    {
        return $this->isActive();
    }
}