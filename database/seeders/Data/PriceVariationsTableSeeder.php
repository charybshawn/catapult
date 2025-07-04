<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriceVariationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        DB::table('product_price_variations')->truncate();

        // Insert data
        DB::table('product_price_variations')->insert([
            [
                'id' => 1,
                'name' => 'Retail - Clamshell (24oz) - $5.00',
                'is_name_manual' => 0,
                'unit' => 'units',
                'pricing_unit' => null,
                'sku' => null,
                'weight' => null,
                'price' => 5.00,
                'fill_weight' => null,
                'packaging_type_id' => 2,
                'pricing_type' => 'retail',
                'fill_weight_grams' => null,
                'template_id' => null,
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'created_at' => '2025-07-02 23:52:39',
                'updated_at' => '2025-07-02 23:52:39',
            ],
            [
                'id' => 3,
                'name' => 'Wholesale - Clamshell (24oz) - $3.50',
                'is_name_manual' => 0,
                'unit' => 'units',
                'pricing_unit' => 'per_item',
                'sku' => null,
                'weight' => null,
                'price' => 3.50,
                'fill_weight' => null,
                'packaging_type_id' => 2,
                'pricing_type' => 'wholesale',
                'fill_weight_grams' => null,
                'template_id' => null,
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'created_at' => '2025-07-03 19:08:09',
                'updated_at' => '2025-07-03 19:08:09',
            ],
            [
                'id' => 4,
                'name' => 'Retail - 32oz Clamshell (32oz) - $5.00',
                'is_name_manual' => 0,
                'unit' => 'units',
                'pricing_unit' => null,
                'sku' => null,
                'weight' => null,
                'price' => 5.00,
                'fill_weight' => null,
                'packaging_type_id' => 3,
                'pricing_type' => 'retail',
                'fill_weight_grams' => null,
                'template_id' => null,
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'created_at' => '2025-07-03 19:13:12',
                'updated_at' => '2025-07-03 19:13:12',
            ],
            [
                'id' => 5,
                'name' => 'Retail - 32oz Clamshell (32oz) - $3.50',
                'is_name_manual' => 0,
                'unit' => 'units',
                'pricing_unit' => null,
                'sku' => null,
                'weight' => null,
                'price' => 3.50,
                'fill_weight' => null,
                'packaging_type_id' => 3,
                'pricing_type' => 'retail',
                'fill_weight_grams' => null,
                'template_id' => null,
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'created_at' => '2025-07-03 19:13:24',
                'updated_at' => '2025-07-03 19:13:24',
            ],
            [
                'id' => 6,
                'name' => 'Retail - Live Tray',
                'is_name_manual' => 1,
                'unit' => 'units',
                'pricing_unit' => 'per_item',
                'sku' => null,
                'weight' => null,
                'price' => 20.00,
                'fill_weight' => null,
                'packaging_type_id' => null,
                'pricing_type' => 'retail',
                'fill_weight_grams' => null,
                'template_id' => null,
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'created_at' => '2025-07-03 19:14:09',
                'updated_at' => '2025-07-03 19:26:52',
            ],
            [
                'id' => 7,
                'name' => 'Retail - Bulk',
                'is_name_manual' => 1,
                'unit' => 'units',
                'pricing_unit' => 'per_item',
                'sku' => null,
                'weight' => null,
                'price' => 0.20,
                'fill_weight' => null,
                'packaging_type_id' => null,
                'pricing_type' => 'retail',
                'fill_weight_grams' => null,
                'template_id' => null,
                'is_default' => 0,
                'is_global' => 1,
                'is_active' => 1,
                'product_id' => null,
                'created_at' => '2025-07-03 19:28:20',
                'updated_at' => '2025-07-03 19:28:20',
            ],
        ]);
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}