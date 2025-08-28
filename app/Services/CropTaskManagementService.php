<?php

namespace App\Services;

use Exception;
use InvalidArgumentException;
use App\Actions\Crops\RecordStageHistory;
use App\Models\Crop;
use App\Models\CropBatch;
use App\Models\CropStage;
use App\Models\NotificationSetting;
use App\Models\TaskSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use App\Notifications\ResourceActionRequired;

/**
 * Comprehensive agricultural production task automation service for microgreens cultivation.
 * 
 * This unified service orchestrates the complete crop lifecycle from seed soaking through
 * harvest, providing automated task scheduling, batch coordination, and stage transition
 * management. Consolidates functionality from multiple specialized services into a single,
 * cohesive agricultural workflow automation system.
 * 
 * @service_domain Agricultural production automation and crop lifecycle management
 * @business_purpose Automates complex multi-stage growing processes with precise timing
 * @agricultural_focus Microgreens cultivation with recipe-based timing calculations
 * @batch_coordination Ensures synchronized processing of multiple trays per variety
 * @automation_scope Task scheduling, stage transitions, watering control, harvest timing
 * 
 * Key Agricultural Workflows:
 * - **Recipe-Based Automation**: Calculates stage transition timing from agricultural recipes
 * - **Batch Coordination**: Synchronizes multiple trays of same variety for efficient processing
 * - **Stage Lifecycle Management**: Automates soaking → germination → blackout → light → harvest
 * - **Task Scheduling**: Creates automated reminders and transition notifications
 * - **Quality Control Integration**: Enforces proper stage sequencing and timing validation
 * - **Watering Automation**: Manages suspension and resumption based on harvest timing
 * 
 * Crop Production Stages and Automation:
 * - **Soaking Stage**: Automated timing for seed hydration with completion warnings
 * - **Germination Stage**: Transition scheduling based on recipe specifications
 * - **Blackout Stage**: Light exclusion period management with automated advancement
 * - **Light Stage**: Final growing phase with harvest date calculations
 * - **Harvest Stage**: Automated harvest alerts and watering suspension coordination
 * 
 * Business Value and Operational Efficiency:
 * - **Labor Optimization**: Reduces manual monitoring through automated notifications
 * - **Quality Consistency**: Enforces standardized timing across all production batches
 * - **Batch Integrity**: Maintains synchronized processing for operational efficiency
 * - **Resource Management**: Coordinates watering schedules and growing space utilization
 * - **Traceability**: Complete audit trail of stage transitions and timing decisions
 * - **Scalability**: Handles multiple simultaneous batches with different timing requirements
 * 
 * Technical Architecture:
 * - **Database Integration**: Uses TaskSchedule model for persistent task management
 * - **Event-Driven Processing**: Leverages Laravel notifications for user alerts
 * - **Transaction Safety**: Ensures data consistency during batch operations
 * - **Memory Optimization**: Implements safeguards for large-scale batch processing
 * - **Recipe Integration**: Connects with Recipe models for agricultural timing calculations
 * - **History Tracking**: Records all stage transitions for compliance and analysis
 * 
 * Integration Points:
 * - CropPlanningService: Receives timing requirements from production planning
 * - RecordStageHistory: Maintains complete audit trail of all stage transitions
 * - NotificationSetting: Configurable alerts for different agricultural events
 * - TaskSchedule: Persistent storage for scheduled agricultural tasks
 * - CropStage: Stage definition and validation for proper lifecycle management
 * 
 * Performance Considerations:
 * - Memory monitoring during bulk operations to prevent resource exhaustion
 * - Batch processing optimization for handling large numbers of simultaneous crops
 * - Database transaction management for consistency during complex operations
 * - Eager loading of relationships to prevent N+1 query problems
 * 
 * Error Handling and Recovery:
 * - Comprehensive validation of stage transitions and timing calculations
 * - Graceful handling of missing recipe data with appropriate fallbacks
 * - Transaction rollback for failed batch operations to maintain data integrity
 * - Detailed logging for troubleshooting agricultural automation issues
 * 
 * @consolidates_services CropTaskService, CropLifecycleService, TaskFactoryService
 * @dependencies Recipe, Crop, CropStage, TaskSchedule, NotificationSetting
 * @notifications ResourceActionRequired for stage transitions and soaking warnings
 * @logging Comprehensive agricultural operation logging for compliance and debugging
 */
class CropTaskManagementService
{
    /**
     * The valid crop stages in order
     */
    private const STAGES = [
        'soaking',
        'germination',
        'blackout',
        'light',
        'harvested'
    ];

