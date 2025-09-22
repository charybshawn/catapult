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
                STRING_AGG(c.tray_number, ', ' ORDER BY c.tray_number) as tray_numbers,
                
                -- First crop's data (for display)
                MIN(c.id) as first_crop_id,
                MIN(c.germination_at) as germination_at,
                MIN(c.current_stage_id) as current_stage_id,
                MIN(c.watering_suspended_at) as watering_suspended_at,

                -- Stage info
                cs.name as current_stage_name,
                cs.code as current_stage_code,
                cs.color as current_stage_color
                
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