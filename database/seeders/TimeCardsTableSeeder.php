<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeCardsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('time_cards')->truncate();

        // Insert data
        DB::table('time_cards')->insert([
            [
                'id' => 1,
                'user_id' => 2,
                'clock_in' => '2025-06-24 09:23:00',
                'clock_out' => '2025-06-24 14:33:00',
                'duration_minutes' => 310,
                'work_date' => '2025-06-24',
                'status' => 'completed',
                'max_shift_exceeded' => 0,
                'max_shift_exceeded_at' => null,
                'requires_review' => 0,
                'flags' => null,
                'review_notes' => null,
                'notes' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0',
                'created_at' => '2025-06-24 16:23:18',
                'updated_at' => '2025-06-24 22:49:51',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'clock_in' => '2025-06-24 14:51:55',
                'clock_out' => '2025-06-24 15:36:44',
                'duration_minutes' => 45,
                'work_date' => '2025-06-24',
                'status' => 'completed',
                'max_shift_exceeded' => 0,
                'max_shift_exceeded_at' => null,
                'requires_review' => 0,
                'flags' => null,
                'review_notes' => null,
                'notes' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0',
                'created_at' => '2025-06-24 21:51:55',
                'updated_at' => '2025-06-24 22:36:44',
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'clock_in' => '2025-06-24 15:42:53',
                'clock_out' => '2025-06-24 15:45:16',
                'duration_minutes' => 2,
                'work_date' => '2025-06-24',
                'status' => 'completed',
                'max_shift_exceeded' => 0,
                'max_shift_exceeded_at' => null,
                'requires_review' => 0,
                'flags' => null,
                'review_notes' => null,
                'notes' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0',
                'created_at' => '2025-06-24 22:42:53',
                'updated_at' => '2025-06-24 22:45:16',
            ],
            [
                'id' => 4,
                'user_id' => 2,
                'clock_in' => '2025-06-25 13:24:04',
                'clock_out' => '2025-06-25 17:45:56',
                'duration_minutes' => 262,
                'work_date' => '2025-06-25',
                'status' => 'completed',
                'max_shift_exceeded' => 0,
                'max_shift_exceeded_at' => null,
                'requires_review' => 0,
                'flags' => null,
                'review_notes' => null,
                'notes' => null,
                'ip_address' => '64.180.6.194',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0',
                'created_at' => '2025-06-25 13:24:04',
                'updated_at' => '2025-06-25 17:45:56',
            ],
            [
                'id' => 5,
                'user_id' => 2,
                'clock_in' => '2025-06-25 08:05:00',
                'clock_out' => '2025-06-25 13:00:00',
                'duration_minutes' => 295,
                'work_date' => '2025-06-25',
                'status' => 'completed',
                'max_shift_exceeded' => 0,
                'max_shift_exceeded_at' => null,
                'requires_review' => 0,
                'flags' => null,
                'review_notes' => null,
                'notes' => null,
                'ip_address' => null,
                'user_agent' => null,
                'created_at' => '2025-06-26 08:06:27',
                'updated_at' => '2025-06-26 08:06:56',
            ],
        ]);
    }
}