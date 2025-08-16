<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropStage;

/**
 * Service for automatically calculating crop stages based on timestamps.
 * 
 * This service determines which stage a crop should be in based on which
 * timestamp fields have been set, following proper stage progression order.
 */
class CropStageCalculator
{
    /**
     * Cache for stage mappings
     */
    protected $stageCache = null;
    /**
     * Calculate the appropriate current_stage_id for a crop based on its timestamps.
     * 
     * Logic:
     * - If harvested_at exists → stage is "harvested"
     * - If light_at exists → stage is "light" 
     * - If blackout_at exists → stage is "blackout"
     * - If germination_at exists → stage is "germination"
     * - If soaking_at exists → stage is "soaking"
     * - If none exist → default to "germination" stage
     *
     * @param Crop $crop
     * @return int The appropriate stage ID
     */
    public function calculateStageId(Crop $crop): int
    {
        // Cache the stage mappings to avoid repeated queries
        if ($this->stageCache === null) {
            $this->stageCache = CropStage::all()->keyBy('code');
        }

        // Check timestamps in reverse order of progression (highest completed stage wins)
        if ($crop->harvested_at) {
            return $this->stageCache->get('harvested')?->id ?? $this->getDefaultStageId();
        }

        if ($crop->light_at) {
            return $this->stageCache->get('light')?->id ?? $this->getDefaultStageId();
        }

        if ($crop->blackout_at) {
            return $this->stageCache->get('blackout')?->id ?? $this->getDefaultStageId();
        }

        if ($crop->germination_at) {
            return $this->stageCache->get('germination')?->id ?? $this->getDefaultStageId();
        }

        if ($crop->soaking_at) {
            return $this->stageCache->get('soaking')?->id ?? $this->getDefaultStageId();
        }

        // No timestamps set, default to germination stage
        return $this->getDefaultStageId();
    }

    /**
     * Update the current_stage_id for a crop if it has changed.
     * 
     * @param Crop $crop
     * @return bool True if the stage was updated, false if no change
     */
    public function updateCropStage(Crop $crop): bool
    {
        $calculatedStageId = $this->calculateStageId($crop);
        
        if ($crop->current_stage_id !== $calculatedStageId) {
            $crop->current_stage_id = $calculatedStageId;
            return true;
        }

        return false;
    }

    /**
     * Get the default stage ID (germination).
     * 
     * @return int
     */
    protected function getDefaultStageId(): int
    {
        if ($this->stageCache === null) {
            $this->stageCache = CropStage::all()->keyBy('code');
        }
        
        $germinationStage = $this->stageCache->get('germination');
        return $germinationStage?->id ?? 2; // Fallback to ID 2 if not found
    }

    /**
     * Validate that stage timestamps follow proper chronological order.
     * 
     * This method checks that timestamps are in the correct sequence according
     * to stage progression. It returns any validation errors found.
     *
     * @param Crop $crop
     * @return array Array of validation error messages (empty if valid)
     */
    public function validateTimestampSequence(Crop $crop): array
    {
        $errors = [];
        $timestamps = [];

        // Collect non-null timestamps with their stage names
        if ($crop->soaking_at) {
            $timestamps['soaking'] = $crop->soaking_at;
        }
        if ($crop->germination_at) {
            $timestamps['germination'] = $crop->germination_at;
        }
        if ($crop->blackout_at) {
            $timestamps['blackout'] = $crop->blackout_at;
        }
        if ($crop->light_at) {
            $timestamps['light'] = $crop->light_at;
        }
        if ($crop->harvested_at) {
            $timestamps['harvested'] = $crop->harvested_at;
        }

        // Define the expected order
        $expectedOrder = ['soaking', 'germination', 'blackout', 'light', 'harvested'];
        
        // Check that timestamps follow the expected sequence
        $lastTimestamp = null;
        $lastStage = null;

        foreach ($expectedOrder as $stage) {
            if (isset($timestamps[$stage])) {
                $currentTimestamp = $timestamps[$stage];
                
                if ($lastTimestamp && $currentTimestamp < $lastTimestamp) {
                    $errors[] = "Stage '{$stage}' timestamp ({$currentTimestamp->format('Y-m-d H:i:s')}) cannot be earlier than '{$lastStage}' timestamp ({$lastTimestamp->format('Y-m-d H:i:s')})";
                }
                
                $lastTimestamp = $currentTimestamp;
                $lastStage = $stage;
            }
        }

        return $errors;
    }

    /**
     * Determine if a crop can automatically advance to the next stage.
     * 
     * This checks if all required conditions are met for automatic advancement
     * based on recipe timing and current stage.
     *
     * @param Crop $crop
     * @return array ['can_advance' => bool, 'next_stage' => ?CropStage, 'reason' => string]
     */
    public function canAutoAdvance(Crop $crop): array
    {
        if (!$crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }

        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        $currentStage = $crop->currentStage;
        $recipe = $crop->recipe;

        if (!$currentStage || !$recipe) {
            return [
                'can_advance' => false,
                'next_stage' => null,
                'reason' => 'Missing current stage or recipe information'
            ];
        }

        // Get next viable stage (skipping stages with 0 days)
        $nextStage = $currentStage->getNextViableStage($recipe);
        
        if (!$nextStage) {
            return [
                'can_advance' => false,
                'next_stage' => null,
                'reason' => 'Already at final stage or no next stage available'
            ];
        }

        // Check if enough time has passed in current stage
        $stageStartTime = $this->getStageStartTime($crop, $currentStage);
        
        if (!$stageStartTime) {
            return [
                'can_advance' => false,
                'next_stage' => $nextStage,
                'reason' => 'No start time available for current stage'
            ];
        }

        $stageDuration = $this->getStageDurationFromRecipe($crop, $currentStage);
        
        if ($stageDuration === null) {
            return [
                'can_advance' => false,
                'next_stage' => $nextStage,
                'reason' => 'Stage duration not defined in recipe'
            ];
        }

        $timeInStage = now()->diffInHours($stageStartTime);
        $requiredHours = $stageDuration * 24; // Convert days to hours

        $canAdvance = $timeInStage >= $requiredHours;

        return [
            'can_advance' => $canAdvance,
            'next_stage' => $nextStage,
            'reason' => $canAdvance 
                ? 'Ready to advance' 
                : "Stage duration: {$timeInStage}h of {$requiredHours}h required"
        ];
    }

    /**
     * Get the start time for a specific stage.
     * 
     * @param Crop $crop
     * @param CropStage $stage
     * @return \Carbon\Carbon|null
     */
    protected function getStageStartTime(Crop $crop, CropStage $stage): ?\Carbon\Carbon
    {
        return match ($stage->code) {
            'soaking' => $crop->soaking_at,
            'germination' => $crop->germination_at,
            'blackout' => $crop->blackout_at,
            'light' => $crop->light_at,
            'harvested' => $crop->harvested_at,
            default => null,
        };
    }

    /**
     * Get the stage duration from the recipe.
     * 
     * @param Crop $crop
     * @param CropStage $stage
     * @return int|null Duration in days
     */
    protected function getStageDurationFromRecipe(Crop $crop, CropStage $stage): ?int
    {
        $recipe = $crop->recipe;
        
        return match ($stage->code) {
            'soaking' => $recipe->seed_soak_hours ? ceil($recipe->seed_soak_hours / 24) : 1,
            'germination' => $recipe->germination_days,
            'blackout' => $recipe->blackout_days,
            'light' => $recipe->light_days,
            default => null,
        };
    }
}