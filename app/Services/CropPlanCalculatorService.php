<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\SeedEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural crop plan calculator service for microgreens production planning.
 * 
 * Transforms customer orders into detailed planting requirements by calculating seed
 * quantities, tray requirements, and growing schedules based on harvest yield data
 * and agricultural growing parameters. Core service for production planning workflow.
 *
 * @business_domain Microgreens production planning and resource allocation
 * @agricultural_concept Converts consumer demand into agricultural production requirements
 * @workflow_integration Order processing -> Crop planning -> Resource allocation -> Production scheduling
 * @yield_dependency Integrates with HarvestYieldCalculator for data-driven planting decisions
 * @performance_optimization Includes relationship preloading to prevent N+1 queries during batch calculations
 * 
 * @related_services HarvestYieldCalculator, OrderPlanningService, CropPlanAggregationService
 * @related_models Order, OrderItem, Product, ProductMix, SeedEntry, Recipe, PriceVariation
 * @filament_integration Used by Order resources and crop planning interfaces
 */
class CropPlanCalculatorService
{
    /**
     * Agricultural yield calculator for harvest-informed planting decisions.
     * 
     * @var HarvestYieldCalculator Service providing weighted yield calculations from historical harvest data
     * @agricultural_dependency Essential for accurate seed quantity and tray calculations
     */
    protected HarvestYieldCalculator $yieldCalculator;

    /**
     * Initialize crop plan calculator with yield calculation dependency.
     *
     * @param HarvestYieldCalculator $yieldCalculator Historical harvest data analyzer for planning accuracy
     * @agricultural_context Yield calculator provides data-driven growing recommendations vs recipe estimates
     * @dependency_injection Laravel container resolves yield calculator with harvest data access
     */
    public function __construct(HarvestYieldCalculator $yieldCalculator)
    {
        $this->yieldCalculator = $yieldCalculator;
    }

    /**
     * Calculate aggregate planting requirements for multiple orders in production planning.
     * 
     * Processes collection of orders to generate consolidated seed requirements, tray calculations,
     * and resource allocation for batch production planning. Aggregates seed varieties across
     * all orders to optimize procurement and growing space utilization.
     *
     * @param Collection<Order> $orders Collection of orders with loaded relationships for calculation
     * @return array [
     *     'planting_plan' => array<int, array{ Aggregated seed requirements by seed_entry_id
     *         'seed_entry' => SeedEntry, Seed variety information and growing parameters
     *         'total_grams_needed' => float, Total seed weight required across all orders
     *         'total_trays_needed' => int, Total growing trays required for production
     *         'orders' => array<array{ Order breakdown for traceability
     *             'order_id' => int, Order identifier for tracking
     *             'customer' => string, Customer name for reference
     *             'grams' => float, Seed grams for this specific order
     *             'trays' => int Tray count for this order
     *         }>
     *     }>,
     *     'calculation_details' => array<array> Individual order calculations for audit trail
     * ]
     * 
     * @agricultural_workflow
     * 1. Pre-load order relationships to prevent N+1 queries during batch processing
     * 2. Calculate individual order requirements with variety-specific parameters
     * 3. Aggregate seed requirements across all orders for consolidated procurement
     * 4. Generate detailed breakdown for order traceability and production scheduling
     * 
     * @business_rules
     * - Products can be single varieties (direct seed entry) or mixes (multiple varieties with percentages)
     * - Tray calculations use harvest-informed yields when available, recipe estimates as fallback
     * - Fill weights from price variations determine total product weight requirements
     * - Buffer percentages from recipes account for agricultural growing variability
     * 
     * @performance_optimization
     * - Eager loads all required relationships in single query to avoid N+1 problems
     * - Processes orders in memory after loading to minimize database queries
     * - Aggregates requirements efficiently for large order batches
     * 
     * @usage_examples
     * // Weekly production planning
     * $weeklyOrders = Order::whereBetween('delivery_date', [$startDate, $endDate])->get();
     * $plantingPlan = $calculator->calculateForOrders($weeklyOrders);
     * 
     * // Seasonal seed procurement
     * $seasonOrders = Order::where('delivery_date', '>', now()->addWeeks(2))->get();
     * $seedRequirements = $calculator->calculateForOrders($seasonOrders)['planting_plan'];
     * 
     * @throws \InvalidArgumentException If orders collection contains invalid or missing data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If referenced models not found
     */
    public function calculateForOrders(Collection $orders): array
    {
        // Pre-load all required relationships to avoid N+1 queries
        $orders->load(['orderItems.product.productMix.seedEntries', 'orderItems.priceVariation', 'customer', 'user']);
        
        $seedRequirements = [];
        $calculationDetails = [];

        foreach ($orders as $order) {
            $orderDetails = $this->calculateForOrder($order);
            $calculationDetails[] = $orderDetails;

            // Aggregate seed requirements
            foreach ($orderDetails['seed_requirements'] as $seedEntryId => $requirement) {
                if (! isset($seedRequirements[$seedEntryId])) {
                    $seedRequirements[$seedEntryId] = [
                        'seed_entry' => $requirement['seed_entry'],
                        'total_grams_needed' => 0,
                        'total_trays_needed' => 0,
                        'orders' => [],
                    ];
                }

                $seedRequirements[$seedEntryId]['total_grams_needed'] += $requirement['grams_needed'];
                $seedRequirements[$seedEntryId]['total_trays_needed'] += $requirement['trays_needed'];
                $seedRequirements[$seedEntryId]['orders'][] = [
                    'order_id' => $order->id,
                    'customer' => $order->customer ? $order->customer->contact_name : ($order->user ? $order->user->name : 'Unknown'),
                    'grams' => $requirement['grams_needed'],
                    'trays' => $requirement['trays_needed'],
                ];
            }
        }

        return [
            'planting_plan' => $seedRequirements,
            'calculation_details' => $calculationDetails,
        ];
    }

