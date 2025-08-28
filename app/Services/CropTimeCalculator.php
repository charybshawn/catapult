<?php

namespace App\Services;

use App\Models\Crop;
use Carbon\Carbon;

/**
 * Agricultural crop timing calculator for microgreens production lifecycle management.
 * 
 * Provides comprehensive time calculations and display formatting for crop growing
 * stages, aging, and transition timing. Essential for production scheduling, harvest
 * planning, and agricultural operation timing optimization.
 *
 * @business_domain Crop lifecycle timing and agricultural production scheduling
 * @agricultural_concept Tracks growing stage progression and timing for harvest optimization
 * @production_integration Links crop timing to delivery scheduling and resource planning
 * @stage_management Supports multi-stage growing process with precise timing calculations
 * 
 * @growing_stages_supported
 * - **Soaking:** Pre-germination seed preparation phase
 * - **Germination:** Initial sprouting and root development
 * - **Blackout:** Dark growing period for stem elongation
 * - **Light:** Final growing period with light exposure for green development
 * - **Harvested:** Completion of growing cycle
 * 
 * @calculation_types
 * - **Stage Age:** Time elapsed since current stage began
 * - **Total Age:** Complete time since crop started (soaking or germination)
 * - **Time to Next Stage:** Remaining time until expected stage transition
 * - **Stage Status:** On-track, overdue, or ready status indicators
 * 
 * @business_benefits
 * - **Harvest Planning:** Accurate timing for delivery schedule coordination
 * - **Resource Optimization:** Efficient use of growing space and labor
 * - **Quality Control:** Optimal harvest timing for product quality
 * - **Operational Efficiency:** Proactive stage management and alerts
 * 
 * @related_services CropPlanCalculatorService, CropValidationService, CropStageValidationService
 * @related_models Crop, Recipe, CropBatch, CropBatchListView
 * @filament_integration Used by crop management interfaces and production dashboards
 * 
 * @data_migration_context
 * Some methods deprecated in favor of CropBatchListView for performance optimization.
 * View-based calculations reduce real-time computation for dashboard displays.
 */
class CropTimeCalculator
{
    /**
     * Update all time-related calculated fields for a crop batch.
     * 
     * @deprecated Replaced by CropBatchListView for performance optimization
     * @migration_path Use getTimeToNextStageDisplay() for individual calculations or access via CropBatchListView
     * 
     * **Deprecation Rationale:**
     * - Time calculations moved to database view for dashboard performance
     * - CropBatchListView provides pre-calculated time fields
     * - Reduces real-time computation overhead for batch operations
     * - Maintains backward compatibility during transition period
     * 
     * **Migration Strategy:**
     * - Individual time calculations: Use specific methods (getTimeToNextStageDisplay, etc.)
     * - Batch operations: Query CropBatchListView for pre-calculated values
     * - Dashboard displays: Leverage view-based calculations for performance
     * 
     * @param Crop $crop Crop requiring time calculation updates (no-op in current implementation)
     * @return void Method maintained for backward compatibility but performs no operations
     */
    public function updateTimeCalculations(Crop $crop): void
    {
        // This method is deprecated as these fields are now in crop_batches_list_view
        // Keeping it empty to avoid breaking existing code
    }
    
