<?php

namespace Database\Seeders\Legacy;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskSchedulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('task_schedules')->truncate();

        // Insert data
        DB::table('task_schedules')->insert([
            [
                'id' => 1,
                'resource_type' => 'crops',
                'task_name' => 'advance_to_blackout',
                'frequency' => 'once',
                'time_of_day' => null,
                'day_of_week' => null,
                'day_of_month' => null,
                'conditions' => '{\\\"crop_id\\\": 1, \\\"variety\\\": \\\"SUNFLOWER - BLACK OIL - SF4K - 100G\\\", \\\"tray_list\\\": \\\"11, 12\\\", \\\"tray_count\\\": 2, \\\"target_stage\\\": \\\"blackout\\\", \\\"tray_numbers\\\": [\\\"11\\\", \\\"12\\\"], \\\"batch_identifier\\\": \\\"1_2025-06-24_germination\\\"}',
                'is_active' => 1,
                'last_run_at' => null,
                'next_run_at' => '2025-06-27 17:00:59',
                'created_at' => '2025-06-24 17:01:12',
                'updated_at' => '2025-06-24 17:01:12',
            ],
            [
                'id' => 2,
                'resource_type' => 'crops',
                'task_name' => 'advance_to_harvested',
                'frequency' => 'once',
                'time_of_day' => null,
                'day_of_week' => null,
                'day_of_month' => null,
                'conditions' => '{\\\"crop_id\\\": 1, \\\"variety\\\": \\\"SUNFLOWER - BLACK OIL - SF4K - 100G\\\", \\\"tray_list\\\": \\\"11, 12\\\", \\\"tray_count\\\": 2, \\\"target_stage\\\": \\\"harvested\\\", \\\"tray_numbers\\\": [\\\"11\\\", \\\"12\\\"], \\\"batch_identifier\\\": \\\"1_2025-06-24_germination\\\"}',
                'is_active' => 1,
                'last_run_at' => null,
                'next_run_at' => '2025-07-03 17:00:59',
                'created_at' => '2025-06-24 17:01:12',
                'updated_at' => '2025-06-24 17:01:12',
            ],
            [
                'id' => 3,
                'resource_type' => 'crops',
                'task_name' => 'suspend_watering',
                'frequency' => 'once',
                'time_of_day' => null,
                'day_of_week' => null,
                'day_of_month' => null,
                'conditions' => '{\\\"crop_id\\\": 1}',
                'is_active' => 1,
                'last_run_at' => null,
                'next_run_at' => '2025-07-02 17:00:59',
                'created_at' => '2025-06-24 17:01:12',
                'updated_at' => '2025-06-24 17:01:12',
            ],
            [
                'id' => 4,
                'resource_type' => 'crops',
                'task_name' => 'advance_to_light',
                'frequency' => 'once',
                'time_of_day' => null,
                'day_of_week' => null,
                'day_of_month' => null,
                'conditions' => '{\\\"crop_id\\\": 3, \\\"variety\\\": \\\"SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS\\\", \\\"tray_list\\\": \\\"13, 14, 15, 16, 17, 18, 19, 20\\\", \\\"tray_count\\\": 8, \\\"target_stage\\\": \\\"light\\\", \\\"tray_numbers\\\": [\\\"13\\\", \\\"14\\\", \\\"15\\\", \\\"16\\\", \\\"17\\\", \\\"18\\\", \\\"19\\\", \\\"20\\\"], \\\"batch_identifier\\\": \\\"2_2025-06-24_germination\\\"}',
                'is_active' => 1,
                'last_run_at' => null,
                'next_run_at' => '2025-06-27 17:56:42',
                'created_at' => '2025-06-24 17:57:05',
                'updated_at' => '2025-06-24 17:57:05',
            ],
            [
                'id' => 5,
                'resource_type' => 'crops',
                'task_name' => 'advance_to_harvested',
                'frequency' => 'once',
                'time_of_day' => null,
                'day_of_week' => null,
                'day_of_month' => null,
                'conditions' => '{\\\"crop_id\\\": 3, \\\"variety\\\": \\\"SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS\\\", \\\"tray_list\\\": \\\"13, 14, 15, 16, 17, 18, 19, 20\\\", \\\"tray_count\\\": 8, \\\"target_stage\\\": \\\"harvested\\\", \\\"tray_numbers\\\": [\\\"13\\\", \\\"14\\\", \\\"15\\\", \\\"16\\\", \\\"17\\\", \\\"18\\\", \\\"19\\\", \\\"20\\\"], \\\"batch_identifier\\\": \\\"2_2025-06-24_germination\\\"}',
                'is_active' => 1,
                'last_run_at' => null,
                'next_run_at' => '2025-07-03 17:56:42',
                'created_at' => '2025-06-24 17:57:05',
                'updated_at' => '2025-06-24 17:57:05',
            ],
            [
                'id' => 6,
                'resource_type' => 'crops',
                'task_name' => 'suspend_watering',
                'frequency' => 'once',
                'time_of_day' => null,
                'day_of_week' => null,
                'day_of_month' => null,
                'conditions' => '{\\\"crop_id\\\": 3}',
                'is_active' => 1,
                'last_run_at' => null,
                'next_run_at' => '2025-07-02 17:56:42',
                'created_at' => '2025-06-24 17:57:05',
                'updated_at' => '2025-06-24 17:57:05',
            ],
            [
                'id' => 7,
                'resource_type' => 'crops',
                'task_name' => 'advance_to_light',
                'frequency' => 'once',
                'time_of_day' => null,
                'day_of_week' => null,
                'day_of_month' => null,
                'conditions' => '{\\\"crop_id\\\": 11, \\\"variety\\\": \\\"BASIL (GENOVESE) - BAS8Y - 5G -21 DAY\\\", \\\"tray_list\\\": \\\"21, 22\\\", \\\"tray_count\\\": 2, \\\"target_stage\\\": \\\"light\\\", \\\"tray_numbers\\\": [\\\"21\\\", \\\"22\\\"], \\\"batch_identifier\\\": \\\"3_2025-06-24_germination\\\"}',
                'is_active' => 1,
                'last_run_at' => null,
                'next_run_at' => '2025-06-28 21:54:53',
                'created_at' => '2025-06-24 21:55:14',
                'updated_at' => '2025-06-24 21:55:14',
            ],
            [
                'id' => 8,
                'resource_type' => 'crops',
                'task_name' => 'advance_to_harvested',
                'frequency' => 'once',
                'time_of_day' => null,
                'day_of_week' => null,
                'day_of_month' => null,
                'conditions' => '{\\\"crop_id\\\": 11, \\\"variety\\\": \\\"BASIL (GENOVESE) - BAS8Y - 5G -21 DAY\\\", \\\"tray_list\\\": \\\"21, 22\\\", \\\"tray_count\\\": 2, \\\"target_stage\\\": \\\"harvested\\\", \\\"tray_numbers\\\": [\\\"21\\\", \\\"22\\\"], \\\"batch_identifier\\\": \\\"3_2025-06-24_germination\\\"}',
                'is_active' => 1,
                'last_run_at' => null,
                'next_run_at' => '2025-07-15 21:54:53',
                'created_at' => '2025-06-24 21:55:15',
                'updated_at' => '2025-06-24 21:55:15',
            ],
        ]);
    }
}