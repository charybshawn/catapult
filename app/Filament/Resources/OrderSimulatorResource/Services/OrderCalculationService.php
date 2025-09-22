<?php

namespace App\Filament\Resources\OrderSimulatorResource\Services;

use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductMix;
use App\Models\MasterSeedCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderCalculationService
{
    /**
     * Calculate variety requirements for an order
     * 
     * @param array $orderItems Array of order items with product_id, price_variation_id, and quantity
     * @return array Array of variety requirements with totals
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
                    'masterSeedCatalog',
                    'productMix',
                    'productMix.masterSeedCatalogs'
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
                            'variety_name' => $variety->common_name, //. ($variety->cultivar_name ? ' - ' . $variety->cultivar_name : ''),
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
                                'name' => $variety->common_name, //. ($variety->cultivar_name ? ' - ' . $variety->cultivar_name : ''),
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
                            'variety_name' => $variety->common_name, //. ($variety->cultivar_name ? ' - ' . $variety->cultivar_name : ''),
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
                        'name' => $variety->common_name, //. ($variety->cultivar_name ? ' - ' . $variety->cultivar_name : ''),
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
