<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating comprehensive stage timelines for crops
 * Shows all possible stages including skipped ones
 */
class CropStageTimelineService
{
    /**
     * Generate a complete timeline for a crop showing all stages
     * 
     * @param Crop $crop
     * @return array
     */
    public function generateTimeline(Crop $crop): array
    {
        $timeline = [];
        
        // Ensure relationships are loaded
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }
        if (!$crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }
        
        $currentStageCode = $crop->currentStage?->code ?? 'unknown';
        
        
        // Define all possible stages in order
        $allStages = [
            'soaking' => [
                'name' => 'Soaking',
                'timestamp_field' => 'soaking_at', // Note: this field doesn't exist in crops table
                'duration_field' => 'seed_soak_hours',
                'duration_unit' => 'hours',
                'color' => 'purple'
            ],
            'germination' => [
                'name' => 'Germination', 
                'timestamp_field' => 'germination_at',
                'end_field' => 'germination_at',
                'duration_field' => 'germination_days',
                'duration_unit' => 'days',
                'color' => 'yellow'
            ],
            'blackout' => [
                'name' => 'Blackout',
                'timestamp_field' => 'germination_at',
                'end_field' => 'blackout_at', 
                'duration_field' => 'blackout_days',
                'duration_unit' => 'days',
                'color' => 'gray'
            ],
            'light' => [
                'name' => 'Light',
                'timestamp_field' => 'blackout_at',
                'fallback_field' => 'germination_at', // If blackout was skipped
                'end_field' => 'light_at',
                'duration_field' => 'light_days',
                'duration_unit' => 'days',
                'color' => 'green'
            ],
            'harvested' => [
                'name' => 'Harvested',
                'timestamp_field' => 'light_at',
                'end_field' => 'harvested_at',
                'duration_field' => null,
                'duration_unit' => null,
                'color' => 'blue'
            ]
        ];
        
        foreach ($allStages as $stageCode => $stageInfo) {
            $stageData = $this->processStage($crop, $stageCode, $stageInfo, $currentStageCode);
            if ($stageData) {
                $timeline[$stageCode] = $stageData;
            }
        }
        