    /**
     * Schedule comprehensive automated task sequence for crop lifecycle management.
     * 
     * Creates complete task automation schedule for a crop from current stage through
     * harvest, including stage transitions, watering management, and batch coordination.
     * Calculates precise timing based on agricultural recipes and creates persistent
     * task schedules for automated execution and user notifications.
     * 
     * @param Crop $crop The crop to schedule tasks for with recipe and timing data
     * @return void Tasks created and persisted to TaskSchedule table
     * 
     * @agricultural_timing Uses recipe specifications for stage duration calculations
     * @batch_coordination Creates batch-wide tasks for synchronized processing
     * @memory_management Monitors memory usage to prevent issues during bulk operations
     * @task_types Stage transitions, soaking warnings, watering suspension
     * @validation Requires recipe and germination timing for proper calculations
     * 
     * Agricultural Task Scheduling Logic:
     * - **Recipe Validation**: Ensures crop has recipe for timing calculations
     * - **Stage Progression**: Schedules only future stages from current position
     * - **Timing Calculations**: Uses recipe hours/days for precise scheduling
     * - **Batch Identification**: Groups crops by recipe, date, and stage for coordination
     * - **Soaking Handling**: Special logic for crops requiring pre-germination soaking
     * - **Watering Management**: Schedules suspension before harvest if specified
     * 
     * Memory Safety and Performance:
     * - Monitors memory usage before scheduling to prevent exhaustion
     * - Configurable memory limits for large-scale operations
     * - Efficient database operations with minimal relationship loading
     * - Logging for troubleshooting scheduling issues
     * 
     * Task Types Created:
     * - Stage transition tasks for automated advancement notifications
     * - Soaking completion warnings for day-of-completion alerts
     * - Watering suspension tasks for harvest preparation
     * - Batch coordination tasks for synchronized processing
     * 
     * Business Rules:
     * - Only schedules tasks for crops with valid recipes
     * - Respects current stage position in lifecycle progression
     * - Handles both soaking and non-soaking crop varieties
     * - Maintains batch integrity for operational efficiency
     * 
     * @throws None - Gracefully handles missing data with appropriate logging
     * @side_effects Creates TaskSchedule records, logs scheduling activities
     * @performance Includes memory monitoring for large-scale operations
     */
    public function scheduleAllStageTasks(Crop $crop): void
    {
        Log::info('Starting task scheduling for crop', [
            'crop_id' => $crop->id,
            'tray_number' => $crop->tray_number,
            'has_recipe' => !!$crop->recipe,
            'recipe_id' => $crop->recipe_id,
            'current_stage_id' => $crop->current_stage_id,
            'requires_soaking' => $crop->requires_soaking
        ]);
        
        // Prevent memory issues during bulk operations
        $memoryLimitMb = config('tasks.memory_limit_mb', 100);
        if (memory_get_usage(true) > $memoryLimitMb * 1024 * 1024) {
            Log::warning('Memory limit approaching, skipping task scheduling', [
                'crop_id' => $crop->id,
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => $memoryLimitMb * 1024 * 1024
            ]);
            return;
        }
        
        $this->deleteTasksForCrop($crop);
        
        // Only schedule tasks if the crop has a recipe
        if (!$crop->recipe) {
            Log::warning('Crop has no recipe, skipping task scheduling', [
                'crop_id' => $crop->id
            ]);
            return;
        }
        
        $recipe = $crop->recipe;
        $plantedAt = $crop->germination_at;
        
        // Debug current stage loading and fallback to direct lookup if relationship fails
        $currentStageObject = $crop->currentStage;
        $currentStage = $currentStageObject?->code ?? null;
        
        if (!$currentStage && $crop->current_stage_id) {
            Log::warning('Stage relationship failed to load, attempting direct lookup', [
                'crop_id' => $crop->id,
                'current_stage_id' => $crop->current_stage_id,
                'currentStage_relationship_loaded' => $crop->relationLoaded('currentStage'),
                'currentStage_object' => $currentStageObject ? 'object found' : 'null'
            ]);
            
            // Fallback: Direct lookup of the stage
            $stageFromDirect = CropStage::find($crop->current_stage_id);
            if ($stageFromDirect) {
                $currentStage = $stageFromDirect->code;
                Log::info('Successfully found stage via direct lookup', [
                    'crop_id' => $crop->id,
                    'stage_code' => $currentStage,
                    'stage_name' => $stageFromDirect->name
                ]);
            } else {
                Log::error('Stage ID not found in database', [
                    'crop_id' => $crop->id,
                    'current_stage_id' => $crop->current_stage_id
                ]);
            }
        }
        
        Log::info('Current stage and recipe details', [
            'crop_id' => $crop->id,
            'current_stage' => $currentStage,
            'current_stage_id' => $crop->current_stage_id,
            'recipe_name' => $recipe->name ?? 'unknown',
            'seed_soak_hours' => $recipe->seed_soak_hours ?? 0,
            'germination_at' => $plantedAt ? $plantedAt->format('Y-m-d H:i:s') : 'null',
            'soaking_at' => $crop->soaking_at ? $crop->soaking_at->format('Y-m-d H:i:s') : 'null'
        ]);
        
        // Skip if no current stage is set
        if (!$currentStage) {
            Log::warning('No current stage set, skipping task scheduling', [
                'crop_id' => $crop->id
            ]);
            return;
        }
        
        // Get durations from recipe
        $soakHours = $recipe->seed_soak_hours ?? 0;
        $germDays = $recipe->germination_days;
        $blackoutDays = $recipe->blackout_days;
        $lightDays = $recipe->light_days;
        
        // Calculate stage transition times based on whether crop is soaking
        if ($crop->requires_soaking && $crop->soaking_at) {
            // For soaking crops, base calculations on soaking_at
            $soakingStart = $crop->soaking_at;
            $germinationTime = $soakingStart->copy()->addHours($soakHours);
            $blackoutTime = $germinationTime->copy()->addDays($germDays);
            $lightTime = $blackoutTime->copy()->addDays($blackoutDays);
            $harvestTime = $lightTime->copy()->addDays($lightDays);
        } else {
            // For non-soaking crops, use germination_at as base
            $germinationTime = $plantedAt->copy()->addHours($soakHours);
            $blackoutTime = $germinationTime->copy()->addDays($germDays);
            $lightTime = $blackoutTime->copy()->addDays($blackoutDays);
            $harvestTime = $lightTime->copy()->addDays($lightDays);
        }
        
        $now = Carbon::now();
        
        // Schedule soaking → germination transition if crop requires soaking
        if ($currentStage === 'soaking' && $crop->requires_soaking && $soakHours > 0 && $germinationTime->gt($now)) {
            Log::info('Creating soaking tasks', [
                'crop_id' => $crop->id,
                'germination_time' => $germinationTime->format('Y-m-d H:i:s'),
                'is_today' => $germinationTime->isToday()
            ]);
            
            $this->createBatchStageTransitionTask($crop, 'germination', $germinationTime);
            
            // Schedule soaking completion notice for the day it completes
            if ($germinationTime->isToday()) {
                // Create alert for early morning of completion day
                $warningTime = $germinationTime->copy()->startOfDay()->addHours(6); // 6 AM on completion day
                if ($warningTime->isPast()) {
                    $warningTime = $now; // If it's already past 6 AM, schedule immediately
                }
                $this->createSoakingWarningTask($crop, $warningTime);
            }
        } else {
            Log::info('Skipping soaking task creation', [
                'crop_id' => $crop->id,
                'current_stage' => $currentStage,
                'requires_soaking' => $crop->requires_soaking,
                'soak_hours' => $soakHours,
                'germination_time' => $germinationTime ? $germinationTime->format('Y-m-d H:i:s') : 'null',
                'is_future' => $germinationTime ? $germinationTime->gt($now) : false,
                'condition_results' => [
                    'is_soaking_stage' => $currentStage === 'soaking',
                    'requires_soaking' => $crop->requires_soaking,
                    'has_soak_hours' => $soakHours > 0,
                    'germination_in_future' => $germinationTime ? $germinationTime->gt($now) : false
                ]
            ]);
        }
        
        // Only schedule tasks for future stages
        if ($currentStage === 'germination' && $blackoutTime->gt($now)) {
            // Skip blackout stage if blackoutDays is 0
            if ($blackoutDays > 0) {
                $this->createBatchStageTransitionTask($crop, 'blackout', $blackoutTime);
            } else {
                // If skipping blackout, go straight to light
                $this->createBatchStageTransitionTask($crop, 'light', $blackoutTime);
            }
        }
        
        // Only create light transition if we're going through blackout stage
        if ($currentStage === 'blackout' && $lightTime->gt($now)) {
            $this->createBatchStageTransitionTask($crop, 'light', $lightTime);
        }
        
        if (in_array($currentStage, ['soaking', 'germination', 'blackout', 'light']) && $harvestTime->gt($now)) {
            $this->createBatchStageTransitionTask($crop, 'harvested', $harvestTime);
        }

        // Schedule Suspend Watering Task (if applicable)
        if ($recipe->suspend_water_hours > 0) {
            $suspendTime = $harvestTime->copy()->subHours($recipe->suspend_water_hours);
            if ($suspendTime->isAfter($plantedAt) && $suspendTime->gt($now)) {
                $this->createWateringSuspensionTask($crop, $suspendTime);
            } else {
                Log::warning("Suspend watering time ({$suspendTime->toDateTimeString()}) is before planting time ({$plantedAt->toDateTimeString()}) or in the past for Crop ID {$crop->id}. Task not generated.");
            }
        }
        
        Log::info('Completed task scheduling for crop', [
            'crop_id' => $crop->id
        ]);
    }

