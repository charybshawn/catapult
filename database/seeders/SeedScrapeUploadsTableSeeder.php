<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedScrapeUploadsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('seed_scrape_uploads')->truncate();

        // Insert data
        DB::table('seed_scrape_uploads')->insert([
            [
                'id' => 1,
                'original_filename' => 'sprouting_com_detailed_20250609_103826.json',
                'status' => 'processing',
                'uploaded_at' => '2025-06-23 03:02:01',
                'processed_at' => null,
                'notes' => null,
                'failed_entries' => null,
                'total_entries' => 0,
                'successful_entries' => 0,
                'failed_entries_count' => 0,
                'created_at' => '2025-06-23 03:02:01',
                'updated_at' => '2025-06-23 03:02:01',
            ],
            [
                'id' => 2,
                'original_filename' => 'sprouting_com_detailed_20250603_103912.json',
                'status' => 'completed',
                'uploaded_at' => '2025-06-24 16:05:14',
                'processed_at' => '2025-06-24 16:05:18',
                'notes' => 'Processed 87/87 products successfully with supplier: Sprouting Seeds.',
                'failed_entries' => '[]',
                'total_entries' => 87,
                'successful_entries' => 87,
                'failed_entries_count' => 0,
                'created_at' => '2025-06-24 16:05:14',
                'updated_at' => '2025-06-24 16:05:18',
            ],
        ]);
    }
}