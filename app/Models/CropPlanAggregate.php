<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class CropPlanAggregate extends Model
{
    use HasFactory;

    protected $table = 'crop_plans_aggregate';

    protected $fillable = [
        'variety_id',
        'harvest_date',
        'total_grams_needed',
        'total_trays_needed',
        'grams_per_tray',
        'plant_date',
        'seed_soak_date',
        'status',
        'calculation_details',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'plant_date' => 'date',
        'seed_soak_date' => 'date',
        'total_grams_needed' => 'decimal:2',
        'grams_per_tray' => 'decimal:2',
        'calculation_details' => 'array',
    ];

    /**
     * The variety (master seed catalog) this aggregated plan is for
     */
    public function variety(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class, 'variety_id');
    }

    /**
     * The individual crop plans that are part of this aggregation
     */
    public function cropPlans(): HasMany
    {
        return $this->hasMany(CropPlan::class, 'aggregated_crop_plan_id');
    }

    /**
     * The user who created this aggregated plan
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user who last updated this aggregated plan
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get aggregated plans by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get aggregated plans for a specific harvest date range
     */
    public function scopeByHarvestDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('harvest_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get aggregated plans for a specific plant date range
     */
    public function scopeByPlantDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('plant_date', [$startDate, $endDate]);
    }

    /**
     * Get the total number of orders included in this aggregation
     */
    public function getTotalOrdersAttribute()
    {
        // Get from calculation_details if available, otherwise count crop plans
        if ($this->calculation_details && isset($this->calculation_details['total_orders'])) {
            return $this->calculation_details['total_orders'];
        }
        
        // Fallback to counting crop plans with non-null order_id
        return $this->cropPlans()->whereNotNull('order_id')->distinct('order_id')->count('order_id');
    }

    /**
     * Calculate the seed quantity needed in the appropriate unit
     */
    public function calculateSeedQuantity()
    {
        // This would typically involve converting grams to seeds based on 
        // seed weight data from the variety
        // For now, return the grams needed
        return [
            'quantity' => $this->total_grams_needed,
            'unit' => 'grams'
        ];
    }

    /**
     * Check if this aggregated plan can be confirmed
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === 'draft' && $this->cropPlans()->count() > 0;
    }

    /**
     * Confirm this aggregated plan
     */
    public function confirm()
    {
        if (!$this->canBeConfirmed()) {
            throw new \Exception('This aggregated plan cannot be confirmed.');
        }

        $this->update([
            'status' => 'confirmed',
            'updated_by' => auth()->id()
        ]);

        // Update all associated crop plans
        $this->cropPlans()->update(['status_id' => CropPlanStatus::where('code', 'approved')->value('id')]);

        Log::info('Aggregated crop plan confirmed', [
            'id' => $this->id,
            'variety' => $this->variety->common_name,
            'harvest_date' => $this->harvest_date->format('Y-m-d'),
            'crop_plans_count' => $this->cropPlans()->count()
        ]);
    }

    /**
     * Generate individual crops from this aggregated plan
     */
    public function generateCrops()
    {
        if ($this->status !== 'confirmed') {
            throw new \Exception('Only confirmed aggregated plans can generate crops.');
        }

        $this->update([
            'status' => 'in_progress',
            'updated_by' => auth()->id()
        ]);

        // Logic to generate actual crop entries would go here
        // This would typically be handled by a service class
        
        Log::info('Starting crop generation from aggregated plan', [
            'id' => $this->id,
            'variety' => $this->variety->common_name,
            'total_trays' => $this->total_trays_needed
        ]);
    }

    /**
     * Boot method to handle model events
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->created_by) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}