<?php

namespace App\Filament\Resources\OrderSimulatorResource\Services;

use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductMix;
use App\Models\MasterSeedCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural Order Calculation Service for Microgreens Variety Requirements
 * 
 * This service transforms customer order data into detailed agricultural production
 * requirements, calculating precise seed variety quantities needed for microgreens
 * production. It handles complex agricultural business logic including variety
 * calculations, product mix distributions, and package sizing conversions.
 * 
 * Core Agricultural Functions:
 * - Order item analysis: Convert customer orders to production requirements
 * - Variety calculation: Determine seed quantities needed for each microgreens variety
 * - Mix distribution: Calculate variety percentages for blended microgreens products
 * - Weight conversion: Transform package quantities to growing weight requirements
 * - Production validation: Identify missing data that prevents accurate calculations
 * 
 * Business Context:
 * - Customer orders specify product quantities but production requires seed variety weights
 * - Package fill weights represent finished product weight driving backward seed calculations
 * - Product mixes require percentage-based variety distribution calculations
 * - Single variety products have direct product-to-variety mapping
 * - Missing fill weights prevent accurate production planning and must be flagged
 * 
 * Agricultural Calculation Logic:
 * - Single products: Direct multiplication of quantity × package fill weight
 * - Mix products: Percentage distribution of total weight across constituent varieties
 * - Variety aggregation: Sum requirements across all products needing same variety
 * - Weight precision: Round to 2 decimal places for practical agricultural measurements
 * - Error detection: Identify incomplete product configurations preventing calculations
 * 
 * Integration Points:
 * - Order Simulator: UI interface for agricultural production planning
 * - Product Management: Variety and mix configuration for production calculations
 * - Price Variations: Package sizing and fill weight specifications
 * - Production Planning: Seed procurement and growing schedule generation
 * 
 * @business_domain Agricultural microgreens production planning and variety calculation
 * @agricultural_context Seed-to-harvest weight conversions and variety distribution
 * @order_management Customer order analysis and production requirement translation
 * @production_planning Seed procurement calculations and growing schedule support
 * 
 * @see \App\Filament\Resources\OrderSimulatorResource For UI integration and order simulation
 * @see \App\Models\Product For agricultural product definitions and variety relationships
 * @see \App\Models\ProductMix For blend composition and percentage calculations
 * @see \App\Models\PriceVariation For package sizing and fill weight specifications
 */