    /**
     * Calculate time remaining until next agricultural stage transition.
     * 
     * Provides real-time calculation of remaining time until crop should transition
     * to next growing stage. Uses recipe-defined stage durations and current stage
     * start times for accurate agricultural timing.
     *
     * @param Crop $crop Crop requiring next stage transition timing
     * @return string Human-readable time display (e.g., "2d 4h", "Overdue", "Calculating...")
     * 
     * @agricultural_timing
     * **Stage Transition Logic:**
     * - Calculates expected transition time using recipe stage durations
     * - Compares against current time to determine remaining duration
     * - Provides "Overdue" status when transition time has passed
     * - Uses "Calculating..." when insufficient data available
     * 
     * **Recipe Integration:**
     * - Loads recipe relationship to access stage duration parameters
     * - Uses stage-specific durations (soaking_hours, germination_days, etc.)
     * - Handles harvested crops with no further transitions
     * 
     * @business_applications
     * **Production Scheduling:**
     * - Enables proactive stage transition planning
     * - Supports labor scheduling for stage management activities
     * - Facilitates harvest timing coordination with delivery schedules
     * 
     * **Quality Management:**
     * - Prevents over-aging of crops through timely transition alerts
     * - Ensures optimal growing conditions at each stage
     * - Supports consistent product quality through timing precision
     * 
     * @display_formatting
     * **Time Format Examples:**
     * - "45m" - 45 minutes remaining
     * - "3h 15m" - 3 hours 15 minutes remaining
     * - "2d 8h" - 2 days 8 hours remaining
     * - "Overdue" - Transition time has passed
     * - "Calculating..." - Missing data for calculation
     * 
     * @error_handling
     * **Missing Data Scenarios:**
     * - No recipe loaded: Returns "Calculating..." after attempting lazy load
     * - Harvested crops: Returns "Calculating..." (no further transitions)
     * - Invalid stage data: Graceful degradation with placeholder text
     * 
     * @performance_considerations
     * - Lazy loads recipe relationship only if not already loaded
     * - Single-use calculation for immediate display needs
     * - Consider CropBatchListView for batch operations or dashboard displays
     * 
     * @usage_scenarios
     * ```php
     * // Individual crop stage timing
     * $timeRemaining = $calculator->getTimeToNextStageDisplay($crop);
     * 
     * // Real-time dashboard updates
     * foreach ($activeCrops as $crop) {
     *     $crop->time_display = $calculator->getTimeToNextStageDisplay($crop);
     * }
     * ```
     */
    public function getTimeToNextStageDisplay(Crop $crop): string
    {
        // Ensure recipe is loaded to avoid lazy loading violations
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (!$crop->recipe || $crop->current_stage === 'harvested') {
            return 'Calculating...';
        }

        $expectedTransitionTime = $this->getExpectedStageTransitionTime($crop);
        
        if (!$expectedTransitionTime) {
            return 'Calculating...';
        }

        // Calculate minutes FROM now TO expected transition time (can be negative if overdue)
        $minutesRemaining = Carbon::now()->diffInMinutes($expectedTransitionTime, false);
        
        if ($minutesRemaining <= 0) {
            return 'Overdue';
        }
        
        return $this->formatTimeDisplay((int) $minutesRemaining);
    }
    
    /**
     * Calculate current growing stage age for agricultural monitoring.
     * 
     * Determines how long the crop has been in its current growing stage.
     * Essential for tracking stage progression and identifying crops ready
     * for transition or requiring attention.
     *
     * @param Crop $crop Crop requiring current stage age calculation
     * @return string Human-readable stage age display (e.g., "1d 6h", "45m")
     * 
     * @agricultural_monitoring
     * **Stage Age Significance:**
     * - Shows elapsed time since current stage began
     * - Helps identify crops approaching stage transition timing
     * - Supports quality control through stage duration tracking
     * - Enables comparison against recipe expected durations
     * 
     * **Growing Stage Context:**
     * - **Soaking Age:** Time since soaking began (hours typically)
     * - **Germination Age:** Time since germination started (days typically)
     * - **Blackout Age:** Time in dark growing phase (days)
     * - **Light Age:** Time under light exposure (days)
     * 
     * @business_applications
     * **Quality Control:**
     * - Monitor crops for optimal stage transition timing
     * - Identify crops that may be over-aging in current stage
     * - Support consistent growing practices across batches
     * 
     * **Production Efficiency:**
     * - Prioritize crops approaching transition readiness
     * - Optimize labor allocation for stage management
     * - Coordinate harvest timing with delivery requirements
     * 
     * @calculation_method
     * Uses calculateStageAge() to determine minutes elapsed since stage start,
     * then formats using formatTimeDisplay() for human-readable output.
     * 
     * @display_examples
     * - "30m" - 30 minutes in current stage
     * - "4h 15m" - 4 hours 15 minutes in current stage
     * - "3d 2h" - 3 days 2 hours in current stage
     * 
     * @integration_usage
     * Commonly used in crop management interfaces, production dashboards,
     * and stage transition decision support systems.
     */
    public function getStageAgeDisplay(Crop $crop): string
    {
        $minutes = $this->calculateStageAge($crop);
        return $this->formatTimeDisplay($minutes);
    }
    
