<?php

namespace App\Services;

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
 * Service for generating crop plans from orders
 */
class CropPlanningService
{
    protected HarvestYieldCalculator $yieldCalculator;
    protected RecipeService $recipeService;
    protected InventoryManagementService $inventoryService;

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
     * Generate individual crop plans for all valid orders in a date range
     * Each order gets its own crop plans, which can then be grouped in the UI
     * 
     * @param string|null $startDate Start date for order range (default: today)
     * @param string|null $endDate End date for order range (default: 30 days ahead)
     * @return Collection
     */
    public function generateIndividualPlansForAllOrders(?string $startDate = null, ?string $endDate = null): Collection
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now();
        $endDate = $endDate ? Carbon::parse($endDate) : now()->addDays(30);
        
        // Get all valid orders in the date range
        $orders = Order::with([
            'orderItems.product.productMix.masterSeedCatalogs',
            'orderItems.product.masterSeedCatalog',
            'orderItems.priceVariation.packagingType'
        ])
        ->where('harvest_date', '>=', $startDate)
        ->where('harvest_date', '<=', $endDate)
        ->whereHas('status', function ($query) {
            $query->whereIn('code', ['draft', 'confirmed', 'active']);
        })
        ->get();
        
        $allCropPlans = collect();
        
        // Generate individual crop plans for each order
        foreach ($orders as $order) {
            $orderPlans = $this->generatePlanFromOrder($order);
            $allCropPlans = $allCropPlans->merge($orderPlans);
        }
        
        return $allCropPlans;
    }

    /**
     * Generate crop plans from an order
     * 
     * @param Order $order
     * @return Collection Collection of CropPlan models
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

        // Delete any existing draft plans for this order to prevent duplicates
        CropPlan::where('order_id', $order->id)
            ->whereHas('status', function ($query) {
                $query->where('code', 'draft');
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
     * Calculate total grams needed for a collection of order items
     * This method is only called for harvested products, not live trays
     * 
     * @param Collection $items
     * @return float
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
     * Check if order items are for live trays
     * 
     * @param Collection $items
     * @return bool
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
     * Calculate total trays needed for a collection of order items
     * 
     * @param Collection $items
     * @return int
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
     * Generate crop plans for a product mix
     * 
     * @param Order $order
     * @param Product $product
     * @param float $totalGramsNeeded
     * @param array &$processedVarieties
     * @return Collection
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
                }
            }
        }

        return $plans;
    }

    /**
     * Generate a crop plan for a single variety
     * 
     * @param Order $order
     * @param Product $product
     * @param int $masterSeedCatalogId
     * @param float $gramsNeeded
     * @param string|null $cultivar
     * @return CropPlan|null
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
                'cultivar' => $cultivar,
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
        $plantByDate = $this->calculatePlantByDate($recipe, $order->delivery_date);
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
            'cultivar' => $cultivar,
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
                'cultivar' => $cultivar,
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
        $plantByDate = $this->calculatePlantByDate($recipe, $order->delivery_date);
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
            'cultivar' => $cultivar,
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
     * Calculate when to plant based on delivery date and recipe growth stages
     * 
     * @param Recipe $recipe
     * @param Carbon $deliveryDate
     * @return Carbon
     */
    public function calculatePlantByDate(Recipe $recipe, Carbon $deliveryDate): Carbon
    {
        // Work backwards from delivery date
        // Subtract harvest window (typically 1 day before delivery)
        $harvestDate = $deliveryDate->copy()->subDay();
        
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
                \Filament\Notifications\Actions\Action::make('create_recipe')
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
                'cultivar' => $cultivar,
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
                    'cultivar' => $cultivar,
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
                $order = Order::find($orderId);
                if ($order) {
                    // Generate individual crop plan for this order and variety combination
                    $individualPlans = $this->generatePlanFromOrder($order);
                    foreach ($individualPlans as $plan) {
                        if ($plan->variety_id == $varietyId && $plan->cultivar == $cultivar) {
                            $cropPlans->push($plan);
                        }
                    }
                }
            }
            
            Log::info('Created aggregated crop plan', [
                'crop_plan_id' => $cropPlan->id,
                'variety_id' => $varietyId,
                'variety_name' => $masterSeedCatalog->common_name,
                'cultivar' => $cultivar,
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