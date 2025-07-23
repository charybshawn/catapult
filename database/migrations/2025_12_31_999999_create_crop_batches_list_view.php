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
            CREATE OR REPLACE VIEW crop_batches_list_view AS
            SELECT 
                cb.id,
                cb.recipe_id,
                cb.order_id,
                cb.crop_plan_id,
                cb.created_at,
                cb.updated_at,
                r.name as recipe_name,
                COUNT(c.id) as crop_count,
                GROUP_CONCAT(c.tray_number ORDER BY c.tray_number SEPARATOR ', ') as tray_numbers,
                MIN(c.current_stage_id) as current_stage_id,
                cs.name as current_stage_name,
                cs.code as current_stage_code,
                MIN(c.soaking_at) as soaking_at,
                MIN(c.germination_at) as germination_at,
                MIN(c.blackout_at) as blackout_at,
                MIN(c.light_at) as light_at,
                MIN(c.harvested_at) as harvested_at,
                MIN(c.expected_harvest_at) as expected_harvest_at,
                MIN(CASE WHEN c.watering_suspended_at IS NOT NULL THEN c.watering_suspended_at END) as watering_suspended_at,
                -- Use MIN for consistent values across the batch
                MIN(c.time_to_next_stage_minutes) as time_to_next_stage_minutes,
                MIN(c.time_to_next_stage_display) as time_to_next_stage_display,
                MIN(c.stage_age_minutes) as stage_age_minutes,
                MIN(c.stage_age_display) as stage_age_display,
                MIN(c.total_age_minutes) as total_age_minutes,
                MIN(c.total_age_display) as total_age_display,
                -- Calculate planting_at as the earliest non-null stage timestamp
                LEAST(
                    COALESCE(MIN(c.soaking_at), '9999-12-31'),
                    COALESCE(MIN(c.germination_at), '9999-12-31'),
                    COALESCE(MIN(c.blackout_at), '9999-12-31'),
                    COALESCE(MIN(c.light_at), '9999-12-31')
                ) as planting_at
            FROM crop_batches cb
            INNER JOIN recipes r ON cb.recipe_id = r.id
            LEFT JOIN crops c ON c.crop_batch_id = cb.id
            LEFT JOIN crop_stages cs ON c.current_stage_id = cs.id
            GROUP BY cb.id, cb.recipe_id, cb.order_id, cb.crop_plan_id, 
                     cb.created_at, cb.updated_at, r.name,
                     cs.name, cs.code
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