    /**
     * Calculate complete crop lifecycle age for maturity assessment.
     * 
     * Determines total time elapsed since crop began growing process.
     * Critical for harvest timing, maturity assessment, and delivery
     * schedule coordination in agricultural operations.
     *
     * @param Crop $crop Crop requiring total lifecycle age calculation
     * @return string Human-readable total age display (e.g., "7d 2h", "10d")
     * 
     * @agricultural_maturity
     * **Lifecycle Timing:**
     * - Measures complete growing cycle from start to current time
     * - Supports harvest timing decisions based on total maturity
     * - Enables comparison against recipe days_to_maturity targets
     * - Critical for delivery schedule coordination
     * 
     * **Growing Start Points:**
     * - **Soaking Crops:** Measures from soaking_at timestamp
     * - **Regular Crops:** Measures from germination_at timestamp
     * - Handles different crop types with appropriate lifecycle start
     * 
     * @harvest_planning
     * **Maturity Assessment:**
     * - Compare against recipe days_to_maturity for harvest readiness
     * - Identify crops approaching optimal harvest window
     * - Support delivery date coordination with crop maturity
     * 
     * **Quality Optimization:**
     * - Prevent over-aging beyond optimal harvest quality
     * - Ensure consistent product maturity across batches
     * - Support premium product timing for special orders
     * 
     * @calculation_integration
     * Uses calculateTotalAge() for minute-level precision, then formats
     * with formatTimeDisplay() for operational readability.
     * 
     * @business_context
     * **Customer Delivery:**
     * - Ensures crops reach optimal maturity for delivery dates
     * - Supports harvest planning for specific delivery requirements
     * - Enables proactive communication about order readiness
     * 
     * **Operational Efficiency:**
     * - Prioritizes harvest activities based on crop maturity
     * - Optimizes growing space utilization through timely harvesting
     * - Supports batch coordination for efficient operations
     * 
     * @usage_applications
     * ```php
     * // Harvest readiness assessment
     * $totalAge = $calculator->getTotalAgeDisplay($crop);
     * 
     * // Batch maturity comparison
     * $crops->each(function($crop) use ($calculator) {
     *     $crop->maturity_display = $calculator->getTotalAgeDisplay($crop);
     * });
     * ```
     */
    public function getTotalAgeDisplay(Crop $crop): string
    {
        $minutes = $this->calculateTotalAge($crop);
        return $this->formatTimeDisplay($minutes);
    }