        return $timeline;
    }
    
    /**
     * Process a single stage for the timeline
     */
    private function processStage(Crop $crop, string $stageCode, array $stageInfo, string $currentStageCode): ?array
    {
        
        // Check if this stage is relevant for this recipe
        if (!$this->isStageRelevant($crop, $stageCode)) {
            return [
                'name' => $stageInfo['name'],
                'status' => 'skipped',
                'reason' => $this->getSkipReason($crop, $stageCode),
                'color' => 'gray',
                'duration' => null,
                'start_date' => null,
                'end_date' => null
            ];
        }
        
        // Get the start timestamp for this stage
        $startTimestamp = $this->getStageStartTime($crop, $stageInfo);
        
        // Determine stage status
        if ($stageCode === $currentStageCode) {
            // Current stage
            if ($startTimestamp) {
                $duration = $this->calculateDuration(Carbon::parse($startTimestamp), Carbon::now());
                $expectedDuration = $this->getExpectedDuration($crop, $stageInfo);
                
                return [
                    'name' => $stageInfo['name'],
                    'status' => 'current',
                    'color' => $stageInfo['color'],
                    'duration' => $duration,
                    'expected_duration' => $expectedDuration,
                    'progress' => $this->calculateProgress($startTimestamp, $expectedDuration, $stageInfo['duration_unit']),
                    'start_date' => Carbon::parse($startTimestamp)->format('M j, Y g:i A'),
                    'end_date' => null
                ];
            } else {
                // Current stage but no start timestamp
                return [
                    'name' => $stageInfo['name'],
                    'status' => 'current',
                    'color' => $stageInfo['color'],
                    'duration' => null,
                    'expected_duration' => $this->getExpectedDuration($crop, $stageInfo),
                    'start_date' => null,
                    'end_date' => null
                ];
            }
        } elseif ($this->isStageCompleted($crop, $stageCode, $currentStageCode)) {
            // Completed stage
            if ($startTimestamp) {
                $endTimestamp = $this->getStageEndTime($crop, $stageInfo);
                
                if ($endTimestamp) {
                    $duration = $this->calculateDuration(
                        Carbon::parse($startTimestamp), 
                        Carbon::parse($endTimestamp)
                    );
                    
                    return [
                        'name' => $stageInfo['name'],
                        'status' => 'completed',
                        'color' => $stageInfo['color'],
                        'duration' => $duration,
                        'start_date' => Carbon::parse($startTimestamp)->format('M j, Y g:i A'),
                        'end_date' => Carbon::parse($endTimestamp)->format('M j, Y g:i A')
                    ];
                }
            }
        } else {
            // Future stage
            $expectedStart = $this->calculateExpectedStartDate($crop, $stageCode);
            $expectedDuration = $this->getExpectedDuration($crop, $stageInfo);
            
            return [
                'name' => $stageInfo['name'],
                'status' => 'future',
                'color' => 'gray',
                'duration' => null,
                'expected_duration' => $expectedDuration,
                'expected_start' => $expectedStart?->format('M j, Y'),
                'start_date' => null,
                'end_date' => null
            ];
        }
        
        return null;
    }
    
    /**
     * Check if a stage is relevant for this crop's recipe
     */
    private function isStageRelevant(Crop $crop, string $stageCode): bool
    {
        if (!$crop->recipe) {
            return false;
        }
        
        switch ($stageCode) {
            case 'soaking':
                return $crop->requires_soaking || ($crop->recipe->seed_soak_hours > 0);
            case 'blackout':
                return $crop->recipe->blackout_days > 0;
            case 'germination':
            case 'light':
            case 'harvested':
                return true; // Always relevant
            default:
                return false;
        }
    }
    
    /**
     * Get reason why a stage was skipped
     */
    private function getSkipReason(Crop $crop, string $stageCode): string
    {
        switch ($stageCode) {
            case 'soaking':
                return 'Recipe does not require soaking';
            case 'blackout':
                return 'Recipe skips blackout stage';
            default:
                return 'Not applicable for this recipe';
        }
    }
    
    /**
     * Get the start time for a stage
     */
    private function getStageStartTime(Crop $crop, array $stageInfo): ?string
    {
        $timestampField = $stageInfo['timestamp_field'];
        
        // Check main timestamp field
        if ($crop->$timestampField) {
            return $crop->$timestampField;
        }
        
        // Check fallback field if available
        if (isset($stageInfo['fallback_field'])) {
            $fallbackField = $stageInfo['fallback_field'];
            if ($crop->$fallbackField) {
                return $crop->$fallbackField;
            }
        }
        
        return null;
    }
    
    /**
     * Get the end time for a stage
     */
    private function getStageEndTime(Crop $crop, array $stageInfo): ?string
    {
        if (!isset($stageInfo['end_field'])) {
            return null;
        }
        
        $endField = $stageInfo['end_field'];
        return $crop->$endField;
    }
    
    /**
     * Get expected duration for a stage
     */
    private function getExpectedDuration(Crop $crop, array $stageInfo): ?string
    {
        if (!$crop->recipe || !$stageInfo['duration_field']) {
            return null;
        }
        
        $durationField = $stageInfo['duration_field'];
        $durationValue = $crop->recipe->$durationField;
        
        if (!$durationValue) {
            return null;
        }
        
        return $durationValue . ' ' . $stageInfo['duration_unit'];
    }
    
    /**
     * Calculate actual duration between two timestamps
     */
    private function calculateDuration(Carbon $start, Carbon $end): string
    {
        $diff = $start->diff($end);
        
        $parts = [];
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        }
        if (empty($parts) && $diff->i > 0) {
            $parts[] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }
        
        return implode(' ', $parts) ?: 'Just started';
    }
    
    /**
     * Calculate progress percentage for current stage
     */
    private function calculateProgress(?string $startTimestamp, ?string $expectedDuration, ?string $unit): ?int
    {
        if (!$startTimestamp || !$expectedDuration || !$unit) {
            return null;
        }
        
        $start = Carbon::parse($startTimestamp);
        $elapsed = $start->diffInMinutes(Carbon::now());
        
        // Parse expected duration
        preg_match('/(\d+)/', $expectedDuration, $matches);
        $expectedValue = isset($matches[1]) ? (int)$matches[1] : 0;
        
        if (!$expectedValue) {
            return null;
        }
        
        // Convert to minutes
        $expectedMinutes = match($unit) {
            'hours' => $expectedValue * 60,
            'days' => $expectedValue * 24 * 60,
            default => $expectedValue
        };
        
        $progress = min(100, round(($elapsed / $expectedMinutes) * 100));
        
        return $progress;
    }
    
    /**
     * Check if a stage has been completed
     */
    private function isStageCompleted(Crop $crop, string $stageCode, string $currentStageCode): bool
    {
        $stageOrder = ['soaking', 'germination', 'blackout', 'light', 'harvested'];
        
        $currentIndex = array_search($currentStageCode, $stageOrder);
        $checkIndex = array_search($stageCode, $stageOrder);
        
        return $checkIndex !== false && $currentIndex !== false && $checkIndex < $currentIndex;
    }
    
    /**
     * Calculate expected start date for future stages
     */
    private function calculateExpectedStartDate(Crop $crop, string $stageCode): ?Carbon
    {
        // This would require complex calculation based on current stage
        // and remaining durations. For now, return null.
        return null;
    }
}