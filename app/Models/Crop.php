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
        'planting_at',
        'germination_at',
        'blackout_at',
        'light_at',
        'harvested_at',
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
        'planting_at' => 'datetime',
        'germination_at' => 'datetime',
        'blackout_at' => 'datetime',
        'light_at' => 'datetime',
        'harvested_at' => 'datetime',
        'watering_suspended_at' => 'datetime',
        'harvest_weight_grams' => 'float',
        'tray_number' => 'string',
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
     * Get the seed variety for this crop through the recipe.
     */
    public function seedVariety()
    {
        if ($this->recipe) {
            return $this->recipe->seedVariety;
        }
        return null;
    }
    
    /**
     * For compatibility with the dashboard template - tray is actually just a string field.
     */
    public function tray()
    {
        // This is a workaround to provide a tray "object" with tray_number property
        return new class($this->tray_number) {
            public $tray_number;
            
            public function __construct($tray_number) {
                $this->tray_number = $tray_number;
            }
        };
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
        $now = now();
        
        $this->current_stage = match ($currentStage) {
            'planting' => 'germination',
            'germination' => 'blackout',
            'blackout' => 'light',
            'light' => 'harvested',
            default => $this->current_stage,
        };
        
        // Record timestamp for the new stage
        $stageTimestampField = "{$this->current_stage}_at";
        $this->$stageTimestampField = $now;
        
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
        $stageField = "{$this->current_stage}_at";
        
        if (!$this->$stageField) {
            return 0;
        }
        
        return $this->$stageField->diffInDays(now());
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
                'planting_at',
                'germination_at',
                'blackout_at',
                'light_at',
                'harvested_at',
                'harvest_weight_grams',
                'watering_suspended_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    /**
     * Set the initial planting timestamp when creating a new crop.
     */
    protected static function booted()
    {
        static::creating(function ($crop) {
            // Set planting_at timestamp when creating a new crop in planting stage
            if ($crop->current_stage === 'planting' && !$crop->planting_at) {
                $crop->planting_at = $crop->planted_at ?? now();
            }
        });
        
        static::created(function ($crop) {
            // Schedule stage transition tasks
            app(\App\Services\CropTaskService::class)->scheduleAllStageTasks($crop);
        });
        
        static::updated(function ($crop) {
            // If the stage has changed or planted_at has changed, recalculate tasks
            if ($crop->isDirty('current_stage') || $crop->isDirty('planted_at')) {
                app(\App\Services\CropTaskService::class)->scheduleAllStageTasks($crop);
            }
        });
    }
    
    /**
     * Reset to a specific stage and clear future stage timestamps.
     */
    public function resetToStage(string $newStage): void
    {
        $currentStage = $this->current_stage;
        $now = now();
        
        // Set the new stage
        $this->current_stage = $newStage;
        
        // Update the timestamp for the current stage if it's not already set
        $stageTimestampField = "{$newStage}_at";
        if (!$this->$stageTimestampField) {
            $this->$stageTimestampField = $now;
        }
        
        // Clear all timestamps for stages that come after the current stage
        $stageOrder = ['planting', 'germination', 'blackout', 'light', 'harvested'];
        $currentStageIndex = array_search($newStage, $stageOrder);
        
        // If we found the stage in our ordered list
        if ($currentStageIndex !== false) {
            // Clear timestamps for all stages after the current one
            for ($i = $currentStageIndex + 1; $i < count($stageOrder); $i++) {
                $fieldName = "{$stageOrder[$i]}_at";
                $this->$fieldName = null;
            }
            
            // Also clear harvest weight if we're no longer at the harvested stage
            if ($newStage !== 'harvested') {
                $this->harvest_weight_grams = null;
            }
        }
        
        $this->save();
    }
}
