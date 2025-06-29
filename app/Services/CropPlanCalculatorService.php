<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\SeedEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CropPlanCalculatorService
{
    protected HarvestYieldCalculator $yieldCalculator;

    public function __construct(HarvestYieldCalculator $yieldCalculator)
    {
        $this->yieldCalculator = $yieldCalculator;
    }

    /**
     * Calculate planting requirements for a collection of orders.
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
     * Calculate planting requirements for a single order.
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
     * Calculate planting requirements for a single order item.
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
     * Calculate how many trays are needed based on grams required and seed entry properties.
     * Uses harvest data when available, falls back to recipe expected yield.
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
     * Find the seed entry that corresponds to a product.
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
     * Get information about yield data sources for transparency.
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
