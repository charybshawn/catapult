<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSeedCatalogTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing data
        DB::table('master_seed_catalog')->truncate();

        // Insert data (linking to first cultivar of each type)
        DB::table('master_seed_catalog')->insert([
            [
                'id' => 1,
                'common_name' => 'Amaranth',
                'cultivar_id' => 1, // Red
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:08',
                'updated_at' => '2025-07-03 09:19:08',
            ],
            [
                'id' => 2,
                'common_name' => 'Arugula',
                'cultivar_id' => 2, // Arugula
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:08',
                'updated_at' => '2025-07-03 09:19:08',
            ],
            [
                'id' => 3,
                'common_name' => 'Basil',
                'cultivar_id' => 3, // Genovese (first of multiple)
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:08',
                'updated_at' => '2025-07-03 09:19:08',
            ],
            [
                'id' => 4,
                'common_name' => 'Beet',
                'cultivar_id' => 5, // Ruby
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:08',
                'updated_at' => '2025-07-03 09:19:08',
            ],
            [
                'id' => 5,
                'common_name' => 'Borage',
                'cultivar_id' => 6, // Borage
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:26',
                'updated_at' => '2025-07-03 09:19:26',
            ],
            [
                'id' => 6,
                'common_name' => 'Broccoli',
                'cultivar_id' => 7, // Broccoli
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:26',
                'updated_at' => '2025-07-03 09:19:26',
            ],
            [
                'id' => 7,
                'common_name' => 'Cabbage',
                'cultivar_id' => 9, // Red
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:26',
                'updated_at' => '2025-07-03 09:19:26',
            ],
            [
                'id' => 8,
                'common_name' => 'Clover',
                'cultivar_id' => null, // No cultivar in MasterCultivarsTableSeeder
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:40',
                'updated_at' => '2025-07-03 09:19:40',
            ],
            [
                'id' => 9,
                'common_name' => 'Coriander',
                'cultivar_id' => 10, // Coriander
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:40',
                'updated_at' => '2025-07-03 09:19:40',
            ],
            [
                'id' => 10,
                'common_name' => 'Cress',
                'cultivar_id' => 11, // Curly (Garden )
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:40',
                'updated_at' => '2025-07-03 09:19:40',
            ],
            [
                'id' => 11,
                'common_name' => 'Fenugreek',
                'cultivar_id' => 12, // Fenugreek
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:56',
                'updated_at' => '2025-07-03 09:19:56',
            ],
            [
                'id' => 12,
                'common_name' => 'Kale',
                'cultivar_id' => 13, // Green (first of multiple)
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:56',
                'updated_at' => '2025-07-03 09:19:56',
            ],
            [
                'id' => 13,
                'common_name' => 'Kohlrabi',
                'cultivar_id' => 15, // Purple
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:19:56',
                'updated_at' => '2025-07-03 09:19:56',
            ],
            [
                'id' => 14,
                'common_name' => 'Mustard',
                'cultivar_id' => 16, // Oriental
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:20:38',
                'updated_at' => '2025-07-03 09:20:38',
            ],
            [
                'id' => 15,
                'common_name' => 'Peas',
                'cultivar_id' => 17, // Speckled
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:20:38',
                'updated_at' => '2025-07-03 09:20:38',
            ],
            [
                'id' => 16,
                'common_name' => 'Radish',
                'cultivar_id' => 18, // Red (first of multiple)
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:20:38',
                'updated_at' => '2025-07-03 09:20:38',
            ],
            [
                'id' => 17,
                'common_name' => 'Sunflower',
                'cultivar_id' => 20, // Black Oilseed
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:20:38',
                'updated_at' => '2025-07-03 09:20:38',
            ],
            [
                'id' => 18,
                'common_name' => 'Swiss Chard',
                'cultivar_id' => 21, // Yellow (first of multiple)
                'category' => null,
                'aliases' => null,
                'growing_notes' => null,
                'description' => null,
                'is_active' => 1,
                'created_at' => '2025-07-03 09:20:54',
                'updated_at' => '2025-07-03 09:20:54',
            ],
        ]);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
