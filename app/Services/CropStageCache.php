<?php

namespace App\Services;

use App\Models\CropStage;
use Illuminate\Support\Collection;

/**
 * Agricultural crop stage caching service for microgreens production optimization.
 * 
 * Provides high-performance cached access to crop stage data, eliminating
 * repetitive database queries during agricultural workflow operations.
 * Essential for time-sensitive crop management operations where stage
 * transitions and validations occur frequently throughout production cycles.
 * 
 * @business_domain Agricultural crop lifecycle management and production optimization
 * @agricultural_workflow Optimizes stage transition performance during crop growing cycles
 * @performance_focus Reduces database load for frequently accessed agricultural reference data
 * 
 * @agricultural_stages
 * - Soaking: Initial seed hydration phase before planting
 * - Germinating: Seed sprouting and initial growth phase
 * - Growing: Active microgreens development phase
 * - Harvest Ready: Mature crop ready for harvesting
 * - Harvested: Completed crop lifecycle stage
 * 
 * @caching_strategy
 * - Static class variables for request-lifetime persistence
 * - Multi-index caching (by ID and by code) for flexible access patterns
 * - Lazy loading with automatic cache building on first access
 * - Cache clearing capability for testing and data refresh scenarios
 * 
 * @business_benefits
 * - Faster agricultural workflow processing
 * - Reduced database load during peak production operations
 * - Consistent stage data access across agricultural services
 * - Improved user experience in crop management interfaces
 * 
 * @agricultural_use_cases
 * - Stage transition validation during crop lifecycle management
 * - Agricultural workflow UI rendering (stage dropdowns, progress indicators)
 * - Batch crop processing operations requiring stage lookups
 * - Production reporting and analytics requiring stage categorization
 * 
 * @example
 * // Get all available crop stages
 * $stages = CropStageCache::all();
 * 
 * // Find specific stage for validation
 * $germinatingStage = CropStageCache::findByCode('germinating');
 * 
 * // Navigate agricultural workflow
 * $nextStage = CropStageCache::getNextStage($currentStage);
 * 
 * @performance_characteristics
 * - First access: Single database query loads all stages
 * - Subsequent access: In-memory cache retrieval (microsecond response)
 * - Memory efficient: Stages loaded only once per request lifecycle
 * - Multi-index support: O(1) lookup by ID or code
 * 
 * @see CropStage For agricultural stage model and database schema
 * @see CropStageValidationService For stage transition business rules
 * @see CropStageTimelineService For stage progression workflows
 */
class CropStageCache
{
    /**
     * Request-lifetime cache of all agricultural crop stages.
     * 
     * @var Collection<CropStage>|null Complete collection of crop stages from database
     */
    private static ?Collection $stages = null;
    
    /**
     * Fast lookup cache indexed by stage code for agricultural workflow efficiency.
     * 
     * @var array<string, CropStage>|null Stage code to CropStage object mapping
     */
    private static ?array $stagesByCode = null;
    
    /**
     * Fast lookup cache indexed by stage ID for database relationship performance.
     * 
     * @var array<int, CropStage>|null Stage ID to CropStage object mapping
     */
    private static ?array $stagesById = null;
    
    /**
     * Retrieve all agricultural crop stages with request-lifetime caching.
     * 
     * Provides complete collection of crop stages for agricultural workflow
     * operations, using efficient caching to minimize database queries
     * during crop management operations. Initial call loads data from
     * database and builds lookup indices for optimal performance.
     * 
     * @business_purpose Complete agricultural stage reference for production workflows
     * @performance_optimization Single database query per request with memory caching
     * @agricultural_coverage All stages from seed soaking through final harvest
     * 
     * @return Collection<CropStage> Complete collection of agricultural crop stages
     * 
     * @caching_behavior
     * - First call: Loads from database and builds index caches
     * - Subsequent calls: Returns cached collection (microsecond response)
     * - Cache persists for entire request lifecycle
     * - Automatic index building for ID and code lookups
     * 
     * @agricultural_includes
     * - Active and inactive stages for complete agricultural reference
     * - Stage progression order (sort_order) for workflow navigation
     * - Stage metadata (names, descriptions, colors) for UI rendering
     * 
     * @example
     * // Get all stages for dropdown population
     * $allStages = CropStageCache::all();
     * 
     * // Filter for active agricultural workflow stages
     * $activeStages = CropStageCache::all()->where('is_active', true);
     * 
     * // Build stage progression timeline
     * $timeline = CropStageCache::all()->sortBy('sort_order');
     */
    public static function all(): Collection
    {
        if (self::$stages === null) {
            self::$stages = CropStage::all();
            self::buildCaches();
        }
        
        return self::$stages;
    }
    
