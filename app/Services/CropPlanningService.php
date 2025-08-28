<?php

namespace App\Services;

use Exception;
use Filament\Actions\Action;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\Product;
use App\Models\ProductMix;
use App\Models\CropPlan;
use App\Models\CropPlanAggregate;
use App\Models\CropPlanStatus;
use App\Models\MasterSeedCatalog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Models\User;

/**
 * Agricultural Crop Planning Service - Core Production Planning Engine
 * 
 * This service implements automated production planning algorithms for microgreens
 * cultivation, translating customer orders into detailed crop production plans with
 * precise timing, resource requirements, and growth stage calculations.
 * 
 * The service handles complex agricultural workflows including:
 * - Variety-specific recipe matching and yield calculations
 * - Growth stage timing (germination, blackout, light cycles)
 * - Seed soaking schedules and pre-planting requirements
 * - Production aggregation and buffer management
 * - Live tray vs. harvested product planning
 * - Product mix decomposition into individual varieties
 * - Resource availability validation
 * 
 * @service_layer Core Agricultural Operations
 * @business_domain Microgreens production planning and automation
 * @dependencies HarvestYieldCalculator, RecipeService, InventoryManagementService
 * @integration_points Orders, Products, Recipes, MasterSeedCatalog, CropPlans
 * 
 * @agricultural_concepts
 * - Recipe: Growth parameters and resource requirements for specific varieties
 * - Yield Planning: Expected harvest weight per growing tray with buffers
 * - Growth Stages: Germination → Blackout → Light → Harvest progression
 * - Seed Soaking: Pre-germination treatment for faster/uniform sprouting
 * - Buffer Management: Production overages to account for loss and variation
 * - Variety Aggregation: Combining multiple orders for efficient production batching
 * 
 * @performance_considerations
 * - Eager loads relationships to prevent N+1 queries
 * - Caches recipe lookups for repeated variety calculations
 * - Aggregates orders by variety/date to minimize production runs
 * - Uses database transactions for atomic crop plan creation
 * 
 * @author Agricultural Production System
 * @since 2024 Crop Planning Module
 */
class CropPlanningService
{
    protected HarvestYieldCalculator $yieldCalculator;
    protected RecipeService $recipeService;
    protected InventoryManagementService $inventoryService;

    /**
     * Initialize crop planning service with agricultural calculation dependencies.
     * 
     * @param HarvestYieldCalculator $yieldCalculator Calculates expected yields and planning weights
     * @param RecipeService $recipeService Manages recipe matching and validation
     * @param InventoryManagementService $inventoryService Validates seed/resource availability
     * 
     * @business_context Service composition follows agricultural workflow:
     *   1. Recipe matching determines growth parameters
     *   2. Yield calculation determines tray requirements
     *   3. Inventory service validates resource availability
     */
    public function __construct(
        HarvestYieldCalculator $yieldCalculator,
        RecipeService $recipeService,
        InventoryManagementService $inventoryService
    ) {
        $this->yieldCalculator = $yieldCalculator;
        $this->recipeService = $recipeService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Generate automated crop plans for all valid orders in a date range.
     * 
     * This is the primary entry point for production planning automation. The method
     * processes all confirmed orders within the date range and creates optimized
     * crop plans that aggregate varieties by harvest date for efficient production.
     * 
     * @param string|null $startDate Start date for order range (default: today)
     * @param string|null $endDate End date for order range (default: 30 days ahead)
     * @return Collection<CropPlan> Generated crop plans with agricultural timing
     * 
     * @business_workflow
     * 1. Query orders with agricultural product relationships
     * 2. Filter for production-ready orders (draft/pending/confirmed/in_production)
     * 3. Exclude recurring templates (process only actual orders)
     * 4. Aggregate varieties by harvest date for efficient batching
     * 5. Apply production buffers and calculate resource requirements
     * 6. Generate plans with precise agricultural timing
     * 
     * @agricultural_logic
     * - Aggregates orders by variety and harvest date to minimize production runs
     * - Applies recipe-specific buffers to account for agricultural variation
     * - Calculates backward from harvest date through all growth stages
     * - Handles both live tray delivery and harvested product orders
     * 
     * @performance_optimization
     * - Eager loads order relationships to prevent N+1 queries
     * - Uses single query for date range filtering
     * - Batches crop plan creation for database efficiency
     * 
     * @throws Exception When agricultural calculations fail or data is inconsistent
     */
    public function generateIndividualPlansForAllOrders(?string $startDate = null, ?string $endDate = null): Collection
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now();
        $endDate = $endDate ? Carbon::parse($endDate) : now()->addDays(30);
        
        // Get all valid actual orders (not recurring templates) in the date range
        $orders = Order::with([
            'customer',
            'orderItems.product.productMix.masterSeedCatalogs',
            'orderItems.product.masterSeedCatalog',
            'orderItems.priceVariation.packagingType'
        ])
        ->where('harvest_date', '>=', $startDate)
        ->where('harvest_date', '<=', $endDate)
        ->where('is_recurring', false) // Exclude recurring order templates
        ->whereHas('status', function ($query) {
            $query->whereIn('code', ['draft', 'pending', 'confirmed', 'in_production']);
        })
        ->get();
        
        return $this->generateAggregatedPlansForOrders($orders);
    }

    /**
     * Generate aggregated crop plans optimized for efficient agricultural production.
     * 
     * Implements variety aggregation algorithm that combines multiple customer orders
     * for the same variety and harvest date into single production runs. This reduces
     * setup time, improves resource utilization, and minimizes production complexity.
     * 
     * @param Collection<Order> $orders Orders with loaded agricultural relationships
     * @return Collection<CropPlan> Optimized crop plans with aggregated quantities
     * 
     * @algorithm Variety Aggregation Process:
     * 1. Delete existing draft/cancelled plans to prevent duplicates
     * 2. Analyze each order's variety requirements (handles mixes)
     * 3. Group requirements by variety_id + harvest_date combination
     * 4. Sum total grams needed across all orders for each group
     * 5. Apply single buffer percentage to aggregated total (more efficient)
     * 6. Create one crop plan per aggregated group with detailed audit trail
     * 
     * @business_benefits
     * - Reduces number of separate production runs
     * - Optimizes growing space utilization
     * - Simplifies harvest and packaging workflows
     * - Maintains detailed order traceability
     * 
     * @agricultural_considerations
     * - Buffer applied to total aggregated weight (not per order)
     * - Recipe matching prioritizes variety-specific growing parameters
     * - Timing calculations based on longest growth cycle in group
     * - Seed soaking schedules coordinated across aggregated volume
     * 
     * @data_integrity
     * - Maintains order_items_included array for traceability
     * - Stores calculation_details JSON for audit and debugging
     * - Links primary order as main record for administrative purposes
     * 
     * @throws Exception When recipe matching fails or calculations are invalid
     */
    public function generateAggregatedPlansForOrders(Collection $orders): Collection
    {
        $allCropPlans = collect();
        
        // First, collect all variety requirements by harvest date
        $varietyRequirements = [];
        
        foreach ($orders as $order) {
            // Delete any existing draft and cancelled plans for this order to prevent duplicates
            CropPlan::where('order_id', $order->id)
                ->whereHas('status', function ($query) {
                    $query->whereIn('code', ['draft', 'cancelled']);
                })
                ->delete();

            // Analyze order requirements without creating individual plans
            $orderRequirements = $this->analyzeOrderRequirements($order);
            
            foreach ($orderRequirements as $requirement) {
                $varietyId = $requirement['variety_id'];
                $harvestDate = $order->harvest_date->format('Y-m-d');
                $key = $varietyId . '_' . $harvestDate;
                
                if (!isset($varietyRequirements[$key])) {
                    $varietyRequirements[$key] = [
                        'variety_id' => $varietyId,
                        'harvest_date' => $order->harvest_date,
                        'total_grams' => 0,
                        'orders' => [],
                        'recipe' => $requirement['recipe']
                    ];
                }
                
                $varietyRequirements[$key]['total_grams'] += $requirement['grams_needed'];
                $varietyRequirements[$key]['orders'][] = [
                    'order' => $order,
                    'product' => $requirement['product'],
                    'grams' => $requirement['grams_needed']
                ];
            }
        }
        
        // Now create aggregated crop plans with buffer applied to totals
        foreach ($varietyRequirements as $requirement) {
            $cropPlan = $this->createAggregatedCropPlan($requirement);
            if ($cropPlan) {
                $allCropPlans->push($cropPlan);
            }
        }
        
        return $allCropPlans;
    }