    /**
     * Calculate detailed planting requirements for individual order processing.
     * 
     * Analyzes single order to determine seed varieties, quantities, and tray requirements
     * needed for production. Processes all order items to generate comprehensive growing
     * plan with variety-specific agricultural parameters and yield expectations.
     *
     * @param Order $order Order with loaded relationships (orderItems, product, priceVariation)
     * @return array [
     *     'order_id' => int, Order identifier for tracking
     *     'customer' => string, Customer name (contact_name or user name)
     *     'delivery_date' => string, Formatted delivery date (Y-m-d) for scheduling
     *     'order_items' => array<array>, Detailed breakdown of each order item's requirements
     *     'seed_requirements' => array<int, array{ Consolidated seed needs by seed_entry_id
     *         'seed_entry' => SeedEntry, Seed variety with growing parameters
     *         'grams_needed' => float, Total seed weight for this variety
     *         'trays_needed' => int, Total trays required for growing
     *         'items' => array<string> Product names using this seed variety
     *     }>
     * ]
     * 
     * @agricultural_workflow
     * 1. Load order relationships if not already eager loaded for performance
     * 2. Process each order item through variety-specific calculations
     * 3. Aggregate seed requirements across order items for single variety totals
     * 4. Generate order-level summary for production planning integration
     * 
     * @business_context
     * - Single order may contain multiple products using same or different seed varieties
     * - Product mixes require percentage-based calculations for multiple seed varieties
     * - Fill weights from price variations determine actual product weight requirements
     * - Delivery dates drive backward scheduling for planting and growing timelines
     * 
     * @relationship_requirements
     * - Order must have orderItems relationship loaded
     * - OrderItems must have product and priceVariation relationships loaded
     * - Products must have productMix.seedEntries loaded for mix calculations
     * - Customer or user relationship loaded for identification
     * 
     * @yield_integration
     * Uses HarvestYieldCalculator for data-driven tray calculations based on:
     * - Historical harvest yields for accurate planning
     * - Recipe expected yields as fallback when no harvest data
     * - Buffer percentages to account for agricultural variability
     * 
     * @error_handling
     * - Logs warnings for orders without products or missing relationships
     * - Uses default fallback values when agricultural data missing
     * - Continues processing valid items even if some items have errors
     */
    public function calculateForOrder(Order $order): array
    {
        // Ensure required relationships are loaded to avoid lazy loading
        if (!$order->relationLoaded('orderItems')) {
            $order->load(['orderItems.product.productMix.seedEntries', 'orderItems.priceVariation', 'customer', 'user']);
        }
        
        $seedRequirements = [];
        $orderItems = [];

        foreach ($order->orderItems as $orderItem) {
            $itemDetails = $this->calculateForOrderItem($orderItem);
            $orderItems[] = $itemDetails;

            // Aggregate seed requirements from this item
            foreach ($itemDetails['seed_requirements'] as $seedEntryId => $requirement) {
                if (! isset($seedRequirements[$seedEntryId])) {
                    $seedRequirements[$seedEntryId] = [
                        'seed_entry' => $requirement['seed_entry'],
                        'grams_needed' => 0,
                        'trays_needed' => 0,
                        'items' => [],
                    ];
                }

                $seedRequirements[$seedEntryId]['grams_needed'] += $requirement['grams_needed'];
                $seedRequirements[$seedEntryId]['trays_needed'] += $requirement['trays_needed'];
                $seedRequirements[$seedEntryId]['items'][] = $itemDetails['product_name'];
            }
        }

        return [
            'order_id' => $order->id,
            'customer' => $order->customer ? $order->customer->contact_name : ($order->user ? $order->user->name : 'Unknown'),
            'delivery_date' => $order->delivery_date->format('Y-m-d'),
            'order_items' => $orderItems,
            'seed_requirements' => $seedRequirements,
        ];
    }