    /**
     * Calculate precise minutes remaining until agricultural stage transition.
     * 
     * Provides numeric minute count until crop should transition to next growing
     * stage. Used for programmatic timing decisions and alert thresholds in
     * agricultural production management systems.
     *
     * @param Crop $crop Crop requiring transition timing calculation
     * @return int|null Minutes until next stage transition, null if unable to calculate
     * 
     * @precision_timing
     * **Minute-Level Accuracy:**
     * - Returns exact minutes for precise timing decisions
     * - Enables automated alert systems with specific thresholds
     * - Supports integration with scheduling and notification systems
     * - Provides programmatic interface vs human-readable display
     * 
     * **Calculation Logic:**
     * - Uses getExpectedStageTransitionTime() for target transition timing
     * - Calculates difference between current time and expected transition
     * - Returns positive minutes for future transitions, 0 for overdue crops
     * - Handles edge cases with null return for invalid scenarios
     * 
     * @business_automation
     * **Alert Systems:**
     * - Trigger notifications when minutes remaining hits thresholds
     * - Support automated stage transition reminders
     * - Enable proactive harvest planning alerts
     * 
     * **Integration Applications:**
     * - API responses requiring precise timing data
     * - Dashboard widgets with countdown timers
     * - Automated workflow triggers for stage management
     * 
     * @agricultural_decision_support
     * **Production Planning:**
     * - Calculate labor scheduling windows for stage transitions
     * - Determine optimal batch processing timing
     * - Coordinate harvest activities with delivery schedules
     * 
     * **Quality Management:**
     * - Prevent over-aging through precise timing alerts
     * - Ensure consistent stage transition practices
     * - Support optimal product quality through timing precision
     * 
     * @error_handling
     * **Null Return Scenarios:**
     * - Missing recipe data for stage duration calculations
     * - Harvested crops with no further transitions
     * - Invalid stage configurations or missing timestamps
     * 
     * @usage_patterns
     * ```php
     * // Alert system integration
     * if ($calculator->calculateTimeToNextStage($crop) <= 60) {
     *     // Send 1-hour warning notification
     * }
     * 
     * // Batch processing prioritization
     * $crops->sortBy(function($crop) use ($calculator) {
     *     return $calculator->calculateTimeToNextStage($crop) ?? PHP_INT_MAX;
     * });
     * ```
     * 
     * @relationship_with_display
     * Provides raw data for getTimeToNextStageDisplay() which formats this
     * numeric value into human-readable time displays.
     */
    public function calculateTimeToNextStage(Crop $crop): ?int
    {
        // Ensure recipe is loaded to avoid lazy loading violations
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (!$crop->recipe || $crop->current_stage === 'harvested') {
            return null;
        }

        $expectedTransitionTime = $this->getExpectedStageTransitionTime($crop);
        
        if (!$expectedTransitionTime) {
            return null;
        }

        // Calculate minutes FROM now TO expected transition time
        $minutesRemaining = Carbon::now()->diffInMinutes($expectedTransitionTime, false);
        
        return max(0, (int) $minutesRemaining);
    }

    /**
     * Calculate precise stage age in minutes for agricultural timing analysis.
     * 
     * Provides exact minute count since crop entered its current growing stage.
     * Essential for stage duration analysis, transition timing decisions, and
     * agricultural production quality control.
     *
     * @param Crop $crop Crop requiring current stage age analysis
     * @return int|null Minutes elapsed since current stage began, null if unable to calculate
     * 
     * @precision_tracking
     * **Minute-Level Accuracy:**
     * - Exact elapsed time for detailed agricultural analysis
     * - Supports precise timing comparisons against recipe expectations
     * - Enables statistical analysis of stage duration performance
     * - Provides raw data for various display and calculation needs
     * 
     * **Stage Start Detection:**
     * - Uses getCurrentStageStartTime() to identify stage beginning
     * - Calculates positive minutes from stage start to current time
     * - Handles all growing stages (soaking, germination, blackout, light)
     * 
     * @agricultural_analysis
     * **Performance Monitoring:**
     * - Compare actual stage durations against recipe expectations
     * - Identify crops deviating from standard growing timelines
     * - Support continuous improvement of growing practices
     * 
     * **Quality Control:**
     * - Monitor stage aging for optimal transition timing
     * - Prevent over-aging that could affect product quality
     * - Ensure consistent growing practices across production batches
     * 
     * @business_applications
     * **Stage Management:**
     * - Prioritize crops approaching optimal transition timing
     * - Schedule labor for stage management activities
     * - Coordinate growing operations with resource availability
     * 
     * **Data Analytics:**
     * - Statistical analysis of stage duration patterns
     * - Recipe optimization based on actual growing performance
     * - Seasonal growing condition impact assessment
     * 
     * @calculation_methodology
     * Uses Carbon date difference calculation from stage start timestamp
     * to current time, providing positive minute values representing elapsed time.
     * 
     * @error_handling
     * **Null Return Scenarios:**
     * - Missing stage start timestamp for current stage
     * - Invalid crop stage configurations
     * - Database inconsistencies in stage tracking
     * 
     * @integration_usage
     * ```php
     * // Stage performance analysis
     * $stageAge = $calculator->calculateStageAge($crop);
     * $expectedMinutes = $crop->recipe->germination_days * 24 * 60;
     * $performanceRatio = $stageAge / $expectedMinutes;
     * 
     * // Quality control thresholds
     * if ($calculator->calculateStageAge($crop) > $maxStageMinutes) {
     *     // Flag crop for immediate attention
     * }
     * ```
     * 
     * @relationship_with_display
     * Provides raw data for getStageAgeDisplay() which formats this numeric
     * value into human-readable time displays for operational interfaces.
     */
    public function calculateStageAge(Crop $crop): ?int
    {
        $stageStartTime = $this->getCurrentStageStartTime($crop);
        
        if (!$stageStartTime) {
            return null;
        }

        // Calculate minutes FROM stage start TO now (positive value)
        return (int) $stageStartTime->diffInMinutes(Carbon::now());
    }

