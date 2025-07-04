<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('recipes')->truncate();

        // Insert data
        DB::table('recipes')->insert([
            [
                'id' => 1,
                'name' => 'SUNFLOWER - BLACK OIL - SF4K - 100G',
                'supplier_soil_id' => null,
                'soil_consumable_id' => 2,
                'seed_consumable_id' => 1,
                'seed_density' => null,
                'seed_soak_hours' => 9,
                'germination_days' => 3,
                'blackout_days' => 1,
                'days_to_maturity' => 9,
                'light_days' => 5,
                'harvest_days' => 7,
                'expected_yield_grams' => 450,
                'buffer_percentage' => 15,
                'seed_density_grams_per_tray' => 100,
                'notes' => null,
                'suspend_water_hours' => 24,
                'is_active' => 1,
                'created_at' => '2025-06-24 17:00:50',
                'updated_at' => '2025-06-24 17:00:50',
                'seed_entry_id' => null,
                'common_name' => null,
                'cultivar_name' => null,
            ],
            [
                'id' => 2,
                'name' => 'SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS',
                'supplier_soil_id' => null,
                'soil_consumable_id' => 2,
                'seed_consumable_id' => 3,
                'seed_density' => null,
                'seed_soak_hours' => 4,
                'germination_days' => 3,
                'blackout_days' => 0,
                'days_to_maturity' => 9,
                'light_days' => 6,
                'harvest_days' => 7,
                'expected_yield_grams' => null,
                'buffer_percentage' => 15,
                'seed_density_grams_per_tray' => 100,
                'notes' => null,
                'suspend_water_hours' => 24,
                'is_active' => 1,
                'created_at' => '2025-06-24 17:56:15',
                'updated_at' => '2025-06-24 17:56:15',
                'seed_entry_id' => null,
                'common_name' => null,
                'cultivar_name' => null,
            ],
            [
                'id' => 3,
                'name' => 'BASIL (GENOVESE) - BAS8Y - 5G -21 DAY',
                'supplier_soil_id' => null,
                'soil_consumable_id' => 2,
                'seed_consumable_id' => 9,
                'seed_density' => null,
                'seed_soak_hours' => 0,
                'germination_days' => 4,
                'blackout_days' => 0,
                'days_to_maturity' => 21,
                'light_days' => 17,
                'harvest_days' => 7,
                'expected_yield_grams' => 80,
                'buffer_percentage' => 10,
                'seed_density_grams_per_tray' => 5,
                'notes' => null,
                'suspend_water_hours' => 0,
                'is_active' => 1,
                'created_at' => '2025-06-24 21:16:57',
                'updated_at' => '2025-06-24 21:16:57',
                'seed_entry_id' => null,
                'common_name' => null,
                'cultivar_name' => null,
            ],
        ]);
    }
}