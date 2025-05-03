<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create a database view for grouped crops (crop batches)
        DB::statement("
            CREATE OR REPLACE VIEW crop_batches AS
            SELECT 
                recipe_id,
                planted_at,
                current_stage,
                MIN(id) as id,
                MIN(created_at) as created_at,
                MIN(updated_at) as updated_at,
                MIN(planting_at) as planting_at,
                MIN(germination_at) as germination_at,
                MIN(blackout_at) as blackout_at,
                MIN(light_at) as light_at,
                MIN(harvested_at) as harvested_at,
                AVG(harvest_weight_grams) as harvest_weight_grams,
                MIN(time_to_next_stage_minutes) as time_to_next_stage_minutes,
                MIN(time_to_next_stage_status) as time_to_next_stage_status,
                MIN(stage_age_minutes) as stage_age_minutes,
                MIN(stage_age_status) as stage_age_status,
                MIN(total_age_minutes) as total_age_minutes,
                MIN(total_age_status) as total_age_status,
                MIN(watering_suspended_at) as watering_suspended_at,
                MIN(notes) as notes,
                COUNT(id) as tray_count,
                GROUP_CONCAT(DISTINCT tray_number ORDER BY tray_number SEPARATOR ', ') as tray_number_list
            FROM crops
            GROUP BY recipe_id, planted_at, current_stage
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS crop_batches");
    }
};