class OrderCalculationService
{
    /**
     * Calculate agricultural variety requirements for customer order simulation
     * 
     * Transforms customer order items into detailed agricultural production requirements,
     * providing farmers with precise seed variety quantities needed for microgreens
     * production. This method handles complex agricultural business logic including
     * single variety products, multi-variety mixes, and package sizing conversions.
     * 
     * Agricultural Calculation Process:
     * 1. Order Item Validation: Verify complete product and pricing information
     * 2. Product Analysis: Determine if product is single variety or mix
     * 3. Weight Calculation: Convert package quantities to production weights
     * 4. Variety Distribution: Calculate individual variety requirements
     * 5. Aggregation: Sum variety needs across all order items
     * 6. Error Detection: Identify missing fill weights preventing calculations
     * 
     * Business Logic:
     * - Single variety products: Direct quantity × fill_weight calculation
     * - Mix products: Total weight distributed by percentage across varieties
     * - Variety aggregation: Sum requirements for same variety from multiple products
     * - Missing data handling: Flag incomplete product configurations
     * - Precision rounding: 2 decimal places for practical agricultural measurements
     * 
     * Agricultural Context:
     * - Fill weights represent finished microgreens product weight per package
     * - Variety calculations enable seed procurement and growing space planning
     * - Mix percentages ensure proper blend ratios in finished products
     * - Weight totals drive tray allocation and growing resource requirements
     * - Error identification prevents inaccurate production planning
     * 
     * Production Planning Applications:
     * - Seed procurement: Exact variety quantities needed for order fulfillment
     * - Growing space allocation: Tray and growing area requirements by variety
     * - Harvest planning: Expected yield calculations for delivery scheduling
     * - Quality control: Proper variety ratios for consistent product quality
     * - Resource optimization: Minimize waste through accurate quantity calculations
     * 
     * @param array $orderItems Array of order line items containing:
     *                          - product_id: Product identifier for variety lookup
     *                          - price_variation_id: Package size and fill weight reference
     *                          - quantity: Number of packages ordered
     *                          Items missing any field are skipped with validation logging
     * 
     * @return array Comprehensive agricultural production analysis containing:
     *               - variety_totals: Aggregated seed requirements by variety with product breakdown
     *               - item_breakdown: Individual order item analysis with variety distributions
     *               - summary: Order totals (varieties, items, total grams)
     *               - missing_fill_weights: Products lacking fill weight data
     *               - has_errors: Boolean indicating calculation completeness
     * 
     * @business_workflow Order simulation and agricultural production planning
     * @agricultural_planning Seed procurement and growing resource allocation
     * @quality_control Variety ratio verification for product consistency
     * @error_handling Missing data identification for production planning accuracy
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If referenced models don't exist
     * @see \App\Models\Product::masterSeedCatalog For single variety product relationships
     * @see \App\Models\ProductMix::masterSeedCatalogs For multi-variety mix compositions
     * @see \App\Models\PriceVariation::fill_weight_grams For package weight specifications
     */
    public function calculateVarietyRequirements(array $orderItems): array
    {
        $varietyTotals = [];
        $itemBreakdown = collect();
        $missingFillWeights = [];

        foreach ($orderItems as $item) {
            if (empty($item['product_id']) || empty($item['price_variation_id']) || empty($item['quantity'])) {
                continue;
            }

            $priceVariation = PriceVariation::find($item['price_variation_id']);
            if (!$priceVariation) {
                continue;
            }

            $product = Product::with([
                    'masterSeedCatalog.cultivar',
                    'masterSeedCatalog.primaryCultivar', 
                    'productMix',
                    'productMix.masterSeedCatalogs',
                    'productMix.masterSeedCatalogs.cultivar',
                    'productMix.masterSeedCatalogs.primaryCultivar'
                ])
                ->find($item['product_id']);
            
            if (!$product) {
                continue;
            }

            $quantity = (int) $item['quantity'];
            $fillWeight = $priceVariation->fill_weight_grams ?? 0;
            
            // Check for missing fill weight
            if ($fillWeight <= 0) {
                $missingFillWeights[] = [
                    'product_name' => $product->name,
                    'variation_name' => $priceVariation->name,
                    'variation_id' => $priceVariation->id,
                    'quantity' => $quantity
                ];
                Log::warning('Missing fill weight for product variation', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'variation_id' => $priceVariation->id,
                    'variation_name' => $priceVariation->name
                ]);
                continue; // Skip this item since we can't calculate without fill weight
            }
            
            $totalWeight = $quantity * $fillWeight;
            
            Log::info('Processing product', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'has_master_seed_catalog_id' => !is_null($product->master_seed_catalog_id),
                'has_product_mix_id' => !is_null($product->product_mix_id),
                'quantity' => $quantity,
                'fill_weight' => $fillWeight,
                'total_weight' => $totalWeight
            ]);

