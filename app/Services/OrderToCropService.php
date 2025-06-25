<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Crop;
use App\Models\CropPlan;
use App\Models\Recipe;
use App\Models\SeedEntry;
use App\Models\ProductMix;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OrderToCropService
{
    /**
     * Generate crop plans needed for an order based on its items
     */
    public function generateCropPlansForOrder(Order $order, bool $dryRun = false): array
    {
        $results = [
            'plans_created' => 0,
            'plans_planned' => [],
            'errors' => []
        ];

        try {
            // Calculate what needs to be grown for this order
            $requiredCrops = $this->calculateRequiredCrops($order);
            
            if ($dryRun) {
                $results['plans_planned'] = $requiredCrops;
                $results['plans_created'] = count($requiredCrops);
                return $results;
            }

            // Create the actual crop plan records
            foreach ($requiredCrops as $cropPlanData) {
                $cropPlan = $this->createCropPlanFromData($order, $cropPlanData);
                if ($cropPlan) {
                    $results['plans_created']++;
                    $results['plans_planned'][] = [
                        'plan_id' => $cropPlan->id,
                        'recipe' => $cropPlanData['recipe']->name,
                        'quantity' => $cropPlanData['trays_needed'],
                        'plant_by_date' => $cropPlanData['plant_by_date']
                    ];
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Calculate what crops are needed for an order
     */
    private function calculateRequiredCrops(Order $order): array
    {
        $requiredCrops = [];

        // Ensure orderItems are loaded with product relationships
        if (!$order->relationLoaded('orderItems')) {
            $order->load(['orderItems.product.productMix.components.seedEntry.recipes']);
        }

        foreach ($order->orderItems as $orderItem) {
            $product = $orderItem->product;
            $quantity = $orderItem->quantity;

            // Get recipes needed for this product
            $recipes = $this->getRecipesForProduct($product);

            foreach ($recipes as $recipe) {
                $gramsNeeded = $this->calculateGramsNeeded($product, $quantity, $recipe);
                $traysNeeded = $this->calculateTraysNeeded($gramsNeeded, $recipe);
                $plantByDate = $this->calculatePlantByDate($order->delivery_date, $recipe);

                // Group by recipe to avoid duplicate crops
                $recipeKey = $recipe->id;
                
                if (isset($requiredCrops[$recipeKey])) {
                    $requiredCrops[$recipeKey]['trays_needed'] += $traysNeeded;
                    $requiredCrops[$recipeKey]['grams_needed'] += $gramsNeeded;
                } else {
                    $requiredCrops[$recipeKey] = [
                        'recipe' => $recipe,
                        'trays_needed' => $traysNeeded,
                        'grams_needed' => $gramsNeeded,
                        'plant_by_date' => $plantByDate,
                        'order_items' => []
                    ];
                }

                $requiredCrops[$recipeKey]['order_items'][] = [
                    'product' => $product->name,
                    'quantity' => $quantity,
                    'grams_for_item' => $gramsNeeded
                ];
            }
        }

        return array_values($requiredCrops);
    }

    /**
     * Get recipes needed for a product
     */
    private function getRecipesForProduct($product): Collection
    {
        $recipes = collect();

        // Check if product has a product mix
        if ($product->product_mix_id && $product->productMix) {
            // For mixed products, get recipes for each component
            foreach ($product->productMix->components as $component) {
                if ($component->seedEntry) {
                    // Use eager loaded relationship if available
                    if ($component->seedEntry->relationLoaded('recipes')) {
                        $recipe = $component->seedEntry->recipes->first();
                    } else {
                        $recipe = $component->seedEntry->recipes()->first();
                    }
                    
                    if ($recipe) {
                        $recipes->push($recipe);
                    }
                }
            }
        } else {
            // For single products, try to find a recipe
            // This might need adjustment based on how you link products to recipes
            $recipe = $this->findRecipeForProduct($product);
            if ($recipe) {
                $recipes->push($recipe);
            }
        }

        return $recipes;
    }

    /**
     * Find a recipe for a single (non-mixed) product
     */
    private function findRecipeForProduct($product): ?Recipe
    {
        // Option 1: Direct recipe name match
        $recipe = Recipe::where('name', 'like', "%{$product->name}%")->first();
        if ($recipe) {
            return $recipe;
        }

        // Option 2: Extract key words from product name to match seed entries
        $productWords = explode(' ', strtolower($product->name));
        foreach ($productWords as $word) {
            if (strlen($word) > 2) { // Skip short words like "of", "in", etc.
                $seedEntry = SeedEntry::where('common_name', 'like', "%{$word}%")
                    ->orWhere('cultivar_name', 'like', "%{$word}%")
                    ->first();

                if ($seedEntry) {
                    $recipe = $seedEntry->recipes()->first();
                    if ($recipe) {
                        return $recipe;
                    }
                }
            }
        }

        // Option 3: Find by category or other matching logic
        // You might need to customize this based on your naming conventions

        return null;
    }

    /**
     * Calculate grams needed for a specific recipe/product combination
     */
    private function calculateGramsNeeded($product, $quantity, $recipe): float
    {
        // This is a simplified calculation - you may need to adjust based on:
        // - Product packaging sizes
        // - Waste factors
        // - Customer-specific requirements
        
        $baseGramsPerUnit = 100; // Default assumption
        
        // Try to get from price variation if packaging defines weight
        if ($product->priceVariations()->exists()) {
            $priceVar = $product->priceVariations()->first();
            if ($priceVar->fill_weight) {
                $baseGramsPerUnit = $priceVar->fill_weight;
            }
        }

        return $quantity * $baseGramsPerUnit;
    }

    /**
     * Calculate how many trays are needed based on grams and recipe yield
     */
    private function calculateTraysNeeded(float $gramsNeeded, Recipe $recipe): int
    {
        $expectedYield = $recipe->expected_yield_grams ?? 200; // Default if not set
        $traysNeeded = ceil($gramsNeeded / $expectedYield);
        
        // Always plant at least 1 tray
        return max(1, $traysNeeded);
    }

    /**
     * Calculate when to plant based on delivery date and growing time
     */
    private function calculatePlantByDate(Carbon $deliveryDate, Recipe $recipe): Carbon
    {
        $totalGrowingDays = ($recipe->germination_days ?? 3) + 
                          ($recipe->blackout_days ?? 3) + 
                          ($recipe->light_days ?? 8);
        
        // Add a buffer day for harvesting/processing
        $totalDays = $totalGrowingDays + 1;
        
        return $deliveryDate->copy()->subDays($totalDays);
    }

    /**
     * Create crop plan record from calculation data
     */
    private function createCropPlanFromData(Order $order, array $cropPlanData): ?CropPlan
    {
        $recipe = $cropPlanData['recipe'];
        $plantByDate = $cropPlanData['plant_by_date'];
        $deliveryDate = $order->delivery_date;
        $expectedHarvestDate = $order->harvest_date ?? $deliveryDate->copy()->subDay();

        // Build calculation details for transparency
        $calculationDetails = [
            'base_grams_per_unit' => $cropPlanData['grams_needed'] / max(1, array_sum(array_column($cropPlanData['order_items'], 'quantity'))),
            'total_quantity_ordered' => array_sum(array_column($cropPlanData['order_items'], 'quantity')),
            'recipe_yield_grams' => $recipe->expected_yield_grams ?? 200,
            'calculation_method' => 'automatic_from_order',
            'growing_days_total' => ($recipe->germination_days ?? 3) + ($recipe->blackout_days ?? 3) + ($recipe->light_days ?? 8),
        ];

        // Build order items included for transparency
        $orderItemsIncluded = [];
        foreach ($cropPlanData['order_items'] as $item) {
            $orderItemsIncluded[] = [
                'product_name' => $item['product'],
                'quantity' => $item['quantity'],
                'grams_for_item' => $item['grams_for_item'],
            ];
        }

        $cropPlan = CropPlan::create([
            'order_id' => $order->id,
            'recipe_id' => $recipe->id,
            'status' => 'draft',
            'trays_needed' => $cropPlanData['trays_needed'],
            'grams_needed' => $cropPlanData['grams_needed'],
            'grams_per_tray' => $recipe->expected_yield_grams ?? 200,
            'plant_by_date' => $plantByDate,
            'expected_harvest_date' => $expectedHarvestDate,
            'delivery_date' => $deliveryDate,
            'calculation_details' => $calculationDetails,
            'order_items_included' => $orderItemsIncluded,
            'created_by' => Auth::id(),
            'notes' => "Auto-generated from order #{$order->id} requirements",
        ]);

        return $cropPlan;
    }

    /**
     * Get a summary of what needs to be planted for upcoming orders
     */
    public function getPlantingSchedule(int $daysAhead = 14): Collection
    {
        $endDate = now()->addDays($daysAhead);
        
        $orders = Order::whereIn('status', ['pending', 'queued', 'preparing'])
            ->where('delivery_date', '<=', $endDate)
            ->whereDoesntHave('cropPlans') // Orders that don't have crop plans yet
            ->with(['orderItems.product'])
            ->get();

        $schedule = collect();

        foreach ($orders as $order) {
            $requiredCrops = $this->calculateRequiredCrops($order);
            
            foreach ($requiredCrops as $cropPlan) {
                $schedule->push([
                    'order_id' => $order->id,
                    'customer' => $order->user->name ?? 'Unknown',
                    'delivery_date' => $order->delivery_date,
                    'plant_by_date' => $cropPlan['plant_by_date'],
                    'recipe' => $cropPlan['recipe']->name,
                    'trays_needed' => $cropPlan['trays_needed'],
                    'products' => collect($cropPlan['order_items'])->pluck('product')->join(', ')
                ]);
            }
        }

        return $schedule->sortBy('plant_by_date');
    }
}