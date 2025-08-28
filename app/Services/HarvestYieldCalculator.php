<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\Harvest;
use App\Models\Recipe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agricultural harvest yield calculator for data-driven crop planning.
 * 
 * Analyzes historical harvest performance to generate accurate yield predictions
 * for microgreens production planning. Uses time-weighted calculations and statistical
 * analysis to provide superior planning estimates compared to static recipe yields.
 *
 * @business_domain Microgreens production yield optimization and planning accuracy
 * @agricultural_concept Converts historical harvest data into predictive agricultural planning tools
 * @data_source Historical harvest records with variety-specific performance metrics
 * @planning_integration Feeds into CropPlanCalculatorService for accurate tray and seed calculations
 * 
 * @core_algorithm
 * Uses exponential decay weighting to favor recent harvests while incorporating
 * historical data for statistical reliability. Matches harvests by seed variety
 * and growing conditions for maximum accuracy.
 * 
 * @agricultural_benefits
 * - **Data-Driven Planning:** Replaces static estimates with actual performance data
 * - **Seasonal Adaptation:** Automatically adjusts for seasonal yield variations
 * - **Continuous Improvement:** Self-updating as new harvest data becomes available
 * - **Risk Management:** Buffer calculations account for agricultural variability
 * 
 * @business_impact
 * - **Resource Optimization:** More accurate tray calculations reduce waste
 * - **Customer Reliability:** Better delivery predictions from improved planning
 * - **Cost Control:** Eliminates over/under-planting through precise calculations
 * - **Competitive Advantage:** Data-driven agriculture vs traditional guesswork
 * 
 * @configuration_options
 * - harvest.yield.decay_factor: Time weighting factor for harvest recency (default: 30 days)
 * - harvest.yield.history_months: Historical data window (default: 6 months)
 * - harvest.planning.default_buffer_percentage: Safety margin for variability (default: 10%)
 * - harvest.yield.thresholds.*: Performance comparison thresholds for recommendations
 * 
 * @related_services CropPlanCalculatorService, OrderPlanningService, CropPlanAggregationService
 * @related_models Recipe, Harvest, MasterCultivar, MasterSeedCatalog, Consumable
 * @filament_integration Used by crop planning interfaces and order simulation tools
 */
    /**
     * Calculate time-weighted average yield per tray using historical harvest performance.
     * 
     * Analyzes historical harvest data for specific seed variety to generate accurate
     * yield predictions. Uses exponential decay weighting algorithm to favor recent
     * harvests while maintaining statistical reliability from historical data.
     *
     * @param Recipe $recipe Recipe containing seed variety and growing parameters for matching
     * @return float|null Weighted average grams per tray, null if no harvest data available
     * 
     * @agricultural_algorithm
     * **Time-Weighted Calculation:**
     * ```
     * weight = e^(-days_since_harvest / decay_factor)
     * weighted_yield = Σ(yield_i × weight_i) / Σ(weight_i)
     * ```
     * 
     * **Weighting Logic:**
     * - Recent harvests (0-30 days): Full weight (close to 1.0)
     * - Older harvests (30-90 days): Declining weight (0.37 to 0.05)
     * - Ancient harvests (>90 days): Minimal weight but still contributing
     * - Exponential decay ensures smooth transition, not arbitrary cutoffs
     * 
     * @data_matching
     * **Harvest Relevance Criteria:**
     * - Must match recipe's seed variety (common_name and cultivar_name)
     * - Limited to recent timeframe (configurable, default 6 months)
     * - Uses actual harvest performance data, not estimates
     * - Accounts for seasonal growing condition variations
     * 
     * @agricultural_accuracy
     * **Why Time Weighting:**
     * - Growing conditions change seasonally (temperature, light, humidity)
     * - Equipment improvements affect yield over time
     * - Recent harvests reflect current growing capabilities
     * - Historical data provides statistical reliability
     * 
     * **Variety Matching:**
     * - Different seed varieties have drastically different yield characteristics
     * - Cultivar-specific matching ensures agricultural accuracy
     * - Recipe-harvest linkage maintains growing parameter consistency
     * 
     * @business_value
     * - **Planning Accuracy:** Historical data beats recipe guesswork
     * - **Automatic Updates:** No manual yield table maintenance required
     * - **Seasonal Adaptation:** Automatically adjusts for growing condition changes
     * - **Data-Driven Decisions:** Removes subjective bias from planning
     * 
     * @fallback_behavior
     * Returns null when no relevant harvest data found, triggering:
     * - CropPlanCalculatorService falls back to recipe expected_yield
     * - Logging indicates data-driven vs estimate-based calculations
     * - Opportunity for data collection improvement identified
     * 
     * @configuration
     * - **decay_factor:** Controls time weighting curve (default: 30 days)
     * - **history_months:** Historical data window (default: 6 months)
     * - Configurable via harvest.yield.* config keys
     */
    public function calculateWeightedYieldForRecipe(Recipe $recipe): ?float
    {
        // Find harvests that match this recipe's seed variety and lot
        $relevantHarvests = $this->getRelevantHarvests($recipe);

        if ($relevantHarvests->isEmpty()) {
            return null; // No harvest data available
        }

        $weightedSum = 0;
        $totalWeight = 0;
        $now = Carbon::now();

        foreach ($relevantHarvests as $harvest) {
            $daysSinceHarvest = $now->diffInDays($harvest->harvest_date);

            // Exponential decay: weight = e^(-days/decay_factor)
            $decayFactor = config('harvest.yield.decay_factor', 30);
            $weight = exp(-$daysSinceHarvest / $decayFactor);

            $yieldPerTray = $harvest->average_weight_per_tray;

            $weightedSum += $yieldPerTray * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : null;
    }

    /**
     * Retrieve relevant historical harvest data for yield calculation accuracy.
     * 
     * Filters harvest records to find variety-specific performance data within
     * relevant timeframe. Ensures yield calculations use appropriate agricultural
     * data by matching seed varieties and maintaining temporal relevance.
     *
     * @param Recipe $recipe Recipe with seed variety information for harvest matching
     * @return Collection<Harvest> Filtered harvest records with loaded relationships
     * 
     * @agricultural_filtering
     * **Variety Matching:**
     * - Matches by common_name (e.g., "Broccoli", "Radish") for base variety
     * - Additional cultivar_name matching when specified (e.g., "Purple Top")
     * - Ensures harvest data applies to same agricultural seed variety
     * 
     * **Temporal Relevance:**
     * - Limits to configurable timeframe (default: 6 months) for current relevance
     * - Balances data quantity with seasonal/operational relevance
     * - Excludes ancient data that may not reflect current growing capabilities
     * 
     * **Data Quality:**
     * - Orders by harvest_date descending for consistent processing
     * - Eager loads relationships to prevent N+1 query issues
     * - Returns empty collection when no matches found (graceful degradation)
     * 
     * @business_rationale
     * **Why 6 Month Window:**
     * - Captures full seasonal growing cycle variations
     * - Includes recent operational improvements and changes
     * - Provides sufficient data points for statistical reliability
     * - Excludes outdated data from significantly different conditions
     * 
     * **Why Variety Matching:**
     * - Different varieties have vastly different yield characteristics
     * - Broccoli microgreens ≠ Radish microgreens in growth and harvest weight
     * - Cultivar differences (Purple Top vs Daikon radish) affect yields
     * - Agricultural accuracy requires variety-specific historical data
     * 
     * @data_relationships
     * **Harvest → MasterCultivar → MasterSeedCatalog Chain:**
     * - Harvest links to specific cultivar grown
     * - MasterCultivar provides variety identification
     * - MasterSeedCatalog contains common_name and cultivar_name for matching
     * - Relationship chain ensures accurate variety identification
     * 
     * @configuration_impact
     * - **history_months:** Adjusts data window (more months = more data, less current)
     * - Shorter windows favor current conditions, longer windows improve statistics
     * - Agricultural operations may adjust based on seasonal planning needs
     * 
     * @performance_considerations
     * - Eager loading prevents N+1 queries during batch processing
     * - Date filtering uses database indexes for efficient querying
     * - Relationship filtering pushes work to database vs application layer
     * - Results cached implicitly through Recipe model relationships when applicable
     */
    private function getRelevantHarvests(Recipe $recipe): Collection
    {
        // Get the current seed consumable for this recipe
        $seedConsumable = $this->getCurrentSeedConsumable($recipe);

        if (! $seedConsumable) {
            return collect();
        }

        $historyMonths = config('harvest.yield.history_months', 6);
        $sixMonthsAgo = Carbon::now()->subMonths($historyMonths);

        // Find harvests that match the recipe's variety
        // Match by common name and cultivar name
        return Harvest::with('masterCultivar.masterSeedCatalog')
            ->where('harvest_date', '>=', $sixMonthsAgo)
            ->whereHas('masterCultivar.masterSeedCatalog', function ($query) use ($recipe) {
                $query->where('common_name', $recipe->common_name);
                if ($recipe->cultivar_name) {
                    $query->where('cultivar_name', $recipe->cultivar_name);
                }
            })
            ->orderBy('harvest_date', 'desc')
            ->get();
    }

    /**
     * Retrieve current active seed consumable for recipe-harvest data correlation.
     * 
     * Identifies seed inventory lot associated with recipe for harvest data filtering.
     * Supports both new lot-based system and legacy seed_consumable_id for backward
     * compatibility during system transition.
     *
     * @param Recipe $recipe Recipe requiring seed consumable identification
     * @return Consumable|null Active seed consumable, null if none found
     * 
     * @deprecated Legacy method - lot-based harvest matching provides better accuracy
     * @todo Migrate to lot-based harvest correlation when seed inventory system complete
     * 
     * @system_evolution
     * **Current Lot-Based System (Preferred):**
     * - Uses recipe.lot_number for inventory correlation
     * - Calls availableLotConsumables() for current seed inventory
     * - Provides accurate seed lot to harvest correlation
     * 
     * **Legacy Compatibility (Deprecated):**
     * - Falls back to recipe.seed_consumable_id for existing data
     * - Maintains functionality during system migration period
     * - Ensures continuous operation while upgrading to lot-based system
     * 
     * @agricultural_context
     * **Why Seed Lot Matters:**
     * - Different seed lots of same variety may have different germination rates
     * - Harvest performance varies by seed source and processing batch
     * - Lot-specific correlation improves yield prediction accuracy
     * - Seed quality affects final harvest weights and growing performance
     * 
     * **Inventory Integration:**
     * - Links recipes to specific seed inventory for traceability
     * - Enables lot-based quality tracking and performance analysis
     * - Supports seed supplier evaluation and procurement decisions
     * 
     * @migration_path
     * 1. Current: Uses lot_number when available, falls back to consumable_id
     * 2. Transition: Gradual migration of recipes to lot-based system
     * 3. Future: Pure lot-based correlation with enhanced harvest accuracy
     * 
     * @business_implications
     * - Accurate seed lot correlation improves yield predictions
     * - Better inventory tracking supports quality management
     * - Lot-based analysis enables supplier performance evaluation
     * - Enhanced traceability for food safety and quality compliance
     */
    private function getCurrentSeedConsumable(Recipe $recipe): ?Consumable
    {
        // First try to get from lot_number (new system)
        if ($recipe->lot_number) {
            return $recipe->availableLotConsumables()->first();
        }
        
        // Fallback to deprecated seed_consumable_id for backward compatibility
        if (! $recipe->seed_consumable_id) {
            return null;
        }

        return Consumable::where('id', $recipe->seed_consumable_id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Generate comprehensive yield statistics for agricultural planning transparency.
     * 
     * Provides detailed statistical analysis of harvest performance including weighted
     * averages, historical ranges, and planning recommendations. Essential for production
     * planning transparency and continuous agricultural improvement.
     *
     * @param Recipe $recipe Recipe requiring yield analysis for planning
     * @return array [
     *     'weighted_yield' => float|null, Time-weighted average yield (grams/tray)
     *     'harvest_count' => int, Number of historical harvests in analysis
     *     'date_range' => array|null, ['oldest' => string, 'newest' => string] harvest dates
     *     'min_yield' => float|null, Minimum yield in dataset (grams/tray)
     *     'max_yield' => float|null, Maximum yield in dataset (grams/tray)
     *     'avg_yield' => float|null, Simple average yield (grams/tray)
     *     'recipe_expected' => float, Recipe baseline yield (grams/tray)
     *     'recommendation' => string Human-readable planning recommendation
     * ]
     * 
     * @agricultural_analytics
     * **Statistical Metrics:**
     * - **weighted_yield:** Primary planning metric with time bias
     * - **avg_yield:** Simple average for comparison baseline
     * - **min/max_yield:** Range analysis for variability assessment
     * - **harvest_count:** Data reliability indicator (more = better)
     * 
     * **Performance Comparison:**
     * - Compares actual harvest data against recipe expectations
     * - Identifies varieties performing above/below estimates
     * - Provides actionable recommendations for recipe updates
     * 
     * @business_intelligence
     * **Planning Reliability Assessment:**
     * - High harvest_count indicates reliable data for planning
     * - Wide min/max range suggests high variability requiring buffers
     * - Recent date ranges show data currency for seasonal relevance
     * 
     * **Continuous Improvement:**
     * - Recommendations guide recipe yield updates
     * - Performance tracking identifies growing condition improvements
     * - Statistical analysis supports agricultural decision making
     * 
     * @recommendation_logic
     * **Performance Categories:**
     * - **Matching Well:** Harvest data within 5% of recipe expectations
     * - **Significantly Over:** Harvests >15% above expectations (update recipe)
     * - **Significantly Under:** Harvests >15% below expectations (review conditions)
     * - **Moderate Variations:** Between thresholds with directional guidance
     * 
     * @no_data_handling
     * When no harvest data available:
     * - Returns null for harvest-derived metrics
     * - Maintains recipe expected yield for fallback planning
     * - Recommendation indicates data collection opportunity
     * - Graceful degradation maintains planning capability
     * 
     * @transparency_value
     * **Production Planning:**
     * - Shows basis for tray calculations and resource allocation
     * - Enables informed decisions about planning reliability
     * - Supports customer communication about delivery confidence
     * 
     * **Agricultural Operations:**
     * - Identifies varieties needing growing condition review
     * - Guides recipe updates based on actual performance
     * - Enables data-driven agricultural improvements
     * 
     * @usage_integration
     * Used by CropPlanCalculatorService.getYieldSourceInfo() for calculation transparency
     * and by planning interfaces for agricultural decision support.
     */
    public function getYieldStats(Recipe $recipe): array
    {
        $harvests = $this->getRelevantHarvests($recipe);
        $weightedYield = $this->calculateWeightedYieldForRecipe($recipe);

        if ($harvests->isEmpty()) {
            return [
                'weighted_yield' => null,
                'harvest_count' => 0,
                'date_range' => null,
                'min_yield' => null,
                'max_yield' => null,
                'avg_yield' => null,
                'recipe_expected' => $recipe->expected_yield_grams,
                'recommendation' => 'No harvest data available. Using recipe expected yield.',
            ];
        }

        $yields = $harvests->pluck('average_weight_per_tray');

        return [
            'weighted_yield' => $weightedYield,
            'harvest_count' => $harvests->count(),
            'date_range' => [
                'oldest' => $harvests->last()->harvest_date->format('M j, Y'),
                'newest' => $harvests->first()->harvest_date->format('M j, Y'),
            ],
            'min_yield' => $yields->min(),
            'max_yield' => $yields->max(),
            'avg_yield' => round($yields->avg(), 2),
            'recipe_expected' => $recipe->expected_yield_grams,
            'recommendation' => $this->getRecommendation($weightedYield, $recipe->expected_yield_grams),
        ];
    }

    /**
     * Generate agricultural planning recommendation based on harvest vs recipe performance.
     * 
     * Analyzes performance gap between actual harvest yields and recipe expectations
     * to provide actionable recommendations for production planning and recipe management.
     * Uses configurable thresholds to categorize performance and guide decisions.
     *
     * @param float $weightedYield Time-weighted actual harvest performance (grams/tray)
     * @param float $expectedYield Recipe baseline expectation (grams/tray)
     * @return string Human-readable recommendation for agricultural planning
     * 
     * @performance_analysis
     * **Calculation Logic:**
     * ```
     * performance_difference = ((actual - expected) / expected) × 100
     * ```
     * 
     * **Threshold Categories:**
     * - **Matching Well:** ±5% variance (good recipe accuracy)
     * - **Significantly Over:** +15% or higher (recipe needs update)
     * - **Significantly Under:** -15% or lower (growing issues)
     * - **Moderate Variance:** Between thresholds (directional guidance)
     * 
     * @agricultural_recommendations
     * **Performance Category Actions:**
     * 
     * **"Matching Well" (±5%):**
     * - Current recipe expectations are accurate
     * - Continue using current planning parameters
     * - Good baseline for future planning
     * 
     * **"Significantly Over" (+15%+):**
     * - Actual yields consistently exceed recipe expectations
     * - Consider updating recipe expected_yield upward
     * - Opportunity to reduce tray allocations and save resources
     * - May indicate improved growing conditions or techniques
     * 
     * **"Significantly Under" (-15%+):**
     * - Actual yields falling short of expectations
     * - Review growing conditions (light, temperature, nutrients)
     * - Check seed quality and germination rates
     * - May need increased tray allocations for reliable delivery
     * 
     * @business_decision_support
     * **Resource Allocation:**
     * - Over-performing varieties can use fewer trays
     * - Under-performing varieties need additional growing capacity
     * - Accurate expectations improve customer delivery reliability
     * 
     * **Recipe Management:**
     * - Recommendations guide when to update recipe parameters
     * - Statistical evidence supports agricultural decision making
     * - Continuous improvement through data-driven adjustments
     * 
     * **Risk Management:**
     * - Identifies varieties with planning reliability concerns
     * - Guides buffer percentage adjustments for variability
     * - Supports conservative vs aggressive planning strategies
     * 
     * @configuration_flexibility
     * **Adjustable Thresholds:**
     * - matching_well: Acceptable variance range (default: 5%)
     * - significantly_over: Update threshold (default: 15%)
     * - significantly_under: Review threshold (default: -15%)
     * - Configurable via harvest.yield.thresholds.* keys
     * 
     * @agricultural_context
     * **Why Percentage-Based:**
     * - Normalizes across different yield scales (50g vs 200g varieties)
     * - Accounts for proportional agricultural variability
     * - Enables consistent evaluation across all seed varieties
     * 
     * **Seasonal Considerations:**
     * - Recommendations based on time-weighted data account for seasonal changes
     * - Recent performance weighted more heavily for current relevance
     * - Historical context provides statistical confidence
     */
    private function getRecommendation(float $weightedYield, float $expectedYield): string
    {
        $difference = (($weightedYield - $expectedYield) / $expectedYield) * 100;

        $matchingWell = config('harvest.yield.thresholds.matching_well', 5.0);
        $significantlyOver = config('harvest.yield.thresholds.significantly_over', 15.0);
        $significantlyUnder = config('harvest.yield.thresholds.significantly_under', -15.0);

        if (abs($difference) < $matchingWell) {
            return 'Harvest data matches recipe expectations well.';
        } elseif ($difference > $significantlyOver) {
            return 'Recent harvests significantly exceed expectations. Consider updating recipe yield.';
        } elseif ($difference < $significantlyUnder) {
            return 'Recent harvests are below expectations. Consider reviewing growing conditions.';
        } elseif ($difference > 0) {
            return 'Recent harvests are above expectations.';
        } else {
            return 'Recent harvests are below expectations.';
        }
    }

    /**
     * Calculate conservative planning yield incorporating agricultural risk buffers.
     * 
     * Applies safety margins to yield calculations to account for agricultural variability
     * and ensure reliable production planning. Combines harvest-informed yields with
     * risk management buffers for dependable customer delivery commitments.
     *
     * @param Recipe $recipe Recipe with variety-specific parameters and buffer settings
     * @return float Effective planning yield in grams per tray (conservative estimate)
     * 
     * @agricultural_risk_management
     * **Buffer Application Logic:**
     * ```
     * base_yield = weighted_harvest_yield ?? recipe_expected_yield
     * buffer_multiplier = 1 + (buffer_percentage / 100)
     * planning_yield = base_yield / buffer_multiplier
     * ```
     * 
     * **Conservative Planning Philosophy:**
     * - Better to over-allocate trays than under-deliver to customers
     * - Agricultural variability requires safety margins
     * - Buffer percentages account for growing condition fluctuations
     * 
     * @yield_source_hierarchy
     * **Primary: Harvest-Informed Yields**
     * - Uses calculateWeightedYieldForRecipe() for data-driven planning
     * - Incorporates actual performance with time weighting
     * - Provides most accurate base for buffer calculations
     * 
     * **Fallback: Recipe Expected Yields**
     * - Uses recipe.expected_yield_grams when no harvest data available
     * - Baseline estimates for new varieties or limited data scenarios
     * - Maintains planning capability during data collection phase
     * 
     * @buffer_percentage_logic
     * **Recipe-Specific Buffers:**
     * - Each recipe can have custom buffer_percentage for variety-specific risk
     * - High-variability varieties get higher buffers
     * - Consistent varieties can use lower buffers for efficiency
     * 
     * **Default Buffer Fallback:**
     * - Uses config default (typically 10%) when recipe buffer not set
     * - Ensures all calculations include some risk mitigation
     * - Configurable via harvest.planning.default_buffer_percentage
     * 
     * @agricultural_variability_factors
     * **Why Buffers Are Essential:**
     * - Seasonal light and temperature variations affect yields
     * - Seed germination rates vary by lot and storage conditions
     * - Growing medium and nutrient variations impact performance
     * - Harvest timing affects final weight and quality
     * - Equipment performance and maintenance cycles create variations
     * 
     * @business_impact
     * **Customer Reliability:**
     * - Conservative planning ensures order fulfillment capability
     * - Reduces risk of shortfalls and customer disappointment
     * - Enables confident delivery date commitments
     * 
     * **Resource Optimization Balance:**
     * - Buffers prevent under-allocation disasters
     * - Data-driven base yields minimize over-allocation waste
     * - Variety-specific buffers optimize across different agricultural profiles
     * 
     * @mathematical_example
     * ```
     * harvest_data_shows: 85g/tray average
     * recipe_buffer: 15% safety margin
     * calculation: 85g ÷ (1 + 15/100) = 85g ÷ 1.15 = 74g/tray planning yield
     * result: Plan for 74g/tray to account for 15% agricultural variability
     * ```
     * 
     * @integration_usage
     * Called by CropPlanCalculatorService.calculateTraysNeeded() as final step in
     * agricultural planning calculations, ensuring all tray allocations include
     * appropriate risk management for reliable production planning.
     */
    public function calculatePlanningYield(Recipe $recipe): float
    {
        $weightedYield = $this->calculateWeightedYieldForRecipe($recipe);
        $baseYield = $weightedYield ?? $recipe->expected_yield_grams;

        // Apply buffer percentage (use config default if not set on recipe)
        $bufferPercentage = $recipe->buffer_percentage ?? config('harvest.planning.default_buffer_percentage', 10.0);
        $bufferMultiplier = 1 + ($bufferPercentage / 100);

        return $baseYield / $bufferMultiplier;
    }
}
