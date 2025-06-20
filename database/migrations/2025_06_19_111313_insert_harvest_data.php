<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip data insertion during testing or fresh migrations
        if (app()->environment('testing')) {
            return;
        }
        
        // Skip if master_cultivars table is empty (fresh database)
        if (DB::table('master_cultivars')->count() === 0) {
            return;
        }
        
        // Get the first available user ID, or create a default user if none exists
        $firstUserId = DB::table('users')->value('id');
        if (!$firstUserId) {
            // Create a default user if none exists
            $firstUserId = DB::table('users')->insertGetId([
                'name' => 'System',
                'email' => 'system@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        $harvests = [
            [
                'id' => 1,
                'master_cultivar_id' => 18,
                'user_id' => $firstUserId,
                'total_weight_grams' => 1165.00,
                'tray_count' => 4,
                'average_weight_per_tray' => 291.25,
                'harvest_date' => '2025-06-18',
                'week_start_date' => '2025-06-18',
                'notes' => null,
                'created_at' => '2025-06-18 12:00:05',
                'updated_at' => '2025-06-18 12:00:05'
            ],
            [
                'id' => 2,
                'master_cultivar_id' => 27,
                'user_id' => $firstUserId,
                'total_weight_grams' => 456.00,
                'tray_count' => 3,
                'average_weight_per_tray' => 152.00,
                'harvest_date' => '2025-06-18',
                'week_start_date' => '2025-06-18',
                'notes' => null,
                'created_at' => '2025-06-18 12:00:28',
                'updated_at' => '2025-06-18 12:00:28'
            ],
            [
                'id' => 3,
                'master_cultivar_id' => 36,
                'user_id' => $firstUserId,
                'total_weight_grams' => 747.00,
                'tray_count' => 4,
                'average_weight_per_tray' => 186.75,
                'harvest_date' => '2025-06-18',
                'week_start_date' => '2025-06-18',
                'notes' => null,
                'created_at' => '2025-06-18 12:00:47',
                'updated_at' => '2025-06-18 12:00:47'
            ],
            [
                'id' => 4,
                'master_cultivar_id' => 40,
                'user_id' => $firstUserId,
                'total_weight_grams' => 709.00,
                'tray_count' => 3,
                'average_weight_per_tray' => 236.33,
                'harvest_date' => '2025-06-18',
                'week_start_date' => '2025-06-18',
                'notes' => null,
                'created_at' => '2025-06-18 12:01:24',
                'updated_at' => '2025-06-18 12:01:24'
            ],
            [
                'id' => 5,
                'master_cultivar_id' => 68,
                'user_id' => $firstUserId,
                'total_weight_grams' => 600.00,
                'tray_count' => 4,
                'average_weight_per_tray' => 150.00,
                'harvest_date' => '2025-06-18',
                'week_start_date' => '2025-06-18',
                'notes' => null,
                'created_at' => '2025-06-18 12:01:40',
                'updated_at' => '2025-06-18 12:01:40'
            ],
            [
                'id' => 6,
                'master_cultivar_id' => 17,
                'user_id' => $firstUserId,
                'total_weight_grams' => 578.00,
                'tray_count' => 2,
                'average_weight_per_tray' => 289.00,
                'harvest_date' => '2025-06-18',
                'week_start_date' => '2025-06-18',
                'notes' => null,
                'created_at' => '2025-06-18 12:02:01',
                'updated_at' => '2025-06-18 12:02:01'
            ],
            [
                'id' => 7,
                'master_cultivar_id' => 1,
                'user_id' => $firstUserId,
                'total_weight_grams' => 1422.00,
                'tray_count' => 4,
                'average_weight_per_tray' => 355.50,
                'harvest_date' => '2025-06-18',
                'week_start_date' => '2025-06-18',
                'notes' => null,
                'created_at' => '2025-06-18 14:14:51',
                'updated_at' => '2025-06-18 14:14:51'
            ],
            [
                'id' => 8,
                'master_cultivar_id' => 40,
                'user_id' => $firstUserId,
                'total_weight_grams' => 708.00,
                'tray_count' => 3,
                'average_weight_per_tray' => 236.00,
                'harvest_date' => '2025-06-18',
                'week_start_date' => '2025-06-18',
                'notes' => null,
                'created_at' => '2025-06-18 15:11:32',
                'updated_at' => '2025-06-18 15:11:32'
            ]
        ];

        foreach ($harvests as $harvest) {
            // Remove the generated columns from the data
            unset($harvest['average_weight_per_tray']);
            unset($harvest['week_start_date']);
            
            // Check if harvest with this ID already exists
            if (!DB::table('harvests')->where('id', $harvest['id'])->exists()) {
                DB::table('harvests')->insert($harvest);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the inserted harvest records
        $ids = [1, 2, 3, 4, 5, 6, 7, 8];
        DB::table('harvests')->whereIn('id', $ids)->delete();
    }
};