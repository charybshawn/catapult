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
        DB::statement('DROP VIEW IF EXISTS recipes_optimized_view');
        
        DB::statement('
            CREATE VIEW recipes_optimized_view AS
            SELECT 
                r.id,
                r.name,
                r.master_seed_catalog_id,
                r.master_cultivar_id,
                r.common_name,
                r.cultivar_name,
                r.seed_consumable_id,
                r.lot_number,
                r.lot_depleted_at,
                r.soil_consumable_id,
                r.germination_days,
                r.blackout_days,
                r.days_to_maturity,
                r.light_days,
                r.harvest_days,
                r.seed_soak_hours,
                r.expected_yield_grams,
                r.buffer_percentage,
                r.seed_density_grams_per_tray,
                r.is_active,
                r.notes,
                r.suspend_water_hours,
                r.created_at,
                r.updated_at,
                
                -- Calculated fields
                (r.germination_days + r.blackout_days + r.light_days) AS total_days,
                
                -- Seed consumable data
                sc.name AS seed_consumable_name,
                sc.total_quantity AS seed_total_quantity,
                sc.consumed_quantity AS seed_consumed_quantity,
                GREATEST(0, sc.total_quantity - sc.consumed_quantity) AS seed_available_quantity,
                sc.quantity_unit AS seed_quantity_unit,
                
                -- Soil consumable data
                soil.name AS soil_consumable_name,
                soil.total_quantity AS soil_total_quantity,
                soil.consumed_quantity AS soil_consumed_quantity,
                GREATEST(0, soil.total_quantity - soil.consumed_quantity) AS soil_available_quantity,
                soil.quantity_unit AS soil_quantity_unit,
                
                -- Crop counts
                (
                    SELECT COUNT(*) 
                    FROM crops c
                    JOIN crop_stages cs ON c.current_stage_id = cs.id
                    WHERE c.recipe_id = r.id 
                    AND cs.code != "harvested"
                ) AS active_crops_count,
                
                (
                    SELECT COUNT(*) 
                    FROM crops c
                    WHERE c.recipe_id = r.id
                ) AS total_crops_count,
                
                -- Check if recipe has soaking requirement
                CASE 
                    WHEN r.seed_soak_hours > 0 THEN 1
                    ELSE 0
                END AS requires_soaking
                
            FROM recipes r
            LEFT JOIN consumables sc ON r.seed_consumable_id = sc.id
            LEFT JOIN consumables soil ON r.soil_consumable_id = soil.id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS recipes_optimized_view');
    }
};