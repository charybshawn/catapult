<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductMix;
use App\Models\SeedEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Weekly agricultural tray requirement calculator for production planning.
 * 
 * Aggregates customer orders by week to determine total tray requirements for each
 * seed variety. Essential for weekly production planning, growing space allocation,
 * and resource scheduling in microgreens agricultural operations.
 *
 * @business_domain Weekly production planning and resource allocation
 * @agricultural_concept Converts customer demand into growing infrastructure requirements
 * @planning_scope Weekly batch planning for optimal resource utilization
 * @integration_point Links customer orders to agricultural production capacity
 * 
 * @core_functionality
 * - **Variety Aggregation:** Combines orders to show total trays needed per seed variety
 * - **Mix Calculations:** Handles complex product mixes with percentage distributions
 * - **Weekly Batching:** Groups requirements by calendar week for batch production
 * - **Resource Planning:** Provides infrastructure requirements for growing space
 * 
 * @agricultural_workflow
 * 1. Identify all orders within specified week timeframe
 * 2. Process each order item for variety-specific tray requirements
 * 3. Handle product mixes with proportional variety calculations
 * 4. Aggregate total tray counts by seed variety across all orders
 * 5. Generate comprehensive summary for production planning
 * 
 * @business_benefits
 * - **Efficient Batching:** Group similar varieties for optimal growing cycles
 * - **Resource Planning:** Determine growing space and infrastructure needs
 * - **Seed Procurement:** Calculate seed quantities needed for weekly production
 * - **Labor Scheduling:** Plan planting and harvest activities based on tray counts
 * 
 * @related_services CropPlanCalculatorService, OrderPlanningService, CropPlanAggregationService
 * @related_models Order, OrderItem, ProductMix, SeedEntry, Recipe
 * @filament_integration Used by weekly planning interfaces and crop planning dashboards
 */
    /**
     * Calculate aggregate tray requirements by seed variety for weekly production planning.
     * 
     * Analyzes all customer orders within specified week to determine total growing
     * trays needed for each seed variety. Handles both single-variety products and
     * complex mixes with proportional calculations for accurate resource planning.
     *
     * @param Carbon $weekStart Start of production week for order aggregation
     * @return array<int, int> Tray requirements keyed by seed_entry_id [variety_id => total_trays_needed]
     * 
     * @agricultural_workflow
     * **Week Boundary Definition:**
     * - Uses weekStart to weekEnd (endOfWeek()) for complete week coverage
     * - Captures all orders with delivery dates within the weekly timeframe
     * - Excludes cancelled orders to avoid over-allocation of resources
     * 
     * **Order Processing:**
     * - Eager loads relationships to prevent N+1 queries during aggregation
     * - Processes each order item for variety-specific calculations
     * - Accumulates tray requirements across all orders in the week
     * 
     * **Variety Calculation Logic:**
     * 
     * **Single Variety Items:**
     * - Direct mapping through recipe.seed_entry_id relationship
     * - Simple 1:1 relationship between order item quantity and tray count
     * - Straightforward aggregation by variety identifier
     * 
     * **Product Mix Items:**
     * - Delegates to calculateMixTrays() for complex percentage calculations
     * - Each mix component contributes proportional trays to variety totals
     * - Mix percentages determine how total trays split across varieties
     * 
     * @business_value
     * **Production Planning:**
     * - Determines exact growing capacity needed for weekly demand
     * - Enables efficient batch planting of similar varieties
     * - Supports growing space allocation and infrastructure planning
     * 
     * **Resource Optimization:**
     * - Aggregated view reduces redundant calculations
     * - Week-based batching optimizes growing cycles and labor
     * - Variety totals support seed procurement and inventory planning
     * 
     * @data_relationships
     * **Required Relationships:**
     * - Order → OrderItems (items) for product breakdown
     * - OrderItem → Recipe → SeedEntry for variety identification
     * - OrderItem → ProductMix for complex mix calculations
     * 
     * @agricultural_context
     * **Why Weekly Planning:**
     * - Microgreens have predictable growing cycles (7-14 days)
     * - Weekly batching aligns with agricultural production rhythms
     * - Enables efficient use of growing space and labor scheduling
     * - Supports customer delivery schedule coordination
     * 
     * @performance_considerations
     * - Single database query with eager loading for all week orders
     * - In-memory aggregation after data loading for efficiency
     * - Relationship preloading prevents N+1 query problems
     * 
     * @usage_examples
     * ```php
     * // Calculate current week tray requirements
     * $thisWeek = Carbon::now()->startOfWeek();
     * $varietyTrays = $calculator->calculateWeeklyTrays($thisWeek);
     * 
     * // Plan next week production
     * $nextWeek = Carbon::now()->addWeek()->startOfWeek();
     * $requirements = $calculator->calculateWeeklyTrays($nextWeek);
     * ```
     */
    public function calculateWeeklyTrays(Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->endOfWeek();
        
        // Get all orders for the week
        $orders = Order::whereBetween('delivery_date', [$weekStart, $weekEnd])
            ->where('status', '!=', 'cancelled')
            ->with(['items.recipe.seedEntry', 'items.productMix'])
            ->get();
            
        $varietyTrays = [];
        
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->product_mix_id) {
                    // Handle mix items
                    $mix = $item->productMix;
                    $mixTrays = $this->calculateMixTrays($mix, $item->quantity);
                    
                    foreach ($mixTrays as $varietyId => $trays) {
                        $varietyTrays[$varietyId] = ($varietyTrays[$varietyId] ?? 0) + $trays;
                    }
                } else {
                    // Handle single variety items
                    $varietyId = $item->recipe->seed_entry_id;
                    $varietyTrays[$varietyId] = ($varietyTrays[$varietyId] ?? 0) + $item->quantity;
                }
            }
        }
        
        return $varietyTrays;
    }
    
    /**
     * Calculate proportional tray distribution for product mix varieties.
     * 
     * Distributes total tray requirement across multiple seed varieties based on
     * product mix percentage configurations. Essential for complex products that
     * combine multiple varieties in specific proportions.
     *
     * @param ProductMix $mix Product mix with variety percentages and configurations
     * @param int $totalTrays Total trays ordered for this mix product
     * @return array<int, int> Variety tray distribution [variety_id => trays_needed]
     * 
     * @agricultural_delegation
     * Delegates to ProductMix.calculateVarietyTrays() method which contains the
     * business logic for percentage-based tray distribution calculations.
     * 
     * **Mix Calculation Logic (in ProductMix model):**
     * ```php
     * variety_trays = total_trays × (variety_percentage / 100)
     * ```
     * 
     * @product_mix_context
     * **Agricultural Mix Products:**
     * - "Spring Mix" = 40% Arugula + 30% Lettuce + 30% Spinach
     * - "Spicy Blend" = 50% Radish + 30% Mustard + 20% Wasabi
     * - Complex combinations requiring proportional growing
     * 
     * **Percentage Accuracy:**
     * - Mix percentages must total 100% for agricultural accuracy
     * - Each variety gets proportional share of total tray allocation
     * - Rounding handled by ProductMix model for consistency
     * 
     * @business_rationale
     * **Why Delegate to Model:**
     * - ProductMix model contains variety percentage data
     * - Encapsulates mix-specific calculation business rules
     * - Maintains consistency across all mix calculations
     * - Simplifies testing and validation of mix logic
     * 
     * **Agricultural Planning Impact:**
     * - Each variety in mix requires separate growing trays
     * - Different varieties may have different growing parameters
     * - Proportional planning ensures correct mix composition
     * - Supports complex product offerings with simple interface
     * 
     * @calculation_example
     * ```php
     * // Spring Mix: 10 total trays ordered
     * // Arugula: 40% = 4 trays
     * // Lettuce: 30% = 3 trays  
     * // Spinach: 30% = 3 trays
     * $mixTrays = $this->calculateMixTrays($springMix, 10);
     * // Returns: [arugula_id => 4, lettuce_id => 3, spinach_id => 3]
     * ```
     * 
     * @integration_usage
     * Called by calculateWeeklyTrays() when processing order items with
     * product_mix_id to distribute tray requirements across mix components.
     */
    protected function calculateMixTrays(ProductMix $mix, int $totalTrays): array
    {
        return $mix->calculateVarietyTrays($totalTrays);
    }
    
    /**
     * Generate comprehensive weekly tray summary with variety details and metadata.
     * 
     * Creates detailed breakdown of tray requirements including variety names,
     * crop types, and growing information for comprehensive production planning
     * and agricultural operations management.
     *
     * @param Carbon $weekStart Start of production week for summary generation
     * @return Collection<array> Detailed variety requirements with metadata [
     *     [
     *         'variety_id' => int, Seed entry identifier for database relationships
     *         'variety_name' => string, Human-readable variety name for planning
     *         'trays_needed' => int, Total trays required for this variety
     *         'crop_type' => string Crop type classification (leafy, brassica, etc.)
     *     ], ...
     * ] Sorted alphabetically by variety name for easy review
     * 
     * @agricultural_enhancement
     * **Enriched Planning Data:**
     * - Combines raw tray calculations with agricultural metadata
     * - Adds variety names for human-readable planning documents
     * - Includes crop type information for growing condition grouping
     * - Alphabetical sorting for systematic planning review
     * 
     * **Business Intelligence:**
     * - Variety names enable clear communication with growing staff
     * - Crop types support grouping by growing conditions (temperature, light)
     * - Tray counts provide direct resource allocation numbers
     * - Structured format supports export to planning tools
     * 
     * @data_enrichment_process
     * 1. **Calculate Base Requirements:** Uses calculateWeeklyTrays() for variety totals
     * 2. **Lookup Variety Details:** Queries SeedEntry for each variety ID
     * 3. **Extract Planning Metadata:** Pulls name and crop type for operations
     * 4. **Structure for Planning:** Formats as collection with consistent schema
     * 5. **Sort for Usability:** Alphabetical order for systematic review
     * 
     * @error_handling
     * **Missing Variety Data:**
     * - Handles missing SeedEntry records gracefully with 'Unknown' defaults
     * - Maintains planning capability even with incomplete seed catalog
     * - Provides variety_id for manual lookup and correction
     * 
     * @agricultural_grouping
     * **Crop Type Categories:**
     * - **Brassicas:** Arugula, Kale, Broccoli (similar growing conditions)
     * - **Leafy Greens:** Lettuce, Spinach, Chard (shared requirements)
     * - **Root Vegetables:** Radish, Beet (different growing parameters)
     * - **Herbs:** Basil, Cilantro (specialized conditions)
     * 
     * @usage_applications
     * **Production Planning:**
     * - Generate weekly planting schedules with variety details
     * - Create growing space allocation plans by crop type
     * - Export to spreadsheets for manual planning workflows
     * 
     * **Operational Communication:**
     * - Provide clear variety names for growing staff assignments
     * - Support crop type grouping for efficient growing arrangements
     * - Enable systematic review of weekly production requirements
     * 
     * @performance_optimization
     * **Efficient Data Loading:**
     * - Leverages calculateWeeklyTrays() aggregation for base data
     * - Single SeedEntry lookup per unique variety (not per order)
     * - In-memory processing after initial database queries
     * 
     * @integration_examples
     * ```php
     * // Generate weekly planning report
     * $weekStart = Carbon::parse('2024-01-15')->startOfWeek();
     * $summary = $calculator->getWeeklySummary($weekStart);
     * 
     * // Export to production planning spreadsheet
     * $planningData = $summary->toArray();
     * 
     * // Group by crop type for growing area assignment
     * $byCropType = $summary->groupBy('crop_type');
     * ```
     */
    public function getWeeklySummary(Carbon $weekStart): Collection
    {
        $varietyTrays = $this->calculateWeeklyTrays($weekStart);
        
        return collect($varietyTrays)->map(function ($trays, $varietyId) {
            $seedEntry = SeedEntry::find($varietyId);
            return [
                'variety_id' => $varietyId,
                'variety_name' => $seedEntry ? $seedEntry->name : 'Unknown',
                'trays_needed' => $trays,
                'crop_type' => $seedEntry ? $seedEntry->crop_type : 'Unknown',
            ];
        })->sortBy('variety_name');
    }
} 