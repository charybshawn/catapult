<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagingTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('packaging_types')->truncate();

        // Insert data
        DB::table('packaging_types')->insert([
            [
                'id' => 1,
                'name' => '16oz Clamshell',
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
                'name' => '24oz Clamshell',
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
                'name' => '32oz Clamshell',
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
                'name' => '48oz Clamshell',
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
                'name' => '64oz Clamshell',
                'capacity_volume' => 64,
                'volume_unit' => 'oz',
                'description' => null,
                'is_active' => 1,
                'cost_per_unit' => 0,
                'created_at' => '2025-06-24 18:01:04',
                'updated_at' => '2025-06-24 18:01:04',
            ],
        ]);
    }
}