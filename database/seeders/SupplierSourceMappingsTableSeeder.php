<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSourceMappingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('supplier_source_mappings')->truncate();

        // Insert data
        DB::table('supplier_source_mappings')->insert([
            [
                'id' => 1,
                'source_url' => 'https://sprouting.com',
                'domain' => 'sprouting.com',
                'supplier_id' => 1,
                'is_active' => 1,
                'metadata' => '{\\\"created_at\\\": \\\"2025-06-24T16:05:14.423482Z\\\", \\\"import_file\\\": \\\"sprouting_com_detailed_20250603_103912.json\\\", \\\"import_method\\\": \\\"pre_selected\\\"}',
                'created_at' => '2025-06-23 03:02:01',
                'updated_at' => '2025-06-24 16:05:14',
            ],
        ]);
    }
}