<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriceVariationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('price_variations')->truncate();

        // Insert data
        DB::table('price_variations')->insert([
            [
                'id' => 1,
                'name' => 'Clamshell (24oz) (Ret)',
                'sku' => null,
                'price' => 5,
                'pricing_unit' => 'per_item',
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'template_id' => null,
                'created_at' => '2025-06-24 18:40:05',
                'updated_at' => '2025-06-24 18:42:41',
                'packaging_type_id' => 2,
                'fill_weight_grams' => 70,
            ],
            [
                'id' => 2,
                'name' => 'Clamshell (32oz) (Ret)',
                'sku' => null,
                'price' => 5,
                'pricing_unit' => 'per_item',
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'template_id' => null,
                'created_at' => '2025-06-24 18:40:47',
                'updated_at' => '2025-06-24 18:42:58',
                'packaging_type_id' => 3,
                'fill_weight_grams' => 70,
            ],
            [
                'id' => 3,
                'name' => 'Bulk',
                'sku' => null,
                'price' => 0.2,
                'pricing_unit' => 'per_g',
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'template_id' => null,
                'created_at' => '2025-06-24 18:44:16',
                'updated_at' => '2025-06-24 18:44:16',
                'packaging_type_id' => null,
                'fill_weight_grams' => null,
            ],
            [
                'id' => 4,
                'name' => 'Live Tray',
                'sku' => null,
                'price' => 30,
                'pricing_unit' => 'per_item',
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'template_id' => null,
                'created_at' => '2025-06-24 18:44:47',
                'updated_at' => '2025-06-24 18:44:47',
                'packaging_type_id' => null,
                'fill_weight_grams' => null,
            ],
            [
                'id' => 5,
                'name' => 'Default',
                'sku' => null,
                'price' => 30,
                'pricing_unit' => 'per_item',
                'is_default' => 1,
                'is_global' => 0,
                'is_active' => 1,
                'product_id' => 1,
                'template_id' => 1,
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:46:21',
                'packaging_type_id' => null,
                'fill_weight_grams' => 80,
            ],
            [
                'id' => 7,
                'name' => 'Default',
                'sku' => null,
                'price' => 0.2,
                'pricing_unit' => 'per_item',
                'is_default' => 0,
                'is_global' => 0,
                'is_active' => 1,
                'product_id' => 1,
                'template_id' => 3,
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:45:58',
                'packaging_type_id' => null,
                'fill_weight_grams' => null,
            ],
            [
                'id' => 8,
                'name' => 'Default',
                'sku' => null,
                'price' => 30,
                'pricing_unit' => 'per_item',
                'is_default' => 0,
                'is_global' => 0,
                'is_active' => 1,
                'product_id' => 1,
                'template_id' => 4,
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:45:58',
                'packaging_type_id' => null,
                'fill_weight_grams' => null,
            ],
        ]);
    }
}