    /**
     * Advance crop to next lifecycle stage with comprehensive batch coordination.
     * 
     * Performs validated stage advancement for individual crop that automatically
     * includes all crops in the same batch to maintain synchronized processing.
     * Records complete stage transition history and validates proper agricultural
     * sequencing with full error handling and recovery mechanisms.
     * 
     * @param Crop $crop The crop to advance (triggers batch-wide advancement)
     * @param Carbon|null $timestamp Optional timestamp for transition (defaults to now)
     * @return void Stage advancement completed with history recording
     * 
     * @throws ValidationException If stage transition validation fails
     * @throws Exception If advancement process encounters system errors
     * 
     * @batch_coordination Advances ALL crops in batch for synchronized processing
     * @stage_validation Enforces proper agricultural stage sequencing
     * @history_tracking Records complete audit trail via RecordStageHistory
     * @agricultural_integrity Maintains proper crop lifecycle progression
     * 
     * Batch Processing Logic:
     * - **Batch Identification**: Finds all crops in same batch using recipe, date, stage
     * - **Synchronized Advancement**: Ensures all batch crops advance together
     * - **Stage Validation**: Verifies advancement follows proper agricultural sequence
     * - **Timestamp Management**: Updates appropriate stage timestamp fields
     * - **History Recording**: Creates audit trail for all stage transitions
     * - **Error Recovery**: Handles individual crop failures within batch context
     * 
     * Agricultural Validation:
     * - Ensures advancement follows natural growing progression
     * - Validates stage transitions match recipe specifications
     * - Maintains data integrity across all related crops
     * - Prevents invalid or backwards stage transitions
     * 
     * Business Impact:
     * - **Operational Efficiency**: Batch processing reduces labor requirements
     * - **Quality Control**: Synchronized advancement ensures uniform processing
     * - **Traceability**: Complete history for compliance and analysis
     * - **Error Prevention**: Validation prevents costly agricultural mistakes
     * 
     * Error Handling:
     * - ValidationException for agricultural rule violations
     * - Comprehensive logging for troubleshooting batch operations
     * - Transaction safety for data consistency during failures
     * - Detailed error context for operational teams
     * 
     * @delegates_to advanceStageWithHistory() for core advancement logic
     * @logging Records advancement success, batch sizes, and error conditions
     * @performance Efficient batch processing with minimal database queries
     */
    public function advanceStage(Crop $crop, ?Carbon $timestamp = null): void
    {
        $transitionTime = $timestamp ?? Carbon::now();
        
        try {
            $result = $this->advanceStageWithHistory($crop, $transitionTime);
            
            Log::info('Crop stage advanced successfully', [
                'crop_id' => $crop->id,
                'batch_size' => $result['affected_count'] ?? 1,
                'advanced' => $result['advanced'] ?? 0,
                'failed' => $result['failed'] ?? 0
            ]);
            
        } catch (ValidationException $e) {
            Log::error('Crop stage advancement failed validation', [
                'crop_id' => $crop->id,
                'errors' => $e->errors()
            ]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Crop stage advancement failed', [
                'crop_id' => $crop->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Execute comprehensive stage advancement with complete validation and history tracking.
     * 
     * Performs atomic stage transition operation within database transaction, including
     * full agricultural validation, batch coordination, history recording, and error
     * recovery. Handles both individual crops and crop batches with consistent
     * processing logic and comprehensive audit trail creation.
     * 
     * @param Crop|CropBatch $target The crop or batch to advance
     * @param Carbon $transitionTime Timestamp for the stage transition
     * @param array $options Additional options (tray_numbers for soaking transitions)
     * @return array Results with counts and details of advancement operation
     * 
     * @throws ValidationException If no crops found or invalid stage transition
     * @throws Exception If database transaction or advancement logic fails
     * 
     * @database_transaction Ensures atomic operation for data consistency
     * @agricultural_validation Complete stage sequencing and timing validation
     * @batch_processing Handles multiple crops with synchronized advancement
     * @history_recording Uses RecordStageHistory for complete audit trail
     * 
     * Transaction Processing Flow:
     * - **Target Resolution**: Determines crops to advance (individual or batch)
     * - **Stage Validation**: Verifies current stage and calculates next stage
     * - **Agricultural Rules**: Ensures advancement follows proper growing sequence
     * - **Batch Coordination**: Processes all related crops simultaneously
     * - **History Recording**: Creates complete audit trail for all transitions
     * - **Error Recovery**: Handles failures with appropriate rollback
     * 
     * Return Structure:
     * - 'advanced': Number of crops successfully advanced
     * - 'failed': Number of crops that failed advancement
     * - 'affected_count': Total crops processed in operation
     * - 'warnings': Any issues encountered during processing
     * - 'crops': Detailed results for each individual crop
     * 
     * Agricultural Business Rules:
     * - Validates stage progression follows natural growing sequence
     * - Ensures timing consistency across batch for synchronized processing
     * - Maintains tray number assignments during soaking transitions
     * - Records complete history for traceability and compliance
     * 
     * Error Handling and Recovery:
     * - ValidationException for agricultural rule violations
     * - Individual crop error handling within batch context
     * - Transaction rollback for system-level failures
     * - Comprehensive error logging for operational troubleshooting
     * 
     * @performance Database transaction optimization for batch operations
     * @consistency Atomic operations ensure data integrity across all crops
     * @traceability Complete audit trail via RecordStageHistory integration
     */
    public function advanceStageWithHistory($target, Carbon $transitionTime, array $options = []): array
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

            // Perform the transition with history recording
            return $this->performAdvancement($crops, $currentStage, $nextStage, $transitionTime, $options);
        });
    }

    /**
     * Execute scheduled crop stage transition task with comprehensive processing logic.
     * 
     * Processes automated task schedules created by task scheduling system, handling
     * various task types including stage transitions, soaking warnings, and batch
     * coordination. Provides intelligent routing based on task conditions and
     * maintains proper agricultural workflow automation.
     * 
     * @param TaskSchedule $task The scheduled task to process with conditions and timing
     * @return array Processing results with success status and detailed messages
     * 
     * @task_routing Intelligently routes to appropriate processing method based on task type
     * @agricultural_automation Executes scheduled agricultural operations automatically
     * @batch_processing Handles both individual crops and batch operations seamlessly
     * @notification_management Integrates with notification system for user alerts
     * 
     * Task Processing Logic:
     * - **Task Analysis**: Extracts conditions and parameters from scheduled task
     * - **Type Detection**: Identifies task type (stage transition, soaking warning, etc.)
     * - **Routing Decision**: Delegates to appropriate specialized processing method
     * - **Batch Coordination**: Handles batch operations when batch identifier present
     * - **Individual Processing**: Falls back to single crop processing when needed
     * - **Result Compilation**: Returns standardized processing results
     * 
     * Supported Task Types:
     * - **Stage Transitions**: Automated advancement through growing stages
     * - **Soaking Warnings**: Day-of-completion alerts for soaking operations
     * - **Watering Suspension**: Pre-harvest watering management
     * - **Batch Coordination**: Synchronized processing for multiple trays
     * 
     * Task Condition Parameters:
     * - crop_id: Individual crop identifier for single-crop tasks
     * - batch_identifier: Batch grouping for coordinated operations
     * - target_stage: Destination stage for advancement tasks
     * - tray_numbers: Array of tray numbers for batch processing
     * - warning_type: Specific warning category for notification tasks
     * 
     * Business Value:
     * - **Automation**: Reduces manual monitoring and intervention requirements
     * - **Consistency**: Ensures standardized timing across all production batches
     * - **Efficiency**: Batch processing optimizes operational workflows
     * - **Quality Control**: Automated enforcement of proper agricultural timing
     * - **Scalability**: Handles multiple simultaneous crops and batches
     * 
     * Return Structure:
     * - success: Boolean indicating overall task processing success
     * - message: Detailed description of processing results and actions taken
     * - Additional context depending on specific task type processed
     * 
     * Error Handling:
     * - Validates task conditions before processing
     * - Graceful fallback from batch to individual processing
     * - Comprehensive error messages for troubleshooting
     * - Logging for agricultural automation audit trails
     * 
     * @delegates_to Multiple specialized processing methods based on task type
     * @agricultural_timing Respects recipe-based timing for all automated operations
     * @performance Efficient task processing with minimal database overhead
     */
    public function processCropStageTask(TaskSchedule $task): array
    {
        $conditions = $task->conditions;
        $cropId = $conditions['crop_id'] ?? null;
        $targetStage = $conditions['target_stage'] ?? null;
        $batchIdentifier = $conditions['batch_identifier'] ?? null;
        $trayNumbers = $conditions['tray_numbers'] ?? null;
        $warningType = $conditions['warning_type'] ?? null;
        
        // Handle soaking completion warning
        if ($task->task_name === 'soaking_completion_warning' && $warningType === 'soaking_completion') {
            return $this->processSoakingWarningTask($task, $batchIdentifier, $trayNumbers);
        }
        
        if (!$targetStage) {
            return [
                'success' => false,
                'message' => 'Invalid task conditions: missing target_stage',
            ];
        }
        
        // Process batch if we have batch information
        if ($batchIdentifier && is_array($trayNumbers) && count($trayNumbers) > 0) {
            return $this->processBatchStageTransition($task, $batchIdentifier, $targetStage, $trayNumbers);
        }
        
        // Fallback to single crop processing
        return $this->processSingleCropStageTransition($task, $cropId, $targetStage);
    }