    /**
     * Calculate agricultural requirements for individual product order item.
     * 
     * Processes single order item to determine seed varieties, quantities, tray requirements,
     * and growing parameters needed for production. Handles both single-variety products
     * and complex mixes with multiple seed varieties and percentage distributions.
     *
     * @param OrderItem $orderItem Order item with loaded product and priceVariation relationships
     * @return array [
     *     'product_name' => string, Product name for identification
     *     'quantity' => int, Number of units ordered
     *     'fill_weight' => float, Weight per unit in grams from price variation
     *     'total_grams_needed' => float, Total product weight required (quantity × fill_weight)
     *     'packaging_type' => string, Container type from price variation
     *     'seed_requirements' => array<int, array{ Seed variety requirements by seed_entry_id
     *         'seed_entry' => SeedEntry, Seed variety with agricultural parameters
     *         'percentage' => float, Percentage of mix (100% for single varieties)
     *         'grams_needed' => float, Seed weight required for this variety
     *         'trays_needed' => int Tray count based on yield calculations
     *     }>,
     *     'yield_source' => array<string, array> Yield data transparency for audit
     * ]
     * 
     * @agricultural_calculations
     * 
     * **Single Variety Products:**
     * - Direct mapping to seed entry through product name matching
     * - 100% of total weight allocated to single seed variety
     * - Tray calculation uses variety-specific yield data
     * 
     * **Product Mix Calculations:**
     * - Multiple seed varieties with percentage distributions
     * - Each variety gets proportional weight allocation (percentage/100 × total_weight)
     * - Individual tray calculations for each variety based on specific yields
     * - Mix percentages must total 100% for agricultural accuracy
     * 
     * @yield_integration
     * **Harvest-Informed Calculations:**
     * - Uses HarvestYieldCalculator for actual yield performance data
     * - Incorporates recipe buffer percentages for growing variability
     * - Falls back to recipe expected yields when no harvest data available
     * - Logs yield source for production planning transparency
     * 
     * @business_rules
     * - Fill weight from price variation determines product weight requirements
     * - Products without productMix use direct seed entry matching by name
     * - Missing products log warnings but return safe default values
     * - Tray calculations always round up to ensure sufficient growing capacity
     * 
     * @agricultural_context
     * - Seed weight requirements drive procurement and inventory planning
     * - Tray calculations determine growing space and infrastructure needs
     * - Yield sources provide transparency for production planning decisions
     * - Variety percentages ensure accurate mix composition for product quality
     * 
     * @error_handling
     * - Missing products return default structure with warnings
     * - Unfound seed entries log warnings for manual review
     * - Default fill weights prevent calculation failures
     * - Yield calculation failures use conservative fallback values
     */
    public function calculateForOrderItem(OrderItem $orderItem): array
    {
        $product = $orderItem->product;
        $priceVariation = $orderItem->priceVariation;
        $quantity = $orderItem->quantity;

        if (! $product) {
            Log::warning("Order item {$orderItem->id} has no product");

            return [
                'product_name' => 'Unknown Product',
                'quantity' => $quantity,
                'fill_weight' => 0,
                'total_grams_needed' => 0,
                'packaging_type' => 'Unknown',
                'seed_requirements' => [],
            ];
        }

        // Get fill weight from price variation
        $fillWeightGrams = $priceVariation?->fill_weight ?? 100; // Default to 100g if not set
        $totalGramsNeeded = $quantity * $fillWeightGrams;

        $seedRequirements = [];

        if ($product->productMix) {
            // Product has a mix - calculate for each seed entry in the mix
            foreach ($product->productMix->seedEntries as $seedEntry) {
                $percentage = $seedEntry->pivot->percentage;
                $gramsForThisSeed = ($percentage / 100) * $totalGramsNeeded;
                $traysForThisSeed = $this->calculateTraysNeeded($gramsForThisSeed, $seedEntry);

                $seedRequirements[$seedEntry->id] = [
                    'seed_entry' => $seedEntry,
                    'percentage' => $percentage,
                    'grams_needed' => $gramsForThisSeed,
                    'trays_needed' => $traysForThisSeed,
                ];
            }
        } else {
            // Single variety product - need to find the seed entry
            // For now, we'll assume the product name matches or is related to a seed entry
            // This might need refinement based on your data structure
            $seedEntry = $this->findSeedEntryForProduct($product);

            if ($seedEntry) {
                $traysNeeded = $this->calculateTraysNeeded($totalGramsNeeded, $seedEntry);

                $seedRequirements[$seedEntry->id] = [
                    'seed_entry' => $seedEntry,
                    'percentage' => 100,
                    'grams_needed' => $totalGramsNeeded,
                    'trays_needed' => $traysNeeded,
                ];
            }
        }

        return [
            'product_name' => $product->name,
            'quantity' => $quantity,
            'fill_weight' => $fillWeightGrams,
            'total_grams_needed' => $totalGramsNeeded,
            'packaging_type' => $priceVariation->packagingType?->name ?? 'Unknown',
            'seed_requirements' => $seedRequirements,
            'yield_source' => $this->getYieldSourceInfo($seedRequirements),
        ];
    }

