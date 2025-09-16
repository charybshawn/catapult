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
                cb.order_id,
                cb.crop_plan_id,
                cb.created_at,
                cb.updated_at,
                r.name as recipe_name,
                COUNT(c.id) as crop_count,
                STRING_AGG(c.tray_number::TEXT, ', ' ORDER BY c.tray_number) as tray_numbers,
                MIN(c.current_stage_id) as current_stage_id,
                cs.name as current_stage_name,
                cs.code as current_stage_code,
                MIN(c.soaking_at) as soaking_at,
                MIN(c.germination_at) as germination_at,
                MIN(c.blackout_at) as blackout_at,
                MIN(c.light_at) as light_at,
                NULL as harvested_at,
                MIN(c.watering_suspended_at) as watering_suspended_at,
                
                -- Calculate expected harvest date
                CASE 
                    WHEN r.days_to_maturity IS NOT NULL AND r.days_to_maturity > 0 THEN
                        COALESCE(MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at) + 
                        INTERVAL '1 day' * r.days_to_maturity
                    ELSE NULL
                END as expected_harvest_at,
                
                -- Calculate time to next stage in minutes
                CASE 
                    WHEN cs.code = 'harvested' THEN 0
                    WHEN cs.code = 'soaking' AND MIN(c.soaking_at) IS NOT NULL AND r.seed_soak_hours > 0 THEN
                        GREATEST(0, EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/60)
                    WHEN cs.code = 'germination' AND MIN(c.germination_at) IS NOT NULL AND r.germination_days > 0 THEN
                        GREATEST(0, EXTRACT(EPOCH FROM (MIN(c.germination_at) + INTERVAL '1 day' * r.germination_days - CURRENT_TIMESTAMP))/60)
                    WHEN cs.code = 'blackout' AND MIN(c.blackout_at) IS NOT NULL AND r.blackout_days > 0 THEN
                        GREATEST(0, EXTRACT(EPOCH FROM (MIN(c.blackout_at) + INTERVAL '1 day' * r.blackout_days - CURRENT_TIMESTAMP))/60)
                    WHEN cs.code = 'light' AND MIN(c.light_at) IS NOT NULL AND r.light_days > 0 THEN
                        GREATEST(0, EXTRACT(EPOCH FROM (MIN(c.light_at) + INTERVAL '1 day' * r.light_days - CURRENT_TIMESTAMP))/60)
                    ELSE 0
                END as time_to_next_stage_minutes,
                
                -- Format time to next stage display
                CASE 
                    WHEN cs.code = 'harvested' THEN 'Harvested'
                    WHEN cs.code = 'soaking' AND MIN(c.soaking_at) IS NOT NULL AND r.seed_soak_hours > 0 THEN
                        CASE
                            WHEN EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/60 <= 0 THEN 'Ready to advance'
                            WHEN EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/3600 >= 24 THEN 
                                FLOOR(EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/3600 / 24)::TEXT || 'd ' || 
                                (FLOOR(EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/3600) % 24)::TEXT || 'h'
                            WHEN EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/3600 > 0 THEN 
                                FLOOR(EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/3600)::TEXT || 'h ' || 
                                (FLOOR(EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/60) % 60)::TEXT || 'm'
                            ELSE FLOOR(EXTRACT(EPOCH FROM (MIN(c.soaking_at) + INTERVAL '1 hour' * r.seed_soak_hours - CURRENT_TIMESTAMP))/60)::TEXT || 'm'
                        END
                    ELSE 'Unknown'
                END as time_to_next_stage_display,
                
                -- Calculate stage age in minutes
                CASE 
                    WHEN cs.code = 'soaking' AND MIN(c.soaking_at) IS NOT NULL THEN
                        EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.soaking_at)))/60
                    WHEN cs.code = 'germination' AND MIN(c.germination_at) IS NOT NULL THEN
                        EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.germination_at)))/60
                    WHEN cs.code = 'blackout' AND MIN(c.blackout_at) IS NOT NULL THEN
                        EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.blackout_at)))/60
                    WHEN cs.code = 'light' AND MIN(c.light_at) IS NOT NULL THEN
                        EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.light_at)))/60
                    WHEN cs.code = 'harvested' THEN
                        0
                    ELSE 0
                END as stage_age_minutes,
                
                -- Format stage age display
                CASE 
                    WHEN cs.code = 'soaking' AND MIN(c.soaking_at) IS NOT NULL THEN
                        CASE
                            WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.soaking_at)))/86400 > 0 THEN 
                                FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.soaking_at)))/86400)::TEXT || 'd ' || 
                                (FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.soaking_at)))/3600) % 24)::TEXT || 'h'
                            WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.soaking_at)))/3600 > 0 THEN 
                                FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.soaking_at)))/3600)::TEXT || 'h ' || 
                                (FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.soaking_at)))/60) % 60)::TEXT || 'm'
                            ELSE FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(c.soaking_at)))/60)::TEXT || 'm'
                        END
                    WHEN cs.code = 'harvested' THEN
                        'Harvested'
                    ELSE '0m'
                END as stage_age_display,
                
                -- Calculate total age from the earliest timestamp
                EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - 
                    COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at)
                ))/60 as total_age_minutes,
                
                -- Format total age display
                CASE
                    WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at)))/86400 > 0 THEN 
                        FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at)))/86400)::TEXT || 'd ' || 
                        (FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at)))/3600) % 24)::TEXT || 'h'
                    WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at)))/3600 > 0 THEN 
                        FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at)))/3600)::TEXT || 'h ' || 
                        (FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at)))/60) % 60)::TEXT || 'm'
                    ELSE FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at)))/60)::TEXT || 'm'
                END as total_age_display,
                
                -- Calculate planting_at as the earliest timestamp
                COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at) as planting_at
            FROM crop_batches cb
            JOIN crops c ON c.crop_batch_id = cb.id
            JOIN recipes r ON cb.recipe_id = r.id
            LEFT JOIN crop_stages cs ON c.current_stage_id = cs.id
            GROUP BY cb.id, cb.recipe_id, cb.order_id, cb.crop_plan_id, 
                     cb.created_at, cb.updated_at, r.name, r.seed_soak_hours,
                     r.germination_days, r.blackout_days, r.light_days, r.days_to_maturity,
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