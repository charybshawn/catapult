<?php

namespace App\Services;

use Exception;
use App\Models\Recipe;
use App\Models\CropStage;
use App\Models\Crop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive agricultural quality control and crop lifecycle validation service.
 * 
 * This specialized service enforces critical business rules and data integrity standards
 * for microgreens production operations. Manages crop lifecycle validation, stage
 * progression rules, inventory coordination, and agricultural compliance to ensure
 * consistent product quality and operational efficiency.
 * 
 * @service_domain Agricultural quality control and crop lifecycle management
 * @business_purpose Enforces production standards and agricultural compliance
 * @agricultural_focus Microgreens cultivation with recipe-based validation rules
 * @quality_control Ensures proper stage progression and timing validation
 * @integration_scope Coordinates with inventory, task management, and stage systems
 * 
 * Core Validation Areas:
 * - **Stage Progression Validation**: Ensures chronological stage advancement
 * - **Timestamp Integrity**: Validates agricultural timing and sequence consistency
 * - **Recipe Compliance**: Enforces recipe specifications and requirements
 * - **Inventory Coordination**: Manages seed deduction and resource allocation
 * - **Business Rule Enforcement**: Applies agricultural standards and constraints
 * - **Data Consistency**: Maintains integrity across crop lifecycle operations
 * 
 * Agricultural Quality Control Functions:
 * - **Chronological Validation**: Ensures stage timestamps follow natural progression
 * - **Recipe Integration**: Validates crop configuration against recipe specifications
 * - **Stage Management**: Enforces proper stage transitions and requirements
 * - **Resource Validation**: Coordinates inventory deduction and availability
 * - **Compliance Monitoring**: Ensures adherence to agricultural standards
 * - **Error Prevention**: Catches and prevents invalid crop configurations
 * 
 * Business Rules and Standards:
 * - **Stage Sequencing**: Enforces natural agricultural progression (soaking → germination → blackout → light → harvest)
 * - **Timing Validation**: Ensures timestamps follow chronological order
 * - **Recipe Requirements**: Validates soaking requirements and stage specifications
 * - **Inventory Consistency**: Coordinates seed deduction with crop creation
 * - **Watering Rules**: Applies recipe-based watering suspension logic
 * - **Quality Standards**: Maintains consistent production parameters
 * 
 * Integration Architecture:
 * - **CropTaskManagementService**: Coordinates automated task scheduling
 * - **InventoryManagementService**: Manages seed deduction and resource allocation
 * - **Recipe Models**: Validates against agricultural specifications
 * - **CropStage Models**: Ensures valid stage assignments and progressions
 * - **Crop Models**: Applies validation rules to crop lifecycle data
 * 
 * Validation Workflow:
 * - **Pre-Creation**: Initializes default values based on recipe requirements
 * - **Data Validation**: Checks business rules and constraints before persistence
 * - **Post-Creation**: Triggers inventory deduction and task scheduling
 * - **Update Validation**: Ensures changes maintain data integrity and business rules
 * - **Stage Transitions**: Validates progression follows agricultural standards
 * 
 * Quality Assurance Benefits:
 * - **Error Prevention**: Catches invalid configurations before they cause issues
 * - **Consistency**: Ensures uniform application of agricultural standards
 * - **Compliance**: Maintains adherence to microgreens production best practices
 * - **Integration**: Coordinates validation across multiple system components
 * - **Scalability**: Handles validation for large-scale agricultural operations
 * 
 * Performance and Reliability:
 * - **Efficient Validation**: Minimizes database queries during validation checks
 * - **Error Handling**: Graceful handling of validation failures with detailed messages
 * - **Logging**: Comprehensive logging for troubleshooting validation issues
 * - **Bulk Operations**: Special handling for efficient bulk crop creation
 * - **Testing Integration**: Environment-aware behavior for testing scenarios
 * 
 * Business Impact:
 * - **Quality Assurance**: Prevents production issues through proactive validation
 * - **Operational Efficiency**: Automates complex validation logic consistently
 * - **Resource Management**: Ensures proper inventory coordination and allocation
 * - **Compliance**: Maintains adherence to agricultural production standards
 * - **Risk Mitigation**: Reduces errors and inconsistencies in production data
 * 
 * @dependencies CropTaskManagementService, InventoryManagementService, Recipe, CropStage
 * @validation_scope Complete crop lifecycle from creation through harvest
 * @agricultural_standards Microgreens production best practices and timing requirements
 * @integration_pattern Service coordination for comprehensive crop management
 */
