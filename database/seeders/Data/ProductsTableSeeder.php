<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data safely (disable foreign key checks temporarily)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

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
                'stock_status_id' => 3, // Out of Stock
                'wholesale_discount_percentage' => 30,
                'sku' => null,
                'base_price' => null,
                'wholesale_price' => null,
                'bulk_price' => null,
                'special_price' => null,
                'is_visible_in_store' => 1,
                'image' => null,
                'category_id' => null,
                'product_mix_id' => null,
                'master_seed_catalog_id' => 1,
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:45:58',
                'deleted_at' => null,
            ],
        ]);
    }
}