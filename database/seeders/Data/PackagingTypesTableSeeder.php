<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagingTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        
        // Clear existing data
        DB::table('packaging_types')->truncate();

        // Insert data
        DB::table('packaging_types')->insert([
            [
                'id' => 1,
                'name' => 'Clamshell (16oz)',
                'type_category_id' => 1,
                'unit_type_id' => 1,
                'capacity_volume' => 16,
                'volume_unit' => 'oz',
                'description' => null,
                'is_active' => 1,
                'cost_per_unit' => 0,
                'created_at' => '2025-06-24 18:01:03',
                'updated_at' => '2025-06-24 18:01:03',
            ],
            [
                'id' => 2,
                'name' => 'Clamshell (24oz)',
                'type_category_id' => 1,
                'unit_type_id' => 1,
                'capacity_volume' => 24,
                'volume_unit' => 'oz',
                'description' => null,
                'is_active' => 1,
                'cost_per_unit' => 0,
                'created_at' => '2025-06-24 18:01:03',
                'updated_at' => '2025-06-24 18:01:03',
            ],
            [
                'id' => 3,
                'name' => 'Clamshell (32oz)',
                'type_category_id' => 1,
                'unit_type_id' => 1,
                'capacity_volume' => 32,
                'volume_unit' => 'oz',
                'description' => null,
                'is_active' => 1,
                'cost_per_unit' => 0,
                'created_at' => '2025-06-24 18:01:03',
                'updated_at' => '2025-06-24 18:01:03',
            ],
            [
                'id' => 4,
                'name' => 'Clamshell (48oz)',
                'type_category_id' => 1,
                'unit_type_id' => 1,
                'capacity_volume' => 48,
                'volume_unit' => 'oz',
                'description' => null,
                'is_active' => 1,
                'cost_per_unit' => 0,
                'created_at' => '2025-06-24 18:01:04',
                'updated_at' => '2025-06-24 18:01:04',
            ],
            [
                'id' => 5,
                'name' => 'Clamshell (64oz)',
                'type_category_id' => 1,
                'unit_type_id' => 1,
                'capacity_volume' => 64,
                'volume_unit' => 'oz',
                'description' => null,
                'is_active' => 1,
                'cost_per_unit' => 0,
                'created_at' => '2025-06-24 18:01:04',
                'updated_at' => '2025-06-24 18:01:04',
            ],
        ]);
        
        // Re-enable foreign key checks
    }
}