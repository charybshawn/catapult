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
                GROUP_CONCAT(c.tray_number ORDER BY c.tray_number SEPARATOR ', ') as tray_numbers,
                MIN(c.current_stage_id) as current_stage_id,
                cs.name as current_stage_name,
                cs.code as current_stage_code,
                MIN(c.soaking_at) as soaking_at,
                MIN(c.germination_at) as germination_at,
                MIN(c.blackout_at) as blackout_at,
                MIN(c.light_at) as light_at,
                NULL as harvested_at,
                MIN(CASE WHEN c.watering_suspended_at IS NOT NULL THEN c.watering_suspended_at END) as watering_suspended_at,
                
                -- Calculate expected harvest date
                CASE 
                    WHEN r.days_to_maturity IS NOT NULL AND r.days_to_maturity > 0 THEN
                        DATE_ADD(
                            COALESCE(MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at),
                            INTERVAL r.days_to_maturity DAY
                        )
                    ELSE NULL
                END as expected_harvest_at,
                
                -- Calculate time to next stage in minutes
                CASE 
                    WHEN cs.code = 'harvested' THEN 0
                    WHEN cs.code = 'soaking' AND MIN(c.soaking_at) IS NOT NULL AND r.seed_soak_hours > 0 THEN
                        GREATEST(0, TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)))
                    WHEN cs.code = 'germination' AND MIN(c.germination_at) IS NOT NULL AND r.germination_days > 0 THEN
                        GREATEST(0, TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)))
                    WHEN cs.code = 'blackout' AND MIN(c.blackout_at) IS NOT NULL AND r.blackout_days > 0 THEN
                        GREATEST(0, TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)))
                    WHEN cs.code = 'light' AND MIN(c.light_at) IS NOT NULL AND r.light_days > 0 THEN
                        GREATEST(0, TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)))
                    ELSE 0
                END as time_to_next_stage_minutes,
                
                -- Format time to next stage display
                CASE 
                    WHEN cs.code = 'harvested' THEN 'Harvested'
                    WHEN cs.code = 'soaking' AND MIN(c.soaking_at) IS NOT NULL AND r.seed_soak_hours > 0 THEN
                        CASE
                            WHEN TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)) <= 0 THEN 'Ready to advance'
                            WHEN TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)) >= 24 THEN 
                                CONCAT(FLOOR(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)) / 24), 'd ', 
                                       MOD(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)), 24), 'h')
                            WHEN TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)), 60), 'm')
                            ELSE CONCAT(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.soaking_at), INTERVAL r.seed_soak_hours HOUR)), 'm')
                        END
                    WHEN cs.code = 'germination' AND MIN(c.germination_at) IS NOT NULL AND r.germination_days > 0 THEN
                        CASE
                            WHEN TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)) <= 0 THEN 'Ready to advance'
                            WHEN TIMESTAMPDIFF(DAY, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(DAY, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)), 'd ', 
                                       MOD(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)), 24), 'h')
                            WHEN TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)), 60), 'm')
                            ELSE CONCAT(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.germination_at), INTERVAL r.germination_days DAY)), 'm')
                        END
                    WHEN cs.code = 'blackout' AND MIN(c.blackout_at) IS NOT NULL AND r.blackout_days > 0 THEN
                        CASE
                            WHEN TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)) <= 0 THEN 'Ready to advance'
                            WHEN TIMESTAMPDIFF(DAY, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(DAY, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)), 'd ', 
                                       MOD(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)), 24), 'h')
                            WHEN TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)), 60), 'm')
                            ELSE CONCAT(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.blackout_at), INTERVAL r.blackout_days DAY)), 'm')
                        END
                    WHEN cs.code = 'light' AND MIN(c.light_at) IS NOT NULL AND r.light_days > 0 THEN
                        CASE
                            WHEN TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)) <= 0 THEN 'Ready to advance'
                            WHEN TIMESTAMPDIFF(DAY, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(DAY, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)), 'd ', 
                                       MOD(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)), 24), 'h')
                            WHEN TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)), 60), 'm')
                            ELSE CONCAT(TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(c.light_at), INTERVAL r.light_days DAY)), 'm')
                        END
                    ELSE 'Calculating...'
                END as time_to_next_stage_display,
                
                -- Calculate stage age in minutes
                CASE 
                    WHEN cs.code = 'soaking' AND MIN(c.soaking_at) IS NOT NULL THEN
                        TIMESTAMPDIFF(MINUTE, MIN(c.soaking_at), NOW())
                    WHEN cs.code = 'germination' AND MIN(c.germination_at) IS NOT NULL THEN
                        TIMESTAMPDIFF(MINUTE, MIN(c.germination_at), NOW())
                    WHEN cs.code = 'blackout' AND MIN(c.blackout_at) IS NOT NULL THEN
                        TIMESTAMPDIFF(MINUTE, MIN(c.blackout_at), NOW())
                    WHEN cs.code = 'light' AND MIN(c.light_at) IS NOT NULL THEN
                        TIMESTAMPDIFF(MINUTE, MIN(c.light_at), NOW())
                    WHEN cs.code = 'harvested' THEN
                        0
                    ELSE 0
                END as stage_age_minutes,
                
                -- Format stage age display
                CASE 
                    WHEN cs.code = 'soaking' AND MIN(c.soaking_at) IS NOT NULL THEN
                        CASE
                            WHEN TIMESTAMPDIFF(DAY, MIN(c.soaking_at), NOW()) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(DAY, MIN(c.soaking_at), NOW()), 'd ', 
                                       MOD(TIMESTAMPDIFF(HOUR, MIN(c.soaking_at), NOW()), 24), 'h')
                            WHEN TIMESTAMPDIFF(HOUR, MIN(c.soaking_at), NOW()) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, MIN(c.soaking_at), NOW()), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, MIN(c.soaking_at), NOW()), 60), 'm')
                            ELSE CONCAT(TIMESTAMPDIFF(MINUTE, MIN(c.soaking_at), NOW()), 'm')
                        END
                    WHEN cs.code = 'germination' AND MIN(c.germination_at) IS NOT NULL THEN
                        CASE
                            WHEN TIMESTAMPDIFF(DAY, MIN(c.germination_at), NOW()) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(DAY, MIN(c.germination_at), NOW()), 'd ', 
                                       MOD(TIMESTAMPDIFF(HOUR, MIN(c.germination_at), NOW()), 24), 'h')
                            WHEN TIMESTAMPDIFF(HOUR, MIN(c.germination_at), NOW()) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, MIN(c.germination_at), NOW()), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, MIN(c.germination_at), NOW()), 60), 'm')
                            ELSE CONCAT(TIMESTAMPDIFF(MINUTE, MIN(c.germination_at), NOW()), 'm')
                        END
                    WHEN cs.code = 'blackout' AND MIN(c.blackout_at) IS NOT NULL THEN
                        CASE
                            WHEN TIMESTAMPDIFF(DAY, MIN(c.blackout_at), NOW()) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(DAY, MIN(c.blackout_at), NOW()), 'd ', 
                                       MOD(TIMESTAMPDIFF(HOUR, MIN(c.blackout_at), NOW()), 24), 'h')
                            WHEN TIMESTAMPDIFF(HOUR, MIN(c.blackout_at), NOW()) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, MIN(c.blackout_at), NOW()), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, MIN(c.blackout_at), NOW()), 60), 'm')
                            ELSE CONCAT(TIMESTAMPDIFF(MINUTE, MIN(c.blackout_at), NOW()), 'm')
                        END
                    WHEN cs.code = 'light' AND MIN(c.light_at) IS NOT NULL THEN
                        CASE
                            WHEN TIMESTAMPDIFF(DAY, MIN(c.light_at), NOW()) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(DAY, MIN(c.light_at), NOW()), 'd ', 
                                       MOD(TIMESTAMPDIFF(HOUR, MIN(c.light_at), NOW()), 24), 'h')
                            WHEN TIMESTAMPDIFF(HOUR, MIN(c.light_at), NOW()) > 0 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, MIN(c.light_at), NOW()), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, MIN(c.light_at), NOW()), 60), 'm')
                            ELSE CONCAT(TIMESTAMPDIFF(MINUTE, MIN(c.light_at), NOW()), 'm')
                        END
                    WHEN cs.code = 'harvested' THEN
                        'Harvested'
                    ELSE '0m'
                END as stage_age_display,
                
                -- Calculate total age from the earliest timestamp
                TIMESTAMPDIFF(MINUTE, 
                    COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at),
                    NOW()
                ) as total_age_minutes,
                
                -- Format total age display
                CASE
                    WHEN TIMESTAMPDIFF(DAY, COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at), NOW()) > 0 THEN 
                        CONCAT(TIMESTAMPDIFF(DAY, COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at), NOW()), 'd ', 
                               MOD(TIMESTAMPDIFF(HOUR, COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at), NOW()), 24), 'h')
                    WHEN TIMESTAMPDIFF(HOUR, COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at), NOW()) > 0 THEN 
                        CONCAT(TIMESTAMPDIFF(HOUR, COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at), NOW()), 'h ', 
                               MOD(TIMESTAMPDIFF(MINUTE, COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at), NOW()), 60), 'm')
                    ELSE CONCAT(TIMESTAMPDIFF(MINUTE, COALESCE(MIN(c.soaking_at), MIN(c.germination_at), MIN(c.blackout_at), MIN(c.light_at), cb.created_at), NOW()), 'm')
                END as total_age_display,
                
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
        // Restore the placeholder version
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
                NULL as harvested_at,
                NULL as expected_harvest_at,
                MIN(CASE WHEN c.watering_suspended_at IS NOT NULL THEN c.watering_suspended_at END) as watering_suspended_at,
                -- Placeholder values for time calculations
                0 as time_to_next_stage_minutes,
                'Calculating...' as time_to_next_stage_display,
                0 as stage_age_minutes,
                '0m' as stage_age_display,
                0 as total_age_minutes,
                '0m' as total_age_display,
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
};