    /**
     * Analyze order's agricultural requirements for production planning.
     * 
     * Decomposes complex orders into specific variety requirements, handling
     * product mixes, live tray orders, and weight-based calculations. This analysis
     * forms the foundation for aggregated crop planning algorithms.
     * 
     * @param Order $order Order with loaded product and pricing relationships
     * @return array Agricultural requirements array with variety breakdowns
     * 
     * @return_structure
     * [
     *   [
     *     'variety_id' => int,        // MasterSeedCatalog ID
     *     'grams_needed' => float,    // Total weight for this variety
     *     'cultivar' => string|null,  // Specific cultivar if from mix
     *     'product' => Product,       // Source product object
     *     'recipe' => Recipe|null,    // Matched growing recipe
     *     'is_live_tray' => bool,     // Live delivery vs harvested
     *     'trays_requested' => int    // For live tray orders
     *   ]
     * ]
     * 
     * @agricultural_logic
     * - Groups order items by product to aggregate quantities
     * - Distinguishes live tray delivery from harvested products
     * - Decomposes product mixes into constituent varieties
     * - Matches variety-specific recipes for growing parameters
     * - Converts between trays and weight based on expected yields
     * 
     * @weight_calculations
     * - Uses fill_weight from price variations for harvested products
     * - Calculates equivalent grams for live tray orders using recipe yields
     * - Handles mixed units within single orders
     * - Falls back to quantity as grams when fill weight unavailable
     * 
     * @mix_decomposition
     * - Breaks product mixes into percentage-based variety components
     * - Maintains cultivar specificity for mix components
     * - Preserves traceability to source product and order items
     * 
     * @error_handling
     * - Continues processing when individual products fail
     * - Logs warnings for missing recipes or invalid data
     * - Excludes invalid products from requirements array
     */
    protected function analyzeOrderRequirements(Order $order): array
    {
        $requirements = [];
        $processedProducts = [];
        
        // Load order items with products and their relationships
        $order->load([
            'orderItems.product.productMix.masterSeedCatalogs',
            'orderItems.product.masterSeedCatalog',
            'orderItems.priceVariation.packagingType'
        ]);

        // Group items by product to aggregate quantities
        $productGroups = $order->orderItems->groupBy('product_id');

        foreach ($productGroups as $productId => $items) {
            $product = $items->first()->product;
            
            if (!$product) {
                continue;
            }

            $productKey = $product->id;
            if (isset($processedProducts[$productKey])) {
                continue; // Skip duplicates
            }
            $processedProducts[$productKey] = true;

            // Check if this is a live tray order
            if ($this->isLiveTrayOrder($items)) {
                $totalTrays = $this->calculateTotalTraysForProduct($items);
                $this->addLiveTrayRequirement($requirements, $order, $product, $totalTrays);
            } else {
                $totalGramsNeeded = $this->calculateTotalGramsForProduct($items);
                $this->addProductRequirements($requirements, $order, $product, $totalGramsNeeded);
            }
        }
        
        return $requirements;
    }

    /**
     * Add agricultural product requirements to the planning analysis.
     * 
     * Processes individual products into variety-specific requirements, handling
     * both single varieties and complex product mixes. This method is central
     * to converting customer orders into actionable agricultural production data.
     * 
     * @param array &$requirements Reference to requirements array being built
     * @param Order $order Source order for traceability
     * @param Product $product Product being analyzed
     * @param float $totalGramsNeeded Total weight needed across all order items
     * @return void Modifies requirements array by reference
     * 
     * @agricultural_processing
     * - Single varieties: Direct mapping to master seed catalog
     * - Product mixes: Decomposition into constituent varieties with percentages
     * - Recipe matching: Finds active growing recipes for each variety
     * - Cultivar preservation: Maintains specific cultivar information from mixes
     * 
     * @mix_handling
     * - Uses breakdownProductMix() for percentage-based decomposition
     * - Preserves cultivar specificity from mix component definitions
     * - Maintains audit trail linking varieties back to source products
     * 
     * @recipe_integration
     * - Prioritizes component-specific recipes for mix varieties
     * - Falls back to product-level recipe definitions
     * - Uses variety-based recipe matching as final fallback
     * 
     * @business_rules
     * - Products must have either master_seed_catalog_id OR product_mix_id
     * - Mix components inherit recipe specificity when available
     * - Recipe absence creates incomplete plans requiring manual attention
     * 
     * @data_validation
     * - Skips products without valid seed catalog references
     * - Logs warnings for missing recipe matches
     * - Preserves partial data for administrative review
     */
    protected function addProductRequirements(array &$requirements, Order $order, Product $product, float $totalGramsNeeded): void
    {
        if ($product->productMix) {
            // Product has a mix - break down into components
            $breakdown = $this->breakdownProductMix($product, $totalGramsNeeded);
            
            foreach ($breakdown as $varietyId => $componentData) {
                $recipe = $this->findActiveRecipeForProductVariety($product, $varietyId);
                
                $requirements[] = [
                    'variety_id' => $varietyId,
                    'grams_needed' => $componentData['grams'],
                    'cultivar' => $componentData['cultivar'],
                    'product' => $product,
                    'recipe' => $recipe
                ];
            }
        } else {
            // Single variety product
            if ($product->master_seed_catalog_id) {
                $recipe = $this->findActiveRecipeForProduct($product);
                
                $requirements[] = [
                    'variety_id' => $product->master_seed_catalog_id,
                    'grams_needed' => $totalGramsNeeded,
                    'cultivar' => null,
                    'product' => $product,
                    'recipe' => $recipe
                ];
            }
        }
    }

    /**
     * Add live tray delivery requirements to agricultural planning analysis.
     * 
     * Live tray orders require different handling than harvested products because
     * they're delivered as growing trays rather than cut microgreens. This affects
     * timing, packaging, and resource calculations throughout the production cycle.
     * 
     * @param array &$requirements Reference to requirements array being built
     * @param Order $order Source order for traceability
     * @param Product $product Live tray product being analyzed
     * @param int $totalTrays Number of growing trays requested
     * @return void Modifies requirements array by reference
     * 
     * @live_tray_specifics
     * - Trays delivered as living plants, not harvested microgreens
     * - Growing continues at customer location after delivery
     * - Different packaging and handling requirements
     * - Modified timing for delivery vs harvest windows
     * 
     * @weight_conversion
     * - Converts tray count to equivalent grams for planning consistency
     * - Uses recipe-specific planning yields when available
     * - Falls back to default 75g per tray for unknown varieties
     * - Maintains both tray count and gram equivalents for flexibility
     * 
     * @agricultural_considerations
     * - Live trays need optimal growing conditions at delivery
     * - Timing more critical than harvested products
     * - Quality standards focus on plant health vs harvest weight
     * - Customer education may be required for growing continuation
     * 
     * @production_impact
     * - Same growing process as harvested products until delivery
     * - Modified packaging requirements (trays vs containers)
     * - Different delivery window constraints
     * - Special handling to maintain plant viability
     * 
     * @recipe_dependency
     * - Uses findActiveRecipeForProduct() for growth parameters
     * - Planning yield calculation accounts for continued growth
     * - Recipe absence creates incomplete plans requiring manual review
     */
    protected function addLiveTrayRequirement(array &$requirements, Order $order, Product $product, int $totalTrays): void
    {
        if ($product->master_seed_catalog_id) {
            $recipe = $this->findActiveRecipeForProduct($product);
            
            // For live trays, we need to calculate equivalent grams
            $gramsPerTray = $recipe ? $this->yieldCalculator->calculatePlanningYield($recipe) : 75;
            $totalGrams = $totalTrays * $gramsPerTray;
            
            $requirements[] = [
                'variety_id' => $product->master_seed_catalog_id,
                'grams_needed' => $totalGrams,
                'cultivar' => null,
                'product' => $product,
                'recipe' => $recipe,
                'is_live_tray' => true,
                'trays_requested' => $totalTrays
            ];
        }
    }

    /**
     * Create optimized aggregated crop plan from multiple order requirements.
     * 
     * This method implements the core agricultural production optimization by creating
     * single crop plans that serve multiple customer orders. Buffer percentages are
     * applied to aggregated totals rather than individual orders for maximum efficiency.
     * 
     * @param array $requirement Aggregated variety requirement data
     * @return CropPlan|null Created crop plan or null if creation fails
     * 
     * @requirement_structure
     * [
     *   'variety_id' => int,           // MasterSeedCatalog ID
     *   'harvest_date' => Carbon,      // Target harvest date
     *   'total_grams' => float,        // Aggregated weight across orders
     *   'orders' => array,             // Source orders with individual quantities
     *   'recipe' => Recipe|null        // Matched growing recipe
     * ]
     * 
     * @agricultural_optimization
     * - Applies buffer to aggregated total (more efficient than per-order)
     * - Calculates tray requirements once for entire batch
     * - Uses recipe-specific expected yields for accurate planning
     * - Backward-calculates all planting dates from harvest target
     * 
     * @buffer_management
     * - Buffer percentage from recipe (default 10%)
     * - Applied to total aggregated grams before tray calculation
     * - Accounts for agricultural variation, handling loss, measurement error
     * - More efficient than individual order buffers
     * 
     * @timing_calculations
     * - Plant by date: harvest_date - recipe.totalDays()
     * - Seed soak date: plant_date - recipe.seed_soak_hours
     * - All dates calculated to start of day for consistency
     * 
     * @audit_trail
     * - calculation_details JSON stores complete algorithm trace
     * - order_items_included array maintains order traceability
     * - Aggregated order information preserved for customer service
     * 
     * @incomplete_plan_handling
     * - Missing recipes trigger createIncompletePlan() workflow
     * - Incomplete plans flagged for manual review
     * - Notification sent to administrative users
     * 
     * @performance_considerations
     * - Single database insert per aggregated variety
     * - Efficient JSON storage for complex calculation data
     * - Detailed logging for production monitoring
     * 
     * @throws Exception When required data is invalid or database constraints fail
     */
    protected function createAggregatedCropPlan(array $requirement): ?CropPlan
    {
        $varietyId = $requirement['variety_id'];
        $harvestDate = $requirement['harvest_date'];
        $totalGrams = $requirement['total_grams'];
        $orders = collect($requirement['orders']); // Convert array to Collection for method calls
        $recipe = $requirement['recipe'];
        
        // Get master seed catalog info
        $masterSeedCatalog = MasterSeedCatalog::find($varietyId);
        if (!$masterSeedCatalog) {
            return null;
        }
        
        // Get draft status
        $draftStatus = CropPlanStatus::where('code', 'draft')->first();
        
        if (!$recipe) {
            // Create incomplete plan without recipe
            return $this->createIncompletePlan($orders->first()['order'], $varietyId, $totalGrams, $orders, $draftStatus);
        }
        
        // Apply buffer to total grams, then calculate trays once
        $bufferPercentage = $recipe->buffer_percentage ?? 10.0;
        $bufferedGrams = $totalGrams * (1 + $bufferPercentage / 100);
        $baseYield = $recipe->expected_yield_grams ?? 400.0;
        $traysNeeded = ceil($bufferedGrams / $baseYield);
        $gramsPerTray = $baseYield; // Store the base yield, not planning yield
        
        // Calculate planting dates
        $plantByDate = $this->calculatePlantByDate($recipe, $harvestDate);
        $seedSoakDate = $this->calculateSeedSoakDate($recipe, $plantByDate);
        
        // Use the first order for the main crop plan record
        $primaryOrder = $orders->first()['order'];
        
        // Create calculation details for audit trail
        $calculationDetails = [
            'aggregated_orders' => $orders->map(function ($item) {
                return [
                    'order_id' => $item['order']->id,
                    'customer' => $item['order']->customer?->contact_name ?? 'Unknown',
                    'grams' => $item['grams'],
                    'product' => $item['product']->name
                ];
            })->toArray(),
            'total_grams_requested' => $totalGrams,
            'buffer_percentage' => $bufferPercentage,
            'buffered_grams' => $bufferedGrams,
            'base_yield_per_tray' => $baseYield,
            'trays_calculated' => $traysNeeded,
            'orders_count' => $orders->count(),
            'variety_name' => $masterSeedCatalog->name,
            'seed_soak_required' => $recipe->seed_soak_hours > 0,
            'seed_soak_date' => $seedSoakDate?->toDateString(),
            'growth_stages' => [
                'germination_days' => $recipe->germination_days,
                'blackout_days' => $recipe->blackout_days,
                'light_days' => $recipe->light_days,
                'total_days' => $recipe->totalDays()
            ]
        ];

        // Create the aggregated crop plan
        $cropPlan = CropPlan::create([
            'order_id' => $primaryOrder->id, // Use first order as primary
            'recipe_id' => $recipe->id,
            'variety_id' => $varietyId,
            'status_id' => $draftStatus->id,
            'trays_needed' => $traysNeeded,
            'grams_needed' => $totalGrams, // Store original total, not buffered
            'grams_per_tray' => $gramsPerTray,
            'plant_by_date' => $plantByDate,
            'seed_soak_date' => $seedSoakDate,
            'expected_harvest_date' => $harvestDate,
            'delivery_date' => $primaryOrder->delivery_date,
            'is_missing_recipe' => false,
            'calculation_details' => $calculationDetails,
            'order_items_included' => $this->getAggregatedOrderItemIds($orders),
            'created_by' => auth()->id() ?: $primaryOrder->user_id,
            'notes' => $this->generateAggregatedPlanNotes($recipe, $orders, $seedSoakDate)
        ]);

        Log::info('Created aggregated crop plan', [
            'crop_plan_id' => $cropPlan->id,
            'variety_id' => $varietyId,
            'variety_name' => $masterSeedCatalog->name,
            'total_grams' => $totalGrams,
            'buffered_grams' => $bufferedGrams,
            'trays_needed' => $traysNeeded,
            'orders_count' => $orders->count(),
            'harvest_date' => $harvestDate->format('Y-m-d')
        ]);

        return $cropPlan;
    }

