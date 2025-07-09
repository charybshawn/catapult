<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductMixesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data safely (disable foreign key checks temporarily)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_mix_components')->truncate();
        DB::table('product_mixes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Insert product mixes
        DB::table('product_mixes')->insert([
            [
                'id' => 1,
                'name' => 'Rainbow Mix',
                'description' => '',
                'is_active' => 1,
                'created_at' => '2025-07-08 19:15:47',
                'updated_at' => '2025-07-08 19:15:47',
            ],
            [
                'id' => 2,
                'name' => 'Spicy Mix',
                'description' => '',
                'is_active' => 1,
                'created_at' => '2025-07-08 19:17:09',
                'updated_at' => '2025-07-08 19:17:09',
            ],
        ]);

        // Insert product mix components based on current data
        DB::table('product_mix_components')->insert([
            // Rainbow Mix components
            [
                'id' => 1,
                'product_mix_id' => 1,
                'master_seed_catalog_id' => 15,
                'percentage' => 25.00000,
                'cultivar' => 'Speckled',
                'created_at' => '2025-07-08 19:15:47',
                'updated_at' => '2025-07-08 19:15:47',
            ],
            [
                'id' => 2,
                'product_mix_id' => 1,
                'master_seed_catalog_id' => 16,
                'percentage' => 15.00000,
                'cultivar' => 'Ruby Stem',
                'created_at' => '2025-07-08 19:15:47',
                'updated_at' => '2025-07-08 19:15:47',
            ],
            [
                'id' => 3,
                'product_mix_id' => 1,
                'master_seed_catalog_id' => 17,
                'percentage' => 35.00000,
                'cultivar' => 'Black Oilseed',
                'created_at' => '2025-07-08 19:15:47',
                'updated_at' => '2025-07-08 19:15:47',
            ],
            [
                'id' => 4,
                'product_mix_id' => 1,
                'master_seed_catalog_id' => 7,
                'percentage' => 10.00000,
                'cultivar' => 'Red',
                'created_at' => '2025-07-08 19:15:47',
                'updated_at' => '2025-07-08 19:15:47',
            ],
            // Spicy Mix components
            [
                'id' => 5,
                'product_mix_id' => 2,
                'master_seed_catalog_id' => 14,
                'percentage' => 28.00000,
                'cultivar' => 'Oriental',
                'created_at' => '2025-07-08 19:17:09',
                'updated_at' => '2025-07-08 19:17:09',
            ],
            [
                'id' => 6,
                'product_mix_id' => 2,
                'master_seed_catalog_id' => 2,
                'percentage' => 18.00000,
                'cultivar' => 'Arugula',
                'created_at' => '2025-07-08 19:17:09',
                'updated_at' => '2025-07-08 19:17:09',
            ],
            [
                'id' => 7,
                'product_mix_id' => 2,
                'master_seed_catalog_id' => 12,
                'percentage' => 9.00000,
                'cultivar' => 'Red',
                'created_at' => '2025-07-08 19:17:09',
                'updated_at' => '2025-07-08 19:17:09',
            ],
            [
                'id' => 8,
                'product_mix_id' => 2,
                'master_seed_catalog_id' => 16,
                'percentage' => 35.00000,
                'cultivar' => 'Ruby Stem',
                'created_at' => '2025-07-08 19:17:09',
                'updated_at' => '2025-07-08 19:17:09',
            ],
            [
                'id' => 9,
                'product_mix_id' => 2,
                'master_seed_catalog_id' => 7,
                'percentage' => 10.00000,
                'cultivar' => 'Red',
                'created_at' => '2025-07-08 19:17:09',
                'updated_at' => '2025-07-08 19:17:09',
            ],
        ]);
    }
}