    /**
     * Calculate agricultural tray requirements using harvest-informed yield data.
     * 
     * Determines growing tray count needed to produce required gram weight of microgreens
     * using data-driven yield calculations. Integrates historical harvest performance
     * with recipe parameters to provide accurate agricultural planning estimates.
     *
     * @param float $gramsNeeded Total weight of finished microgreens required in grams
     * @param SeedEntry $seedEntry Seed variety with agricultural growing parameters
     * @return int Number of growing trays required (always rounded up for capacity)
     * 
     * @agricultural_workflow
     * 1. Find active recipe associated with seed variety for growing parameters
     * 2. Use HarvestYieldCalculator for harvest-informed yield per tray
     * 3. Apply recipe buffer percentages for agricultural growing variability
     * 4. Calculate tray count with ceiling rounding to ensure sufficient capacity
     * 5. Log yield source and calculations for production transparency
     * 
     * @yield_calculation_hierarchy
     * **Primary: Harvest-Informed Yields**
     * - Uses weighted average of recent harvest data for variety
     * - Applies recipe buffer percentage for growing variability
     * - Provides most accurate planning based on actual performance
     * 
     * **Fallback: Recipe Expected Yields**
     * - Uses recipe expected_yield when no harvest data available
     * - Still applies buffer percentage for conservative planning
     * - Provides baseline estimates for new varieties
     * 
     * **Emergency: Default Fallback**
     * - Uses 50g/tray default when no recipe or yield data available
     * - Logs warnings for manual review and data collection
     * - Prevents calculation failures in edge cases
     * 
     * @agricultural_parameters
     * - **Buffer Percentage:** Recipe-defined margin for growing variability (typically 10-20%)
     * - **Weighted Yield:** Recent harvest performance averaged with time weighting
     * - **Growing Conditions:** Implicit in historical yield data and recipe parameters
     * - **Seasonal Variation:** Accounted for in weighted harvest calculations
     * 
     * @business_impact
     * - **Under-planning:** Insufficient trays lead to order shortfalls and customer dissatisfaction
     * - **Over-planning:** Excess trays waste growing space, labor, and resources
     * - **Yield Accuracy:** Data-driven calculations improve resource utilization efficiency
     * - **Cost Control:** Accurate planning reduces waste and optimizes production costs
     * 
     * @logging_transparency
     * - Logs yield source (harvest vs recipe) for audit trail
     * - Records weighted yield values and buffer applications
     * - Warns when no recipe found for variety
     * - Provides calculation details for production review
     * 
     * @usage_context
     * Called by calculateForOrderItem() for each seed variety in product calculations.
     * Results drive growing space allocation and resource planning decisions.
     * 
     * @mathematical_formula
     * ```
     * trays_needed = ceil(grams_needed / effective_yield_per_tray)
     * effective_yield_per_tray = weighted_harvest_yield × (1 - buffer_percentage/100)
     * ```
     */
    protected function calculateTraysNeeded(float $gramsNeeded, SeedEntry $seedEntry): int
    {
        // Find the recipe that uses this seed entry
        $recipe = Recipe::where('seed_entry_id', $seedEntry->id)
            ->where('is_active', true)
            ->first();

        if (! $recipe) {
            // Fallback to default if no recipe found
            Log::warning("No active recipe found for seed entry: {$seedEntry->id} ({$seedEntry->common_name})");
            $gramsPerTray = 50; // Default fallback
        } else {
            // Use harvest-informed yield calculation
            $gramsPerTray = $this->yieldCalculator->calculatePlanningYield($recipe);

            // Log the yield source for transparency
            $weightedYield = $this->yieldCalculator->calculateWeightedYieldForRecipe($recipe);
            if ($weightedYield) {
                Log::info("Using harvest-based yield for {$seedEntry->common_name}: {$gramsPerTray}g/tray (weighted: {$weightedYield}g, buffer: {$recipe->buffer_percentage}%)");
            } else {
                Log::info("Using recipe expected yield for {$seedEntry->common_name}: {$gramsPerTray}g/tray (no harvest data)");
            }
        }

        return (int) ceil($gramsNeeded / $gramsPerTray);
    }