class CropValidationService
{
    /**
     * Agricultural task automation service for crop lifecycle management.
     * 
     * Provides integration with automated task scheduling, watering management,
     * and stage transition coordination. Essential for validation operations
     * that need to coordinate with agricultural workflow automation.
     * 
     * @var CropTaskManagementService
     */
    protected CropTaskManagementService $cropTaskService;
    
    /**
     * Seed inventory and resource management service.
     * 
     * Handles seed deduction, lot tracking, and resource allocation coordination.
     * Critical for validation operations that affect inventory levels and
     * resource consumption in agricultural production workflows.
     * 
     * @var InventoryManagementService
     */
    protected InventoryManagementService $inventoryService;
    
    /**
     * Initialize crop validation service with essential agricultural dependencies.
     * 
     * Creates service instance with required dependencies for comprehensive crop
     * validation, inventory coordination, and task management integration. Establishes
     * service architecture for coordinated agricultural quality control operations.
     * 
     * @param CropTaskManagementService $cropTaskService Agricultural task automation service
     * @param InventoryManagementService $inventoryService Seed inventory and resource management
     * @return void Service initialized with agricultural workflow dependencies
     * 
     * @dependency_injection Receives essential services for comprehensive validation
     * @service_coordination Establishes integration with agricultural management systems
     * @architectural_pattern Follows dependency injection for testable, modular design
     * 
     * Service Dependencies:
     * - **CropTaskManagementService**: Provides agricultural task scheduling and automation
     * - **InventoryManagementService**: Manages seed deduction and resource allocation
     * - **Integration Architecture**: Enables coordinated validation across systems
     * 
     * @agricultural_architecture Coordinates multiple agricultural service components
     * @quality_control Establishes foundation for comprehensive crop validation
     */
    public function __construct(CropTaskManagementService $cropTaskService, InventoryManagementService $inventoryService)
    {
        $this->cropTaskService = $cropTaskService;
        $this->inventoryService = $inventoryService;
    }
    /**
     * Validate chronological progression of agricultural stage timestamps.
     * 
     * Ensures crop lifecycle timestamps follow natural agricultural progression,
     * preventing data inconsistencies that could compromise production tracking
     * and quality control. Critical for maintaining agricultural integrity and
     * enabling accurate progress monitoring throughout crop development.
     * 
     * @param Crop $crop The crop to validate with stage timestamps
     * @return void Validation passes silently or throws exception
     * 
     * @throws Exception If timestamps are not in chronological order
     * 
     * @agricultural_integrity Ensures stage progression follows natural growing sequence
     * @data_validation Prevents timestamp inconsistencies in production tracking
     * @quality_control Maintains accurate agricultural progress monitoring
     * @business_rules Enforces proper crop lifecycle data standards
     * 
     * Validation Logic:
     * - **Timestamp Collection**: Gathers all non-null stage timestamps
     * - **Chronological Ordering**: Validates timestamps follow agricultural sequence
     * - **Flexibility**: Allows same timestamps for simultaneous transitions
     * - **Error Reporting**: Provides clear messages for validation failures
     * - **Efficiency**: Skips validation for insufficient data points
     * 
     * Agricultural Stage Sequence:
     * 1. germination_at: When seeds begin sprouting
     * 2. blackout_at: When crops enter darkness period (if applicable)
     * 3. light_at: When crops receive growing light
     * 4. harvested_at: When crops are harvested
     * 
     * Validation Rules:
     * - Each subsequent stage must occur at or after the previous stage
     * - Missing timestamps are ignored (crops may skip stages)
     * - Same timestamps are allowed for operational flexibility
     * - Clear error messages indicate which stages are out of order
     * 
     * Business Benefits:
     * - **Data Integrity**: Prevents impossible timestamp configurations
     * - **Quality Assurance**: Ensures accurate progress tracking
     * - **Error Prevention**: Catches data entry errors before persistence
     * - **Compliance**: Maintains agricultural data standards
     * - **Troubleshooting**: Clear error messages for operational teams
     * 
     * Error Handling:
     * - Detailed exception messages identify problematic stage transitions
     * - Human-readable stage names in error messages
     * - Specific identification of which stages are out of sequence
     * - Graceful handling of missing or null timestamps
     * 
     * @performance Efficient validation with minimal overhead for valid data
     * @flexibility Accommodates various crop configurations and timing scenarios
     * @agricultural_standards Based on natural microgreens growing progression
     */
    public function validateTimestampSequence(Crop $crop): void
    {
        $timestamps = [];
        
        // Build array of non-null timestamps with their labels
        if ($crop->germination_at) {
            $timestamps['germination_at'] = $crop->germination_at;
        }
        if ($crop->blackout_at) {
            $timestamps['blackout_at'] = $crop->blackout_at;
        }
        if ($crop->light_at) {
            $timestamps['light_at'] = $crop->light_at;
        }
        if ($crop->harvested_at) {
            $timestamps['harvested_at'] = $crop->harvested_at;
        }
        
        // Skip validation if we have fewer than 2 timestamps
        if (count($timestamps) < 2) {
            return;
        }
        
        // Convert to Carbon instances for comparison
        $carbonTimestamps = array_map(function($timestamp) {
            return $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);
        }, $timestamps);
        
