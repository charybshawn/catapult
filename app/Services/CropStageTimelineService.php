<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive agricultural stage timeline generation service for crop lifecycle visualization.
 * 
 * Creates detailed timelines showing complete microgreens production lifecycle including
 * completed, current, future, and skipped stages. Provides agricultural operators with
 * visual progress tracking, timing analysis, and production planning capabilities.
 * 
 * @business_domain Agricultural crop lifecycle visualization and production planning
 * @agricultural_workflow Tracks complete microgreens growing cycle from seed to harvest
 * @production_planning Supports crop scheduling and resource allocation decisions
 * @quality_tracking Enables monitoring of stage durations and agricultural compliance
 * 
 * @example
 * $timeline = $this->generateTimeline($crop);
 * foreach ($timeline as $stage => $data) {
 *     echo "{$data['name']}: {$data['status']} - {$data['duration']}";
 * }
 * 
 * @features
 * - Complete stage progression visualization
 * - Recipe-based stage relevance determination
 * - Actual vs expected duration comparison
 * - Progress calculation for current stages
 * - Skipped stage identification with reasoning
 * 
 * @see CropStageCache For stage definition access
 * @see CropStageValidationService For stage transition validation
 * @see Recipe For stage duration specifications
 */
class CropStageTimelineService
{
    /**
     * Generate comprehensive agricultural timeline for crop lifecycle visualization.
     * 
     * Creates detailed timeline showing all stages in microgreens production workflow
     * including completed stages, current stage with progress, future stages, and
     * stages skipped based on recipe requirements. Essential for production monitoring
     * and agricultural decision-making.
     * 
     * @agricultural_visualization Complete crop lifecycle from seed to harvest
     * @production_monitoring Real-time progress tracking and stage analysis
     * @recipe_compliance Shows which stages apply based on variety requirements
     * @operational_planning Supports scheduling and resource allocation decisions
     * 
     * @param Crop $crop Crop instance with recipe and current stage relationships
     * @return array Comprehensive timeline data with stage details and progress
     * 
     * @timeline_structure
     * [
     *   'soaking' => ['name' => 'Soaking', 'status' => 'completed', 'duration' => '8 hours'],
     *   'germination' => ['name' => 'Germination', 'status' => 'current', 'progress' => 60],
     *   'blackout' => ['name' => 'Blackout', 'status' => 'skipped', 'reason' => 'Recipe skips blackout'],
     *   'light' => ['name' => 'Light', 'status' => 'future', 'expected_start' => 'Mar 15, 2024']
     * ]
     * 
     * @example
     * $timeline = $timelineService->generateTimeline($crop);
     * $currentStage = collect($timeline)->where('status', 'current')->first();
     * if ($currentStage && $currentStage['progress'] > 90) {
     *     // Crop is ready for next stage advancement
     * }
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
        
        
        // Define complete agricultural lifecycle stages for microgreens production
        $allStages = [
            'soaking' => [
                'name' => 'Soaking',
                'timestamp_field' => 'soaking_at', // Agricultural: seed hydration preparation
                'duration_field' => 'seed_soak_hours',
                'duration_unit' => 'hours',
                'color' => 'purple'
            ],
            'germination' => [
                'name' => 'Germination', 
                'timestamp_field' => 'germination_at', // Agricultural: initial sprouting phase
                'end_field' => 'germination_at',
                'duration_field' => 'germination_days',
                'duration_unit' => 'days',
                'color' => 'yellow'
            ],
            'blackout' => [
                'name' => 'Blackout',
                'timestamp_field' => 'germination_at', // Agricultural: darkness promotes stem elongation
                'end_field' => 'blackout_at', 
                'duration_field' => 'blackout_days',
                'duration_unit' => 'days',
                'color' => 'gray'
            ],
            'light' => [
                'name' => 'Light',
                'timestamp_field' => 'blackout_at', // Agricultural: photosynthesis and chlorophyll development
                'fallback_field' => 'germination_at', // If blackout stage was skipped per recipe
                'end_field' => 'light_at',
                'duration_field' => 'light_days',
                'duration_unit' => 'days',
                'color' => 'green'
            ],
            'harvested' => [
                'name' => 'Harvested',
                'timestamp_field' => 'light_at', // Agricultural: crop maturity and cutting
                'end_field' => 'harvested_at',
                'duration_field' => null, // No duration - harvest is instantaneous
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
     * Process individual agricultural stage for timeline integration.
     * 
     * Analyzes single stage within crop lifecycle to determine status, timing,
     * and relevant agricultural data. Handles completed, current, future, and
     * skipped stages with appropriate calculations and formatting.
     * 
     * @agricultural_analysis Determines stage relevance and status for crop variety
     * @timing_calculations Computes durations, progress, and expected timelines
     * @recipe_integration Applies variety-specific growing requirements
     * @internal Core logic for timeline generation processing
     * 
     * @param Crop $crop Crop instance being analyzed
     * @param string $stageCode Agricultural stage code identifier
     * @param array $stageInfo Stage configuration and field mappings
     * @param string $currentStageCode Current agricultural stage of the crop
     * @return array|null Processed stage data or null if not applicable
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
     * Determine if agricultural stage is relevant for crop variety recipe.
     * 
     * Evaluates whether specific stage applies to the crop based on recipe
     * requirements and variety characteristics. Some stages like soaking or
     * blackout may be skipped based on microgreens variety specifications.
     * 
     * @recipe_compliance Validates stage requirements against variety specifications
     * @agricultural_requirements Different microgreens have different stage needs
     * @stage_relevance Determines which stages apply to specific varieties
     * 
     * @param Crop $crop Crop with recipe relationship loaded
     * @param string $stageCode Agricultural stage code to evaluate
     * @return bool Whether stage is applicable for this crop variety
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
                return $crop->recipe->blackout_days > 0; // Some varieties skip blackout entirely
            case 'germination':
            case 'light':
            case 'harvested':
                return true; // Core agricultural stages always applicable
            default:
                return false;
        }
    }
    
    /**
     * Provide agricultural explanation for skipped stage.
     * 
     * Returns user-friendly explanation of why specific agricultural stage
     * was not applicable for the crop variety. Helps operators understand
     * recipe-based stage variations in microgreens production.
     * 
     * @agricultural_education Explains variety-specific growing requirements
     * @user_guidance Provides clear reasoning for stage omissions
     * @recipe_transparency Makes variety differences understandable
     * 
     * @param Crop $crop Crop instance with recipe context
     * @param string $stageCode Agricultural stage code that was skipped
     * @return string Human-readable explanation for stage omission
     */
    private function getSkipReason(Crop $crop, string $stageCode): string
    {
        switch ($stageCode) {
            case 'soaking':
                return 'Variety does not require seed pre-soaking for optimal germination';
            case 'blackout':
                return 'Variety specification skips blackout phase for direct light exposure';
            default:
                return 'Stage not applicable for this microgreens variety';
        }
    }
    