    /**
     * Locate seed variety entry matching agricultural product for single-variety calculations.
     * 
     * Performs intelligent matching between product names and seed catalog entries
     * using exact name matching and fuzzy search algorithms. Essential for linking
     * customer-facing product names to agricultural seed varieties and growing data.
     *
     * @param Product $product Customer product requiring seed variety identification
     * @return SeedEntry|null Matching seed variety with growing parameters, null if no match
     * 
     * @matching_algorithm
     * **Priority 1: Exact Name Matching**
     * - Matches product name against seed common_name field
     * - Matches product name against seed cultivar_name field
     * - Provides highest confidence for established product-seed relationships
     * 
     * **Priority 2: Fuzzy Common Name Matching**
     * - Uses LIKE pattern matching on common_name field
     * - Handles variations in product naming conventions
     * - Catches partial matches and common abbreviations
     * 
     * **Fallback: Manual Review Required**
     * - Logs warning when no automatic match found
     * - Requires manual mapping or product name standardization
     * - Prevents calculation failures with graceful degradation
     * 
     * @business_context
     * **Single-Variety Products:**
     * - Customer products that map directly to single seed varieties
     * - Used when product does NOT have productMix relationship
     * - Common for simple, single-variety microgreen products
     * 
     * **Agricultural Mapping Challenges:**
     * - Customer product names may differ from botanical/seed names
     * - Multiple cultivars of same variety may use different naming
     * - Seasonal variety changes require mapping updates
     * 
     * @data_quality_requirements
     * - Consistent naming conventions between products and seed entries
     * - Regular review of unmapped products for manual correction
     * - Seed catalog completeness with accurate common and cultivar names
     * - Product name standardization for reliable matching
     * 
     * @agricultural_implications
     * - Incorrect matching leads to wrong seed procurement and growing parameters
     * - Missing matches prevent accurate crop planning and resource allocation
     * - Fuzzy matching may require manual verification for critical calculations
     * - Product-seed mapping accuracy directly affects production planning quality
     * 
     * @logging_and_monitoring
     * - Logs warnings for unmapped products requiring manual review
     * - Provides product ID and name for tracking and correction
     * - Enables monitoring of mapping success rates and data quality
     * 
     * @improvement_opportunities
     * - Add master seed catalog ID field to products for direct relationships
     * - Implement product-seed mapping table for complex naming scenarios
     * - Add fuzzy matching score thresholds for confidence-based decisions
     * - Create admin interface for manual product-seed mapping management
     */
    protected function findSeedEntryForProduct(Product $product): ?SeedEntry
    {
        // Try to find by exact name match first
        $seedEntry = SeedEntry::where('common_name', $product->name)
            ->orWhere('cultivar_name', $product->name)
            ->first();

        if (! $seedEntry) {
            // Try fuzzy matching on common name
            $seedEntry = SeedEntry::where('common_name', 'LIKE', '%'.$product->name.'%')
                ->first();
        }

        if (! $seedEntry) {
            // Log this for review
            Log::warning("Could not find seed entry for product: {$product->name} (ID: {$product->id})");
        }

        return $seedEntry;
    }