    /**
     * Find agricultural crop stage by database ID with caching optimization.
     * 
     * Provides O(1) lookup performance for stage retrieval by ID, essential
     * for database relationship resolution and agricultural workflow operations.
     * Automatically initializes cache indices if not already built.
     * 
     * @business_purpose Fast stage resolution for database relationships and crop management
     * @performance_optimization O(1) array lookup after initial cache building
     * @agricultural_context Stage ID lookups common in crop lifecycle operations
     * 
     * @param mixed $id Database primary key of the agricultural crop stage
     * @return CropStage|null Matched crop stage or null if not found
     * 
     * @caching_behavior
     * - Lazy cache initialization on first access
     * - O(1) array lookup performance after initialization
     * - Graceful null handling for invalid IDs
     * 
     * @agricultural_use_cases
     * - Crop model relationship resolution (crop->stage)
     * - Agricultural workflow validation by stage ID
     * - Database query result processing with stage context
     * - API response building with stage information
     * 
     * @example
     * // Resolve stage from crop relationship
     * $stage = CropStageCache::find($crop->crop_stage_id);
     * 
     * // Validate stage exists before workflow operation
     * if ($stage = CropStageCache::find($stageId)) {
     *     // Proceed with agricultural workflow
     * }
     */
    public static function find($id): ?CropStage
    {
        if (self::$stagesById === null) {
            self::all(); // This will build the caches
        }
        
        return self::$stagesById[$id] ?? null;
    }
    
    /**
     * Find agricultural crop stage by workflow code with caching optimization.
     * 
     * Provides O(1) lookup performance for stage retrieval by code, essential
     * for agricultural workflow logic and stage transition validation. Uses
     * human-readable codes like 'soaking', 'germinating' for business logic.
     * 
     * @business_purpose Agricultural workflow logic using readable stage codes
     * @performance_optimization O(1) array lookup for frequent workflow operations
     * @agricultural_semantics Human-readable codes align with production terminology
     * 
     * @param string $code Agricultural workflow stage code (e.g., 'soaking', 'germinating')
     * @return CropStage|null Matched crop stage or null if code not found
     * 
     * @caching_behavior
     * - Lazy cache initialization on first access
     * - O(1) array lookup performance after initialization
     * - Graceful null handling for invalid codes
     * 
     * @agricultural_codes
     * - 'soaking': Initial seed hydration phase
     * - 'germinating': Seed sprouting and emergence
     * - 'growing': Active microgreens development
     * - 'harvest_ready': Mature crop ready for harvest
     * - 'harvested': Completed agricultural lifecycle
     * 
     * @agricultural_use_cases
     * - Workflow transition validation (can move from 'soaking' to 'germinating')
     * - Business logic implementation using semantic stage names
     * - Agricultural rule enforcement based on current stage
     * - Production reporting using stage code categorization
     * 
     * @example
     * // Validate agricultural workflow transition
     * $currentStage = CropStageCache::findByCode('soaking');
     * $nextStage = CropStageCache::findByCode('germinating');
     * 
     * // Business logic using stage codes
     * if ($crop->stage->code === 'harvest_ready') {
     *     // Enable harvest operations
     * }
     * 
     * // Agricultural validation
     * $germinatingStage = CropStageCache::findByCode('germinating');
     * if ($germinatingStage && $crop->canTransitionTo($germinatingStage)) {
     *     // Proceed with stage transition
     * }
     */
    public static function findByCode(string $code): ?CropStage
    {
        if (self::$stagesByCode === null) {
            self::all(); // This will build the caches
        }
        
        return self::$stagesByCode[$code] ?? null;
    }
    
