<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
        'typical_duration_days',
        'requires_light',
        'requires_watering',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'typical_duration_days' => 'integer',
        'requires_light' => 'boolean',
        'requires_watering' => 'boolean',
    ];

    /**
     * Get the crops for this stage.
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'current_stage_id');
    }

    /**
     * Get options for select fields (active stages only).
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
     * Get all active crop stages.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find crop stage by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is the soaking stage.
     */
    public function isSoaking(): bool
    {
        return $this->code === 'soaking';
    }

    /**
     * Check if this is the germination stage.
     */
    public function isGermination(): bool
    {
        return $this->code === 'germination';
    }

    /**
     * Check if this is the blackout stage.
     */
    public function isBlackout(): bool
    {
        return $this->code === 'blackout';
    }

    /**
     * Check if this is the light stage.
     */
    public function isLight(): bool
    {
        return $this->code === 'light';
    }

    /**
     * Check if this is the harvested stage.
     */
    public function isHarvested(): bool
    {
        return $this->code === 'harvested';
    }

    /**
     * Check if this stage is a pre-harvest stage.
     */
    public function isPreHarvest(): bool
    {
        return !$this->isHarvested();
    }

    /**
     * Check if this stage is the first stage.
     */
    public function isFirstStage(): bool
    {
        // Check for soaking first, then fall back to germination for backward compatibility
        if ($this->isSoaking()) {
            return true;
        }
        
        // If soaking stage doesn't exist, germination is the first stage
        $soakingStage = static::findByCode('soaking');
        if (!$soakingStage) {
            return $this->isGermination();
        }
        
        return false;
    }

    /**
     * Check if this stage is the final stage.
     */
    public function isFinalStage(): bool
    {
        return $this->isHarvested();
    }

    /**
     * Get the next stage in the workflow.
     */
    public function getNextStage(): ?self
    {
        return static::where('sort_order', '>', $this->sort_order)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Get the next viable stage based on recipe timing (skipping stages with 0 days)
     */
    public function getNextViableStage($recipe): ?self
    {
        $currentStage = $this;
        $nextStage = $this->getNextStage();
        
        // Handle soaking → germination transition
        if ($this->isSoaking()) {
            $germinationStage = static::findByCode('germination');
            if ($germinationStage) {
                return $germinationStage;
            }
        }
        
        while ($nextStage) {
            // Check if this stage should be skipped based on recipe
            $shouldSkip = false;
            
            if ($nextStage->code === 'blackout' && ($recipe->blackout_days ?? 0) <= 0) {
                $shouldSkip = true;
            }
            
            if (!$shouldSkip) {
                return $nextStage;
            }
            
            // This stage should be skipped, check the next one
            $nextStage = $nextStage->getNextStage();
        }
        
        return null;
    }

    /**
     * Get the previous stage in the workflow.
     */
    public function getPreviousStage(): ?self
    {
        return static::where('sort_order', '<', $this->sort_order)
            ->where('is_active', true)
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    /**
     * Check if this stage can transition to another stage.
     */
    public function canTransitionTo(CropStage $targetStage): bool
    {
        // Generally, stages should progress in order
        return $targetStage->sort_order > $this->sort_order;
    }

    /**
     * Get the environmental requirements for this stage.
     */
    public function getEnvironmentalRequirements(): array
    {
        return [
            'light' => $this->requires_light,
            'watering' => $this->requires_watering,
            'typical_duration_days' => $this->typical_duration_days,
        ];
    }
}