    /**
     * Generate yield data source transparency for agricultural planning audit.
     * 
     * Provides detailed information about data sources used in yield calculations
     * for production planning transparency and agricultural decision audit trails.
     * Essential for understanding basis of tray calculations and planning reliability.
     *
     * @param array $seedRequirements Seed variety requirements with calculations
     * @return array<string, array> Yield source information keyed by seed common name [
     *     'variety_name' => [
     *         'harvest_count' => int, Number of historical harvests available for analysis
     *         'weighted_yield' => float|null, Weighted average yield from harvest data (grams/tray)
     *         'recipe_expected' => float, Recipe expected yield baseline (grams/tray)
     *         'recommendation' => string, Which yield source was used ('harvest'|'recipe'|'default')
     *         'buffer_percentage' => float Recipe buffer percentage applied for variability
     *     ]
     * ]
     * 
     * @agricultural_transparency
     * **Harvest Data Availability:**
     * - harvest_count shows depth of historical data available
     * - weighted_yield provides actual performance metrics
     * - Higher harvest counts increase planning reliability
     * 
     * **Yield Source Hierarchy:**
     * - 'harvest': Data-driven planning using actual performance
     * - 'recipe': Baseline planning using expected yields
     * - 'default': Emergency fallback when no data available
     * 
     * @business_value
     * **Production Planning Confidence:**
     * - Varieties with extensive harvest data enable accurate resource planning
     * - New varieties with limited data require conservative planning approaches
     * - Transparent data sources support informed agricultural decisions
     * 
     * **Agricultural Risk Assessment:**
     * - Buffer percentages show risk mitigation for growing variability
     * - Recommendation sources indicate calculation reliability levels
     * - Harvest counts reveal data maturity for variety-specific planning
     * 
     * @usage_context
     * **Order Processing:**
     * - Included in order item calculations for customer transparency
     * - Shows basis for delivery commitments and growing schedules
     * - Supports customer communication about production reliability
     * 
     * **Production Review:**
     * - Enables post-harvest analysis of planning accuracy
     * - Identifies varieties needing better data collection
     * - Supports continuous improvement of yield estimation
     * 
     * @data_integration
     * Uses HarvestYieldCalculator.getYieldStats() for comprehensive yield analysis:
     * - Weighted yield calculations from recent harvest performance
     * - Recipe baseline comparisons for validation
     * - Statistical analysis of yield variability and trends
     * 
     * @audit_trail
     * Provides complete transparency for agricultural planning decisions:
     * - Data source identification for calculation validation
     * - Historical depth assessment for reliability evaluation
     * - Buffer application documentation for risk management
     * - Enables traceability from customer orders to agricultural planning basis
     */
    protected function getYieldSourceInfo(array $seedRequirements): array
    {
        $yieldSources = [];

        foreach ($seedRequirements as $seedEntryId => $requirement) {
            $seedEntry = $requirement['seed_entry'];
            $recipe = Recipe::where('seed_entry_id', $seedEntry->id)
                ->where('is_active', true)
                ->first();

            if ($recipe) {
                $stats = $this->yieldCalculator->getYieldStats($recipe);
                $yieldSources[$seedEntry->common_name] = [
                    'harvest_count' => $stats['harvest_count'],
                    'weighted_yield' => $stats['weighted_yield'],
                    'recipe_expected' => $stats['recipe_expected'],
                    'recommendation' => $stats['recommendation'],
                    'buffer_percentage' => $recipe->buffer_percentage,
                ];
            }
        }

        return $yieldSources;
    }
}
