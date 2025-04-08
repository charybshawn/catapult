<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Crop extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
        'order_id',
        'tray_number',
        'planted_at',
        'current_stage',
        'stage_updated_at',
        'harvest_weight_grams',
        'watering_suspended_at',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'planted_at' => 'datetime',
        'stage_updated_at' => 'datetime',
        'watering_suspended_at' => 'datetime',
        'harvest_weight_grams' => 'float',
    ];
    
    /**
     * Get the recipe for this crop.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
    
    /**
     * Get the order for this crop.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Check if watering is suspended.
     */
    public function isWateringSuspended(): bool
    {
        return $this->watering_suspended_at !== null;
    }
    
    /**
     * Suspend watering.
     */
    public function suspendWatering(): void
    {
        $this->watering_suspended_at = now();
        $this->save();
    }
    
    /**
     * Resume watering.
     */
    public function resumeWatering(): void
    {
        $this->watering_suspended_at = null;
        $this->save();
    }
    
    /**
     * Advance to the next stage.
     */
    public function advanceStage(): void
    {
        $currentStage = $this->current_stage;
        
        $this->current_stage = match ($currentStage) {
            'planting' => 'germination',
            'germination' => 'blackout',
            'blackout' => 'light',
            'light' => 'harvested',
            default => $this->current_stage,
        };
        
        $this->stage_updated_at = now();
        $this->save();
    }
    
    /**
     * Calculate the expected harvest date.
     */
    public function expectedHarvestDate(): ?Carbon
    {
        if (!$this->planted_at) {
            return null;
        }
        
        $recipe = $this->recipe;
        $totalDays = $recipe->totalDays();
        
        return $this->planted_at->copy()->addDays($totalDays);
    }
    
    /**
     * Calculate days in current stage.
     */
    public function daysInCurrentStage(): int
    {
        if (!$this->stage_updated_at) {
            return 0;
        }
        
        return $this->stage_updated_at->diffInDays(now());
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'recipe_id', 
                'order_id', 
                'tray_number',
                'planted_at',
                'current_stage',
                'stage_updated_at',
                'harvest_weight_grams',
                'watering_suspended_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
