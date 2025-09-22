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
        
        DB::statement("
            CREATE VIEW recipes_optimized_view AS
            SELECT 
                r.id,
                
                -- Direct master catalog data (no consumables needed!)
                msc.common_name,
                msc.category,
                msc.growing_notes,
                msc.description as seed_description,
                msc.aliases as seed_aliases,
                
                -- Master cultivar data
                mc.cultivar_name,
                mc.description as cultivar_description,
                mc.aliases as cultivar_aliases,
                
                -- Recipe-specific data
                r.master_seed_catalog_id,
                r.master_cultivar_id,
                r.lot_number,
                r.germination_days,
                r.blackout_days,
                r.light_days,
                r.days_to_maturity,
                r.seed_soak_hours,
                r.expected_yield_grams,
                r.buffer_percentage,
                r.seed_density_grams_per_tray,
                r.suspend_water_hours,
                r.notes,
                r.is_active,
                r.created_at,
                r.updated_at,
                
                -- Computed name field (replaces both display_name and original name)
                COALESCE(
                    CASE WHEN msc.common_name IS NOT NULL 
                         THEN CONCAT(
                            msc.common_name,
                            CASE WHEN mc.cultivar_name IS NOT NULL 
                                 THEN CONCAT(' - ', mc.cultivar_name) 
                                 ELSE '' END,
                            CASE WHEN r.lot_number IS NOT NULL 
                                 THEN CONCAT(' - ', r.lot_number) 
                                 ELSE '' END
                         )
                         ELSE r.name END,
                    r.name
                ) as name,
                
                CONCAT(msc.common_name, ' - ', COALESCE(mc.cultivar_name, 'Unknown Cultivar')) as variety_name,
                
                CASE WHEN r.seed_soak_hours > 0 THEN true ELSE false END as requires_soaking
                
            FROM recipes r
            LEFT JOIN master_seed_catalog msc ON r.master_seed_catalog_id = msc.id  
            LEFT JOIN master_cultivars mc ON r.master_cultivar_id = mc.id
            WHERE r.is_active = 1
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS recipes_optimized_view');
    }
};