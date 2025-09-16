<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS crop_batches_list_view');
        
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL version
            DB::statement("
                CREATE VIEW crop_batches_list_view AS
                SELECT 
                    cb.id,
                    cb.recipe_id,
                    cb.order_id,
                    cb.crop_plan_id,
                    cb.created_at,
                    cb.updated_at,
                    r.name as recipe_name,
                    COUNT(c.id) as crop_count,
                    STRING_AGG(c.tray_number::text, ', ' ORDER BY c.tray_number) as tray_numbers,
                    MIN(c.current_stage_id) as current_stage_id,
                    cs.name as current_stage_name,
                    cs.code as current_stage_code,
                    MIN(c.soaking_at) as soaking_at,
                    MIN(c.germination_at) as germination_at,
                    MIN(c.blackout_at) as blackout_at,
                    MIN(c.light_at) as light_at,
                    NULL as harvested_at,
                    MIN(c.watering_suspended_at) as watering_suspended_at,
                    
                    -- Calculate expected harvest date dynamically
                    CASE 
                        WHEN r.days_to_maturity IS NOT NULL AND r.days_to_maturity > 0 THEN
                            COALESCE(MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at) + (r.days_to_maturity || ' days')::interval
                        ELSE NULL
                    END as expected_harvest_at,
                    
                    -- For now, just return 0 for time calculations since they depend on stage durations
                    0 as time_to_next_stage_minutes,
                    'Calculating...' as time_to_next_stage_display,
                    0 as stage_age_minutes,
                    '0m' as stage_age_display,
                    0 as total_age_minutes,
                    '0m' as total_age_display,
                    
                    -- Calculate planting_at as the earliest timestamp
                    COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at) as planting_at
                FROM crop_batches cb
                JOIN crops c ON c.crop_batch_id = cb.id
                JOIN recipes r ON cb.recipe_id = r.id
                LEFT JOIN crop_stages cs ON c.current_stage_id = cs.id
                GROUP BY cb.id, cb.recipe_id, cb.order_id, cb.crop_plan_id, 
                         cb.created_at, cb.updated_at, r.name, r.days_to_maturity,
                         cs.name, cs.code
            ");
        } else {
            // MySQL version (original)
            DB::statement("
                CREATE VIEW crop_batches_list_view AS
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
                    NULL as harvested_at,
                    MIN(c.watering_suspended_at) as watering_suspended_at,
                    
                    -- Calculate expected harvest date dynamically
                    CASE 
                        WHEN r.days_to_maturity IS NOT NULL AND r.days_to_maturity > 0 THEN
                            DATE_ADD(
                                COALESCE(MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at),
                                INTERVAL r.days_to_maturity DAY
                            )
                        ELSE NULL
                    END as expected_harvest_at,
                    
                    -- For now, just return 0 for time calculations since they depend on stage durations
                    0 as time_to_next_stage_minutes,
                    'Calculating...' as time_to_next_stage_display,
                    0 as stage_age_minutes,
                    '0m' as stage_age_display,
                    0 as total_age_minutes,
                    '0m' as total_age_display,
                    
                    -- Calculate planting_at as the earliest timestamp
                    COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at) as planting_at
                FROM crop_batches cb
                JOIN crops c ON c.crop_batch_id = cb.id
                JOIN recipes r ON cb.recipe_id = r.id
                LEFT JOIN crop_stages cs ON c.current_stage_id = cs.id
                GROUP BY cb.id, cb.recipe_id, cb.order_id, cb.crop_plan_id, 
                         cb.created_at, cb.updated_at, r.name, r.days_to_maturity,
                         cs.name, cs.code
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS crop_batches_list_view');
    }
};