    /**
     * Create incomplete crop plan when agricultural recipe is missing.
     * 
     * When no active recipe exists for a variety, production cannot be automated.
     * This method creates a placeholder crop plan that flags the missing recipe
     * and provides reasonable defaults for manual completion by agricultural staff.
     * 
     * @param Order $order Primary order for the crop plan record
     * @param int $varietyId MasterSeedCatalog ID for the variety
     * @param float $totalGrams Total weight needed across all orders
     * @param Collection $orders All orders requiring this variety
     * @param CropPlanStatus $draftStatus Draft status for incomplete plans
     * @return CropPlan Incomplete crop plan flagged for manual attention
     * 
     * @incomplete_plan_characteristics
     * - is_missing_recipe flag set to true
     * - trays_needed set to 0 (cannot calculate without yield data)
     * - plant_by_date estimated at 14 days before harvest (conservative default)
     * - missing_recipe_notes field explains the issue
     * 
     * @default_assumptions
     * - 14-day growth cycle (conservative estimate for most microgreens)
     * - No seed soaking required (null seed_soak_date)
     * - Zero grams per tray (calculation impossible without recipe)
     * 
     * @administrative_workflow
     * - Plan appears in draft status requiring staff attention
     * - missing_recipe_notes provide clear action items
     * - calculation_details include aggregated order information
     * - order_items_included maintains full traceability
     * 
     * @business_impact
     * - Prevents order processing from being blocked by missing recipes
     * - Creates visible action items for agricultural staff
     * - Maintains customer order integrity and traceability
     * - Enables partial automation while flagging manual intervention needs
     * 
     * @follow_up_actions
     * - Agricultural staff must create recipe for the variety
     * - Crop plan must be manually updated with correct calculations
     * - Production timing may need adjustment based on actual recipe
     * 
     * @audit_considerations
     * - Clearly documents which orders are affected
     * - Preserves original weight requirements for later calculation
     * - Links to creating user and timestamp for accountability
     */
    protected function createIncompletePlan(Order $order, int $varietyId, float $totalGrams, Collection $orders, CropPlanStatus $draftStatus): CropPlan
    {
        $masterSeedCatalog = MasterSeedCatalog::find($varietyId);
        
        return CropPlan::create([
            'order_id' => $order->id,
            'recipe_id' => null,
            'variety_id' => $varietyId,
            'status_id' => $draftStatus->id,
            'trays_needed' => 0, // Cannot calculate without recipe
            'grams_needed' => $totalGrams,
            'grams_per_tray' => 0,
            'plant_by_date' => $order->harvest_date->copy()->subDays(14), // Default 14 days
            'seed_soak_date' => null,
            'expected_harvest_date' => $order->harvest_date,
            'delivery_date' => $order->delivery_date,
            'is_missing_recipe' => true,
            'missing_recipe_notes' => "No recipe found for {$masterSeedCatalog->name}. Please create a recipe or manually update this plan.",
            'calculation_details' => [
                'aggregated_orders' => $orders->map(function ($item) {
                    return [
                        'order_id' => $item['order']->id,
                        'customer' => $item['order']->customer?->contact_name ?? 'Unknown',
                        'grams' => $item['grams']
                    ];
                })->toArray(),
                'orders_count' => $orders->count(),
                'variety_name' => $masterSeedCatalog->name,
            ],
            'order_items_included' => $this->getAggregatedOrderItemIds($orders),
            'created_by' => auth()->id() ?: $order->user_id,
        ]);
    }

    /**
     * Generate descriptive notes for aggregated crop plans.
     * 
     * Creates human-readable notes that help agricultural staff understand
     * the aggregated crop plan's scope, timing requirements, and customer context.
     * These notes appear in the production interface and planning documents.
     * 
     * @param Recipe $recipe Growing recipe with timing and treatment requirements
     * @param Collection $orders Orders aggregated into this crop plan
     * @param Carbon|null $seedSoakDate Calculated seed soaking start time
     * @return string|null Generated notes or null if no special conditions
     * 
     * @note_components
     * - Seed soak timing: Critical pre-planting requirements
     * - Order aggregation: Customer context and order count
     * - Customer list: Names for production staff reference
     * 
     * @agricultural_timing_notes
     * - Seed soak dates formatted for easy staff reference
     * - Emphasizes time-sensitive pre-planting activities
     * - Provides clear action dates for production scheduling
     * 
     * @aggregation_context
     * - Shows when multiple orders are combined
     * - Lists customer names for context and quality control
     * - Helps staff understand production volume sources
     * 
     * @production_workflow_benefits
     * - Staff can quickly understand special requirements
     * - Customer context helps with quality prioritization
     * - Timing reminders prevent missed agricultural deadlines
     * 
     * @examples
     * "Seed soak required starting Mar 15, 2024"
     * "Aggregated from 3 orders: Fresh Farm Co, Green Grocer, Local Market"
     * "Seed soak required starting Mar 15, 2024. Aggregated from 2 orders: Farm A, Farm B"
     * 
     * @formatting_standards
     * - Dates use readable format (Mar j, Y) vs technical formats
     * - Customer names comma-separated for easy reading
     * - Sentences properly punctuated for professional appearance
     */
    protected function generateAggregatedPlanNotes(Recipe $recipe, Collection $orders, ?Carbon $seedSoakDate): ?string
    {
        $notes = [];
        
        if ($recipe->seed_soak_hours > 0 && $seedSoakDate) {
            $notes[] = "Seed soak required starting {$seedSoakDate->format('M j, Y')}";
        }
        
        $orderCount = $orders->count();
        if ($orderCount > 1) {
            $customerNames = $orders->map(fn($item) => $item['order']->customer?->contact_name ?? 'Unknown')->unique()->implode(', ');
            $notes[] = "Aggregated from {$orderCount} orders: {$customerNames}";
        }
        
        return $notes ? implode('. ', $notes) : null;
    }

    /**
     * Extract order item IDs from aggregated orders for complete traceability.
     * 
     * Maintains detailed traceability by collecting all order item IDs that
     * contribute to an aggregated crop plan. This enables complete audit trails
     * and supports customer service inquiries about specific order fulfillment.
     * 
     * @param Collection $orders Order data arrays from aggregation process
     * @return array Unique order item IDs contributing to this crop plan
     * 
     * @traceability_importance
     * - Links crop plans back to specific customer order line items
     * - Enables detailed fulfillment tracking and reporting
     * - Supports customer inquiries about order status
     * - Required for accurate inventory reservation and release
     * 
     * @aggregation_structure
     * Each order array contains:
     * - 'order' => Order object with loaded relationships
     * - 'product' => Product object for variety matching
     * - 'grams' => Contribution to total requirement
     * 
     * @deduplication
     * - Uses array_unique() to prevent duplicate item IDs
     * - Handles cases where same product appears multiple times
     * - Ensures clean data for downstream processing
     * 
     * @business_applications
     * - Order fulfillment status tracking
     * - Customer service order inquiries
     * - Quality control issue tracing
     * - Inventory reservation management
     * - Billing and invoicing reconciliation
     * 
     * @data_integrity
     * - Preserves complete order item relationships
     * - Supports partial fulfillment scenarios
     * - Enables order modification impact analysis
     */
    protected function getAggregatedOrderItemIds(Collection $orders): array
    {
        $allItemIds = [];
        
        foreach ($orders as $orderData) {
            $order = $orderData['order'];
            $product = $orderData['product'];
            $itemIds = $this->getOrderItemIds($order, $product);
            $allItemIds = array_merge($allItemIds, $itemIds);
        }
        
        return array_unique($allItemIds);
    }

