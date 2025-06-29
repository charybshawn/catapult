<?php

namespace App\Services;

use App\Models\Crop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CropLifecycleService
{
    /**
     * The valid crop stages in order
     */
    private const STAGES = [
        'germination',
        'blackout',
        'light',
        'harvested'
    ];

    /**
     * Advance a crop to the next stage in its lifecycle
     * IMPORTANT: This advances ALL crops in the batch to maintain batch integrity
     * 
     * @param Crop $crop The crop to advance (will advance entire batch)
     * @param Carbon|null $timestamp Optional timestamp for when the advancement occurred
     */
    public function advanceStage(Crop $crop, ?Carbon $timestamp = null): void
    {
        $currentStage = $crop->current_stage;
        $nextStage = $this->getNextStage($currentStage);
        
        if (!$nextStage) {
            Log::warning('Cannot advance crop beyond final stage', [
                'crop_id' => $crop->id,
                'current_stage' => $currentStage
            ]);
            return;
        }

        // Find ALL crops in this batch to maintain batch integrity
        $batchCrops = Crop::where('recipe_id', $crop->recipe_id)
            ->where('planting_at', $crop->planting_at)
            ->where('current_stage', $crop->current_stage)
            ->get();

        $advancementTime = $timestamp ?? Carbon::now();
        $count = 0;
        
        // Advance all crops in the batch together
        foreach ($batchCrops as $batchCrop) {
            $batchCrop->current_stage = $nextStage;
            
            // Set stage-specific timestamps
            match ($nextStage) {
                'germination' => $batchCrop->germination_at = $advancementTime,
                'blackout' => $batchCrop->blackout_at = $advancementTime,
                'light' => $batchCrop->light_at = $advancementTime,
                'harvested' => $batchCrop->harvested_at = $advancementTime,
                default => null
            };

            $batchCrop->stage_updated_at = $advancementTime;
            $batchCrop->save();
            $count++;
        }

        Log::info('Crop batch stage advanced', [
            'initiating_crop_id' => $crop->id,
            'batch_size' => $count,
            'recipe_id' => $crop->recipe_id,
            'planting_at' => $crop->planting_at,
            'from_stage' => $currentStage,
            'to_stage' => $nextStage
        ]);
    }

    /**
     * Reset a crop to a specific stage
     */
    public function resetToStage(Crop $crop, string $targetStage): void
    {
        if (!in_array($targetStage, self::STAGES)) {
            throw new \InvalidArgumentException("Invalid stage: {$targetStage}");
        }

        $crop->current_stage = $targetStage;
        $now = Carbon::now();

        // Clear timestamps for stages that come after the target stage
        $stageIndex = array_search($targetStage, self::STAGES);
        
        foreach (self::STAGES as $index => $stage) {
            $timestampField = $this->getStageTimestampField($stage);
            
            if ($index > $stageIndex) {
                // Clear future stage timestamps
                $crop->{$timestampField} = null;
            } elseif ($index === $stageIndex && !$crop->{$timestampField}) {
                // Set current stage timestamp if not set
                $crop->{$timestampField} = $now;
            }
        }

        $crop->stage_updated_at = $now;
        $crop->save();

        Log::info('Crop stage reset', [
            'crop_id' => $crop->id,
            'reset_to_stage' => $targetStage
        ]);
    }

    /**
     * Calculate the expected harvest date for a crop
     */
    public function calculateExpectedHarvestDate(Crop $crop): ?Carbon
    {
        if (!$crop->recipe || !$crop->planting_at) {
            return null;
        }

        $plantedAt = Carbon::parse($crop->planting_at);
        $daysToMaturity = $crop->recipe->days_to_maturity ?? 0;

        if ($daysToMaturity <= 0) {
            return null;
        }

        return $plantedAt->addDays($daysToMaturity);
    }

    /**
     * Calculate how many days the crop has been in its current stage
     */
    public function calculateDaysInCurrentStage(Crop $crop): int
    {
        $stageTimestamp = $this->getCurrentStageTimestamp($crop);
        
        if (!$stageTimestamp) {
            return 0;
        }

        return Carbon::now()->diffInDays(Carbon::parse($stageTimestamp));
    }

    /**
     * Check if watering should be suspended for this crop
     */
    public function shouldSuspendWatering(Crop $crop): bool
    {
        if (!$crop->recipe) {
            return false;
        }

        $suspendHours = $crop->recipe->suspend_watering_hours;
        
        if (!$suspendHours || $suspendHours <= 0) {
            return false;
        }

        $harvestDate = $this->calculateExpectedHarvestDate($crop);
        
        if (!$harvestDate) {
            return false;
        }

        $suspendAt = $harvestDate->subHours($suspendHours);
        
        return Carbon::now()->gte($suspendAt);
    }

    /**
     * Suspend watering for a crop
     * IMPORTANT: This suspends watering for ALL crops in the batch to maintain batch integrity
     * 
     * @param Crop $crop The crop to suspend watering for (will suspend entire batch)
     * @param Carbon|null $timestamp Optional timestamp for when watering was suspended
     */
    public function suspendWatering(Crop $crop, ?Carbon $timestamp = null): void
    {
        // Find ALL crops in this batch to maintain batch integrity
        $batchCrops = Crop::where('recipe_id', $crop->recipe_id)
            ->where('planting_at', $crop->planting_at)
            ->where('current_stage', $crop->current_stage)
            ->get();

        $suspensionTime = $timestamp ?? Carbon::now();
        $count = 0;
        $alreadySuspended = 0;
        
        foreach ($batchCrops as $batchCrop) {
            if ($batchCrop->watering_suspended_at) {
                $alreadySuspended++;
                continue;
            }
            
            $batchCrop->watering_suspended_at = $suspensionTime;
            $batchCrop->save();
            $count++;
        }

        Log::info('Watering suspended for crop batch', [
            'initiating_crop_id' => $crop->id,
            'batch_size' => $batchCrops->count(),
            'newly_suspended' => $count,
            'already_suspended' => $alreadySuspended,
            'recipe_id' => $crop->recipe_id,
            'planting_at' => $crop->planting_at
        ]);
    }

    /**
     * Resume watering for a crop
     * IMPORTANT: This resumes watering for ALL crops in the batch to maintain batch integrity
     */
    public function resumeWatering(Crop $crop): void
    {
        // Find ALL crops in this batch to maintain batch integrity
        $batchCrops = Crop::where('recipe_id', $crop->recipe_id)
            ->where('planting_at', $crop->planting_at)
            ->where('current_stage', $crop->current_stage)
            ->get();

        $count = 0;
        $alreadyActive = 0;
        
        foreach ($batchCrops as $batchCrop) {
            if (!$batchCrop->watering_suspended_at) {
                $alreadyActive++;
                continue;
            }
            
            $batchCrop->watering_suspended_at = null;
            $batchCrop->save();
            $count++;
        }

        Log::info('Watering resumed for crop batch', [
            'initiating_crop_id' => $crop->id,
            'batch_size' => $batchCrops->count(),
            'newly_resumed' => $count,
            'already_active' => $alreadyActive,
            'recipe_id' => $crop->recipe_id,
            'planting_at' => $crop->planting_at
        ]);
    }

    /**
     * Get the next stage in the lifecycle
     */
    private function getNextStage(string $currentStage): ?string
    {
        $currentIndex = array_search($currentStage, self::STAGES);
        
        if ($currentIndex === false || $currentIndex >= count(self::STAGES) - 1) {
            return null;
        }

        return self::STAGES[$currentIndex + 1];
    }

    /**
     * Get the timestamp field name for a stage
     */
    private function getStageTimestampField(string $stage): string
    {
        return match ($stage) {
            'germination' => 'germination_at',
            'blackout' => 'blackout_at',
            'light' => 'light_at',
            'harvested' => 'harvested_at',
            default => throw new \InvalidArgumentException("Unknown stage: {$stage}")
        };
    }

    /**
     * Get the timestamp for the crop's current stage
     */
    private function getCurrentStageTimestamp(Crop $crop): ?string
    {
        $timestampField = $this->getStageTimestampField($crop->current_stage);
        return $crop->{$timestampField};
    }
}