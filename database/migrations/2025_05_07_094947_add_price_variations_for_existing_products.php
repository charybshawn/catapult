<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\PriceVariation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all products that don't have price variations
        $products = Product::whereDoesntHave('priceVariations')->get();
        
        foreach ($products as $product) {
            // Only proceed if the product has a base price
            if ($product->base_price) {
                // Create a default price variation
                $product->createDefaultPriceVariation([
                    'name' => 'Default',
                    'price' => $product->base_price,
                ]);
                
                // Create a wholesale price variation if a wholesale price exists
                if ($product->wholesale_price) {
                    $product->createWholesalePriceVariation($product->wholesale_price);
                }
                
                // Create a bulk price variation if a bulk price exists
                if ($product->bulk_price) {
                    $product->createBulkPriceVariation($product->bulk_price);
                }
                
                // Create a special price variation if a special price exists
                if ($product->special_price) {
                    $product->createSpecialPriceVariation($product->special_price);
                }
            }
        }
        
        // Update products that have price variations, but don't have all variations
        $productsWithVariations = Product::whereHas('priceVariations')->get();
        
        foreach ($productsWithVariations as $product) {
            // Create a wholesale variation if wholesale price exists but no "Wholesale" variation
            if ($product->wholesale_price && 
                !$product->priceVariations()->where('name', 'Wholesale')->exists()) {
                $product->createWholesalePriceVariation($product->wholesale_price);
            }
            
            // Create a bulk variation if bulk price exists but no "Bulk" variation
            if ($product->bulk_price && 
                !$product->priceVariations()->where('name', 'Bulk')->exists()) {
                $product->createBulkPriceVariation($product->bulk_price);
            }
            
            // Create a special variation if special price exists but no "Special" variation
            if ($product->special_price && 
                !$product->priceVariations()->where('name', 'Special')->exists()) {
                $product->createSpecialPriceVariation($product->special_price);
            }
            
            // Ensure there's a default variation
            if (!$product->priceVariations()->where('is_default', true)->exists() && 
                $product->base_price) {
                $product->createDefaultPriceVariation([
                    'name' => 'Default',
                    'price' => $product->base_price,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     * 
     * This is a data migration, so there's no true reversal.
     * We leave the variations in place to avoid data loss.
     */
    public function down(): void
    {
        // No reversal for data migrations
    }
};
