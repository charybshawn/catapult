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
        DB::statement('DROP VIEW IF EXISTS crop_batches_list_view');
        
        DB::statement("
            CREATE VIEW crop_batches_list_view AS
            SELECT 
                cb.id,
                cb.recipe_id,
                cb.created_at,
                cb.updated_at,
                
                -- Recipe info
                r.name as recipe_name,
                
                -- Aggregate crop data
                COUNT(c.id) as crop_count,
                GROUP_CONCAT(c.tray_number ORDER BY c.tray_number SEPARATOR ', ') as tray_numbers,
                
                -- First crop's data (for display)
                MIN(c.id) as first_crop_id,
                MIN(c.planting_at) as planting_at,
                MIN(c.current_stage_id) as current_stage_id,
                MIN(c.expected_harvest_at) as expected_harvest_at,
                MIN(c.watering_suspended_at) as watering_suspended_at,
                
                -- Time display fields from first crop
                (SELECT stage_age_display FROM crops WHERE id = MIN(c.id)) as stage_age_display,
                (SELECT time_to_next_stage_display FROM crops WHERE id = MIN(c.id)) as time_to_next_stage_display,
                (SELECT total_age_display FROM crops WHERE id = MIN(c.id)) as total_age_display,
                
                -- Stage info
                cs.name as current_stage_name,
                cs.code as current_stage_code,
                cs.color as current_stage_color,
                
                -- Additional computed fields to avoid N+1 queries
                (SELECT stage_age_minutes FROM crops WHERE id = MIN(c.id)) as stage_age_minutes,
                (SELECT time_to_next_stage_minutes FROM crops WHERE id = MIN(c.id)) as time_to_next_stage_minutes,
                (SELECT total_age_minutes FROM crops WHERE id = MIN(c.id)) as total_age_minutes
                
            FROM crop_batches cb
            INNER JOIN crops c ON c.crop_batch_id = cb.id
            LEFT JOIN recipes r ON cb.recipe_id = r.id
            LEFT JOIN crop_stages cs ON c.current_stage_id = cs.id
            GROUP BY 
                cb.id, 
                cb.recipe_id, 
                cb.created_at, 
                cb.updated_at, 
                r.name,
                cs.name,
                cs.code,
                cs.color
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS crop_batches_list_view');
    }
};