<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('suppliers')->truncate();

        // Insert data
        DB::table('suppliers')->insert([
            [
                'id' => 1,
                'name' => 'Mumm\'s Sprouting Seeds',
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-23 03:01:59',
                'updated_at' => '2025-06-24 18:01:46',
            ],
            [
                'id' => 2,
                'name' => 'Germina Seeds',
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 17:51:32',
                'updated_at' => '2025-06-24 18:01:28',
            ],
            [
                'id' => 3,
                'name' => 'Britelands',
                'type' => 'packaging',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:01:03',
                'updated_at' => '2025-06-24 18:06:10',
            ],
            [
                'id' => 4,
                'name' => 'William Dam Seeds',
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:01:09',
                'updated_at' => '2025-06-24 18:01:09',
            ],
            [
                'id' => 5,
                'name' => 'Ecoline',
                'type' => 'soil',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:06:28',
                'updated_at' => '2025-06-24 18:06:28',
            ],
            [
                'id' => 6,
                'name' => 'Buckerfields',
                'type' => 'soil',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:06:47',
                'updated_at' => '2025-06-24 18:07:02',
            ],
            [
                'id' => 7,
                'name' => 'Johnny\'s Seeds',
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:07:22',
                'updated_at' => '2025-06-24 18:07:22',
            ],
            [
                'id' => 8,
                'name' => 'True Leaf Market',
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:07:43',
                'updated_at' => '2025-06-24 18:07:43',
            ],
        ]);
    }
}