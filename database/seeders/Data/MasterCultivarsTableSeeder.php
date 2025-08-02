<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCultivarsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        DB::table('master_cultivars')->truncate();

        // Insert data with correct catalog-to-cultivar mappings
        DB::table('master_cultivars')->insert([
            // Amaranth (ID: 1)
            [
                'id' => 1,
                'master_seed_catalog_id' => 1,
                'cultivar_name' => 'Red',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 16:57:48',
                'updated_at' => '2025-06-24 16:57:48',
            ],
            // Arugula (ID: 2)
            [
                'id' => 2,
                'master_seed_catalog_id' => 2,
                'cultivar_name' => 'Arugula',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 21:14:14',
                'updated_at' => '2025-06-24 21:14:14',
            ],
            // Basil (ID: 3)
            [
                'id' => 3,
                'master_seed_catalog_id' => 3,
                'cultivar_name' => 'Genovese',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 21:14:14',
                'updated_at' => '2025-06-24 21:14:14',
            ],
            [
                'id' => 4,
                'master_seed_catalog_id' => 3,
                'cultivar_name' => 'Thai',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:22:14',
                'updated_at' => '2025-06-25 13:22:14',
            ],
            // Beet (ID: 4)
            [
                'id' => 5,
                'master_seed_catalog_id' => 4,
                'cultivar_name' => 'Ruby',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:22:14',
                'updated_at' => '2025-06-25 13:22:14',
            ],
            // Borage (ID: 5)
            [
                'id' => 6,
                'master_seed_catalog_id' => 5,
                'cultivar_name' => 'Borage',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:22:37',
                'updated_at' => '2025-06-25 13:22:37',
            ],
            // Broccoli (ID: 6)
            [
                'id' => 7,
                'master_seed_catalog_id' => 6,
                'cultivar_name' => 'Broccoli',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:22:37',
                'updated_at' => '2025-06-25 13:22:37',
            ],
            [
                'id' => 8,
                'master_seed_catalog_id' => 6,
                'cultivar_name' => 'Raab (Rapini)',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:22:37',
                'updated_at' => '2025-06-25 13:22:37',
            ],
            // Cabbage (ID: 7)
            [
                'id' => 9,
                'master_seed_catalog_id' => 7,
                'cultivar_name' => 'Red',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Clover (ID: 8) - Skip for now
            // Coriander (ID: 9)
            [
                'id' => 10,
                'master_seed_catalog_id' => 9,
                'cultivar_name' => 'Coriander',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Cress (ID: 10)
            [
                'id' => 11,
                'master_seed_catalog_id' => 10,
                'cultivar_name' => 'Curly (Garden )',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Fenugreek (ID: 11)
            [
                'id' => 12,
                'master_seed_catalog_id' => 11,
                'cultivar_name' => 'Fenugreek',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Kale (ID: 12)
            [
                'id' => 13,
                'master_seed_catalog_id' => 12,
                'cultivar_name' => 'Green',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            [
                'id' => 14,
                'master_seed_catalog_id' => 12,
                'cultivar_name' => 'Red',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Kohlrabi (ID: 13)
            [
                'id' => 15,
                'master_seed_catalog_id' => 13,
                'cultivar_name' => 'Purple',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Mustard (ID: 14)
            [
                'id' => 16,
                'master_seed_catalog_id' => 14,
                'cultivar_name' => 'Oriental',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Peas, (ID: 15)
            [
                'id' => 17,
                'master_seed_catalog_id' => 15,
                'cultivar_name' => 'Speckled',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Radish (ID: 16)
            [
                'id' => 18,
                'master_seed_catalog_id' => 16,
                'cultivar_name' => 'Red',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            [
                'id' => 19,
                'master_seed_catalog_id' => 16,
                'cultivar_name' => 'Ruby Stem',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Sunflower (ID: 17)
            [
                'id' => 20,
                'master_seed_catalog_id' => 17,
                'cultivar_name' => 'Black Oilseed',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            // Swiss Chard (ID: 18)
            [
                'id' => 21,
                'master_seed_catalog_id' => 18,
                'cultivar_name' => 'Yellow',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
            [
                'id' => 22,
                'master_seed_catalog_id' => 18,
                'cultivar_name' => 'Red',
                'aliases' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-06-25 13:23:55',
                'updated_at' => '2025-06-25 13:23:55',
            ],
        ]);
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}