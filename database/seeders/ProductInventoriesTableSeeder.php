<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductInventoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('product_inventories')->truncate();

        // Insert data
        DB::table('product_inventories')->insert([
            [
                'id' => 1,
                'product_id' => 1,
                'price_variation_id' => 5,
                'lot_number' => null,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'cost_per_unit' => 0,
                'expiration_date' => null,
                'production_date' => '2025-06-24',
                'location' => null,
                'notes' => 'Auto-created for 24oz Clamshell variation',
                'status' => 'active',
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:45:58',
            ],
            [
                'id' => 2,
                'product_id' => 1,
                'price_variation_id' => null,
                'lot_number' => null,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'cost_per_unit' => 0,
                'expiration_date' => null,
                'production_date' => '2025-06-24',
                'location' => null,
                'notes' => 'Auto-created for 32oz Clamshell variation',
                'status' => 'active',
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:45:58',
            ],
            [
                'id' => 3,
                'product_id' => 1,
                'price_variation_id' => 7,
                'lot_number' => null,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'cost_per_unit' => 0,
                'expiration_date' => null,
                'production_date' => '2025-06-24',
                'location' => null,
                'notes' => 'Auto-created for Default variation',
                'status' => 'active',
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:45:58',
            ],
            [
                'id' => 4,
                'product_id' => 1,
                'price_variation_id' => 8,
                'lot_number' => null,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'cost_per_unit' => 0,
                'expiration_date' => null,
                'production_date' => '2025-06-24',
                'location' => null,
                'notes' => 'Auto-created for Default variation',
                'status' => 'active',
                'created_at' => '2025-06-24 18:45:58',
                'updated_at' => '2025-06-24 18:45:58',
            ],
        ]);
    }
}