        // Check if timestamps are in order (allow same timestamp for flexibility)
        $previousTimestamp = null;
        $previousLabel = null;
        
        foreach ($carbonTimestamps as $label => $timestamp) {
            if ($previousTimestamp && $timestamp->lt($previousTimestamp)) {
                $readableLabel = str_replace('_at', '', str_replace('_', ' ', $label));
                $readablePrevious = str_replace('_at', '', str_replace('_', ' ', $previousLabel));
                throw new Exception("Growth stage timestamps must be in chronological order. {$readableLabel} cannot be before {$readablePrevious}.");
            }
            $previousTimestamp = $timestamp;
            $previousLabel = $label;
        }
    }

    /**
     * Initialize crop with appropriate default values based on recipe requirements.
     * 
     * Configures new crop with correct stage assignment, timing timestamps, and
     * soaking requirements based on recipe specifications. Essential for ensuring
     * consistent crop initialization and proper agricultural workflow setup from
     * the beginning of the production lifecycle.
     * 
     * @param Crop $crop The new crop to initialize with default values
     * @return void Crop configured with appropriate defaults
     * 
     * @agricultural_setup Configures crop based on recipe specifications
     * @stage_initialization Sets appropriate starting stage and timestamps
     * @soaking_management Handles soaking vs. direct germination workflows
     * @recipe_compliance Ensures initialization matches recipe requirements
     * 
     * Initialization Logic:
     * - **Recipe Analysis**: Determines soaking requirements from recipe specifications
     * - **Stage Assignment**: Sets appropriate starting stage (soaking vs. germination)
     * - **Timestamp Configuration**: Establishes initial timing based on workflow type
     * - **Default Values**: Applies consistent defaults for operational efficiency
     * - **Workflow Setup**: Prepares crop for proper agricultural progression
     * 
     * Soaking Workflow Initialization:
     * - Sets requires_soaking flag based on recipe specifications
     * - Assigns 'soaking' as initial stage for soaking-required crops
     * - Sets soaking_at timestamp to current time for immediate tracking
     * - Prepares for automated transition to germination stage
     * 
     * Direct Germination Initialization:
     * - Bypasses soaking stage for non-soaking varieties
     * - Assigns 'germination' as initial stage for direct-planted crops
     * - Sets germination_at timestamp to current time
     * - Prepares for progression through blackout and light stages
     * 
     * Recipe Integration:
     * - Analyzes recipe requirements using requiresSoaking() method
     * - Applies recipe-specific initialization parameters
     * - Ensures crop configuration matches agricultural specifications
     * - Maintains consistency with recipe-based automation systems
     * 
     * Business Benefits:
     * - **Consistency**: Uniform initialization across all crop creation
     * - **Automation Ready**: Prepares crops for automated task scheduling
     * - **Error Prevention**: Reduces manual configuration errors
     * - **Quality Control**: Ensures proper workflow setup from start
     * - **Operational Efficiency**: Standardizes crop creation process
     * 
     * Data Architecture Notes:
     * - Computed time fields moved to crop_batches_list_view for performance
     * - Individual crop records focus on core agricultural data
     * - View-based calculations support efficient reporting and analysis
     * 
     * @recipe_driven Initialization based on agricultural recipe specifications
     * @workflow_preparation Sets up crop for proper agricultural progression
     * @performance_optimized Leverages view-based calculations for efficiency
     */
    public function initializeNewCrop(Crop $crop): void
    {
        // Auto-set requires_soaking based on recipe
        if ($crop->recipe_id && !isset($crop->requires_soaking)) {
            $recipe = Recipe::find($crop->recipe_id);
            if ($recipe) {
                $crop->requires_soaking = $recipe->requiresSoaking();
            }
        }


        // Set stage-specific timestamps and current_stage based on recipe requirements
        if ($crop->requires_soaking && $crop->recipe_id) {
            // Start at soaking stage if recipe requires it
            if (!$crop->current_stage_id) {
                $soakingStage = CropStage::findByCode('soaking');
                if ($soakingStage) {
                    $crop->current_stage_id = $soakingStage->id;
                }
            }

            // Set soaking_at if not provided
            if (!$crop->soaking_at) {
                $crop->soaking_at = now();
            }

        } else {
            // Set germination_at automatically to now for non-soaking crops
            if (!$crop->germination_at) {
                $crop->germination_at = now();
            }

            // Always start at germination stage if not set
            if (!$crop->current_stage_id) {
                $germinationStage = CropStage::findByCode('germination');
                if ($germinationStage) {
                    $crop->current_stage_id = $germinationStage->id;
                }
            }
        }

        // Note: Computed time fields have been moved to crop_batches_list_view
        // They are no longer stored on individual crop records
    }

    /**
     * Legacy method for adjusting stage timestamps (no longer needed).
     * 
     * This method was previously used to recalculate stage timestamps when
     * planting dates changed. With the removal of planting_at field and
     * architectural improvements, this functionality is no longer required.
     * Method retained for backward compatibility but performs no operations.
     * 
     * @param Crop $crop The crop that would have had timestamps adjusted
     * @return void No operations performed (legacy compatibility)
     * 
     * @deprecated Method no longer needed since planting_at field removal
     * @architectural_change Timing calculations moved to view-based system
     * @backward_compatibility Retained to prevent breaking changes
     * 
     * Historical Context:
     * - Previously recalculated stage timestamps based on planting date changes
     * - Functionality replaced by more efficient view-based calculations
     * - Individual crop records now focus on core agricultural data
     * - Computed time fields handled by crop_batches_list_view
     * 
     * Current Architecture:
     * - Stage timestamps managed directly through stage transition events
     * - Recipe-based calculations performed at view level for efficiency
     * - Crop timing managed through specialized service methods
     * - More accurate and consistent timestamp management system
     * 
     * @legacy_method Retained for compatibility but performs no operations
     * @architectural_improvement Part of system optimization and simplification
     */
    public function adjustStageTimestamps(Crop $crop): void
    {
        // Method no longer needed since planting_at was removed
    }

    /**
     * Comprehensive crop data validation before persistence operations.
     * 
     * Performs thorough validation of crop data against business rules,
     * agricultural standards, and system constraints. Returns detailed error
     * array for any violations, enabling graceful error handling and user
     * feedback for data correction before database persistence.
     * 
     * @param Crop $crop The crop to validate with all configured data
     * @return array Array of validation error messages (empty if valid)
     * 
     * @business_rules_validation Enforces agricultural and operational constraints
     * @data_integrity Ensures crop data meets system requirements
     * @error_prevention Catches invalid configurations before persistence
     * @user_feedback Provides clear error messages for correction guidance
     * 
     * Validation Categories:
     * - **Quantitative Validation**: Tray counts and numeric field constraints
     * - **Relationship Validation**: Recipe and stage reference integrity
     * - **Business Rules**: Agricultural standards and operational requirements
     * - **Data Consistency**: Cross-field validation and logical constraints
     * 
     * Tray Count Validation:
     * - Ensures tray_count is positive when specified
     * - Prevents zero or negative tray configurations
     * - Supports null values for flexible crop configurations
     * - Clear error messages for invalid quantities
     * 
     * Recipe Validation:
     * - Verifies recipe_id references valid, existing recipe
     * - Ensures recipe relationship is properly loaded when specified
     * - Supports crops without recipes for flexible configurations
     * - Critical for recipe-based automation and timing calculations
     * 
     * Stage Validation:
     * - Validates current_stage_id against valid CropStage records
     * - Ensures stage assignments use legitimate agricultural stages
     * - Supports proper stage progression and automation systems
     * - Prevents invalid stage configurations that break workflows
     * 
     * Error Reporting:
     * - Returns array of human-readable error messages
     * - Empty array indicates successful validation
     * - Clear, actionable messages for user correction
     * - Suitable for display in user interfaces
     * 
     * Business Benefits:
     * - **Quality Assurance**: Prevents invalid crop configurations
     * - **User Experience**: Clear feedback for data correction
     * - **System Integrity**: Maintains data consistency across operations
     * - **Error Prevention**: Catches issues before they cause system problems
     * - **Automation Ready**: Ensures crops meet requirements for automated processing
     * 
     * Integration Considerations:
     * - Called before crop persistence operations
     * - Suitable for both manual entry and automated crop creation
     * - Supports bulk operations with individual crop validation
     * - Compatible with form validation and API error handling
     * 
     * @agricultural_standards Validates against microgreens production requirements
     * @error_handling Returns detailed error information for user correction
     * @system_integrity Maintains data consistency across agricultural operations
     */
    public function validateCrop(Crop $crop): array
    {
        $errors = [];

        // Validate tray count
        if ($crop->tray_count !== null && $crop->tray_count <= 0) {
            $errors[] = 'Tray count must be greater than zero';
        }


        // Validate recipe exists if recipe_id is set
        if ($crop->recipe_id && !$crop->recipe) {
            $errors[] = 'Invalid recipe selected';
        }

        // Validate stage progression
        if ($crop->current_stage_id) {
            $validStageIds = CropStage::pluck('id')->toArray();
            if (!in_array($crop->current_stage_id, $validStageIds)) {
                $errors[] = 'Invalid growth stage';
            }
        }

        return $errors;
    }

    /**
     * Determine if crop requires watering suspension based on recipe specifications.
     * 
     * Evaluates recipe settings and current crop status to determine if watering
     * should be suspended in preparation for harvest. Delegates to CropTaskManagementService
     * for detailed timing calculations while providing simplified validation interface
     * for quality control workflows.
     * 
     * @param Crop $crop The crop to evaluate for watering suspension requirements
     * @return bool True if watering should be suspended, false otherwise
     * 
     * @agricultural_automation Supports automated watering control decisions
     * @recipe_compliance Follows recipe specifications for suspension timing
     * @quality_control Ensures proper pre-harvest preparation
     * @service_delegation Uses specialized service for detailed calculations
     * 
     * Evaluation Logic:
     * - **Recipe Validation**: Ensures crop has recipe with suspension specifications
     * - **Timing Assessment**: Delegates to CropTaskManagementService for precise calculation
     * - **Quality Optimization**: Follows agricultural best practices for product quality
     * - **Automation Integration**: Supports automated watering system control
     * 
     * Recipe Requirements:
     * - Crop must have associated recipe for evaluation
     * - Recipe must specify suspend_water_hours > 0 for suspension consideration
     * - Missing recipe or zero suspension hours results in false return
     * - Ensures only appropriate crops undergo watering suspension
     * 
     * Business Applications:
     * - **Quality Control**: Pre-harvest preparation validation
     * - **Automation Systems**: Integration with watering control systems
     * - **Operational Planning**: Manual watering schedule coordination
     * - **Compliance**: Following recipe specifications for consistent quality
     * 
     * Service Integration:
     * - Delegates detailed calculations to CropTaskManagementService
     * - Provides simplified interface for validation workflows
     * - Maintains separation of concerns between validation and task management
     * - Ensures consistent suspension logic across different use cases
     * 
     * Agricultural Benefits:
     * - **Product Quality**: Proper suspension timing improves harvest characteristics
     * - **Consistency**: Standardized evaluation across all production operations
     * - **Automation**: Enables integration with automated watering systems
     * - **Compliance**: Ensures adherence to recipe specifications
     * 
     * @recipe_driven Evaluation based on agricultural recipe specifications
     * @delegates_to CropTaskManagementService.shouldSuspendWatering() for calculations
     * @quality_optimization Supports pre-harvest preparation for product quality
     */
    public function shouldAutoSuspendWatering(Crop $crop): bool
    {
        if (!$crop->recipe || !$crop->recipe->suspend_water_hours) {
            return false;
        }

        return $this->cropTaskService->shouldSuspendWatering($crop);
    }

    /**
     * Execute comprehensive post-creation workflow for newly created crops.
     * 
     * Coordinates essential post-creation operations including inventory management
     * and automated task scheduling. Handles bulk operation detection and environment
     * awareness to optimize performance during large-scale operations while ensuring
     * complete workflow execution for individual crop creation.
     * 
     * @param Crop $crop The newly created crop requiring post-creation processing
     * @return void Post-creation operations completed or skipped appropriately
     * 
     * @post_creation_workflow Orchestrates essential operations after crop creation
     * @inventory_coordination Manages seed deduction and resource allocation
     * @task_automation Schedules agricultural tasks for crop lifecycle management
     * @performance_optimization Handles bulk operations efficiently
     * 
     * Post-Creation Operations:
     * - **Inventory Management**: Deducts seeds from available inventory
     * - **Task Scheduling**: Creates automated agricultural workflow tasks
     * - **Bulk Operation Awareness**: Optimizes performance for large-scale creation
     * - **Environment Sensitivity**: Adjusts behavior for testing scenarios
     * - **Error Handling**: Graceful handling of post-creation failures
     * 
     * Inventory Management:
     * - Automatically deducts required seeds from inventory
     * - Respects bulk operation mode for performance optimization
     * - Coordinates with InventoryManagementService for proper tracking
     * - Ensures accurate inventory levels after crop creation
     * 
     * Task Scheduling Logic:
     * - Creates complete agricultural task schedule for crop lifecycle
     * - Skips task creation during testing for performance and isolation
     * - Handles bulk operations efficiently by skipping individual scheduling
     * - Provides error logging for troubleshooting task creation issues
     * 
     * Performance Considerations:
     * - **Bulk Operations**: Skips resource-intensive operations during bulk creation
     * - **Testing Environment**: Disables task scheduling during automated testing
     * - **Error Resilience**: Continues operation even if task scheduling fails
     * - **Resource Management**: Optimizes database and external service usage
     * 
     * Business Benefits:
     * - **Complete Workflow**: Ensures all necessary post-creation steps are executed
     * - **Inventory Accuracy**: Maintains accurate seed inventory tracking
     * - **Automation Ready**: Prepares crops for automated agricultural management
     * - **Performance Optimized**: Handles both individual and bulk operations efficiently
     * - **Error Resilient**: Graceful handling of post-creation complications
     * 
     * Bulk Operation Handling:
     * - Detects bulk operation mode via Crop::isInBulkOperation()
     * - Skips inventory deduction and task scheduling for performance
     * - Allows bulk processing to handle these operations at scale
     * - Maintains data integrity while optimizing performance
     * 
     * @inventory_integration Coordinates with InventoryManagementService
     * @task_scheduling Uses CropTaskManagementService for agricultural automation
     * @error_resilience Continues operation despite individual component failures
     */
    public function handleCropCreated(Crop $crop): void
    {
        // Deduct seed from inventory if not in bulk operation mode
        if (!Crop::isInBulkOperation()) {
            $this->inventoryService->deductSeedForCrop($crop);
        }
        
        // Schedule stage transition tasks (skip during testing)
        if (config('app.env') !== 'testing' && !Crop::isInBulkOperation()) {
            try {
                $this->cropTaskService->scheduleAllStageTasks($crop);
            } catch (Exception $e) {
                Log::warning('Error scheduling crop tasks', [
                    'crop_id' => $crop->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Execute post-update workflow for modified crops with intelligent change detection.
     * 
     * Analyzes crop modifications and triggers appropriate follow-up operations,
     * particularly task rescheduling when stage changes occur. Provides environment
     * awareness and error resilience to ensure agricultural workflow consistency
     * after crop modifications.
     * 
     * @param Crop $crop The updated crop with potential stage or configuration changes
     * @return void Post-update operations completed based on detected changes
     * 
     * @post_update_workflow Orchestrates operations after crop modifications
     * @change_detection Intelligently identifies when rescheduling is needed
     * @task_rescheduling Updates agricultural task schedules for modified crops
     * @environment_aware Adjusts behavior for testing and production environments
     * 
     * Change Detection Logic:
     * - **Stage Changes**: Monitors current_stage_id modifications using Laravel's dirty tracking
     * - **Intelligent Response**: Only triggers rescheduling when necessary changes occur
     * - **Performance Optimization**: Avoids unnecessary task operations for irrelevant changes
     * - **Precision Targeting**: Focuses on changes that affect agricultural workflow timing
     * 
     * Task Rescheduling Process:
     * - **Complete Rebuild**: Regenerates entire task schedule for modified crop
     * - **Stage-Aware**: Adjusts scheduling based on new current stage position
     * - **Batch Coordination**: Maintains batch integrity during individual crop updates
     * - **Timing Accuracy**: Ensures task schedules reflect current crop configuration
     * 
     * Environment Considerations:
     * - **Testing Environment**: Disables task scheduling during automated testing
     * - **Production Optimization**: Full task rescheduling in production environment
     * - **Performance Balance**: Minimizes overhead while ensuring workflow accuracy
     * - **Isolation**: Prevents test interference with task scheduling systems
     * 
     * Error Handling and Resilience:
     * - **Graceful Degradation**: Continues operation if task scheduling fails
     * - **Comprehensive Logging**: Records rescheduling errors for troubleshooting
     * - **Context Preservation**: Includes crop ID and error details in logs
     * - **Non-Blocking**: Prevents task scheduling issues from blocking crop updates
     * 
     * Business Benefits:
     * - **Workflow Accuracy**: Maintains correct agricultural task scheduling after changes
     * - **Performance Optimized**: Only reschedules when necessary changes occur
     * - **Error Resilient**: Graceful handling of rescheduling complications
     * - **Production Ready**: Environment-aware behavior for testing and production
     * - **Data Consistency**: Ensures task schedules stay synchronized with crop state
     * 
     * Agricultural Context:
     * - **Stage Transitions**: Critical for maintaining proper agricultural timing
     * - **Automation Integrity**: Ensures automated systems reflect current crop status
     * - **Quality Control**: Maintains consistency between crop state and scheduled operations
     * - **Operational Efficiency**: Prevents outdated task schedules from causing confusion
     * 
     * @change_intelligence Uses Laravel dirty tracking for precise change detection
     * @task_coordination Integrates with CropTaskManagementService for rescheduling
     * @error_logging Comprehensive logging for agricultural workflow troubleshooting
     */
    public function handleCropUpdated(Crop $crop): void
    {
        // If the stage has changed, recalculate tasks
        if ($crop->isDirty('current_stage_id') && config('app.env') !== 'testing') {
            try {
                $this->cropTaskService->scheduleAllStageTasks($crop);
            } catch (Exception $e) {
                Log::warning('Error rescheduling crop tasks', [
                    'crop_id' => $crop->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}