    /**
     * Calculate complete crop lifecycle duration in minutes for maturity analysis.
     * 
     * Provides precise total time elapsed since crop began growing process.
     * Critical for harvest timing decisions, maturity assessment, and delivery
     * coordination in agricultural production management.
     *
     * @param Crop $crop Crop requiring complete lifecycle duration analysis
     * @return int|null Total minutes since crop lifecycle began, null if unable to calculate
     * 
     * @lifecycle_measurement
     * **Complete Growing Cycle:**
     * - Measures from initial crop start (soaking or germination) to current time
     * - Provides total maturity assessment data for harvest decisions
     * - Supports delivery timing coordination with crop readiness
     * - Enables comparison against recipe days_to_maturity targets
     * 
     * **Crop Type Handling:**
     * - **Soaking Crops:** Measures from soaking_at timestamp (complete lifecycle)
     * - **Regular Crops:** Measures from germination_at timestamp (growing cycle)
     * - Accommodates different crop starting points based on variety requirements
     * 
     * @harvest_decision_support
     * **Maturity Assessment:**
     * - Compare against recipe maturity targets for harvest readiness
     * - Identify crops approaching optimal harvest windows
     * - Support quality optimization through precise timing
     * 
     * **Customer Delivery:**
     * - Coordinate harvest timing with delivery schedule requirements
     * - Ensure crops reach optimal maturity for customer orders
     * - Enable proactive communication about order readiness
     * 
     * @agricultural_optimization
     * **Resource Management:**
     * - Prioritize harvest activities based on crop maturity
     * - Optimize growing space through timely harvest and replanting
     * - Support batch coordination for operational efficiency
     * 
     * **Quality Control:**
     * - Prevent over-aging beyond optimal product quality windows
     * - Maintain consistent product standards across production batches
     * - Support premium timing for special customer requirements
     * 
     * @precision_applications
     * **Data Analytics:**
     * - Statistical analysis of complete growing cycle performance
     * - Recipe optimization based on actual maturity timing
     * - Seasonal variation impact on growing duration
     * 
     * **Production Planning:**
     * - Accurate forecasting of harvest timing for planning
     * - Resource allocation based on crop maturity patterns
     * - Integration with ordering and delivery systems
     * 
     * @calculation_logic
     * **Timestamp Selection:**
     * - Prioritizes soaking_at for crops requiring pre-germination soaking
     * - Falls back to germination_at for standard growing cycle measurement
     * - Uses Carbon date calculation for precise minute-level accuracy
     * 
     * @error_handling
     * **Null Return Scenarios:**
     * - Missing both soaking_at and germination_at timestamps
     * - Invalid timestamp formats or database inconsistencies
     * - Crops not yet started in growing process
     * 
     * @business_integration
     * ```php
     * // Harvest readiness assessment
     * $totalAge = $calculator->calculateTotalAge($crop);
     * $maturityTarget = $crop->recipe->days_to_maturity * 24 * 60;
     * $maturityPercentage = ($totalAge / $maturityTarget) * 100;
     * 
     * // Delivery coordination
     * if ($totalAge >= $harvestThreshold) {
     *     // Schedule for harvest and delivery preparation
     * }
     * ```
     * 
     * @relationship_with_display
     * Provides raw data for getTotalAgeDisplay() which formats this numeric
     * value into human-readable time displays for operational interfaces.
     */
    public function calculateTotalAge(Crop $crop): ?int
    {
        // For soaking crops, calculate from soaking_at
        if ($crop->requires_soaking && $crop->soaking_at) {
            return (int) Carbon::parse($crop->soaking_at)->diffInMinutes(Carbon::now());
        }
        
        // For regular crops, calculate from germination_at
        if ($crop->germination_at) {
            return (int) Carbon::parse($crop->germination_at)->diffInMinutes(Carbon::now());
        }
        
        return null;
    }

