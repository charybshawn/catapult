<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('products')->truncate();

        // Insert data
        DB::table('products')->insert([
            [
                'id' => 1,
                'name' => 'Sunflower',
                'description' => null,
                'active' => 1,
                'total_stock' => 0,
                'reserved_stock' => 0,
                'reorder_threshold' => 0,
                'track_inventory' => 1,
                'stock_status' => 'out_of_stock',
                'wholesale_discount_percentage' => 30,
                'sku' => null,
                'base_price' => null,
                'wholesale_price' => null,
                'bulk_price' => null,
                'special_price' => null,
                'is_visible_in_store' => 1,
                'image' => null,
                'category_id' => 1,
                'product_mix_id' => null,
                'master_seed_catalog_id' => 1,
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:45:58',
                'deleted_at' => null,
            ],
        ]);
    }
}