<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('permissions')->truncate();

        // Insert data
        DB::table('permissions')->insert([
            [
                'id' => 1,
                'name' => 'manage products',
                'guard_name' => 'web',
                'created_at' => '2025-06-23 02:33:31',
                'updated_at' => '2025-06-23 02:33:31',
            ],
            [
                'id' => 2,
                'name' => 'view products',
                'guard_name' => 'web',
                'created_at' => '2025-06-23 02:33:31',
                'updated_at' => '2025-06-23 02:33:31',
            ],
            [
                'id' => 3,
                'name' => 'edit products',
                'guard_name' => 'web',
                'created_at' => '2025-06-23 02:33:31',
                'updated_at' => '2025-06-23 02:33:31',
            ],
            [
                'id' => 4,
                'name' => 'delete products',
                'guard_name' => 'web',
                'created_at' => '2025-06-23 02:33:31',
                'updated_at' => '2025-06-23 02:33:31',
            ],
            [
                'id' => 5,
                'name' => 'access filament',
                'guard_name' => 'web',
                'created_at' => '2025-06-23 02:33:31',
                'updated_at' => '2025-06-23 02:33:31',
            ],
        ]);
    }
}