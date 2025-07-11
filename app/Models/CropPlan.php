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
        'aggregated_crop_plan_id',
        'order_id',
        'recipe_id',
        'variety_id',
        'cultivar',
        'status_id',
        'trays_needed',
        'grams_needed',
        'grams_per_tray',
        'plant_by_date',
        'seed_soak_date',
        'expected_harvest_date',
        'delivery_date',
        'calculation_details',
        'order_items_included',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
        'admin_notes',
        'is_missing_recipe',
        'missing_recipe_notes',
    ];

    protected $casts = [
        'plant_by_date' => 'date',
        'seed_soak_date' => 'date',
        'expected_harvest_date' => 'date',
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
        'calculation_details' => 'array',
        'order_items_included' => 'array',
        'is_missing_recipe' => 'boolean',
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

    public function status(): BelongsTo
    {
        return $this->belongsTo(CropPlanStatus::class, 'status_id');
    }

    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'crop_plan_id');
    }

    public function aggregatedCropPlan(): BelongsTo
    {
        return $this->belongsTo(CropPlanAggregate::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class, 'variety_id');
    }

    public function isApproved(): bool
    {
        return $this->status?->code === 'active';
    }

    public function isDraft(): bool
    {
        return $this->status?->code === 'draft';
    }

    public function canBeApproved(): bool
    {
        return $this->status?->code === 'draft';
    }

    public function canGenerateCrops(): bool
    {
        return $this->status?->code === 'active';
    }

    public function approve(?User $user = null): void
    {
        if (!$this->canBeApproved()) {
            throw new \Exception('Crop plan cannot be approved in current status: ' . $this->status?->name);
        }

        $activeStatus = CropPlanStatus::findByCode('active');
        $this->update([
            'status_id' => $activeStatus->id,
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);
    }

    public function markAsGenerating(): void
    {
        if (!$this->canGenerateCrops()) {
            throw new \Exception('Crop plan must be approved before generating crops');
        }

        // Note: 'generating' status doesn't exist in new system, using 'active'
        $activeStatus = CropPlanStatus::findByCode('active');
        $this->update(['status_id' => $activeStatus->id]);
    }

    public function markAsCompleted(): void
    {
        $completedStatus = CropPlanStatus::findByCode('completed');
        $this->update(['status_id' => $completedStatus->id]);
    }

    public function cancel(): void
    {
        $cancelledStatus = CropPlanStatus::findByCode('cancelled');
        $this->update(['status_id' => $cancelledStatus->id]);
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color ?? 'gray';
    }

    public function getDaysUntilPlantingAttribute(): int
    {
        return now()->diffInDays($this->plant_by_date, false);
    }

    public function isOverdue(): bool
    {
        return $this->plant_by_date->isPast() && in_array($this->status?->code, ['draft', 'active']);
    }

    public function isUrgent(): bool
    {
        return $this->days_until_planting <= 2 && in_array($this->status?->code, ['draft', 'active']);
    }
}
