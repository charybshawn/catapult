<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('roles')->truncate();

        // Insert data
        DB::table('roles')->insert([
            [
                'id' => 1,
                'name' => 'admin',
                'guard_name' => 'web',
                'created_at' => '2025-06-23 02:33:23',
                'updated_at' => '2025-06-23 02:33:23',
            ],
            [
                'id' => 2,
                'name' => 'manager',
                'guard_name' => 'web',
                'created_at' => '2025-06-23 02:33:31',
                'updated_at' => '2025-06-23 02:33:31',
            ],
            [
                'id' => 3,
                'name' => 'user',
                'guard_name' => 'web',
                'created_at' => '2025-06-23 02:33:31',
                'updated_at' => '2025-06-23 02:33:31',
            ],
        ]);
    }
}