    /**
     * Navigate to next agricultural stage in production workflow sequence.
     * 
     * Determines the subsequent crop stage in the agricultural production
     * lifecycle, respecting stage ordering and active status. Essential for
     * automated stage progression and workflow transition validation in
     * microgreens production cycles.
     * 
     * @business_purpose Agricultural workflow progression and automated stage transitions
     * @agricultural_sequence Respects natural crop development progression order
     * @workflow_validation Ensures only valid forward transitions in production cycle
     * 
     * @param CropStage $currentStage Current agricultural stage in crop lifecycle
     * @return CropStage|null Next valid stage in sequence or null if at final stage
     * 
     * @agricultural_progression
     * - Soaking → Germinating → Growing → Harvest Ready → Harvested
     * - Skips inactive stages to maintain agricultural workflow integrity
     * - Respects sort_order for proper agricultural sequence
     * 
     * @business_rules
     * - Only returns active stages available for agricultural operations
     * - Uses sort_order to maintain proper crop development sequence
     * - Returns null when crop has reached final agricultural stage
     * - Cached collection provides optimal performance for frequent transitions
     * 
     * @agricultural_use_cases
     * - Automated stage advancement in crop management systems
     * - Workflow validation before allowing manual stage transitions
     * - UI state management for stage progression interfaces
     * - Business rule enforcement in agricultural operations
     * 
     * @example
     * // Advance crop through agricultural workflow
     * $soakingStage = CropStageCache::findByCode('soaking');
     * $nextStage = CropStageCache::getNextStage($soakingStage);
     * // $nextStage would be 'germinating' stage
     * 
     * // Validate transition availability
     * if ($nextStage = CropStageCache::getNextStage($crop->stage)) {
     *     // Stage advancement available
     *     $crop->transitionTo($nextStage);
     * } else {
     *     // Crop has reached final agricultural stage
     * }
     * 
     * // Build workflow progression UI
     * $currentStage = $crop->stage;
     * $nextStage = CropStageCache::getNextStage($currentStage);
     * if ($nextStage) {
     *     // Show "Advance to {$nextStage->name}" button
     * }
     */
    public static function getNextStage(CropStage $currentStage): ?CropStage
    {
        return self::all()
            ->where('sort_order', '>', $currentStage->sort_order)
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->first();
    }
    