    /**
     * Generate agricultural stage progress status assessment.
     * 
     * Analyzes current stage age against recipe expectations to provide
     * operational status indicators for crop management decisions.
     * Essential for production scheduling and quality control.
     *
     * @param Crop $crop Crop requiring stage progress assessment
     * @return string Status indicator ("On Track", "Due Soon", "Overdue", "No recipe configured")
     * 
     * @agricultural_status_logic
     * **Progress Assessment Thresholds:**
     * - **"On Track" (<90%):** Stage progressing normally within expected timeline
     * - **"Due Soon" (90-110%):** Approaching transition window, prepare for next stage
     * - **"Overdue" (>110%):** Past expected duration, immediate attention required
     * - **"No recipe configured":** Missing recipe data prevents assessment
     * 
     * **Calculation Method:**
     * ```php
     * percent_complete = (actual_stage_minutes / expected_stage_minutes) × 100
     * ```
     * 
     * @operational_guidance
     * **"On Track" Status:**
     * - Continue normal growing practices
     * - Monitor regularly but no immediate action needed
     * - Stage progression within acceptable parameters
     * 
     * **"Due Soon" Status:**
     * - Prepare for stage transition activities
     * - Schedule labor for upcoming stage management
     * - Review growing conditions for optimal transition
     * 
     * **"Overdue" Status:**
     * - Immediate evaluation required
     * - Check for growing condition issues
     * - Consider expedited stage transition
     * - Assess potential quality impact
     * 
     * @business_applications
     * **Production Management:**
     * - Prioritize attention based on status urgency
     * - Schedule stage management activities proactively
     * - Optimize labor allocation for crop care
     * 
     * **Quality Control:**
     * - Identify crops at risk of over-aging
     * - Maintain consistent stage management practices
     * - Support optimal product quality through timely interventions
     * 
     * @dashboard_integration
     * Used extensively in crop management interfaces to provide at-a-glance
     * status assessment for production staff decision making.
     */
    public function getStageAgeStatus(Crop $crop): string
    {
        if (!$crop->recipe) {
            return 'No recipe configured';
        }

        $stageAge = $this->calculateStageAge($crop);
        $expectedDuration = $this->getExpectedStageDuration($crop);

        if (!$stageAge || !$expectedDuration) {
            return 'Calculating...';
        }

        $expectedMinutes = $expectedDuration * 24 * 60; // Convert days to minutes
        $percentComplete = ($stageAge / $expectedMinutes) * 100;

        if ($percentComplete < 90) {
            return 'On Track';
        } elseif ($percentComplete < 110) {
            return 'Due Soon';
        } else {
            return 'Overdue';
        }
    }

