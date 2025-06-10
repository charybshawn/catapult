<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class CropPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'recipe_id',
        'status',
        'trays_needed',
        'grams_needed',
        'grams_per_tray',
        'plant_by_date',
        'expected_harvest_date',
        'delivery_date',
        'calculation_details',
        'order_items_included',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
        'admin_notes',
    ];

    protected $casts = [
        'plant_by_date' => 'date',
        'expected_harvest_date' => 'date',
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
        'calculation_details' => 'array',
        'order_items_included' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'crop_plan_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function canGenerateCrops(): bool
    {
        return $this->status === 'approved';
    }

    public function approve(User $user = null): void
    {
        if (!$this->canBeApproved()) {
            throw new \Exception('Crop plan cannot be approved in current status: ' . $this->status);
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);
    }

    public function markAsGenerating(): void
    {
        if (!$this->canGenerateCrops()) {
            throw new \Exception('Crop plan must be approved before generating crops');
        }

        $this->update(['status' => 'generating']);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'approved' => 'success',
            'generating' => 'warning',
            'completed' => 'primary',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getDaysUntilPlantingAttribute(): int
    {
        return now()->diffInDays($this->plant_by_date, false);
    }

    public function isOverdue(): bool
    {
        return $this->plant_by_date->isPast() && in_array($this->status, ['draft', 'approved']);
    }

    public function isUrgent(): bool
    {
        return $this->days_until_planting <= 2 && in_array($this->status, ['draft', 'approved']);
    }
}