    /**
     * Navigate to previous agricultural stage for workflow correction.
     * 
     * Determines the preceding crop stage in the agricultural production
     * sequence, enabling workflow corrections and stage rollback operations.
     * Useful for error correction and agricultural process adjustments
     * when stage advancement was premature or incorrect.
     * 
     * @business_purpose Agricultural workflow correction and stage rollback capability
     * @agricultural_sequence Reverse navigation through crop development stages
     * @error_correction Enables correction of premature stage advancement
     * 
     * @param CropStage $currentStage Current agricultural stage needing rollback
     * @return CropStage|null Previous valid stage in sequence or null if at initial stage
     * 
     * @agricultural_rollback
     * - Harvested → Harvest Ready → Growing → Germinating → Soaking
     * - Skips inactive stages to maintain agricultural workflow integrity
     * - Respects sort_order for proper reverse agricultural sequence
     * 
     * @business_rules
     * - Only returns active stages available for agricultural operations
     * - Uses descending sort_order for reverse crop development sequence
     * - Returns null when crop is at initial agricultural stage
     * - Cached collection provides optimal performance for rollback operations
     * 
     * @agricultural_use_cases
     * - Stage rollback for agricultural process corrections
     * - Workflow debugging and error resolution
     * - Administrative override capabilities for production issues
     * - Quality control rollback when stage advancement was premature
     * 
     * @example
     * // Rollback premature stage advancement
     * $harvestStage = CropStageCache::findByCode('harvest_ready');
     * $previousStage = CropStageCache::getPreviousStage($harvestStage);
     * // $previousStage would be 'growing' stage
     * 
     * // Administrative correction workflow
     * if ($previousStage = CropStageCache::getPreviousStage($crop->stage)) {
     *     // Rollback available for correction
     *     $crop->rollbackTo($previousStage, $reason);
     * } else {
     *     // Crop is at initial stage, no rollback possible
     * }
     * 
     * // Build administrative correction UI
     * if ($previousStage = CropStageCache::getPreviousStage($crop->stage)) {
     *     // Show "Rollback to {$previousStage->name}" option
     * }
     */
    public static function getPreviousStage(CropStage $currentStage): ?CropStage
    {
        return self::all()
            ->where('sort_order', '<', $currentStage->sort_order)
            ->where('is_active', true)
            ->sortByDesc('sort_order')
            ->first();
    }
    
    /**
     * Initialize lookup index caches for optimal agricultural workflow performance.
     * 
     * Builds high-performance lookup arrays indexed by stage ID and code,
     * enabling O(1) access patterns essential for frequent agricultural
     * operations. Called automatically during first cache access to ensure
     * optimal performance for subsequent stage lookups.
     * 
     * @business_purpose Optimize agricultural workflow performance through efficient indexing
     * @performance_optimization Creates O(1) lookup structures for frequent operations
     * @internal_method Private method supporting public cache access patterns
     * 
     * @indexing_strategy
     * - ID index: Fast database relationship resolution
     * - Code index: Fast business logic and workflow operations
     * - Single iteration through stage collection for both indices
     * 
     * @agricultural_benefits
     * - Microsecond stage lookups during crop lifecycle operations
     * - Optimal performance for agricultural workflow validation
     * - Efficient UI rendering for stage-based interfaces
     * - Fast batch processing operations requiring stage context
     * 
     * @private Internal optimization method, not intended for external use
     */
    /**
     * @private Internal optimization method for building lookup index caches
     */
    private static function buildCaches(): void
    {
        self::$stagesById = [];
        self::$stagesByCode = [];
        
        foreach (self::$stages as $stage) {
            self::$stagesById[$stage->id] = $stage;
            self::$stagesByCode[$stage->code] = $stage;
        }
    }
    
    /**
     * Reset agricultural stage cache for testing and data refresh scenarios.
     * 
     * Clears all cached stage data and lookup indices, forcing fresh database
     * retrieval on next access. Essential for testing scenarios and when
     * agricultural stage configuration has been modified during runtime.
     * 
     * @business_purpose Cache invalidation for testing and administrative updates
     * @testing_support Ensures clean state for agricultural workflow testing
     * @administrative_tool Enables cache refresh after stage configuration changes
     * 
     * @use_cases
     * - Unit testing requiring fresh agricultural stage data
     * - Integration testing of stage workflow scenarios
     * - Administrative updates to stage configuration
     * - Development environment cache refresh operations
     * 
     * @cache_impact
     * - Clears all static cache variables to null
     * - Next access will trigger fresh database query and cache rebuild
     * - Temporary performance impact until cache rebuilds
     * - Essential for data consistency in testing environments
     * 
     * @example
     * // Clear cache before agricultural workflow test
     * CropStageCache::clear();
     * $stages = CropStageCache::all(); // Fresh database query
     * 
     * // Administrative cache refresh after stage updates
     * // (After modifying CropStage records)
     * CropStageCache::clear();
     * // Next access will reflect updated stage configuration
     */
    public static function clear(): void
    {
        self::$stages = null;
        self::$stagesById = null;
        self::$stagesByCode = null;
    }
}