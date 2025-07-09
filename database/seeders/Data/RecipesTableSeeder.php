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
        // Clear existing data safely (disable foreign key checks temporarily)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('recipes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get consumables by name/type to avoid hardcoded IDs
        $sunflowerConsumable = DB::table('consumables')->where('name', 'like', '%Sunflower%')->first();
        $basilConsumable = DB::table('consumables')->where('name', 'like', '%Basil (Genovese)%')->first();
        $soilConsumable = DB::table('consumables')->where('type', 'soil')->first(); // If soil consumables exist

        // Insert data
        DB::table('recipes')->insert([
            [
                'id' => 1,
                'name' => 'SUNFLOWER - BLACK OIL - SF4K - 100G',
                'soil_consumable_id' => $soilConsumable ? $soilConsumable->id : null,
                'seed_consumable_id' => $sunflowerConsumable ? $sunflowerConsumable->id : null,
                'seed_density' => 1, // Default density
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
                'common_name' => null,
                'cultivar_name' => null,
            ],
            [
                'id' => 2,
                'name' => 'SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS',
                'soil_consumable_id' => $soilConsumable ? $soilConsumable->id : null,
                'seed_consumable_id' => $sunflowerConsumable ? $sunflowerConsumable->id : null,
                'seed_density' => 1, // Default density
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
                'common_name' => null,
                'cultivar_name' => null,
            ],
            [
                'id' => 3,
                'name' => 'BASIL (GENOVESE) - BAS8Y - 5G -21 DAY',
                'soil_consumable_id' => $soilConsumable ? $soilConsumable->id : null,
                'seed_consumable_id' => $basilConsumable ? $basilConsumable->id : null,
                'seed_density' => 1, // Default density
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
                'common_name' => null,
                'cultivar_name' => null,
            ],
        ]);
    }
}