    /**
     * Generate crop maturity status assessment for harvest planning.
     * 
     * Analyzes total crop age against recipe maturity targets to provide
     * harvest readiness indicators. Critical for delivery coordination
     * and optimal harvest timing decisions.
     *
     * @param Crop $crop Crop requiring maturity status assessment
     * @return string Maturity status ("Growing", "Nearly Ready", "Ready to Harvest", "Past Due", "Not started", "No maturity target")
     * 
     * @maturity_assessment_thresholds
     * **Growth Status Categories:**
     * - **"Growing" (<80%):** Early in growing cycle, not yet approaching harvest
     * - **"Nearly Ready" (80-95%):** Approaching harvest window, prepare for harvest
     * - **"Ready to Harvest" (95-105%):** Optimal harvest window for quality and delivery
     * - **"Past Due" (>105%):** Beyond optimal harvest, quality may be declining
     * - **"Not started":** Missing growth start timestamp
     * - **"No maturity target":** Missing recipe maturity parameters
     * 
     * **Calculation Formula:**
     * ```php
     * maturity_percentage = (total_age_minutes / (days_to_maturity × 24 × 60)) × 100
     * ```
     * 
     * @harvest_decision_guidance
     * **"Growing" Status:**
     * - Continue normal growing practices
     * - Monitor progress toward maturity target
     * - Plan ahead for upcoming harvest activities
     * 
     * **"Nearly Ready" Status:**
     * - Prepare harvest equipment and processing area
     * - Coordinate with delivery schedule requirements
     * - Monitor closely for optimal harvest timing
     * 
     * **"Ready to Harvest" Status:**
     * - Optimal harvest window for quality and freshness
     * - Schedule immediate harvest activities
     * - Coordinate with customer delivery requirements
     * 
     * **"Past Due" Status:**
     * - Immediate harvest required to prevent quality loss
     * - Assess product quality before customer delivery
     * - Consider discounted pricing if quality affected
     * 
     * @business_coordination
     * **Customer Delivery:**
     * - Ensures crops reach optimal maturity for delivery dates
     * - Supports proactive communication about order readiness
     * - Enables delivery schedule adjustments when needed
     * 
     * **Operational Efficiency:**
     * - Prioritizes harvest activities based on maturity urgency
     * - Optimizes growing space utilization through timely harvesting
     * - Supports batch coordination for efficient operations
     * 
     * @quality_management
     * **Product Excellence:**
     * - Maintains consistent harvest timing for product quality
     * - Prevents over-aging that affects taste, texture, and appearance
     * - Supports premium product positioning through optimal timing
     * 
     * @integration_usage
     * Extensively used in harvest planning dashboards, crop management
     * interfaces, and automated harvest scheduling systems.
     */
    public function getTotalAgeStatus(Crop $crop): string
    {
        if (!$crop->recipe || !$crop->recipe->days_to_maturity) {
            return 'No maturity target';
        }

        $totalAge = $this->calculateTotalAge($crop);
        
        if (!$totalAge) {
            return 'Not started';
        }

        $expectedMinutes = $crop->recipe->days_to_maturity * 24 * 60;
        $percentComplete = ($totalAge / $expectedMinutes) * 100;

        if ($percentComplete < 80) {
            return 'Growing';
        } elseif ($percentComplete < 95) {
            return 'Nearly Ready';
        } elseif ($percentComplete < 105) {
            return 'Ready to Harvest';
        } else {
            return 'Past Due';
        }
    }