    /**
     * Extract agricultural stage start timestamp from crop records.
     * 
     * Retrieves the timestamp when specific agricultural stage began, with
     * fallback logic for complex stage relationships (e.g., light stage may
     * start from blackout_at or germination_at if blackout was skipped).
     * 
     * @timestamp_resolution Handles complex stage timing relationships
     * @agricultural_tracking Extracts precise stage commencement times
     * @fallback_logic Supports variety-specific stage progressions
     * 
     * @param Crop $crop Crop instance with stage timestamps
     * @param array $stageInfo Stage configuration with field mappings
     * @return string|null ISO timestamp or null if stage hasn't started
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
     * Extract agricultural stage completion timestamp from crop records.
     * 
     * Retrieves the timestamp when specific agricultural stage was completed
     * and the crop transitioned to the next phase of the growing cycle.
     * Used for calculating actual stage durations and timeline analysis.
     * 
     * @agricultural_completion Tracks when stages finish in growing cycle
     * @duration_calculation Provides end points for stage timing analysis
     * @production_tracking Records actual agricultural milestones
     * 
     * @param Crop $crop Crop instance with stage completion timestamps
     * @param array $stageInfo Stage configuration with end field mapping
     * @return string|null ISO timestamp or null if stage not completed
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
     * Calculate expected agricultural stage duration from recipe specifications.
     * 
     * Retrieves variety-specific duration requirements from recipe configuration
     * to support timeline planning and progress calculation. Different microgreens
     * varieties have different optimal durations for each growing stage.
     * 
     * @recipe_specifications Uses variety-defined optimal stage durations
     * @agricultural_planning Supports production scheduling and expectations
     * @variety_requirements Accounts for microgreens-specific timing needs
     * 
     * @param Crop $crop Crop instance with recipe relationship
     * @param array $stageInfo Stage configuration with duration field mapping
     * @return string|null Formatted duration with units or null if not defined
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
     * Calculate human-readable duration between agricultural milestones.
     * 
     * Computes elapsed time between stage start and end (or current time)
     * with intelligent formatting for agricultural context. Provides days,
     * hours, and minutes as appropriate for production monitoring.
     * 
     * @agricultural_formatting User-friendly duration presentation
     * @production_monitoring Clear elapsed time for stage analysis
     * @timeline_visualization Supports agricultural timeline interfaces
     * 
     * @param Carbon $start Agricultural milestone start timestamp
     * @param Carbon $end Agricultural milestone end timestamp or current time
     * @return string Human-readable duration (e.g., "2 days 4 hours")
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
     * Calculate agricultural stage completion percentage for current operations.
     * 
     * Computes progress through current growing stage based on elapsed time
     * versus expected duration from recipe specifications. Critical for
     * production monitoring and timing agricultural stage transitions.
     * 
     * @progress_tracking Real-time stage advancement monitoring
     * @agricultural_timing Compares actual vs expected stage durations
     * @production_efficiency Helps optimize agricultural workflows
     * @stage_readiness Indicates when crops approach transition points
     * 
     * @param string|null $startTimestamp When current stage began
     * @param string|null $expectedDuration Recipe-specified stage duration
     * @param string|null $unit Duration unit (hours/days) for calculations
     * @return int|null Progress percentage (0-100) or null if cannot calculate
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
        
        // Convert agricultural durations to minutes for calculation
        $expectedMinutes = match($unit) {
            'hours' => $expectedValue * 60, // Soaking stage typically in hours
            'days' => $expectedValue * 24 * 60, // Growing stages typically in days
            default => $expectedValue
        };
        
        $progress = min(100, round(($elapsed / $expectedMinutes) * 100));
        
        return $progress;
    }
    
    /**
     * Determine if agricultural stage has been completed in crop lifecycle.
     * 
     * Evaluates stage completion by comparing position in agricultural workflow
     * sequence. Used to identify which stages are past, current, or future
     * in the microgreens production timeline.
     * 
     * @agricultural_sequence Tracks position in growing cycle workflow
     * @timeline_logic Determines completed vs pending stages
     * @production_status Identifies agricultural milestones achieved
     * 
     * @param Crop $crop Crop instance being evaluated
     * @param string $stageCode Agricultural stage code to check
     * @param string $currentStageCode Current stage in crop lifecycle
     * @return bool Whether specified stage has been completed
     */
    private function isStageCompleted(Crop $crop, string $stageCode, string $currentStageCode): bool
    {
        // Agricultural stage sequence for microgreens production lifecycle
        $stageOrder = ['soaking', 'germination', 'blackout', 'light', 'harvested'];
        
        $currentIndex = array_search($currentStageCode, $stageOrder);
        $checkIndex = array_search($stageCode, $stageOrder);
        
        return $checkIndex !== false && $currentIndex !== false && $checkIndex < $currentIndex;
    }
    
    /**
     * Calculate projected start dates for future agricultural stages.
     * 
     * Estimates when upcoming stages will begin based on current progress
     * and recipe-specified durations. Supports agricultural planning and
     * resource scheduling for microgreens production operations.
     * 
     * @agricultural_forecasting Projects future stage commencement dates
     * @production_planning Supports resource allocation and scheduling
     * @recipe_timing Uses variety-specific duration expectations
     * @future_enhancement Currently returns null - complex calculation needed
     * 
     * @param Crop $crop Crop instance for projection calculations
     * @param string $stageCode Agricultural stage to project start date
     * @return Carbon|null Projected start date or null (not yet implemented)
     * 
     * @todo Implement complex calculation based on current stage progress
     * @todo Consider recipe durations and current stage timing
     * @todo Account for operational scheduling constraints
     */
    private function calculateExpectedStartDate(Crop $crop, string $stageCode): ?Carbon
    {
        // Complex agricultural timing calculation needed:
        // - Current stage progress and remaining duration
        // - Recipe-specified durations for intervening stages  
        // - Operational scheduling constraints and batch coordination
        // TODO: Implement comprehensive agricultural timeline projection
        return null;
    }
}