            // Handle single variety products
            if ($product->master_seed_catalog_id) {
                $variety = $product->masterSeedCatalog;
                if ($variety) {
                    $varietyKey = $variety->id;
                    
                    // Add to totals
                    if (!isset($varietyTotals[$varietyKey])) {
                        $varietyTotals[$varietyKey] = [
                            'variety_id' => $variety->id,
                            'variety_name' => $variety->common_name . ($variety->cultivar_name ? ' - ' . $variety->cultivar_name : ''),
                            'total_grams' => 0,
                            'products' => collect()
                        ];
                    }
                    
                    $varietyTotals[$varietyKey]['total_grams'] += $totalWeight;
                    $varietyTotals[$varietyKey]['products']->push([
                        'product_name' => $product->name,
                        'package_size' => $priceVariation->name,
                        'quantity' => $quantity,
                        'fill_weight' => $fillWeight,
                        'total_grams' => $totalWeight
                    ]);

                    // Add to item breakdown
                    $itemBreakdown->push([
                        'product_name' => $product->name,
                        'package_size' => $priceVariation->name,
                        'quantity' => $quantity,
                        'fill_weight' => $fillWeight,
                        'total_grams' => $totalWeight,
                        'type' => 'single',
                        'varieties' => [
                            [
                                'name' => $variety->common_name . ($variety->cultivar_name ? ' - ' . $variety->cultivar_name : ''),
                                'grams' => $totalWeight,
                                'percentage' => 100
                            ]
                        ]
                    ]);
                }
            }
            // Handle mix products
            elseif ($product->product_mix_id && $product->productMix) {
                $mixVarieties = [];
                
                Log::info('Processing mix product', [
                    'product_name' => $product->name,
                    'product_mix_id' => $product->product_mix_id,
                    'has_productMix' => !is_null($product->productMix),
                    'masterSeedCatalogs_count' => $product->productMix ? $product->productMix->masterSeedCatalogs->count() : 0,
                    'total_weight' => $totalWeight
                ]);
                
                foreach ($product->productMix->masterSeedCatalogs as $variety) {
                    if (!$variety) {
                        continue;
                    }

                    Log::info('Processing variety in mix', [
                        'variety_id' => $variety->id,
                        'variety_name' => $variety->common_name,
                        'has_pivot' => !is_null($variety->pivot),
                        'pivot_percentage' => $variety->pivot ? $variety->pivot->percentage : null
                    ]);

                    $percentage = $variety->pivot->percentage / 100; // Convert to decimal
                    $varietyWeight = $totalWeight * $percentage;
                    $varietyKey = $variety->id;

                    // Add to totals
                    if (!isset($varietyTotals[$varietyKey])) {
                        $varietyTotals[$varietyKey] = [
                            'variety_id' => $variety->id,
                            'variety_name' => $variety->common_name . ($variety->cultivar_name ? ' - ' . $variety->cultivar_name : ''),
                            'total_grams' => 0,
                            'products' => collect()
                        ];
                    }

                    $varietyTotals[$varietyKey]['total_grams'] += $varietyWeight;
                    $varietyTotals[$varietyKey]['products']->push([
                        'product_name' => $product->name . ' (Mix)',
                        'package_size' => $priceVariation->name,
                        'quantity' => $quantity,
                        'fill_weight' => $fillWeight,
                        'percentage' => $variety->pivot->percentage,
                        'total_grams' => $varietyWeight
                    ]);

                    $mixVarieties[] = [
                        'name' => $variety->common_name . ($variety->cultivar_name ? ' - ' . $variety->cultivar_name : ''),
                        'grams' => round($varietyWeight, 2),
                        'percentage' => $variety->pivot->percentage
                    ];
                }

                // Add to item breakdown
                $itemBreakdown->push([
                    'product_name' => $product->name,
                    'package_size' => $priceVariation->name,
                    'quantity' => $quantity,
                    'fill_weight' => $fillWeight,
                    'total_grams' => $totalWeight,
                    'type' => 'mix',
                    'varieties' => $mixVarieties
                ]);
            }
        }

        // Format and sort results
        $sortedTotals = collect($varietyTotals)
            ->map(function ($data) {
                $data['total_grams'] = round($data['total_grams'], 2);
                $data['products'] = $data['products']->toArray();
                return $data;
            })
            ->sortBy('variety_name')
            ->values()
            ->toArray();

        return [
            'variety_totals' => $sortedTotals,
            'item_breakdown' => $itemBreakdown->toArray(),
            'summary' => [
                'total_varieties' => count($sortedTotals),
                'total_items' => count($orderItems),
                'total_grams' => collect($sortedTotals)->sum('total_grams')
            ],
            'missing_fill_weights' => $missingFillWeights,
            'has_errors' => !empty($missingFillWeights)
        ];
    }
}