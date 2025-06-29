<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeCardTasksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('time_card_tasks')->truncate();

        // Insert data
        DB::table('time_card_tasks')->insert([
            [
                'id' => 1,
                'time_card_id' => 2,
                'task_name' => 'Computer Work',
                'task_type_id' => 17,
                'is_custom' => 0,
                'created_at' => '2025-06-24 22:36:44',
                'updated_at' => '2025-06-24 22:36:44',
            ],
            [
                'id' => 2,
                'time_card_id' => 3,
                'task_name' => 'Computer Work',
                'task_type_id' => 17,
                'is_custom' => 0,
                'created_at' => '2025-06-24 22:45:16',
                'updated_at' => '2025-06-24 22:45:16',
            ],
            [
                'id' => 4,
                'time_card_id' => 1,
                'task_name' => 'Washing Trays',
                'task_type_id' => 4,
                'is_custom' => 0,
                'created_at' => '2025-06-24 23:05:09',
                'updated_at' => '2025-06-24 23:05:09',
            ],
            [
                'id' => 5,
                'time_card_id' => 1,
                'task_name' => 'Making Trays',
                'task_type_id' => 5,
                'is_custom' => 0,
                'created_at' => '2025-06-24 23:05:09',
                'updated_at' => '2025-06-24 23:05:09',
            ],
            [
                'id' => 6,
                'time_card_id' => 1,
                'task_name' => 'Making Soil',
                'task_type_id' => 6,
                'is_custom' => 0,
                'created_at' => '2025-06-24 23:05:09',
                'updated_at' => '2025-06-24 23:05:09',
            ],
            [
                'id' => 7,
                'time_card_id' => 1,
                'task_name' => 'Planting Seeds',
                'task_type_id' => 1,
                'is_custom' => 0,
                'created_at' => '2025-06-24 23:05:09',
                'updated_at' => '2025-06-24 23:05:09',
            ],
            [
                'id' => 8,
                'time_card_id' => 1,
                'task_name' => 'Seed Soaking',
                'task_type_id' => 7,
                'is_custom' => 0,
                'created_at' => '2025-06-24 23:05:09',
                'updated_at' => '2025-06-24 23:05:09',
            ],
            [
                'id' => 9,
                'time_card_id' => 1,
                'task_name' => 'Sanitizing Equipment',
                'task_type_id' => 13,
                'is_custom' => 0,
                'created_at' => '2025-06-24 23:05:09',
                'updated_at' => '2025-06-24 23:05:09',
            ],
            [
                'id' => 10,
                'time_card_id' => 4,
                'task_name' => 'Harvesting',
                'task_type_id' => 3,
                'is_custom' => 0,
                'created_at' => '2025-06-25 17:45:56',
                'updated_at' => '2025-06-25 17:45:56',
            ],
            [
                'id' => 11,
                'time_card_id' => 4,
                'task_name' => 'Washing Trays',
                'task_type_id' => 4,
                'is_custom' => 0,
                'created_at' => '2025-06-25 17:45:56',
                'updated_at' => '2025-06-25 17:45:56',
            ],
            [
                'id' => 12,
                'time_card_id' => 4,
                'task_name' => 'Sanitizing Equipment',
                'task_type_id' => 13,
                'is_custom' => 0,
                'created_at' => '2025-06-25 17:45:56',
                'updated_at' => '2025-06-25 17:45:56',
            ],
            [
                'id' => 14,
                'time_card_id' => 5,
                'task_name' => 'Computer Work',
                'task_type_id' => 17,
                'is_custom' => 0,
                'created_at' => '2025-06-26 08:06:56',
                'updated_at' => '2025-06-26 08:06:56',
            ],
        ]);
    }
}