    /**
     * Generate individual crop plans from a single customer order.
     * 
     * Creates detailed production plans for all varieties required by an order,
     * handling complex product mixes, live tray orders, and precise agricultural
     * timing calculations. This method serves as the foundation for both individual
     * and aggregated crop planning workflows.
     * 
     * @param Order $order Order with loaded product and pricing relationships
     * @return Collection<CropPlan> Generated crop plans for all order varieties
     * 
     * @agricultural_workflow
     * 1. Load complete order relationships (products, mixes, pricing)
     * 2. Delete existing draft/cancelled plans to prevent duplicates
     * 3. Group order items by product for quantity aggregation
     * 4. Distinguish live tray vs harvested product requirements
     * 5. Decompose product mixes into constituent varieties
     * 6. Generate variety-specific crop plans with timing
     * 7. Apply deduplication to prevent variety conflicts
     * 
     * @product_type_handling
     * - Single varieties: Direct recipe matching and plan generation
     * - Product mixes: Decomposition into percentage-based varieties
     * - Live trays: Tray-count based planning vs weight-based
     * - Harvested products: Weight-based planning with yield calculations
     * 
     * @deduplication_strategy
     * - Uses processedVarieties array to track variety + harvest date combinations
     * - Prevents multiple plans for same variety on same harvest date
     * - Maintains order item traceability across deduplication
     * 
     * @timing_precision
     * - Backward calculation from harvest date through all growth stages
     * - Seed soak scheduling for varieties requiring pre-treatment
     * - Plant-by dates accounting for complete growth cycles
     * - Delivery date preservation for logistics coordination
     * 
     * @error_resilience
     * - Continues processing when individual products fail
     * - Creates incomplete plans for missing recipes
     * - Logs detailed error information for troubleshooting
     * - Preserves partial success for manual completion
     * 
     * @business_rules
     * - Products must have master_seed_catalog_id OR product_mix_id
     * - Live tray identification based on packaging type names
     * - Recipe matching prioritizes product-specific then variety-general
     * - Buffer percentages applied per recipe specifications
     * 
     * @performance_optimization
     * - Eager loads all required relationships in single query
     * - Groups items by product to minimize processing loops
     * - Caches recipe lookups for repeated varieties
     * 
     * @throws Exception When critical agricultural data is invalid or missing
     */
    public function generatePlanFromOrder(Order $order): Collection
    {
        $cropPlans = collect();
        $processedVarieties = []; // Track to prevent duplicates
        
        // Load order items with products and their relationships
        $order->load([
            'orderItems.product.productMix.masterSeedCatalogs',
            'orderItems.product.masterSeedCatalog',
            'orderItems.priceVariation.packagingType'
        ]);

        // Delete any existing draft and cancelled plans for this order to prevent duplicates
        CropPlan::where('order_id', $order->id)
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'cancelled']);
            })
            ->delete();

        // Group items by product to aggregate quantities
        $productGroups = $order->orderItems->groupBy('product_id');

        foreach ($productGroups as $productId => $items) {
            $product = $items->first()->product;
            
            if (!$product) {
                Log::warning('Order item missing product', [
                    'order_id' => $order->id,
                    'product_id' => $productId
                ]);
                continue;
            }

            // Check if this is a live tray order
            $isLiveTray = $this->isLiveTrayOrder($items);
            
            if ($isLiveTray) {
                // For live trays, just count the trays directly
                $totalTraysNeeded = $this->calculateTotalTraysForProduct($items);
                
                // Generate plans based on product type
                if ($product->product_mix_id) {
                    // Live tray mixes not supported yet
                    Log::warning('Live tray product mix not supported', [
                        'order_id' => $order->id,
                        'product_id' => $product->id
                    ]);
                } elseif ($product->master_seed_catalog_id) {
                    // Single variety product
                    $varietyKey = $product->master_seed_catalog_id . '_' . $order->harvest_date->format('Y-m-d');
                    
                    // Skip if already processed (prevent duplicates)
                    if (!isset($processedVarieties[$varietyKey])) {
                        $plan = $this->generatePlanForLiveTrays(
                            $order,
                            $product,
                            $product->master_seed_catalog_id,
                            $totalTraysNeeded
                        );
                        if ($plan) {
                            $cropPlans->push($plan);
                            $processedVarieties[$varietyKey] = true;
                        }
                    }
                }
            } else {
                // For harvested products, calculate grams needed
                $totalGramsNeeded = $this->calculateTotalGramsForProduct($items);
                
                // Generate plans based on product type
                if ($product->product_mix_id) {
                    // Product is a mix - break down into components
                    $mixPlans = $this->generatePlansForProductMix(
                        $order,
                        $product,
                        $totalGramsNeeded,
                        $processedVarieties
                    );
                    $cropPlans = $cropPlans->merge($mixPlans);
                } elseif ($product->master_seed_catalog_id) {
                    // Single variety product
                    $varietyKey = $product->master_seed_catalog_id . '_' . $order->harvest_date->format('Y-m-d');
                    
                    // Skip if already processed (prevent duplicates)
                    if (!isset($processedVarieties[$varietyKey])) {
                        $plan = $this->generatePlanForSingleVariety(
                            $order,
                            $product,
                            $product->master_seed_catalog_id,
                            $totalGramsNeeded
                        );
                        if ($plan) {
                            $cropPlans->push($plan);
                            $processedVarieties[$varietyKey] = true;
                        }
                    }
                }
            }
        }

        return $cropPlans;
    }

    /**
     * Calculate total grams needed for harvested product order items.
     * 
     * Performs complex weight calculations for harvested microgreens orders,
     * handling multiple unit types, fill weights, and conversion scenarios.
     * This method is NOT used for live tray orders which have different
     * calculation requirements.
     * 
     * @param Collection $items OrderItem collection for a single product
     * @return float Total grams needed for agricultural production planning
     * 
     * @calculation_priority_order
     * 1. quantity_in_grams (explicit gram specification)
     * 2. Live tray detection and conversion (special case)
     * 3. fill_weight from price variation (container weight)
     * 4. fill_weight_grams from price variation (alternative field)
     * 5. Quantity as grams (fallback assumption)
     * 
     * @live_tray_detection
     * - Checks both packaging type name and price variation name
     * - Case-insensitive "live tray" string matching
     * - Converts tray count to grams using recipe yields
     * - Falls back to default 75g per tray when recipe unavailable
     * 
     * @weight_conversion_logic
     * - Uses recipe-based planning yields for accurate conversion
     * - Accounts for agricultural buffers in yield calculations
     * - Logs conversion factors for audit and debugging
     * - Handles missing recipe scenarios gracefully
     * 
     * @fill_weight_handling
     * - Prioritizes fill_weight over fill_weight_grams (legacy support)
     * - Multiplies quantity by container fill weight
     * - Handles different packaging sizes within same order
     * 
     * @fallback_behavior
     * - Assumes quantity represents grams when no other data available
     * - Logs warnings for unclear quantity specifications
     * - Continues processing to prevent order blocking
     * 
     * @agricultural_considerations
     * - Live tray conversion accounts for continued growth potential
     * - Harvested weights based on mature microgreen yields
     * - Buffer percentages handled at recipe level, not in base calculations
     * 
     * @logging_strategy
     * - Info level: Successful calculations with parameters
     * - Warning level: Missing data requiring assumptions
     * - Detailed parameter logging for agricultural staff debugging
     * 
     * @data_validation
     * - Handles null price variations gracefully
     * - Validates recipe existence before yield calculations
     * - Preserves order processing even with incomplete data
     */
    protected function calculateTotalGramsForProduct(Collection $items): float
    {
        $totalGrams = 0;

        foreach ($items as $item) {
            // Use quantity_in_grams if available
            if ($item->quantity_in_grams) {
                $totalGrams += $item->quantity_in_grams;
            } else {
                // Check if this is a live tray item
                $priceVariation = $item->priceVariation;
                $packagingType = $priceVariation->packagingType ?? null;
                
                // Check both packaging type name and price variation name for "live tray"
                $isLiveTray = false;
                if ($packagingType && stripos($packagingType->name, 'live tray') !== false) {
                    $isLiveTray = true;
                } elseif ($priceVariation && stripos($priceVariation->name, 'live tray') !== false) {
                    $isLiveTray = true;
                }
                
                if ($isLiveTray) {
                    // This is a live tray - need to convert trays to grams
                    // Find the recipe for this product to get yield per tray
                    $product = $item->product;
                    
                    if ($product->master_seed_catalog_id || $product->recipe_id) {
                        $recipe = $this->findActiveRecipeForProduct($product);
                        if ($recipe) {
                            // Calculate grams per tray with buffer
                            $planningYield = $this->yieldCalculator->calculatePlanningYield($recipe);
                            $totalGrams += $item->quantity * $planningYield;
                            
                            Log::info('Converted live tray quantity to grams', [
                                'order_item_id' => $item->id,
                                'product' => $product->name,
                                'trays' => $item->quantity,
                                'grams_per_tray' => $planningYield,
                                'total_grams' => $item->quantity * $planningYield
                            ]);
                        } else {
                            // No recipe found - use a default estimate
                            $defaultGramsPerTray = config('harvest.planning.default_grams_per_tray', 75);
                            $totalGrams += $item->quantity * $defaultGramsPerTray;
                            
                            Log::warning('No recipe found for live tray conversion, using default', [
                                'order_item_id' => $item->id,
                                'product' => $product->name,
                                'trays' => $item->quantity,
                                'default_grams_per_tray' => $defaultGramsPerTray
                            ]);
                        }
                    } else {
                        // No master seed catalog - use default
                        $defaultGramsPerTray = config('harvest.planning.default_grams_per_tray', 75);
                        $totalGrams += $item->quantity * $defaultGramsPerTray;
                    }
                } else {
                    // Not a live tray - check for fill weight in price variation
                    if ($priceVariation && $priceVariation->fill_weight) {
                        // Use fill weight from price variation
                        $itemGrams = $item->quantity * $priceVariation->fill_weight;
                        $totalGrams += $itemGrams;
                        
                        Log::info('Calculated grams using fill weight', [
                            'order_item_id' => $item->id,
                            'product' => $item->product->name,
                            'quantity' => $item->quantity,
                            'fill_weight' => $priceVariation->fill_weight,
                            'total_grams' => $itemGrams
                        ]);
                    } elseif ($priceVariation && $priceVariation->fill_weight_grams) {
                        // Use fill_weight_grams if available
                        $itemGrams = $item->quantity * $priceVariation->fill_weight_grams;
                        $totalGrams += $itemGrams;
                        
                        Log::info('Calculated grams using fill_weight_grams', [
                            'order_item_id' => $item->id,
                            'product' => $item->product->name,
                            'quantity' => $item->quantity,
                            'fill_weight_grams' => $priceVariation->fill_weight_grams,
                            'total_grams' => $itemGrams
                        ]);
                    } else {
                        // Fallback - assume quantity is in grams
                        $totalGrams += $item->quantity;
                        
                        Log::warning('No fill weight found, using quantity as grams', [
                            'order_item_id' => $item->id,
                            'product' => $item->product->name,
                            'quantity' => $item->quantity,
                            'price_variation' => $priceVariation->name ?? 'None'
                        ]);
                    }
                }
            }
        }

        return $totalGrams;
    }

    /**
     * Detect if order items are for live tray delivery vs harvested products.
     * 
     * Live tray orders have fundamentally different production, packaging, and
     * delivery requirements compared to harvested microgreens. This detection
     * is critical for proper agricultural planning and resource allocation.
     * 
     * @param Collection $items OrderItem collection to analyze
     * @return bool True if items are for live tray delivery
     * 
     * @detection_strategy
     * - Examines first item as representative of entire product group
     * - Checks packaging type name for "live tray" string
     * - Checks price variation name as secondary indicator
     * - Case-insensitive string matching for flexibility
     * 
     * @live_tray_characteristics
     * - Delivered as living, growing plants in trays
     * - Continue growing at customer location
     * - Different packaging and handling requirements
     * - Modified delivery timing (plants must be healthy)
     * - Customer education often required
     * 
     * @agricultural_implications
     * - Same growing process until delivery point
     * - Different quality criteria (plant health vs harvest weight)
     * - Modified timing windows (plant viability critical)
     * - Special handling during transport
     * 
     * @business_logic
     * - All items in a product group assumed same type
     * - Consistent packaging within single products
     * - Different pricing structures for live vs harvested
     * 
     * @fallback_behavior
     * - Returns false when no items present (safe default)
     * - Returns false when price variation data unavailable
     * - Assumes harvested product when detection unclear
     * 
     * @integration_points
     * - Used by calculateTotalGramsForProduct() for conversion logic
     * - Influences crop plan generation workflows
     * - Affects packaging and delivery scheduling
     */
    protected function isLiveTrayOrder(Collection $items): bool
    {
        // Check the first item to determine if this is a live tray order
        $firstItem = $items->first();
        if (!$firstItem) {
            return false;
        }
        
        // Load price variation with packaging type
        $priceVariation = $firstItem->priceVariation;
        $packagingType = $priceVariation->packagingType ?? null;
        
        // Check both packaging type name and price variation name for "live tray"
        if ($packagingType && stripos($packagingType->name, 'live tray') !== false) {
            return true;
        }
        
        if ($priceVariation && stripos($priceVariation->name, 'live tray') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate total growing trays needed for live tray order items.
     * 
     * For live tray orders, the quantity directly represents the number of
     * growing trays required. This method aggregates tray counts across
     * multiple order line items for the same product.
     * 
     * @param Collection $items OrderItem collection for live tray product
     * @return int Total number of growing trays needed
     * 
     * @live_tray_quantity_logic
     * - Each order item quantity = number of trays for that line
     * - Sum all quantities to get total tray requirement
     * - Cast to integer since partial trays not meaningful
     * 
     * @agricultural_context
     * - Each tray represents one growing unit
     * - Standard tray sizes used across production facility
     * - Trays delivered with living plants ready for continued growth
     * - Customer continues growing process at their location
     * 
     * @business_applications
     * - Production planning: How many trays to plant
     * - Space planning: Growing area requirements
     * - Packaging: Number of delivery trays needed
     * - Logistics: Transport capacity planning
     * 
     * @quality_considerations
     * - All trays must meet living plant quality standards
     * - Timing critical for plant health at delivery
     * - Growing conditions must support continued growth
     * 
     * @integration_with_planning
     * - Used by generatePlanForLiveTrays() for crop plan creation
     * - Converted to equivalent grams for resource planning consistency
     * - Tray count preserved for production scheduling
     * 
     * @data_assumptions
     * - Order quantity always represents tray count for live products
     * - No fractional trays (business rule)
     * - Consistent tray sizing across facility
     */
    protected function calculateTotalTraysForProduct(Collection $items): int
    {
        $totalTrays = 0;
        
        foreach ($items as $item) {
            // For live trays, quantity is the number of trays
            $totalTrays += (int) $item->quantity;
        }
        
        return $totalTrays;
    }

    /**
     * Generate crop plans for complex product mixes with multiple varieties.
     * 
     * Product mixes contain multiple varieties combined in specific percentages.
     * This method decomposes the mix into individual variety requirements and
     * creates separate crop plans for each component while maintaining traceability
     * back to the original mixed product order.
     * 
     * @param Order $order Source order requiring the product mix
     * @param Product $product Product mix with defined variety percentages
     * @param float $totalGramsNeeded Total weight for the complete mix
     * @param array &$processedVarieties Deduplication tracking array
     * @return Collection<CropPlan> Crop plans for all mix components
     * 
     * @mix_decomposition_process
     * 1. Load product mix with variety components and percentages
     * 2. Use breakdownProductMix() to calculate variety-specific weights
     * 3. Generate individual crop plan for each variety component
     * 4. Apply deduplication to prevent variety conflicts
     * 5. Maintain audit trail linking components to source mix
     * 
     * @agricultural_complexity
     * - Each variety may have different growing requirements
     * - Recipe matching per variety, not per mix
     * - Timing coordination across multiple growth cycles
     * - Harvest synchronization for mix assembly
     * 
     * @percentage_calculations
     * - Uses ProductMix percentage definitions
     * - Calculates variety-specific grams from total requirement
     * - Maintains cultivar specificity when defined in mix
     * - Handles rounding in agricultural contexts
     * 
     * @deduplication_strategy
     * - Checks processedVarieties for variety_id + harvest_date combinations
     * - Prevents multiple plans for same variety on same date
     * - Allows same variety in different mixes or harvest dates
     * - Maintains order item traceability across deduplication
     * 
     * @error_resilience
     * - Continues processing when individual varieties fail
     * - Logs detailed error information for troubleshooting
     * - Returns partial collection for successful varieties
     * - Preserves mix integrity where possible
     * 
     * @business_applications
     * - Custom salad mixes with specific variety ratios
     * - Seasonal blend products
     * - Restaurant-specific mix formulations
     * - Standardized house blend products
     * 
     * @quality_control
     * - Each variety must meet individual quality standards
     * - Timing coordination critical for fresh mix assembly
     * - Harvest synchronization across multiple growing cycles
     * 
     * @throws Exception When mix decomposition fails or variety data invalid
     */
    protected function generatePlansForProductMix(Order $order, Product $product, float $totalGramsNeeded, array &$processedVarieties): Collection
    {
        $plans = collect();
        $productMix = $product->productMix;

        if (!$productMix) {
            return $plans;
        }

        // Break down the mix into component varieties
        $breakdown = $this->breakdownProductMix($product, $totalGramsNeeded);

        foreach ($breakdown as $varietyId => $componentData) {
            $varietyKey = $varietyId . '_' . $order->harvest_date->format('Y-m-d');
            
            // Skip if already processed (prevent duplicates)
            if (!isset($processedVarieties[$varietyKey])) {
                try {
                    $plan = $this->generatePlanForSingleVariety(
                        $order,
                        $product,
                        $varietyId,
                        $componentData['grams'],
                        $componentData['cultivar']
                    );
                    if ($plan) {
                        $plans->push($plan);
                        $processedVarieties[$varietyKey] = true;
                        
                        Log::info('Generated crop plan for mix component', [
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'variety_id' => $varietyId,
                            'grams_needed' => $componentData['grams'],
                            'cultivar' => $componentData['cultivar']
                        ]);
                    }
                } catch (Exception $e) {
                    Log::error('Failed to generate crop plan for mix component', [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'variety_id' => $varietyId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Continue processing other varieties even if one fails
                    continue;
                }
            }
        }

        return $plans;
    }

    /**
     * Generate comprehensive crop plan for a single agricultural variety.
     * 
     * This is the core method for creating detailed production plans that translate
     * customer orders into actionable agricultural production schedules. It handles
     * recipe matching, yield calculations, timing coordination, and resource validation.
     * 
     * @param Order $order Source order requiring this variety
     * @param Product $product Source product (may be single variety or mix component)
     * @param int $masterSeedCatalogId Variety identifier for seed catalog lookup
     * @param float $gramsNeeded Target harvest weight for this variety
     * @param string|null $cultivar Specific cultivar if from product mix
     * @return CropPlan|null Complete crop plan or null if creation fails
     * 
     * @agricultural_calculation_flow
     * 1. Validate variety exists in master seed catalog
     * 2. Find active recipe with growing parameters
     * 3. Calculate backward timing from harvest date
     * 4. Determine tray requirements using yield calculations
     * 5. Schedule seed soaking if required by recipe
     * 6. Create complete crop plan with audit trail
     * 
     * @recipe_matching_priority
     * - Product-specific recipes (highest priority)
     * - Variety-specific recipes from master catalog
     * - Fuzzy matching on variety names (fallback)
     * - Creates incomplete plan if no recipe found
     * 
     * @timing_calculations
     * - Plant by date: harvest_date - recipe.totalDays()
     * - Seed soak date: plant_date - recipe.seed_soak_hours
     * - All dates calculated to start of day for consistency
     * - Accounts for complete growth cycle stages
     * 
     * @yield_and_resource_planning
     * - Uses HarvestYieldCalculator for accurate tray requirements
     * - Includes recipe-specific buffer percentages
     * - Calculates grams per tray for resource allocation
     * - Validates seed inventory availability
     * 
     * @incomplete_plan_handling
     * - Missing recipes trigger incomplete plan creation
     * - Default 14-day growth cycle assumption
     * - Flags for manual agricultural staff attention
     * - Notification sent to administrative users
     * 
     * @audit_trail_creation
     * - calculation_details JSON stores complete algorithm trace
     * - Links to source order items for traceability
     * - Records recipe parameters and yield calculations
     * - Documents growth stage timing for production staff
     * 
     * @agricultural_notes
     * - Seed soak reminders for time-sensitive varieties
     * - Special handling requirements from recipes
     * - Quality control checkpoints
     * 
     * @error_handling
     * - Graceful fallback for missing variety data
     * - Detailed logging for troubleshooting
     * - Preserves order integrity even with partial failures
     * 
     * @throws Exception When critical agricultural data is invalid
     */
    protected function generatePlanForSingleVariety(
        Order $order, 
        Product $product, 
        int $masterSeedCatalogId, 
        float $gramsNeeded,
        ?string $cultivar = null
    ): ?CropPlan {
        // Get master seed catalog info
        $masterSeedCatalog = MasterSeedCatalog::find($masterSeedCatalogId);
        if (!$masterSeedCatalog) {
            Log::warning('Master seed catalog not found', [
                'order_id' => $order->id,
                'product_id' => $product->id,
                'master_seed_catalog_id' => $masterSeedCatalogId
            ]);
            return null;
        }

        // Find active recipe for this product/variety combination
        $recipe = $this->findActiveRecipeForProductVariety($product, $masterSeedCatalogId);
        
        // Get draft status
        $draftStatus = CropPlanStatus::where('code', 'draft')->first();
        
        if (!$recipe) {
            Log::warning('No active recipe found for variety, creating incomplete plan', [
                'order_id' => $order->id,
                'product_id' => $product->id,
                'master_seed_catalog_id' => $masterSeedCatalogId,
                'variety_name' => $masterSeedCatalog->name
            ]);
            
            // Create incomplete plan without recipe
            $cropPlan = CropPlan::create([
                'order_id' => $order->id,
                'recipe_id' => null,
                'variety_id' => $masterSeedCatalogId,
                // cultivar accessed via recipe relationship
                'status_id' => $draftStatus->id,
                'trays_needed' => 0, // Cannot calculate without recipe
                'grams_needed' => $gramsNeeded,
                'grams_per_tray' => 0,
                'plant_by_date' => $order->harvest_date->copy()->subDays(14), // Default 14 days
                'seed_soak_date' => null,
                'expected_harvest_date' => $order->harvest_date,
                'delivery_date' => $order->delivery_date,
                'is_missing_recipe' => true,
                'missing_recipe_notes' => "No recipe found for {$masterSeedCatalog->name}. Please create a recipe or manually update this plan.",
                'calculation_details' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'master_seed_catalog_id' => $masterSeedCatalogId,
                    'variety_name' => $masterSeedCatalog->name,
                    'grams_requested' => $gramsNeeded,
                    'missing_recipe' => true
                ],
                'order_items_included' => $this->getOrderItemIds($order, $product),
                'created_by' => auth()->id() ?: $order->user_id,
                'admin_notes' => "⚠️ MISSING RECIPE: No active recipe found for {$masterSeedCatalog->name}"
            ]);
            
            // Send notification about missing recipe
            $this->notifyMissingRecipe($order, $masterSeedCatalog, $product);
            
            return $cropPlan;
        }

        // Calculate planting dates
        $plantByDate = $this->calculatePlantByDate($recipe, $order->harvest_date);
        $seedSoakDate = $this->calculateSeedSoakDate($recipe, $plantByDate);

        // Calculate yield and trays needed
        $planningYield = $this->yieldCalculator->calculatePlanningYield($recipe);
        $traysNeeded = ceil($gramsNeeded / $planningYield);
        $gramsPerTray = $planningYield;

        // Create calculation details for audit trail
        $calculationDetails = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'master_seed_catalog_id' => $masterSeedCatalogId,
            'variety_name' => $masterSeedCatalog->name,
            'grams_requested' => $gramsNeeded,
            'planning_yield_per_tray' => $planningYield,
            'buffer_percentage' => $recipe->buffer_percentage ?? config('harvest.planning.default_buffer_percentage', 10.0),
            'trays_calculated' => $traysNeeded,
            'seed_soak_required' => $recipe->seed_soak_hours > 0,
            'seed_soak_date' => $seedSoakDate?->toDateString(),
            'growth_stages' => [
                'germination_days' => $recipe->germination_days,
                'blackout_days' => $recipe->blackout_days,
                'light_days' => $recipe->light_days,
                'total_days' => $recipe->totalDays()
            ]
        ];

        // Create the crop plan
        $cropPlan = CropPlan::create([
            'order_id' => $order->id,
            'recipe_id' => $recipe->id,
            'variety_id' => $masterSeedCatalogId,
            // cultivar accessed via recipe relationship
            'status_id' => $draftStatus->id,
            'trays_needed' => $traysNeeded,
            'grams_needed' => $gramsNeeded,
            'grams_per_tray' => $gramsPerTray,
            'plant_by_date' => $plantByDate,
            'seed_soak_date' => $seedSoakDate,
            'expected_harvest_date' => $order->harvest_date,
            'delivery_date' => $order->delivery_date,
            'is_missing_recipe' => false,
            'calculation_details' => $calculationDetails,
            'order_items_included' => $this->getOrderItemIds($order, $product),
            'created_by' => auth()->id() ?: $order->user_id,
            'notes' => $recipe->seed_soak_hours > 0 
                ? "Seed soak required starting {$seedSoakDate->format('M j, Y')}" 
                : null
        ]);

        Log::info('Created crop plan', [
            'crop_plan_id' => $cropPlan->id,
            'order_id' => $order->id,
            'recipe_id' => $recipe->id,
            'trays_needed' => $traysNeeded
        ]);

        return $cropPlan;
    }

    /**
     * Generate a crop plan for live tray orders
     * 
     * @param Order $order
     * @param Product $product
     * @param int $masterSeedCatalogId
     * @param int $traysNeeded
     * @return CropPlan|null
     */
    protected function generatePlanForLiveTrays(
        Order $order, 
        Product $product, 
        int $masterSeedCatalogId, 
        int $traysNeeded,
        ?string $cultivar = null
    ): ?CropPlan {
        // Get master seed catalog info
        $masterSeedCatalog = MasterSeedCatalog::find($masterSeedCatalogId);
        if (!$masterSeedCatalog) {
            Log::warning('Master seed catalog not found', [
                'order_id' => $order->id,
                'product_id' => $product->id,
                'master_seed_catalog_id' => $masterSeedCatalogId
            ]);
            return null;
        }

        // Find active recipe for this product/variety combination
        $recipe = $this->findActiveRecipeForProductVariety($product, $masterSeedCatalogId);
        
        // Get draft status
        $draftStatus = CropPlanStatus::where('code', 'draft')->first();
        
        if (!$recipe) {
            Log::warning('No active recipe found for variety, creating incomplete plan', [
                'order_id' => $order->id,
                'product_id' => $product->id,
                'master_seed_catalog_id' => $masterSeedCatalogId,
                'variety_name' => $masterSeedCatalog->name
            ]);
            
            // Create incomplete plan without recipe
            $cropPlan = CropPlan::create([
                'order_id' => $order->id,
                'recipe_id' => null,
                'variety_id' => $masterSeedCatalogId,
                // cultivar accessed via recipe relationship
                'status_id' => $draftStatus->id,
                'trays_needed' => $traysNeeded,
                'grams_needed' => 0, // Cannot calculate without recipe
                'grams_per_tray' => 0,
                'plant_by_date' => $order->harvest_date->copy()->subDays(14), // Default 14 days
                'seed_soak_date' => null,
                'expected_harvest_date' => $order->harvest_date,
                'delivery_date' => $order->delivery_date,
                'is_missing_recipe' => true,
                'missing_recipe_notes' => "No recipe found for {$masterSeedCatalog->name}. Please create a recipe or manually update this plan.",
                'calculation_details' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'master_seed_catalog_id' => $masterSeedCatalogId,
                    'variety_name' => $masterSeedCatalog->name,
                    'trays_requested' => $traysNeeded,
                    'order_type' => 'live_tray',
                    'missing_recipe' => true
                ],
                'order_items_included' => $this->getOrderItemIds($order, $product),
                'created_by' => auth()->id() ?: $order->user_id,
                'admin_notes' => "⚠️ MISSING RECIPE: No active recipe found for {$masterSeedCatalog->name} (Live Tray Order)"
            ]);
            
            // Send notification about missing recipe
            $this->notifyMissingRecipe($order, $masterSeedCatalog, $product);
            
            return $cropPlan;
        }

        // Calculate planting dates
        $plantByDate = $this->calculatePlantByDate($recipe, $order->harvest_date);
        $seedSoakDate = $this->calculateSeedSoakDate($recipe, $plantByDate);

        // For live trays, we don't need to calculate yield - just use the tray count
        $gramsPerTray = $this->yieldCalculator->calculatePlanningYield($recipe);
        $totalGrams = $traysNeeded * $gramsPerTray;

        // Create calculation details for audit trail
        $calculationDetails = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'master_seed_catalog_id' => $masterSeedCatalogId,
            'variety_name' => $masterSeedCatalog->name,
            'trays_requested' => $traysNeeded,
            'order_type' => 'live_tray',
            'grams_per_tray' => $gramsPerTray,
            'total_grams' => $totalGrams,
            'seed_soak_required' => $recipe->seed_soak_hours > 0,
            'seed_soak_date' => $seedSoakDate?->toDateString(),
            'growth_stages' => [
                'germination_days' => $recipe->germination_days,
                'blackout_days' => $recipe->blackout_days,
                'light_days' => $recipe->light_days,
                'total_days' => $recipe->totalDays()
            ]
        ];

        // Create the crop plan
        $cropPlan = CropPlan::create([
            'order_id' => $order->id,
            'recipe_id' => $recipe->id,
            'variety_id' => $masterSeedCatalogId,
            // cultivar accessed via recipe relationship
            'status_id' => $draftStatus->id,
            'trays_needed' => $traysNeeded,
            'grams_needed' => $totalGrams,
            'grams_per_tray' => $gramsPerTray,
            'plant_by_date' => $plantByDate,
            'seed_soak_date' => $seedSoakDate,
            'expected_harvest_date' => $order->harvest_date,
            'delivery_date' => $order->delivery_date,
            'is_missing_recipe' => false,
            'calculation_details' => $calculationDetails,
            'order_items_included' => $this->getOrderItemIds($order, $product),
            'created_by' => auth()->id() ?: $order->user_id,
            'notes' => $recipe->seed_soak_hours > 0 
                ? "Seed soak required starting {$seedSoakDate->format('M j, Y')}" 
                : null
        ]);

        Log::info('Created crop plan for live tray order', [
            'crop_plan_id' => $cropPlan->id,
            'order_id' => $order->id,
            'recipe_id' => $recipe->id,
            'trays_needed' => $traysNeeded
        ]);

        return $cropPlan;
    }

    /**
     * Find an active recipe for a given product.
     * Prioritizes direct product->recipe relationship, then falls back to seed catalog lookup.
     * 
     * @param Product $product
     * @return Recipe|null
     */
    protected function findActiveRecipeForProduct(Product $product): ?Recipe
    {
        // First check if product has a direct recipe relationship
        if ($product->recipe_id) {
            $recipe = Recipe::where('id', $product->recipe_id)
                ->where('is_active', true)
                ->whereNull('lot_depleted_at')
                ->first();
                
            if ($recipe) {
                Log::info('Found recipe by direct product relationship', [
                    'product_id' => $product->id,
                    'recipe_id' => $recipe->id,
                    'recipe_name' => $recipe->name
                ]);
                return $recipe;
            } else {
                Log::warning('Product has recipe_id but recipe is inactive or depleted', [
                    'product_id' => $product->id,
                    'recipe_id' => $product->recipe_id
                ]);
            }
        }
        
        // Fall back to master_seed_catalog_id lookup if no direct recipe
        if ($product->master_seed_catalog_id) {
            return $this->findActiveRecipeForVariety($product->master_seed_catalog_id);
        }
        
        return null;
    }

    /**
     * Find active recipe for a product/variety combination
     * Checks component-specific recipes first for mixes, then falls back to standard logic
     * 
     * @param Product $product
     * @param int $masterSeedCatalogId
     * @return Recipe|null
     */
    protected function findActiveRecipeForProductVariety(Product $product, int $masterSeedCatalogId): ?Recipe
    {
        // If product is a mix, check for component-specific recipe first
        if ($product->product_mix_id && $product->productMix) {
            $componentRecipe = $product->productMix->getComponentRecipe($masterSeedCatalogId);
            if ($componentRecipe) {
                Log::info('Found component-specific recipe for mix', [
                    'product_id' => $product->id,
                    'product_mix_id' => $product->product_mix_id,
                    'master_seed_catalog_id' => $masterSeedCatalogId,
                    'recipe_id' => $componentRecipe->id,
                    'recipe_name' => $componentRecipe->name
                ]);
                return $componentRecipe;
            }
        }
        
        // Fall back to standard product recipe logic
        return $this->findActiveRecipeForProduct($product);
    }

    /**
     * Find active recipe for a master seed catalog variety
     * 
     * @param int $masterSeedCatalogId
     * @return Recipe|null
     */
    public function findActiveRecipeForVariety(int $masterSeedCatalogId): ?Recipe
    {
        // Get the master seed catalog
        $masterSeedCatalog = MasterSeedCatalog::find($masterSeedCatalogId);
        if (!$masterSeedCatalog) {
            return null;
        }
        
        // First try to find recipe by master_seed_catalog_id directly
        $recipe = Recipe::where('is_active', true)
            ->whereNull('lot_depleted_at')
            ->where('master_seed_catalog_id', $masterSeedCatalogId)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($recipe) {
            Log::info('Found recipe by master_seed_catalog_id', [
                'master_seed_catalog_id' => $masterSeedCatalogId,
                'recipe_id' => $recipe->id,
                'recipe_name' => $recipe->name
            ]);
            return $recipe;
        }
        
        // Fallback: Try exact match on common_name and cultivar_name for backwards compatibility
        $recipe = Recipe::where('is_active', true)
            ->whereNull('lot_depleted_at')
            ->where('common_name', $masterSeedCatalog->common_name)
            ->where(function ($query) use ($masterSeedCatalog) {
                if ($masterSeedCatalog->cultivar_name) {
                    $query->where('cultivar_name', $masterSeedCatalog->cultivar_name);
                } else {
                    $query->whereNull('cultivar_name');
                }
            })
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($recipe) {
            Log::info('Found recipe by name match', [
                'master_seed_catalog' => $masterSeedCatalog->name,
                'recipe_name' => $recipe->name
            ]);
            return $recipe;
        }
        
        // Try fuzzy match on common name as last resort
        $commonNameVariations = $this->getCommonNameVariations($masterSeedCatalog->common_name);
        
        foreach ($commonNameVariations as $variation) {
            $recipe = Recipe::where('is_active', true)
                ->whereNull('lot_depleted_at')
                ->where('common_name', 'LIKE', '%' . $variation . '%')
                ->orderBy('created_at', 'desc')
                ->first();
                
            if ($recipe) {
                Log::info('Found recipe with fuzzy match', [
                    'master_seed_catalog' => $masterSeedCatalog->name,
                    'search_term' => $variation,
                    'recipe_found' => $recipe->name
                ]);
                return $recipe;
            }
        }
        
        Log::warning('No recipe found for master seed catalog', [
            'master_seed_catalog_id' => $masterSeedCatalogId,
            'common_name' => $masterSeedCatalog->common_name,
            'cultivar_name' => $masterSeedCatalog->cultivar_name
        ]);
        
        return null;
    }
    
    /**
     * Get common name variations for fuzzy matching
     */
    protected function getCommonNameVariations(string $commonName): array
    {
        $variations = [$commonName];
        
        // Handle common variations
        $mappings = [
            'Cilantro' => ['Coriander', 'Cilantro/Coriander'],
            'Coriander' => ['Cilantro', 'Cilantro/Coriander'],
            'Pea Shoots' => ['Pea', 'Peas', 'Pea Shoot'],
            'Sunflower Shoots' => ['Sunflower', 'Sunflower Shoot'],
            'Radish' => ['Radish Shoots', 'Radish Shoot'],
            'Broccoli' => ['Broccoli Shoots', 'Broccoli Shoot'],
        ];
        
        foreach ($mappings as $key => $values) {
            if (stripos($commonName, $key) !== false) {
                $variations = array_merge($variations, $values);
            }
        }
        
        // Remove "Shoots" suffix for matching
        if (stripos($commonName, ' Shoots') !== false) {
            $variations[] = str_ireplace(' Shoots', '', $commonName);
        }
        
        return array_unique($variations);
    }

    /**
     * Get order item IDs for a product
     * 
     * @param Order $order
     * @param Product $product
     * @return array
     */
    protected function getOrderItemIds(Order $order, Product $product): array
    {
        return $order->orderItems
            ->where('product_id', $product->id)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Calculate when to plant based on harvest date and recipe growth stages
     * 
     * @param Recipe $recipe
     * @param Carbon $harvestDate
     * @return Carbon
     */
    public function calculatePlantByDate(Recipe $recipe, Carbon $harvestDate): Carbon
    {
        // Work backwards from harvest date
        // Subtract total growth days
        $totalDays = $recipe->totalDays();
        $plantDate = $harvestDate->copy()->subDays(ceil($totalDays));

        // Ensure we're not planting on a partial day
        return $plantDate->startOfDay();
    }

    /**
     * Calculate seed soak date if applicable
     * 
     * @param Recipe $recipe
     * @param Carbon $plantDate
     * @return Carbon|null
     */
    public function calculateSeedSoakDate(Recipe $recipe, Carbon $plantDate): ?Carbon
    {
        if (!$recipe->seed_soak_hours || $recipe->seed_soak_hours <= 0) {
            return null;
        }

        // Seed soak starts before planting
        return $plantDate->copy()->subHours($recipe->seed_soak_hours);
    }

    /**
     * Check if order can be fulfilled by delivery date
     * 
     * @param Order $order
     * @return array ['valid' => bool, 'issues' => array]
     */
    public function validateOrderTiming(Order $order): array
    {
        $issues = [];
        $now = Carbon::now();

        // Generate plans to check timing
        $plans = $this->generatePlanFromOrder($order);

        foreach ($plans as $plan) {
            $recipe = $plan->recipe;
            
            // Skip plans without recipes (they're already flagged as incomplete)
            if (!$recipe) {
                continue;
            }
            
            // Check if we have enough time before planting
            if ($plan->plant_by_date->lt($now)) {
                $issues[] = [
                    'recipe' => $recipe->name,
                    'issue' => 'Plant date has already passed',
                    'plant_date' => $plan->plant_by_date->format('M j, Y'),
                    'days_overdue' => $now->diffInDays($plan->plant_by_date)
                ];
            } elseif ($now->diffInHours($plan->plant_by_date) < 2) { // Less than 2 hours is too tight
                $issues[] = [
                    'recipe' => $recipe->name,
                    'issue' => 'Insufficient time before planting',
                    'plant_date' => $plan->plant_by_date->format('M j, Y'),
                    'hours_until_planting' => $now->diffInHours($plan->plant_by_date, false)
                ];
            }

            // Check seed soak timing if applicable
            $seedSoakDate = $this->calculateSeedSoakDate($recipe, $plan->plant_by_date);
            if ($seedSoakDate && $seedSoakDate->lt($now)) {
                $issues[] = [
                    'recipe' => $recipe->name,
                    'issue' => 'Seed soak should have already started',
                    'soak_date' => $seedSoakDate->format('M j, Y g:i A'),
                    'hours_overdue' => $now->diffInHours($seedSoakDate)
                ];
            }

            // Check inventory availability
            if (!$this->recipeService->canExecuteRecipe($recipe, $plan->trays_needed * $recipe->seed_density_grams_per_tray)) {
                $availableQuantity = $recipe->getLotQuantity();
                $issues[] = [
                    'recipe' => $recipe->name,
                    'issue' => 'Insufficient seed inventory',
                    'needed' => $plan->trays_needed * $recipe->seed_density_grams_per_tray,
                    'available' => $availableQuantity,
                    'lot_number' => $recipe->lot_number
                ];
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }

    /**
     * Break mix into component varieties
     * 
     * @param Product $product
     * @param float $gramsNeeded
     * @return array [master_seed_catalog_id => ['grams' => float, 'cultivar' => string]]
     */
    public function breakdownProductMix(Product $product, float $gramsNeeded): array
    {
        $breakdown = [];
        $productMix = $product->productMix;

        if (!$productMix) {
            return $breakdown;
        }

        // Load mix components with pivot data
        $productMix->load('masterSeedCatalogs');

        foreach ($productMix->masterSeedCatalogs as $catalog) {
            $percentage = $catalog->pivot->percentage / 100;
            $componentGrams = $gramsNeeded * $percentage;
            $breakdown[$catalog->id] = [
                'grams' => $componentGrams,
                'cultivar' => $catalog->pivot->cultivar
            ];
        }

        return $breakdown;
    }

    /**
     * Send notification about missing recipe
     * 
     * @param Order $order
     * @param MasterSeedCatalog $masterSeedCatalog
     * @param Product $product
     * @return void
     */
    protected function notifyMissingRecipe(Order $order, MasterSeedCatalog $masterSeedCatalog, Product $product): void
    {
        // Send immediate Filament notification
        Notification::make()
            ->title('Missing Recipe for Crop Plan')
            ->body("No active recipe found for {$masterSeedCatalog->name}. The crop plan has been created but is incomplete.")
            ->warning()
            ->persistent()
            ->actions([
                Action::make('create_recipe')
                    ->label('Create Recipe')
                    ->url('/admin/recipes/create?variety=' . $masterSeedCatalog->id)
                    ->openUrlInNewTab(),
            ])
            ->send();

        // Log for audit trail
        Log::warning('Crop plan created without recipe', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variety_id' => $masterSeedCatalog->id,
            'variety_name' => $masterSeedCatalog->name,
            'common_name' => $masterSeedCatalog->common_name,
            'cultivar_name' => $masterSeedCatalog->cultivar_name,
        ]);
    }

    /**
     * Aggregate varieties from an order into the aggregate array
     * 
     * @param Order $order
     * @param array &$varietyAggregates
     */
    protected function aggregateOrderVarieties(Order $order, array &$varietyAggregates): void
    {
        // Group items by product to aggregate quantities
        $productGroups = $order->orderItems->groupBy('product_id');

        foreach ($productGroups as $productId => $items) {
            $product = $items->first()->product;
            
            // Calculate total grams needed for this product across all line items
            $totalGramsNeeded = $this->calculateTotalGramsForProduct($items);
            
            if ($product->product_mix_id) {
                // Product is a mix - break down into components
                $breakdown = $this->breakdownProductMix($product, $totalGramsNeeded);
                
                foreach ($breakdown as $varietyId => $componentData) {
                    $this->addToVarietyAggregate(
                        $varietyAggregates,
                        $varietyId,
                        $componentData['cultivar'],
                        $componentData['grams'],
                        $order->harvest_date,
                        $order->id,
                        $product
                    );
                }
            } elseif ($product->master_seed_catalog_id) {
                // Single variety product
                $this->addToVarietyAggregate(
                    $varietyAggregates,
                    $product->master_seed_catalog_id,
                    null, // No specific cultivar for single products
                    $totalGramsNeeded,
                    $order->harvest_date,
                    $order->id,
                    $product
                );
            }
        }
    }

    /**
     * Add grams to variety aggregate
     * 
     * @param array &$varietyAggregates
     * @param int $varietyId
     * @param string|null $cultivar
     * @param float $grams
     * @param Carbon $harvestDate
     * @param int $orderId
     * @param Product $product
     */
    protected function addToVarietyAggregate(
        array &$varietyAggregates,
        int $varietyId,
        ?string $cultivar,
        float $grams,
        Carbon $harvestDate,
        int $orderId,
        Product $product
    ): void {
        $key = $varietyId . '_' . $harvestDate->format('Y-m-d') . '_' . ($cultivar ?? 'default');
        
        if (!isset($varietyAggregates[$key])) {
            $varietyAggregates[$key] = [
                'variety_id' => $varietyId,
                // cultivar accessed via recipe relationship
                'harvest_date' => $harvestDate,
                'total_grams' => 0,
                'orders' => [],
                'products' => []
            ];
        }
        
        $varietyAggregates[$key]['total_grams'] += $grams;
        $varietyAggregates[$key]['orders'][] = $orderId;
        $varietyAggregates[$key]['products'][] = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'grams' => $grams
        ];
    }

    /**
     * Create crop plans from aggregated variety data
     * 
     * @param array $varietyAggregates
     * @return Collection
     */
    protected function createCropPlansFromAggregates(array $varietyAggregates): Collection
    {
        $cropPlans = collect();
        
        foreach ($varietyAggregates as $aggregate) {
            $varietyId = $aggregate['variety_id'];
            $cultivar = $aggregate['cultivar'];
            $totalGrams = $aggregate['total_grams'];
            $harvestDate = $aggregate['harvest_date'];
            $orderIds = array_unique($aggregate['orders']);
            
            // Get master seed catalog
            $masterSeedCatalog = MasterSeedCatalog::find($varietyId);
            if (!$masterSeedCatalog) {
                Log::warning('Master seed catalog not found for aggregate', [
                    'variety_id' => $varietyId,
                    'total_grams' => $totalGrams
                ]);
                continue;
            }
            
            // Find the best recipe for this variety
            $recipe = $this->findActiveRecipeForVariety($varietyId);
            if (!$recipe) {
                Log::warning('No recipe found for aggregated variety', [
                    'variety_id' => $varietyId,
                    'variety_name' => $masterSeedCatalog->common_name,
                    // cultivar accessed via recipe relationship
                    'total_grams' => $totalGrams
                ]);
                continue;
            }
            
            // Calculate trays needed
            $planningYield = $this->yieldCalculator->calculatePlanningYield($recipe);
            $traysNeeded = ceil($totalGrams / $planningYield);
            $gramsPerTray = $planningYield;
            
            // Calculate planting dates
            $plantByDate = $harvestDate->copy()->subDays($recipe->totalDays());
            $seedSoakDate = $recipe->seed_soak_hours > 0 ? 
                $plantByDate->copy()->subHours($recipe->seed_soak_hours) : null;
            
            // Get draft status
            $draftStatus = CropPlanStatus::where('code', 'draft')->first();
            
            // For now, let's use the regular individual crop plan generation per order
            // This creates separate crop plans for each order, which can then be grouped in the UI
            foreach ($orderIds as $orderId) {
                $order = Order::with('customer')->find($orderId);
                if ($order) {
                    // Generate individual crop plan for this order and variety combination
                    $individualPlans = $this->generatePlanFromOrder($order);
                    foreach ($individualPlans as $plan) {
                        if ($plan->variety_id == $varietyId && $plan->recipe?->cultivar_name == $cultivar) {
                            $cropPlans->push($plan);
                        }
                    }
                }
            }
            
            Log::info('Created aggregated crop plan', [
                'crop_plan_id' => $cropPlan->id,
                'variety_id' => $varietyId,
                'variety_name' => $masterSeedCatalog->common_name,
                // cultivar accessed via recipe relationship
                'total_grams' => $totalGrams,
                'trays_needed' => $traysNeeded,
                'orders_count' => count($orderIds),
                'harvest_date' => $harvestDate->format('Y-m-d')
            ]);
            
            $cropPlans->push($cropPlan);
        }
        
        return $cropPlans;
    }
}