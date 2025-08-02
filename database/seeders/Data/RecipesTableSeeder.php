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

        // Get specific consumables by lot number to match recipes
        $sunflowerSfr16Consumable = DB::table('consumables')->where('lot_no', 'SFR16')->first();
        $basilBas8yConsumable = DB::table('consumables')->where('lot_no', 'BAS8Y')->first();

        // Note: We'll use SFR16 for both sunflower recipes since it's the only available lot
        $soilConsumable = DB::table('consumables')
            ->join('consumable_types', 'consumables.consumable_type_id', '=', 'consumable_types.id')
            ->where('consumable_types.code', 'soil')
            ->where('consumables.name', 'Pro Mix HP')
            ->select('consumables.*')
            ->first();

        // Get master seed catalog entries directly
        $sunflowerCatalog = DB::table('master_seed_catalog')
            ->where('common_name', 'Sunflower')
            ->first();

        $basilCatalog = DB::table('master_seed_catalog')
            ->where('common_name', 'Basil')
            ->first();

        // Get master cultivars by matching names
        $sunflowerCultivar = DB::table('master_cultivars')
            ->join('master_seed_catalog', 'master_cultivars.master_seed_catalog_id', '=', 'master_seed_catalog.id')
            ->where('master_seed_catalog.common_name', 'Sunflower')
            ->where('master_cultivars.cultivar_name', 'Black Oilseed')
            ->select('master_cultivars.*')
            ->first();

        $basilCultivar = DB::table('master_cultivars')
            ->join('master_seed_catalog', 'master_cultivars.master_seed_catalog_id', '=', 'master_seed_catalog.id')
            ->where('master_seed_catalog.common_name', 'Basil')
            ->where('master_cultivars.cultivar_name', 'Genovese')
            ->select('master_cultivars.*')
            ->first();

        // Insert data
        DB::table('recipes')->insert([
            [
                'id' => 1,
                'name' => 'SUNFLOWER - BLACK OIL - SF4K - 100G',
                'master_seed_catalog_id' => $sunflowerCatalog ? $sunflowerCatalog->id : null,
                'master_cultivar_id' => $sunflowerCultivar ? $sunflowerCultivar->id : null,
                'soil_consumable_id' => $soilConsumable ? $soilConsumable->id : null,
                'seed_consumable_id' => $sunflowerSfr16Consumable ? $sunflowerSfr16Consumable->id : null,
                'lot_number' => 'SF4K',
                'seed_soak_hours' => 9,
                'germination_days' => 3,
                'blackout_days' => 1,
                'days_to_maturity' => 9,
                'light_days' => 5,
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
                'master_seed_catalog_id' => $sunflowerCatalog ? $sunflowerCatalog->id : null,
                'master_cultivar_id' => $sunflowerCultivar ? $sunflowerCultivar->id : null,
                'lot_number' => 'SFK16',
                'soil_consumable_id' => $soilConsumable ? $soilConsumable->id : null,
                'seed_consumable_id' => $sunflowerSfr16Consumable ? $sunflowerSfr16Consumable->id : null,
                'seed_soak_hours' => 4,
                'germination_days' => 3,
                'blackout_days' => 0,
                'days_to_maturity' => 9,
                'light_days' => 6,
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
                'master_seed_catalog_id' => $basilCatalog ? $basilCatalog->id : null,
                'master_cultivar_id' => $basilCultivar ? $basilCultivar->id : null,
                'soil_consumable_id' => $soilConsumable ? $soilConsumable->id : null,
                'seed_consumable_id' => $basilBas8yConsumable ? $basilBas8yConsumable->id : null,
                'lot_number' => 'BAS8Y',
                'seed_soak_hours' => 0,
                'germination_days' => 4,
                'blackout_days' => 0,
                'days_to_maturity' => 21,
                'light_days' => 17,
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
