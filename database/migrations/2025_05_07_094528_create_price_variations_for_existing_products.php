<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Item;
use App\Models\PriceVariation;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all products that have price fields set but no price variations
        $products = DB::table('products')
            ->whereNotNull('base_price')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('price_variations')
                    ->whereRaw('products.id = price_variations.product_id');
            })
            ->whereNull('deleted_at')
            ->get();
            
        foreach ($products as $product) {
            // Create default price variation
            $defaultVariation = new PriceVariation([
                'name' => 'Default',
                'unit' => 'item',
                'price' => $product->base_price,
                'is_default' => true,
                'is_active' => true,
                'product_id' => $product->id
            ]);
            $defaultVariation->save();
            
            // Create wholesale price variation if set
            if (!is_null($product->wholesale_price)) {
                $wholesaleVariation = new PriceVariation([
                    'name' => 'Wholesale',
                    'unit' => 'item',
                    'price' => $product->wholesale_price,
                    'is_default' => false,
                    'is_active' => true,
                    'product_id' => $product->id
                ]);
                $wholesaleVariation->save();
            }
            
            // Create bulk price variation if set
            if (!is_null($product->bulk_price)) {
                $bulkVariation = new PriceVariation([
                    'name' => 'Bulk',
                    'unit' => 'item',
                    'price' => $product->bulk_price,
                    'is_default' => false,
                    'is_active' => true,
                    'product_id' => $product->id
                ]);
                $bulkVariation->save();
            }
            
            // Create special price variation if set
            if (!is_null($product->special_price)) {
                $specialVariation = new PriceVariation([
                    'name' => 'Special',
                    'unit' => 'item',
                    'price' => $product->special_price,
                    'is_default' => false,
                    'is_active' => true,
                    'product_id' => $product->id
                ]);
                $specialVariation->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data migration, we don't want to remove the price variations
        // as they may have been modified by users
    }
};
