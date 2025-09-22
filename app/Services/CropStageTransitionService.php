<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropBatch;
use App\Models\CropStage;
use App\Models\TaskSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CropStageTransitionService
{
    /**
     * Mapping of stage codes to their timestamp fields
     */
    private const STAGE_TIMESTAMP_MAP = [
        'soaking' => 'soaking_at',
        'germination' => 'germination_at',
        'blackout' => 'blackout_at',
        'light' => 'light_at',
        'harvested' => 'harvested_at',
    ];

    /**
     * Initialize a newly created crop's stage timestamps
     *
     * @param Crop $crop The crop to initialize
     * @param Carbon $startTime When the crop was planted/started
     * @return void
     */
    public function initializeCropStage(Crop $crop, Carbon $startTime): void
    {
        // Ensure the currentStage relationship is loaded
        if (!$crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }
        
        if (!$crop->currentStage) {
            Log::warning('Cannot initialize crop stage - no current stage set', [
                'crop_id' => $crop->id,
                'current_stage_id' => $crop->current_stage_id
            ]);
            return;
        }
        
        $stageCode = $crop->currentStage->code;
        
        Log::info('Initializing crop stage', [
            'crop_id' => $crop->id,
            'stage_code' => $stageCode,
            'current_stage_id' => $crop->current_stage_id,
            'start_time' => $startTime->format('Y-m-d H:i:s')
        ]);
        
        // Map stage codes to their appropriate timestamp field
        if (isset(self::STAGE_TIMESTAMP_MAP[$stageCode])) {
            $timestampField = self::STAGE_TIMESTAMP_MAP[$stageCode];
            $crop->update([$timestampField => $startTime]);
            
            Log::info('Successfully initialized crop stage timestamp', [
                'crop_id' => $crop->id,
                'stage' => $stageCode,
                'field' => $timestampField,
                'time' => $startTime->format('Y-m-d H:i:s')
            ]);
        } else {
            Log::warning('No timestamp mapping found for stage', [
                'crop_id' => $crop->id,
                'stage_code' => $stageCode,
                'available_mappings' => array_keys(self::STAGE_TIMESTAMP_MAP)
            ]);
        }
    }

    /**
     * Fix missing stage timestamps for existing crops
     * 
     * @param Crop $crop The crop to fix
     * @return bool Whether any fixes were applied
     */
    public function fixMissingStageTimestamps(Crop $crop): bool
    {
        if (!$crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }
        
        if (!$crop->currentStage) {
            return false;
        }
        
        $stageCode = $crop->currentStage->code;
        $fixed = false;
        
        // Check if current stage timestamp is missing
        if (isset(self::STAGE_TIMESTAMP_MAP[$stageCode])) {
            $timestampField = self::STAGE_TIMESTAMP_MAP[$stageCode];
            
            if (!$crop->$timestampField && $crop->planting_at) {
                // Use planting_at as fallback timestamp
                $crop->update([$timestampField => $crop->planting_at]);
                $fixed = true;
                
                Log::info('Fixed missing stage timestamp', [
                    'crop_id' => $crop->id,
                    'stage' => $stageCode,
                    'field' => $timestampField,
                    'time' => $crop->planting_at->format('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $fixed;
    }

    /**
     * Advance a single crop or batch to the next stage
     *
     * @param Crop|CropBatch $target The crop or batch to advance
     * @param Carbon $transitionTime When the transition occurred
     * @param array $options Additional options (e.g., tray_numbers for soaking->germination)
     * @return array Result with affected crops and any warnings
     * @throws ValidationException
     */
    public function advanceStage($target, Carbon $transitionTime, array $options = []): array
    {
        return DB::transaction(function () use ($target, $transitionTime, $options) {
            $crops = $this->getCropsForTransition($target);
            
            if ($crops->isEmpty()) {
                throw ValidationException::withMessages([
                    'target' => 'No crops found for transition'
                ]);
            }

            // Get current and next stage
            $currentStage = $this->getCurrentStage($crops->first());
            $nextStage = $this->getNextStage($currentStage);

            if (!$nextStage) {
                throw ValidationException::withMessages([
                    'stage' => "Cannot advance from {$currentStage->name} - already at final stage"
                ]);
            }

            // Validate the transition
            $this->validateAdvancement($crops, $currentStage, $nextStage, $transitionTime, $options);

            // Lock crops for update to prevent race conditions
            $this->lockCropsForUpdate($crops);

            // Perform the transition
            $results = $this->performAdvancement($crops, $currentStage, $nextStage, $transitionTime, $options);

            // Log the transition
            $this->logTransition('advance', $crops, $currentStage, $nextStage, $transitionTime, $results);

            return $results;
        });
    }

    /**
     * Revert a single crop or batch to the previous stage
     *
     * @param Crop|CropBatch $target The crop or batch to revert
     * @param string|null $reason Optional reason for reversal
     * @return array Result with affected crops and any warnings
     * @throws ValidationException
     */
    public function revertStage($target, ?string $reason = null): array
    {
        return DB::transaction(function () use ($target, $reason) {
            $crops = $this->getCropsForTransition($target);
            
            if ($crops->isEmpty()) {
                throw ValidationException::withMessages([
                    'target' => 'No crops found for transition'
                ]);
            }

            // Get current and previous stage
            $currentStage = $this->getCurrentStage($crops->first());
            $previousStage = $this->getPreviousStage($currentStage);

            if (!$previousStage) {
                throw ValidationException::withMessages([
                    'stage' => "Cannot revert from {$currentStage->name} - already at first stage"
                ]);
            }

            // Validate the reversal
            $this->validateReversal($crops, $currentStage, $previousStage);

            // Lock crops for update
            $this->lockCropsForUpdate($crops);

            // Perform the reversal
            $results = $this->performReversal($crops, $currentStage, $previousStage, $reason);
            
            // Add reason to results for logging
            if ($reason) {
                $results['reason'] = $reason;
            }

            // Log the transition
            $this->logTransition('revert', $crops, $currentStage, $previousStage, now(), $results);

            return $results;
        });
    }

    /**
     * Validate that a batch can transition together
     *
     * @param Collection $crops
     * @return array Validation results
     */
    public function validateBatchTransition(Collection $crops): array
    {
        $issues = [];

        // Check all crops are in same stage
        $stages = $crops->pluck('current_stage_id')->unique();
        if ($stages->count() > 1) {
            $issues[] = 'Crops are in different stages';
        }

        // Check all crops have same recipe
        $recipes = $crops->pluck('recipe_id')->unique();
        if ($recipes->count() > 1) {
            $issues[] = 'Crops have different recipes';
        }

        // Check for any locked/suspended crops
        $suspended = $crops->where('watering_suspended_at', '!=', null);
        if ($suspended->isNotEmpty()) {
            $issues[] = "{$suspended->count()} crops have suspended watering";
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'details' => [
                'total_crops' => $crops->count(),
                'stages' => $stages->count(),
                'recipes' => $recipes->count(),
                'suspended' => $suspended->count(),
            ]
        ];
    }

    /**
     * Get crops for transition based on target type
     */
    private function getCropsForTransition($target): Collection
    {
        if ($target instanceof Crop) {
            // If using batch_id, get all crops in batch
            if ($target->crop_batch_id) {
                return Crop::where('crop_batch_id', $target->crop_batch_id)
                    ->with(['recipe', 'currentStage'])
                    ->get();
            }
            
            // Fall back to implicit batching
            return Crop::where('recipe_id', $target->recipe_id)
                ->where('planting_at', $target->planting_at)
                ->where('current_stage_id', $target->current_stage_id)
                ->with(['recipe', 'currentStage'])
                ->get();
        }

        if ($target instanceof CropBatch) {
            return $target->crops()->with(['recipe', 'currentStage'])->get();
        }

        throw new \InvalidArgumentException('Target must be Crop or CropBatch instance');
    }

    /**
     * Lock crops for update to prevent race conditions
     */
    private function lockCropsForUpdate(Collection $crops): void
    {
        $ids = $crops->pluck('id')->toArray();
        
        // Use pessimistic locking
        Crop::whereIn('id', $ids)->lockForUpdate()->get();
    }

    /**
     * Get current stage for a crop
     */
    private function getCurrentStage(Crop $crop): CropStage
    {
        return $crop->currentStage ?? CropStage::find($crop->current_stage_id);
    }

    /**
     * Get next stage in progression
     */
    private function getNextStage(CropStage $currentStage): ?CropStage
    {
        return CropStage::where('sort_order', '>', $currentStage->sort_order)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Get previous stage in progression
     */
    private function getPreviousStage(CropStage $currentStage): ?CropStage
    {
        return CropStage::where('sort_order', '<', $currentStage->sort_order)
            ->where('is_active', true)
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    /**
     * Validate advancement is allowed
     */
    private function validateAdvancement(Collection $crops, CropStage $currentStage, CropStage $nextStage, Carbon $transitionTime, array $options): void
    {
        // Check if advancing from soaking requires tray numbers
        if ($currentStage->code === 'soaking' && $nextStage->code === 'germination') {
            if (empty($options['tray_numbers'])) {
                throw ValidationException::withMessages([
                    'tray_numbers' => 'Tray numbers are required when advancing from soaking to germination'
                ]);
            }

            // Validate we have tray numbers for each crop
            $cropIds = $crops->pluck('id')->toArray();
            foreach ($cropIds as $cropId) {
                if (empty($options['tray_numbers'][$cropId])) {
                    throw ValidationException::withMessages([
                        'tray_numbers' => "Missing tray number for crop {$cropId}"
                    ]);
                }
            }

            // Validate tray numbers are unique
            $this->validateTrayNumbersUnique($options['tray_numbers']);
        }

        // Validate transition time is not in future
        if ($transitionTime->isFuture()) {
            throw ValidationException::withMessages([
                'transition_time' => 'Transition time cannot be in the future'
            ]);
        }

        // Validate transition time is after previous stage timestamp
        $this->validateTimestampSequence($crops, $nextStage, $transitionTime);
    }

    /**
     * Validate reversal is allowed
     */
    private function validateReversal(Collection $crops, CropStage $currentStage, CropStage $previousStage): void
    {
        // Check if any crops have been harvested
        if ($currentStage->code === 'harvested') {
            $harvestedCrops = $crops->filter(function ($crop) {
                return $crop->harvests()->exists();
            });

            if ($harvestedCrops->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'harvest' => "Cannot revert {$harvestedCrops->count()} crops that have harvest records"
                ]);
            }
        }

        // Additional validation can be added here
    }

    /**
     * Validate tray numbers are unique among active crops
     */
    private function validateTrayNumbersUnique(array $trayNumbers): void
    {
        $existingTrays = Crop::whereIn('tray_number', array_values($trayNumbers))
            ->whereHas('currentStage', function($query) {
                $query->where('code', '!=', 'harvested');
            })
            ->pluck('tray_number')
            ->toArray();

        if (!empty($existingTrays)) {
            throw ValidationException::withMessages([
                'tray_numbers' => 'Tray numbers already in use: ' . implode(', ', $existingTrays)
            ]);
        }
    }

    /**
     * Validate timestamp sequence remains valid
     */
    private function validateTimestampSequence(Collection $crops, CropStage $nextStage, Carbon $transitionTime): void
    {
        $timestampField = self::STAGE_TIMESTAMP_MAP[$nextStage->code] ?? null;
        if (!$timestampField) {
            return;
        }

        foreach ($crops as $crop) {
            // Check if transition time is after all previous stage timestamps
            $timestamps = [
                'soaking_at' => $crop->soaking_at,
                'planting_at' => $crop->planting_at,
                'germination_at' => $crop->germination_at,
                'blackout_at' => $crop->blackout_at,
                'light_at' => $crop->light_at,
            ];

            foreach ($timestamps as $field => $timestamp) {
                if ($timestamp && $field !== $timestampField) {
                    $stageOrder = array_search($field, array_values(self::STAGE_TIMESTAMP_MAP));
                    $nextOrder = array_search($timestampField, array_values(self::STAGE_TIMESTAMP_MAP));
                    
                    if ($stageOrder !== false && $nextOrder !== false && $stageOrder < $nextOrder) {
                        if ($timestamp->isAfter($transitionTime)) {
                            throw ValidationException::withMessages([
                                'transition_time' => "Transition time must be after {$field} ({$timestamp->format('Y-m-d H:i')})"
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Perform the advancement
     */
    private function performAdvancement(Collection $crops, CropStage $currentStage, CropStage $nextStage, Carbon $transitionTime, array $options): array
    {
        $results = [
            'advanced' => 0,
            'failed' => 0,
            'warnings' => [],
            'crops' => [],
        ];

        $timestampField = self::STAGE_TIMESTAMP_MAP[$nextStage->code] ?? null;

        foreach ($crops as $crop) {
            try {
                // Update tray number if advancing from soaking
                if ($currentStage->code === 'soaking' && isset($options['tray_numbers'][$crop->id])) {
                    $crop->tray_number = $options['tray_numbers'][$crop->id];
                }

                // Update stage
                $crop->current_stage_id = $nextStage->id;
                
                // Update timestamp
                if ($timestampField) {
                    $crop->$timestampField = $transitionTime;
                }

                // Save and refresh
                $crop->save();
                $crop->refresh();

                // Deactivate relevant task schedules
                $this->deactivateTaskSchedulesForStage($crop, $currentStage);

                // Create new task schedules for next stage
                $this->createTaskSchedulesForStage($crop, $nextStage);

                $results['advanced']++;
                $results['crops'][] = [
                    'id' => $crop->id,
                    'tray_number' => $crop->tray_number,
                    'status' => 'success'
                ];

            } catch (\Exception $e) {
                Log::error("Failed to advance crop {$crop->id}", [
                    'error' => $e->getMessage(),
                    'crop_id' => $crop->id
                ]);

                $results['failed']++;
                $results['warnings'][] = "Failed to advance crop {$crop->tray_number}: {$e->getMessage()}";
                $results['crops'][] = [
                    'id' => $crop->id,
                    'tray_number' => $crop->tray_number,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Perform the reversal
     */
    private function performReversal(Collection $crops, CropStage $currentStage, CropStage $previousStage, ?string $reason): array
    {
        $results = [
            'reverted' => 0,
            'failed' => 0,
            'warnings' => [],
            'crops' => [],
        ];

        $currentTimestampField = self::STAGE_TIMESTAMP_MAP[$currentStage->code] ?? null;

        foreach ($crops as $crop) {
            try {
                // Clear current stage timestamp
                if ($currentTimestampField) {
                    $crop->$currentTimestampField = null;
                }

                // Also clear any future stage timestamps to maintain sequence integrity
                $this->clearFutureTimestamps($crop, $previousStage);

                // Update stage
                $crop->current_stage_id = $previousStage->id;

                // Save and refresh
                $crop->save();
                $crop->refresh();

                // Deactivate task schedules for reverted stage
                $this->deactivateTaskSchedulesForStage($crop, $currentStage);

                // Reactivate task schedules for previous stage
                $this->reactivateTaskSchedulesForStage($crop, $previousStage);

                $results['reverted']++;
                $results['crops'][] = [
                    'id' => $crop->id,
                    'tray_number' => $crop->tray_number,
                    'status' => 'success'
                ];

            } catch (\Exception $e) {
                Log::error("Failed to revert crop {$crop->id}", [
                    'error' => $e->getMessage(),
                    'crop_id' => $crop->id,
                    'reason' => $reason
                ]);

                $results['failed']++;
                $results['warnings'][] = "Failed to revert crop {$crop->tray_number}: {$e->getMessage()}";
                $results['crops'][] = [
                    'id' => $crop->id,
                    'tray_number' => $crop->tray_number,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Clear timestamps for stages after the given stage
     */
    private function clearFutureTimestamps(Crop $crop, CropStage $stage): void
    {
        $stageOrder = array_keys(self::STAGE_TIMESTAMP_MAP);
        $currentIndex = array_search($stage->code, $stageOrder);

        if ($currentIndex === false) {
            return;
        }

        // Clear all timestamps after the current stage
        for ($i = $currentIndex + 1; $i < count($stageOrder); $i++) {
            $timestampField = self::STAGE_TIMESTAMP_MAP[$stageOrder[$i]] ?? null;
            if ($timestampField && $crop->$timestampField !== null) {
                $crop->$timestampField = null;
            }
        }
    }

    /**
     * Deactivate task schedules for a specific stage
     */
    private function deactivateTaskSchedulesForStage(Crop $crop, CropStage $stage): void
    {
        TaskSchedule::where('resource_type', 'crops')
            ->where('conditions->crop_id', $crop->id)
            ->where('conditions->stage', $stage->code)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'last_run_at' => now(),
            ]);
    }

    /**
     * Create task schedules for a specific stage
     */
    private function createTaskSchedulesForStage(Crop $crop, CropStage $stage): void
    {
        // Delegate to CropTaskManagementService
        app(CropTaskManagementService::class)->scheduleStageSpecificTasks($crop, $stage->code);
    }

    /**
     * Reactivate task schedules for a specific stage
     */
    private function reactivateTaskSchedulesForStage(Crop $crop, CropStage $stage): void
    {
        // For now, create new schedules. In future, could restore previous ones
        $this->createTaskSchedulesForStage($crop, $stage);
    }

    /**
     * Log the transition for audit trail
     */
    private function logTransition(string $type, Collection $crops, CropStage $fromStage, CropStage $toStage, Carbon $timestamp, array $results): void
    {
        // Log to application log
        Log::info("Crop stage {$type}", [
            'type' => $type,
            'from_stage' => $fromStage->name,
            'to_stage' => $toStage->name,
            'timestamp' => $timestamp->toIso8601String(),
            'batch_id' => $crops->first()->crop_batch_id,
            'crop_count' => $crops->count(),
            'results' => $results,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
        ]);

        // Create audit trail record
        $transitionType = $crops->count() > 1 ? "bulk_{$type}" : $type;
        
        $metadata = [];
        if (isset($results['crops'])) {
            $metadata['affected_crops'] = collect($results['crops'])->map(function ($crop) {
                return [
                    'id' => $crop['id'],
                    'tray_number' => $crop['tray_number'],
                    'status' => $crop['status'],
                ];
            })->toArray();
        }

        // Extract failed crops for separate storage
        $failedCrops = isset($results['crops']) 
            ? collect($results['crops'])->where('status', 'failed')->toArray()
            : [];

        \App\Models\CropStageTransition::create([
            'type' => $transitionType,
            'crop_batch_id' => $crops->first()->crop_batch_id,
            'crop_count' => $crops->count(),
            'from_stage_id' => $fromStage->id,
            'to_stage_id' => $toStage->id,
            'transition_at' => $timestamp,
            'recorded_at' => now(),
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'reason' => $results['reason'] ?? null,
            'metadata' => $metadata,
            'validation_warnings' => $results['warnings'] ?? [],
            'succeeded_count' => $results['advanced'] ?? $results['reverted'] ?? 0,
            'failed_count' => $results['failed'] ?? 0,
            'failed_crops' => $failedCrops,
        ]);
    }
}