    /**
     * Suspend watering for entire crop batch to prepare for harvest operations.
     * 
     * Coordinates watering suspension across all crops in the same production batch
     * to ensure proper harvest preparation and quality optimization. Batch-wide
     * operation maintains synchronized processing while providing detailed tracking
     * of suspension status for each individual crop.
     * 
     * @param Crop $crop Representative crop from batch (triggers batch-wide suspension)
     * @param Carbon|null $timestamp Optional timestamp for suspension (defaults to now)
     * @return void Watering suspended for entire batch with logging
     * 
     * @batch_coordination Suspends watering for ALL crops in production batch
     * @harvest_preparation Pre-harvest watering management for quality optimization
     * @agricultural_timing Coordinated suspension based on recipe specifications
     * @status_tracking Individual crop suspension status and batch-wide reporting
     * 
     * Agricultural Watering Management:
     * - **Batch Identification**: Finds all crops in same production batch
     * - **Synchronized Suspension**: Applies watering suspension to entire batch
     * - **Status Tracking**: Records suspension timestamp for each crop
     * - **Duplicate Prevention**: Skips crops already suspended to avoid conflicts
     * - **Quality Control**: Ensures uniform harvest preparation across batch
     * 
     * Batch Processing Logic:
     * - Locates all crops sharing recipe, planting date, and current stage
     * - Applies watering_suspended_at timestamp to all batch crops
     * - Maintains individual crop status while coordinating batch operation
     * - Provides comprehensive logging for operational transparency
     * 
     * Quality and Consistency Benefits:
     * - **Uniform Processing**: Ensures all crops in batch receive identical treatment
     * - **Harvest Quality**: Proper pre-harvest preparation improves product quality
     * - **Operational Efficiency**: Batch coordination reduces labor requirements
     * - **Traceability**: Complete tracking of watering management decisions
     * 
     * Business Impact:
     * - **Product Quality**: Proper watering suspension improves harvest characteristics
     * - **Operational Coordination**: Synchronized processing for efficient workflows
     * - **Resource Management**: Coordinated watering schedules optimize resource usage
     * - **Compliance**: Documented watering management for quality standards
     * 
     * Status Reporting:
     * - Counts newly suspended crops vs. already suspended crops
     * - Provides batch size and processing statistics
     * - Logs suspension activity for audit and troubleshooting
     * - Records recipe and timing context for operational review
     * 
     * @agricultural_best_practices Follows microgreens production standards
     * @logging Comprehensive watering management activity logging
     * @performance Efficient batch processing with minimal database operations
     */
    public function suspendWatering(Crop $crop, ?Carbon $timestamp = null): void
    {
        $batchCrops = $this->findBatchCrops($crop);

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
     * Resume watering for entire crop batch after suspension period.
     * 
     * Coordinates watering resumption across all crops in the same production batch
     * when suspension needs to be reversed or harvest timing changes. Batch-wide
     * operation ensures synchronized watering management while providing detailed
     * tracking of resumption status for operational transparency.
     * 
     * @param Crop $crop Representative crop from batch (triggers batch-wide resumption)
     * @return void Watering resumed for entire batch with comprehensive logging
     * 
     * @batch_coordination Resumes watering for ALL crops in production batch
     * @watering_management Reverses previous suspension for operational flexibility
     * @agricultural_recovery Supports dynamic watering schedule adjustments
     * @status_tracking Individual crop resumption status and batch-wide reporting
     * 
     * Watering Resumption Logic:
     * - **Batch Identification**: Finds all crops in same production batch
     * - **Synchronized Resumption**: Clears suspension status for entire batch
     * - **Status Validation**: Identifies already active crops to avoid conflicts
     * - **Operational Flexibility**: Supports dynamic schedule changes as needed
     * - **Quality Assurance**: Maintains uniform watering management across batch
     * 
     * Agricultural Use Cases:
     * - **Schedule Adjustments**: When harvest timing changes require continued watering
     * - **Quality Optimization**: Resume watering if suspension was premature
     * - **Operational Corrections**: Reverse accidental or incorrect suspensions
     * - **Dynamic Management**: Adapt to changing growing conditions or requirements
     * 
     * Batch Processing Benefits:
     * - **Operational Consistency**: Uniform watering management across entire batch
     * - **Labor Efficiency**: Batch coordination reduces manual intervention
     * - **Quality Control**: Synchronized treatment ensures product consistency
     * - **Flexibility**: Supports dynamic agricultural management decisions
     * 
     * Business Value:
     * - **Operational Agility**: Ability to adjust watering schedules as conditions change
     * - **Quality Management**: Ensures optimal growing conditions throughout production
     * - **Resource Coordination**: Synchronized watering schedules optimize system usage
     * - **Decision Support**: Provides flexibility for experienced growers
     * 
     * Status Reporting and Logging:
     * - Tracks newly resumed crops vs. crops already active
     * - Provides comprehensive batch processing statistics
     * - Logs resumption activity for operational audit trails
     * - Records batch context for agricultural decision tracking
     * 
     * @agricultural_flexibility Supports dynamic watering management decisions
     * @logging Detailed watering resumption activity for operational transparency
     * @performance Efficient batch processing with optimized database operations
     */
    public function resumeWatering(Crop $crop): void
    {
        $batchCrops = $this->findBatchCrops($crop);

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
     * Reset crop to specific lifecycle stage with complete timestamp management.
     * 
     * Provides manual override capability to reset crop to any valid stage in the
     * agricultural lifecycle, properly managing all associated timestamps and stage
     * relationships. Essential for correcting processing errors or handling
     * exceptional growing conditions that require stage adjustments.
     * 
     * @param Crop $crop The crop to reset with current stage and timing data
     * @param string $targetStageCode Valid stage code (soaking, germination, blackout, light, harvested)
     * @return void Crop reset to target stage with proper timestamp management
     * 
     * @throws InvalidArgumentException If target stage code is invalid or not found
     * 
     * @manual_override Allows experienced growers to correct automated stage assignments
     * @timestamp_management Properly handles all stage-related timestamps
     * @agricultural_flexibility Supports exceptional growing conditions and corrections
     * @data_integrity Maintains proper stage relationships and timing consistency
     * 
     * Stage Reset Logic:
     * - **Stage Validation**: Ensures target stage exists and is valid
     * - **Timestamp Management**: Clears future stage timestamps, sets current if missing
     * - **Stage Assignment**: Updates crop to target stage with proper relationships
     * - **Data Consistency**: Maintains agricultural lifecycle integrity
     * - **Audit Logging**: Records manual reset operations for operational tracking
     * 
     * Timestamp Management Rules:
     * - **Future Stages**: Clears all timestamps for stages after target stage
     * - **Current Stage**: Sets timestamp if not already present
     * - **Previous Stages**: Preserves existing timestamps for historical accuracy
     * - **Data Integrity**: Ensures timestamp sequence matches stage progression
     * 
     * Agricultural Use Cases:
     * - **Error Correction**: Fix incorrect automated stage assignments
     * - **Exceptional Conditions**: Handle unusual growing situations requiring stage adjustments
     * - **Quality Control**: Reset crops that don't meet stage advancement criteria
     * - **Operational Flexibility**: Support experienced grower decision-making
     * 
     * Business Value:
     * - **Operational Control**: Provides manual override for automated systems
     * - **Quality Assurance**: Enables correction of stage assignment errors
     * - **Flexibility**: Supports experienced agricultural decision-making
     * - **Error Recovery**: Allows correction of system or operator mistakes
     * 
     * Data Integrity Considerations:
     * - Validates target stage against defined agricultural stages
     * - Maintains proper timestamp relationships and sequencing
     * - Preserves historical data while enabling forward corrections
     * - Logs reset operations for audit and troubleshooting purposes
     * 
     * @agricultural_stages Uses defined STAGES constant for validation
     * @logging Records stage reset operations with context and timing
     * @data_consistency Maintains proper timestamp relationships
     */
    public function resetToStage(Crop $crop, string $targetStageCode): void
    {
        if (!in_array($targetStageCode, self::STAGES)) {
            throw new InvalidArgumentException("Invalid stage: {$targetStageCode}");
        }

        $targetStage = CropStage::findByCode($targetStageCode);
        if (!$targetStage) {
            throw new InvalidArgumentException("Stage not found: {$targetStageCode}");
        }

        $crop->current_stage_id = $targetStage->id;
        $now = Carbon::now();

        // Clear timestamps for stages that come after the target stage
        $stageIndex = array_search($targetStageCode, self::STAGES);
        
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

        $crop->save();

        Log::info('Crop stage reset', [
            'crop_id' => $crop->id,
            'reset_to_stage' => $targetStageCode
        ]);
    }

    /**
     * Calculate expected harvest date based on agricultural recipe and planting timing.
     * 
     * Determines anticipated harvest date by combining crop's germination timestamp
     * with recipe-specified growing duration. Essential for production planning,
     * resource allocation, and customer delivery scheduling in agricultural operations.
     * 
     * @param Crop $crop The crop with recipe and germination timing data
     * @return Carbon|null Expected harvest date or null if insufficient data
     * 
     * @agricultural_calculation Uses recipe specifications for timing predictions
     * @production_planning Essential for resource allocation and scheduling
     * @customer_service Supports delivery date planning and customer communications
     * @recipe_integration Leverages recipe totalDays() method for duration calculations
     * 
     * Harvest Date Calculation Logic:
     * - **Recipe Validation**: Ensures crop has associated recipe with timing data
     * - **Germination Base**: Uses germination_at timestamp as calculation starting point
     * - **Duration Calculation**: Applies recipe's totalDays() for complete growing period
     * - **Validation Checks**: Returns null for invalid or insufficient data
     * - **Date Arithmetic**: Adds growing days to germination date for harvest prediction
     * 
     * Data Requirements:
     * - Valid recipe association with duration specifications
     * - Germination timestamp indicating when actual growing began
     * - Recipe must have positive totalDays() value for valid calculation
     * 
     * Business Applications:
     * - **Production Planning**: Schedule harvest labor and processing resources
     * - **Customer Service**: Provide accurate delivery date estimates
     * - **Inventory Management**: Plan storage and packaging resource allocation
     * - **Quality Control**: Time quality inspections and harvest preparation
     * - **Automation Support**: Trigger automated harvest-related tasks and notifications
     * 
     * Agricultural Context:
     * - Based on standardized microgreens growing cycles and timing
     * - Accounts for complete agricultural lifecycle from germination to harvest
     * - Supports different crop varieties with varying growing periods
     * - Provides foundation for automated agricultural workflow management
     * 
     * Error Handling:
     * - Returns null if recipe is missing or invalid
     * - Returns null if germination timestamp is not set
     * - Returns null if recipe duration is zero or negative
     * - Graceful handling of missing data without exceptions
     * 
     * @returns Carbon|null Calculated harvest date or null for insufficient data
     * @recipe_dependency Requires valid recipe with totalDays() method
     * @timing_accuracy Based on standardized agricultural growing periods
     */
    public function calculateExpectedHarvestDate(Crop $crop): ?Carbon
    {
        if (!$crop->recipe || !$crop->germination_at) {
            return null;
        }

        $plantedAt = Carbon::parse($crop->germination_at);
        $daysToMaturity = $crop->recipe->totalDays();

        if ($daysToMaturity <= 0) {
            return null;
        }

        return $plantedAt->addDays($daysToMaturity);
    }

    /**
     * Calculate duration crop has spent in current agricultural stage.
     * 
     * Determines number of days elapsed since crop entered its current stage by
     * comparing current timestamp with stage entry timestamp. Critical for monitoring
     * agricultural progress, identifying stage duration anomalies, and supporting
     * quality control decisions in microgreens production.
     * 
     * @param Crop $crop The crop with current stage and timing data
     * @return int Number of complete days in current stage (0 if no timestamp)
     * 
     * @agricultural_monitoring Tracks stage duration for quality control
     * @progress_tracking Monitors crop development against expected timelines
     * @quality_control Identifies crops with unusual stage duration patterns
     * @performance_analysis Supports agricultural efficiency measurements
     * 
     * Stage Duration Calculation Logic:
     * - **Current Stage Detection**: Identifies crop's current agricultural stage
     * - **Timestamp Retrieval**: Gets stage entry timestamp from appropriate field
     * - **Duration Calculation**: Calculates complete days between stage entry and now
     * - **Error Handling**: Returns zero for crops without valid stage timestamps
     * - **Precision**: Uses complete days for agricultural planning consistency
     * 
     * Agricultural Applications:
     * - **Quality Control**: Identify crops spending too long in any single stage
     * - **Progress Monitoring**: Track development against recipe specifications
     * - **Performance Analysis**: Analyze stage duration patterns for optimization
     * - **Alert Systems**: Trigger notifications for unusual stage duration
     * - **Compliance**: Document proper stage progression for quality standards
     * 
     * Business Value:
     * - **Quality Assurance**: Early detection of crops with development issues
     * - **Operational Efficiency**: Optimize stage transition timing and labor allocation
     * - **Production Planning**: Better understand actual vs. expected growing times
     * - **Customer Service**: More accurate delivery estimates based on real progress
     * - **Continuous Improvement**: Data for refining agricultural processes
     * 
     * Data Dependencies:
     * - Requires valid current stage assignment
     * - Needs appropriate stage timestamp (soaking_at, germination_at, etc.)
     * - Uses getCurrentStageTimestamp() for proper timestamp field selection
     * - Handles missing timestamps gracefully with zero return
     * 
     * Measurement Precision:
     * - Returns complete days only (not fractional days)
     * - Uses Carbon date arithmetic for accurate calculations
     * - Consistent with agricultural planning practices
     * - Suitable for stage duration monitoring and alerts
     * 
     * @delegates_to getCurrentStageTimestamp() for appropriate timestamp field
     * @precision Complete days suitable for agricultural monitoring
     * @error_handling Returns zero for missing timestamps without exceptions
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
     * Determine if crop requires watering suspension based on harvest timing.
     * 
     * Evaluates whether crop has reached the point where watering should be suspended
     * in preparation for harvest, based on recipe specifications and calculated harvest
     * timing. Essential for automated harvest preparation and product quality optimization
     * in microgreens production.
     * 
     * @param Crop $crop The crop with recipe and timing data for evaluation
     * @return bool True if watering should be suspended, false otherwise
     * 
     * @agricultural_automation Supports automated watering management decisions
     * @harvest_preparation Pre-harvest quality optimization through watering control
     * @recipe_compliance Follows recipe specifications for watering suspension timing
     * @quality_control Improves product characteristics through proper timing
     * 
     * Watering Suspension Logic:
     * - **Recipe Validation**: Ensures crop has recipe with suspension specifications
     * - **Timing Calculation**: Determines suspension point based on harvest timing
     * - **Current Time Comparison**: Evaluates if suspension time has been reached
     * - **Quality Optimization**: Follows agricultural best practices for product quality
     * - **Automation Support**: Enables automated watering system integration
     * 
     * Decision Criteria:
     * - Recipe must specify suspend_watering_hours > 0
     * - Harvest date must be calculable from recipe and germination timing
     * - Current time must be at or after calculated suspension point
     * - All timing calculations must be valid and realistic
     * 
     * Agricultural Benefits:
     * - **Product Quality**: Proper suspension timing improves harvest characteristics
     * - **Automation**: Enables automated watering system control
     * - **Consistency**: Standardized suspension timing across all production
     * - **Efficiency**: Reduces manual monitoring requirements
     * - **Compliance**: Follows established microgreens production protocols
     * 
     * Business Value:
     * - **Quality Improvement**: Better product characteristics through proper timing
     * - **Operational Automation**: Reduces labor requirements for watering management
     * - **Consistency**: Uniform product quality through standardized processes
     * - **Resource Optimization**: Efficient water usage and system management
     * 
     * Error Handling:
     * - Returns false if crop has no recipe
     * - Returns false if recipe has no suspension specification
     * - Returns false if harvest date cannot be calculated
     * - Graceful handling of timing calculation failures
     * 
     * @recipe_dependency Requires recipe with suspend_watering_hours specification
     * @timing_accuracy Based on calculateExpectedHarvestDate() method
     * @agricultural_standards Follows microgreens production best practices
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
     * Delete all scheduled tasks associated with a specific crop and its batch.
     * 
     * Removes all automated task schedules related to crop's agricultural lifecycle,
     * including stage transitions, watering management, and batch coordination tasks.
     * Essential for task cleanup when crop processing changes or crop is removed
     * from production systems.
     * 
     * @param Crop $crop The crop whose tasks should be deleted
     * @return int Number of tasks successfully deleted from task schedule
     * 
     * @cleanup_operation Removes obsolete tasks to prevent automated execution
     * @batch_coordination Deletes both individual and batch-related tasks
     * @agricultural_automation Maintains clean task schedule for active crops only
     * @data_integrity Prevents execution of tasks for modified or deleted crops
     * 
     * Task Deletion Logic:
     * - **Batch Identification**: Creates batch identifier for comprehensive cleanup
     * - **Individual Tasks**: Removes tasks specific to individual crop ID
     * - **Batch Tasks**: Removes tasks associated with crop's production batch
     * - **Comprehensive Cleanup**: Ensures no orphaned tasks remain in system
     * - **Stage Handling**: Manages cases where stage relationships may be null
     * 
     * Batch Identifier Construction:
     * - Combines recipe_id, germination date, and current stage code
     * - Handles null stage relationships with 'unknown' fallback
     * - Ensures proper task identification across batch coordination system
     * 
     * Business Applications:
     * - **Crop Modifications**: Clean up tasks when crop processing changes
     * - **Error Recovery**: Remove tasks for crops with processing errors
     * - **System Maintenance**: Prevent accumulation of obsolete automated tasks
     * - **Data Consistency**: Maintain alignment between crops and their tasks
     * 
     * Agricultural Context:
     * - Removes stage transition automation for modified crops
     * - Cleans up watering management tasks when processing changes
     * - Ensures batch coordination tasks remain relevant and accurate
     * - Prevents automated notifications for crops no longer in production
     * 
     * @database_operation Uses TaskSchedule model for persistent task management
     * @batch_aware Deletes both individual and batch-coordinated tasks
     * @defensive_programming Handles null stage relationships gracefully
     */
    public function deleteTasksForCrop(Crop $crop): int
    {
        // Create a batch identifier - handle case where currentStage might be null
        $stageCode = $crop->currentStage?->code ?? 'unknown';
        $batchIdentifier = "{$crop->recipe_id}_{$crop->germination_at->format('Y-m-d')}_{$stageCode}";
        
        // Find and delete all tasks related to this batch
        return TaskSchedule::where('resource_type', 'crops')
            ->where(function($query) use ($crop, $batchIdentifier) {
                $query->where('conditions->crop_id', $crop->id)
                      ->orWhere('conditions->batch_identifier', $batchIdentifier);
            })
            ->delete();
    }

    /**
     * Create automated task for coordinated batch stage transition.
     * 
     * Generates persistent task schedule for synchronized stage advancement across
     * entire crop batch, ensuring uniform processing and operational efficiency.
     * Includes comprehensive batch metadata and conditions for intelligent task
     * execution and user notification systems.
     * 
     * @param Crop $crop Representative crop from batch for task creation
     * @param string $targetStage Agricultural stage to transition to
     * @param Carbon $transitionTime When the stage transition should occur
     * @return TaskSchedule Created task with batch coordination data
     * 
     * @batch_coordination Creates single task for entire production batch
     * @agricultural_automation Enables scheduled stage transitions
     * @notification_system Supports automated user alerts for stage changes
     * @operational_efficiency Batch processing reduces manual intervention
     * 
     * Task Creation Logic:
     * - **Batch Discovery**: Finds all crops sharing recipe, date, and stage
     * - **Metadata Collection**: Gathers variety names and tray numbers for context
     * - **Condition Assembly**: Creates comprehensive task execution conditions
     * - **Schedule Configuration**: Sets timing and execution parameters
     * - **Persistence**: Saves task to TaskSchedule for automated execution
     * 
     * Task Conditions Structure:
     * - crop_id: Representative crop for batch identification
     * - batch_identifier: Unique identifier for batch coordination
     * - target_stage: Destination stage for advancement
     * - tray_numbers: Array of all tray numbers in batch
     * - variety: Agricultural variety name for user notifications
     * - tray_count and tray_list: Batch size and display information
     * 
     * Batch Coordination Benefits:
     * - **Synchronized Processing**: All crops advance together for efficiency
     * - **Reduced Labor**: Single task manages multiple crops simultaneously
     * - **Consistency**: Uniform timing ensures product quality
     * - **Resource Optimization**: Coordinated operations improve workflow
     * 
     * Agricultural Context:
     * - Supports microgreens production batch processing requirements
     * - Maintains variety-specific processing with proper identification
     * - Enables efficient use of growing space and labor resources
     * - Ensures consistent product quality through synchronized advancement
     * 
     * @persistent_storage Creates TaskSchedule record for automated execution
     * @variety_identification Includes agricultural variety names for context
     * @timing_precision Uses exact Carbon timestamps for agricultural timing
     */
    protected function createBatchStageTransitionTask(Crop $crop, string $targetStage, Carbon $transitionTime): TaskSchedule
    {
        // Get variety name with proper fallbacks
        $varietyName = $this->getVarietyName($crop);

        // Find other crops in the same batch
        $batchCrops = $this->findBatchCrops($crop);
        $batchTrays = $batchCrops->pluck('tray_number')->toArray();
        $batchTraysList = implode(', ', $batchTrays);
        $batchSize = count($batchTrays);
        
        // Get current stage for batch identifier
        $currentStageCode = $crop->currentStage?->code ?? ($crop->current_stage_id ? CropStage::find($crop->current_stage_id)?->code : 'unknown');
        
        // Create conditions for the task
        $conditions = [
            'crop_id' => (int) $crop->id,
            'batch_identifier' => "{$crop->recipe_id}_{$crop->germination_at->format('Y-m-d')}_{$currentStageCode}",
            'target_stage' => $targetStage,
            'tray_numbers' => $batchTrays,
            'tray_count' => $batchSize,
            'tray_list' => $batchTraysList,
            'variety' => $varietyName,
        ];
        
        // Create the task
        $taskName = "advance_to_{$targetStage}";
        
        $task = new TaskSchedule();
        $task->name = "Advance crop batch to {$targetStage} - {$varietyName}";
        $task->resource_type = 'crops';
        $task->task_name = $taskName;
        $task->frequency = 'once';
        $task->schedule_config = [];
        $task->conditions = $conditions;
        $task->is_active = true;
        $task->next_run_at = $transitionTime;
        $task->save();
        
        return $task;
    }

    /**
     * Create early warning task for soaking stage completion.
     * 
     * Generates automated alert task to notify production staff when seed soaking
     * will complete, enabling timely preparation for germination stage transition.
     * Critical for maintaining proper agricultural timing in automated microgreens
     * production systems.
     * 
     * @param Crop $crop Representative crop from soaking batch
     * @param Carbon $warningTime When the warning notification should be sent
     * @return TaskSchedule Created warning task with batch coordination data
     * 
     * @early_warning Provides advance notice for time-critical operations
     * @agricultural_timing Supports precise timing for seed germination
     * @batch_coordination Manages warnings for entire production batch
     * @quality_control Ensures proper transition timing for product quality
     * 
     * Warning Task Logic:
     * - **Batch Discovery**: Identifies all crops in same soaking batch
     * - **Timing Calculation**: Schedules warning before actual completion
     * - **Metadata Assembly**: Includes variety and tray information for context
     * - **Notification Preparation**: Sets up conditions for user alert system
     * - **Task Persistence**: Saves warning schedule for automated execution
     * 
     * Agricultural Importance:
     * - **Timing Critical**: Soaking duration directly affects germination success
     * - **Quality Impact**: Proper timing ensures optimal growing conditions
     * - **Labor Coordination**: Allows staff to prepare for stage transition
     * - **Process Optimization**: Reduces risk of over or under-soaking
     * 
     * Warning Conditions:
     * - warning_type: 'soaking_completion' for notification system routing
     * - minutes_until_completion: Default 30 minutes advance warning
     * - batch_identifier: Coordination across all crops in soaking batch
     * - variety and tray information: Context for production staff
     * 
     * Business Value:
     * - **Quality Assurance**: Prevents timing errors that affect product quality
     * - **Operational Efficiency**: Enables proactive preparation for transitions
     * - **Labor Optimization**: Coordinates staff activities with production timing
     * - **Risk Mitigation**: Reduces chance of missed critical timing windows
     * 
     * Notification Integration:
     * - Designed for integration with notification system
     * - Provides rich context for meaningful user alerts
     * - Supports both individual and batch processing workflows
     * - Enables customizable warning timing based on operational needs
     * 
     * @timing_critical Essential for maintaining agricultural timing precision
     * @batch_aware Coordinates warnings across entire production batch
     * @notification_ready Prepared for integration with alert systems
     */
    protected function createSoakingWarningTask(Crop $crop, Carbon $warningTime): TaskSchedule
    {
        $varietyName = $this->getVarietyName($crop);
        
        // Find other crops in the same batch
        $batchCrops = $this->findBatchCrops($crop);
        $batchTrays = $batchCrops->pluck('tray_number')->toArray();
        $batchTraysList = implode(', ', $batchTrays);
        $batchSize = count($batchTrays);
        
        // Get current stage for batch identifier
        $currentStageCode = $crop->currentStage?->code ?? ($crop->current_stage_id ? CropStage::find($crop->current_stage_id)?->code : 'unknown');
        
        $conditions = [
            'crop_id' => (int) $crop->id,
            'batch_identifier' => "{$crop->recipe_id}_{$crop->germination_at->format('Y-m-d')}_{$currentStageCode}",
            'target_stage' => 'germination', // Soaking leads to germination
            'tray_numbers' => $batchTrays,
            'tray_count' => $batchSize,
            'tray_list' => $batchTraysList,
            'variety' => $varietyName,
            'warning_type' => 'soaking_completion',
            'minutes_until_completion' => 30
        ];

        $task = new TaskSchedule();
        $task->name = "Soaking completes today - {$varietyName}";
        $task->resource_type = 'crops';
        $task->task_name = 'soaking_completion_warning';
        $task->frequency = 'once';
        $task->schedule_config = [];
        $task->conditions = $conditions;
        $task->is_active = true;
        $task->next_run_at = $warningTime;
        $task->save();
        
        return $task;
    }

    /**
     * Create automated task for pre-harvest watering suspension.
     * 
     * Generates scheduled task to suspend watering at optimal timing before harvest,
     * improving product quality and shelf life according to agricultural best practices.
     * Essential component of automated harvest preparation workflow in microgreens
     * production systems.
     * 
     * @param Crop $crop The crop requiring watering suspension
     * @param Carbon $suspendTime When watering should be suspended
     * @return TaskSchedule Created suspension task with timing and crop data
     * 
     * @harvest_preparation Optimizes product quality through timing control
     * @agricultural_automation Enables automated watering system integration
     * @quality_control Follows best practices for microgreens production
     * @resource_management Coordinates watering schedules with harvest timing
     * 
     * Suspension Task Logic:
     * - **Quality Optimization**: Times suspension for improved product characteristics
     * - **Automation Integration**: Creates task compatible with watering systems
     * - **Context Preservation**: Includes variety and tray information
     * - **Schedule Management**: Sets precise timing for suspension execution
     * - **Task Persistence**: Saves schedule for automated system execution
     * 
     * Agricultural Benefits:
     * - **Product Quality**: Proper suspension timing improves harvest characteristics
     * - **Shelf Life**: Reduced moisture content extends product freshness
     * - **Consistency**: Standardized suspension timing across all production
     * - **Automation**: Enables integration with automated watering systems
     * 
     * Task Conditions:
     * - crop_id: Individual crop identifier for specific suspension
     * - tray_number: Physical location for manual or automated operations
     * - variety: Agricultural variety name for operational context
     * - Timing specifications for precise execution
     * 
     * Business Applications:
     * - **Automated Systems**: Integration with irrigation control systems
     * - **Manual Operations**: Alerts for manual watering suspension
     * - **Quality Assurance**: Ensures consistent pre-harvest preparation
     * - **Resource Optimization**: Coordinates water usage with harvest scheduling
     * 
     * Integration Considerations:
     * - Compatible with both automated and manual watering systems
     * - Provides necessary context for operational decision-making
     * - Supports scalable implementation across multiple production areas
     * - Enables tracking and verification of suspension execution
     * 
     * @individual_crop Handles single crop suspension rather than batch operation
     * @timing_precision Uses exact timestamps for agricultural timing requirements
     * @automation_ready Designed for integration with automated watering systems
     */
    protected function createWateringSuspensionTask(Crop $crop, Carbon $suspendTime): TaskSchedule
    {
        $varietyName = $this->getVarietyName($crop);
        
        $conditions = [
            'crop_id' => (int) $crop->id,
            'tray_number' => $crop->tray_number,
            'variety' => $varietyName,
        ];

        $task = new TaskSchedule();
        $task->name = "Suspend watering - {$varietyName} (Tray #{$crop->tray_number})";
        $task->resource_type = 'crops';
        $task->task_name = 'suspend_watering';
        $task->frequency = 'once';
        $task->schedule_config = [];
        $task->conditions = $conditions;
        $task->is_active = true;
        $task->next_run_at = $suspendTime;
        $task->save();
        
        return $task;
    }

    /**
     * Process batch stage transition
     */
    protected function processBatchStageTransition(TaskSchedule $task, string $batchIdentifier, string $targetStage, array $trayNumbers): array
    {
        // Find all crops in this batch
        $batchParts = explode('_', $batchIdentifier);
        if (count($batchParts) !== 3) {
            return [
                'success' => false,
                'message' => "Invalid batch identifier format: {$batchIdentifier}",
            ];
        }
        
        list($recipeId, $plantedAtDate, $currentStage) = $batchParts;
        
        $crops = Crop::where('recipe_id', $recipeId)
            ->where('germination_at', $plantedAtDate)
            ->whereHas('currentStage', function($query) use ($currentStage) {
                $query->where('code', $currentStage);
            })
            ->whereIn('tray_number', $trayNumbers)
            ->get();
        
        if ($crops->isEmpty()) {
            return [
                'success' => false,
                'message' => "No crops found in batch with identifier {$batchIdentifier}",
            ];
        }
        
        // Check stage order
        $firstCrop = $crops->first();
        $stageOrder = ['soaking', 'germination', 'blackout', 'light', 'harvested'];
        $currentStageIndex = array_search($firstCrop->currentStage->code, $stageOrder);
        $targetStageIndex = array_search($targetStage, $stageOrder);
        
        if ($currentStageIndex === false || $targetStageIndex === false) {
            return [
                'success' => false,
                'message' => "Invalid stage definition for batch {$batchIdentifier} or task target stage {$targetStage}",
            ];
        }
        
        // Process stage advancement
        if ($currentStageIndex < $targetStageIndex) {
            // Send notification
            $this->sendStageTransitionNotification($firstCrop, $targetStage, count($crops));
            
            // Mark the task as processed
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            
            // Advance all crops through required stages
            $stagesNeeded = [];
            for ($i = $currentStageIndex + 1; $i <= $targetStageIndex; $i++) {
                $stagesNeeded[] = $stageOrder[$i];
            }
            
            foreach ($crops as $crop) {
                foreach ($stagesNeeded as $index => $nextStage) {
                    $isFinalStage = ($index === count($stagesNeeded) - 1);
                    
                    if ($isFinalStage) {
                        // Use the unified advanceStage method
                        $this->advanceStage($crop);
                        break; // advanceStage handles the batch, so we only need to call it once
                    } else {
                        // Manually update intermediate stages
                        $stageField = "{$nextStage}_at";
                        $crop->$stageField = now();
                        $nextStageObject = CropStage::findByCode($nextStage);
                        if ($nextStageObject) {
                            $crop->current_stage_id = $nextStageObject->id;
                        }
                        $crop->saveQuietly();
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => "Batch {$batchIdentifier} ({$crops->count()} crops) advanced to {$targetStage} stage" . 
                             (count($stagesNeeded) > 1 ? " (skipped " . (count($stagesNeeded) - 1) . " intermediate stages)" : "") . ".",
            ];
        } elseif ($currentStageIndex >= $targetStageIndex) {
            // Deactivate task if already at or past target stage
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            return [
                'success' => true,
                'message' => "Batch {$batchIdentifier} is already at or past {$targetStage} stage. Task deactivated.",
            ];
        }
        
        return [
            'success' => true,
            'message' => "Batch {$batchIdentifier} not yet ready for {$targetStage}. Notification not sent."
        ];
    }

    /**
     * Process soaking completion warning task
     */
    protected function processSoakingWarningTask(TaskSchedule $task, string $batchIdentifier, array $trayNumbers): array
    {
        // Find the crop
        $conditions = $task->conditions;
        $cropId = $conditions['crop_id'] ?? null;
        $crop = Crop::find($cropId);
        
        if (!$crop) {
            return [
                'success' => false,
                'message' => "Crop with ID {$cropId} not found",
            ];
        }
        
        // Check if crop is still in soaking stage
        if ($crop->currentStage->code !== 'soaking') {
            // Deactivate task if no longer soaking
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            
            return [
                'success' => true,
                'message' => "Crop batch {$batchIdentifier} is no longer in soaking stage. Warning task deactivated.",
            ];
        }
        
        // Send soaking completion warning notification
        $this->sendSoakingWarningNotification($crop, $conditions);
        
        // Mark the task as processed
        $task->is_active = false;
        $task->last_run_at = now();
        $task->save();
        
        return [
            'success' => true,
            'message' => "Soaking completion warning sent for batch {$batchIdentifier} ({$conditions['tray_count']} trays).",
        ];
    }

    /**
     * Process single crop stage transition
     */
    protected function processSingleCropStageTransition(TaskSchedule $task, int $cropId, string $targetStage): array
    {
        $crop = Crop::find($cropId);
        if (!$crop) {
            return [
                'success' => false,
                'message' => "Crop with ID {$cropId} not found",
            ];
        }
        
        // Check stage order
        $stageOrder = ['soaking', 'germination', 'blackout', 'light', 'harvested'];
        $currentStageIndex = array_search($crop->currentStage->code, $stageOrder);
        $targetStageIndex = array_search($targetStage, $stageOrder);

        if ($currentStageIndex === false || $targetStageIndex === false) {
            return [
                'success' => false,
                'message' => "Invalid stage definition for crop ID {$cropId} or task target stage {$targetStage}",
            ];
        }

        if ($currentStageIndex < $targetStageIndex) {
            // Send notification
            $this->sendStageTransitionNotification($crop, $targetStage);
            
            // Mark the task as processed
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            
            // Advance through required stages
            $stagesNeeded = [];
            for ($i = $currentStageIndex + 1; $i <= $targetStageIndex; $i++) {
                $stagesNeeded[] = $stageOrder[$i];
            }
            
            foreach ($stagesNeeded as $index => $nextStage) {
                $isFinalStage = ($index === count($stagesNeeded) - 1);
                
                if ($isFinalStage) {
                    $this->advanceStage($crop);
                } else {
                    $stageField = "{$nextStage}_at";
                    $crop->$stageField = now();
                    $nextStageObject = CropStage::findByCode($nextStage);
                    if ($nextStageObject) {
                        $crop->current_stage_id = $nextStageObject->id;
                    }
                    $crop->saveQuietly();
                }
            }
            
            return [
                'success' => true,
                'message' => "Crop ID {$cropId} advanced to {$targetStage} stage" . 
                             (count($stagesNeeded) > 1 ? " (skipped " . (count($stagesNeeded) - 1) . " intermediate stages)" : "") . ".",
            ];
        } elseif ($currentStageIndex >= $targetStageIndex) {
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            return [
                'success' => true,
                'message' => "Crop ID {$cropId} is already at or past {$targetStage} stage. Task deactivated.",
            ];
        }
        
        return [
            'success' => true,
            'message' => "Crop ID {$cropId} not yet ready for {$targetStage}. Notification not sent."
        ];
    }

    /**
     * Send a notification for soaking completion warning
     */
    protected function sendSoakingWarningNotification(Crop $crop, array $conditions): void
    {
        $setting = NotificationSetting::findByTypeAndEvent('crops', 'stage_transition');
        
        if (!$setting || !$setting->shouldSendEmail()) {
            return;
        }
        
        $recipients = collect($setting->recipients);
        
        if ($recipients->isEmpty()) {
            return;
        }
        
        $minutesUntil = $conditions['minutes_until_completion'] ?? 30;
        $trayCount = $conditions['tray_count'] ?? 1;
        $trayList = $conditions['tray_list'] ?? $crop->tray_number;
        $variety = $conditions['variety'] ?? $this->getVarietyName($crop);
        
        $data = [
            'crop_id' => $crop->id,
            'variety' => $variety,
            'tray_count' => $trayCount,
            'tray_list' => $trayList,
            'minutes_until_completion' => $minutesUntil,
        ];
        
        $subject = "Soaking Completes Today - {$variety}";
        $body = "The soaking stage for {$variety} (Tray" . ($trayCount > 1 ? "s" : "") . " {$trayList}) will complete today. Please monitor for the transition to germination stage.";
        
        Notification::route('mail', $recipients->toArray())
            ->notify(new ResourceActionRequired(
                $subject,
                $body,
                route('filament.admin.resources.crops.edit', ['record' => $crop->id]),
                'View Crop'
            ));
    }

    /**
     * Send a notification for a stage transition
     */
    protected function sendStageTransitionNotification(Crop $crop, string $targetStage, ?int $cropCount = null): void
    {
        $setting = NotificationSetting::findByTypeAndEvent('crops', 'stage_transition');
        
        if (!$setting || !$setting->shouldSendEmail()) {
            return;
        }
        
        $recipients = collect($setting->recipients);
        
        if ($recipients->isEmpty()) {
            return;
        }
        
        $data = [
            'crop_id' => $crop->id,
            'tray_number' => $crop->tray_number,
            'variety' => $this->getVarietyName($crop),
            'stage' => ucfirst($targetStage),
            'days_in_previous_stage' => $crop->daysInCurrentStage(),
        ];
        
        $subject = $setting->getEmailSubject($data);
        $body = $setting->getEmailBody($data);
        
        Notification::route('mail', $recipients->toArray())
            ->notify(new ResourceActionRequired(
                $subject,
                $body,
                route('filament.admin.resources.crops.edit', ['record' => $crop->id]),
                'View Crop'
            ));
    }

    /**
     * Find all crops in the same batch as the given crop
     */
    protected function findBatchCrops(Crop $crop)
    {
        return Crop::where('recipe_id', $crop->recipe_id)
            ->where('germination_at', $crop->germination_at)
            ->where('current_stage_id', $crop->current_stage_id)
            ->get();
    }

    /**
     * Get variety name for a crop
     */
    protected function getVarietyName(Crop $crop): string
    {
        if ($crop->recipe) {
            if ($crop->recipe->seedEntry) {
                return $crop->recipe->seedEntry->common_name . ' - ' . $crop->recipe->seedEntry->cultivar_name;
            } else if ($crop->recipe->name) {
                return $crop->recipe->name;
            }
        }
        return 'Unknown';
    }

    /**
     * Get the timestamp field name for a stage
     */
    protected function getStageTimestampField(string $stage): string
    {
        return match ($stage) {
            'soaking' => 'soaking_at',
            'germination' => 'germination_at',
            'blackout' => 'blackout_at',
            'light' => 'light_at',
            'harvested' => 'harvested_at',
            default => throw new InvalidArgumentException("Unknown stage: {$stage}")
        };
    }

    /**
     * Get the timestamp for the crop's current stage
     */
    protected function getCurrentStageTimestamp(Crop $crop): ?string
    {
        if (!$crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }

        if (!$crop->currentStage) {
            return null;
        }

        $timestampField = $this->getStageTimestampField($crop->currentStage->code);
        return $crop->{$timestampField};
    }

    // ===== STAGE TRANSITION HELPER METHODS =====

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
                ->where('germination_at', $target->germination_at)
                ->where('current_stage_id', $target->current_stage_id)
                ->with(['recipe', 'currentStage'])
                ->get();
        }

        if ($target instanceof CropBatch) {
            return $target->crops()->with(['recipe', 'currentStage'])->get();
        }

        throw new InvalidArgumentException('Target must be Crop or CropBatch instance');
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
     * Perform the advancement with history recording
     */
    private function performAdvancement(Collection $crops, CropStage $currentStage, CropStage $nextStage, Carbon $transitionTime, array $options): array
    {
        $results = [
            'advanced' => 0,
            'failed' => 0,
            'warnings' => [],
            'crops' => [],
            'affected_count' => 0,
        ];

        $timestampField = self::STAGE_TIMESTAMP_MAP[$nextStage->code] ?? null;
        $recordStageHistory = app(RecordStageHistory::class);

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

                // Record stage transition in history
                $recordStageHistory->execute($crop, $nextStage, $transitionTime, 'Stage automatically advanced');

                $results['advanced']++;
                $results['affected_count']++;
                $results['crops'][] = [
                    'id' => $crop->id,
                    'tray_number' => $crop->tray_number,
                    'status' => 'success'
                ];

            } catch (Exception $e) {
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
     * Fix missing stage timestamps for existing crops
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
            
            if (!$crop->$timestampField && $crop->germination_at) {
                // Use germination_at as fallback timestamp
                $crop->update([$timestampField => $crop->germination_at]);
                $fixed = true;
                
                Log::info('Fixed missing stage timestamp', [
                    'crop_id' => $crop->id,
                    'stage' => $stageCode,
                    'field' => $timestampField,
                    'time' => $crop->germination_at->format('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $fixed;
    }

    /**
     * Revert a single crop or batch to the previous stage
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

            // Perform the reversal
            return $this->performReversal($crops, $currentStage, $previousStage, $reason);
        });
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
     * Perform the reversal
     */
    private function performReversal(Collection $crops, CropStage $currentStage, CropStage $previousStage, ?string $reason): array
    {
        $results = [
            'reverted' => 0,
            'failed' => 0,
            'warnings' => [],
            'crops' => [],
            'affected_count' => 0,
        ];

        $currentTimestampField = self::STAGE_TIMESTAMP_MAP[$currentStage->code] ?? null;
        $recordStageHistory = app(RecordStageHistory::class);

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

                // Remove the current stage from history
                $recordStageHistory->removeStageEntry($crop, $currentStage);

                $results['reverted']++;
                $results['affected_count']++;
                $results['crops'][] = [
                    'id' => $crop->id,
                    'tray_number' => $crop->tray_number,
                    'status' => 'success'
                ];

            } catch (Exception $e) {
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
}