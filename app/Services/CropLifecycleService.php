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
     */
    public function advanceStage(Crop $crop): void
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

        $now = Carbon::now();
        $crop->current_stage = $nextStage;
        
        // Set stage-specific timestamps
        match ($nextStage) {
            'germination' => $crop->germination_at = $now,
            'blackout' => $crop->blackout_at = $now,
            'light' => $crop->light_at = $now,
            'harvested' => $crop->harvested_at = $now,
            default => null
        };

        $crop->stage_updated_at = $now;
        $crop->save();

        Log::info('Crop stage advanced', [
            'crop_id' => $crop->id,
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
        if (!$crop->recipe || !$crop->planted_at) {
            return null;
        }

        $plantedAt = Carbon::parse($crop->planted_at);
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
     */
    public function suspendWatering(Crop $crop): void
    {
        if ($crop->watering_suspended_at) {
            return; // Already suspended
        }

        $crop->watering_suspended_at = Carbon::now();
        $crop->save();

        Log::info('Watering suspended for crop', ['crop_id' => $crop->id]);
    }

    /**
     * Resume watering for a crop
     */
    public function resumeWatering(Crop $crop): void
    {
        if (!$crop->watering_suspended_at) {
            return; // Not suspended
        }

        $crop->watering_suspended_at = null;
        $crop->save();

        Log::info('Watering resumed for crop', ['crop_id' => $crop->id]);
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