    /**
     * Format precise time duration into human-readable agricultural display.
     * 
     * Converts minute-based time calculations into intuitive time displays
     * for agricultural operations staff. Provides hierarchical formatting
     * that scales from minutes to weeks for comprehensive time representation.
     *
     * @param int|null $minutes Duration in minutes to format, null returns null
     * @return string|null Formatted time display or null if input is null
     * 
     * @formatting_hierarchy
     * **Display Scale Progression:**
     * - **Minutes (0-59):** "45m" - For short durations and precise timing
     * - **Hours (60-1439):** "3h 15m" or "4h" - For daily operation timing
     * - **Days (1440-10079):** "2d 8h" or "5d" - For growing stage durations
     * - **Weeks (10080+):** "3w 2d" or "4w" - For long-term planning cycles
     * 
     * **Format Examples:**
     * ```
     * 30 minutes → "30m"
     * 125 minutes → "2h 5m"
     * 240 minutes → "4h"
     * 1500 minutes → "1d 1h"
     * 2880 minutes → "2d"
     * 11520 minutes → "1w 1d"
     * 20160 minutes → "2w"
     * ```
     * 
     * @agricultural_context
     * **Operational Relevance:**
     * - Minutes: Stage transitions, soaking durations
     * - Hours: Daily growing activities, short-term monitoring
     * - Days: Standard growing stage durations, harvest planning
     * - Weeks: Production cycles, long-term crop planning
     * 
     * **Display Optimization:**
     * - Omits zero values for cleaner display ("2h" vs "2h 0m")
     * - Uses abbreviated units for dashboard space efficiency
     * - Provides intuitive agricultural timing communication
     * 
     * @business_communication
     * **Staff Interface:**
     * - Clear time communication for production staff
     * - Consistent formatting across all agricultural interfaces
     * - Intuitive duration display for operational decision making
     * 
     * **Planning Documents:**
     * - Professional time display for planning reports
     * - Scalable format accommodates various planning horizons
     * - Standardized time representation across system
     * 
     * @calculation_logic
     * **Hierarchical Conversion:**
     * 1. Calculate largest time unit applicable
     * 2. Determine remainder for next smaller unit
     * 3. Format with appropriate abbreviations
     * 4. Omit zero remainder values for clarity
     * 
     * @usage_integration
     * Called by all display-oriented time calculation methods to provide
     * consistent time formatting across agricultural interfaces:
     * - getTimeToNextStageDisplay()
     * - getStageAgeDisplay()
     * - getTotalAgeDisplay()
     * 
     * @null_handling
     * Gracefully handles null input by returning null, maintaining consistency
     * with calculation methods that may not have sufficient data.
     */
    public function formatTimeDisplay(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        if ($minutes < 60) {
            return "{$minutes}m";
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
        }

        $days = intval($hours / 24);
        $remainingHours = $hours % 24;

        if ($days < 7) {
            return $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days}d";
        }

        $weeks = intval($days / 7);
        $remainingDays = $days % 7;

        return $remainingDays > 0 ? "{$weeks}w {$remainingDays}d" : "{$weeks}w";
    }

    /**
     * Get when the next stage transition is expected
     */
    private function getExpectedStageTransitionTime(Crop $crop): ?Carbon
    {
        $stageStartTime = $this->getCurrentStageStartTime($crop);
        $stageDuration = $this->getExpectedStageDuration($crop);

        if (!$stageStartTime || !$stageDuration) {
            return null;
        }

        // Use copy() to avoid modifying the original Carbon instance
        return $stageStartTime->copy()->addDays($stageDuration);
    }

    /**
     * Get when the current stage started
     */
    private function getCurrentStageStartTime(Crop $crop): ?Carbon
    {
        $timestamp = match ($crop->current_stage) {
            'soaking' => $crop->soaking_at,
            'germination' => $crop->germination_at,
            'blackout' => $crop->blackout_at,
            'light' => $crop->light_at,
            'harvested' => $crop->harvested_at,
            default => null
        };

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * Get the expected duration for the current stage in days
     */
    private function getExpectedStageDuration(Crop $crop): ?float
    {
        // Ensure recipe is loaded to avoid lazy loading violations
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (!$crop->recipe) {
            return null;
        }

        return match ($crop->current_stage) {
            'soaking' => $crop->recipe->seed_soak_hours ? ($crop->recipe->seed_soak_hours / 24) : null,
            'germination' => $crop->recipe->germination_days,
            'blackout' => $crop->recipe->blackout_days,  
            'light' => $crop->recipe->light_days,
            'harvested' => null, // No duration for final stage
            default => null
        };
    }
}