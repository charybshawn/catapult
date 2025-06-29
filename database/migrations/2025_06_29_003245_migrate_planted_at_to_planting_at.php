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
        // Copy data from planted_at to planting_at where planting_at is null
        DB::statement('UPDATE crops SET planting_at = planted_at WHERE planting_at IS NULL AND planted_at IS NOT NULL');
        
        // Update the crop_batches view to use planting_at instead of planted_at
        DB::statement('DROP VIEW IF EXISTS crop_batches');
        DB::statement('
            CREATE OR REPLACE VIEW crop_batches AS
            SELECT 
                MIN(id) as id,
                recipe_id,
                DATE(planting_at) as planting_date,
                current_stage,
                COUNT(*) as tray_count
            FROM crops
            WHERE planting_at IS NOT NULL
            GROUP BY recipe_id, DATE(planting_at), current_stage
        ');
        
        // Remove the old planted_at column
        Schema::table('crops', function (Blueprint $table) {
            if (Schema::hasColumn('crops', 'planted_at')) {
                $table->dropColumn('planted_at');
            }
        });
        
        // Add index for planting_at if it doesn't exist  
        Schema::table('crops', function (Blueprint $table) {
            if (!collect(DB::select("SHOW INDEX FROM crops"))->pluck('Column_name')->contains('planting_at')) {
                $table->index('planting_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the planted_at column
        Schema::table('crops', function (Blueprint $table) {
            if (!Schema::hasColumn('crops', 'planted_at')) {
                $table->timestamp('planted_at')->nullable()->after('stage_updated_at');
                $table->index('planted_at');
            }
        });
        
        // Copy data back from planting_at to planted_at
        DB::statement('UPDATE crops SET planted_at = planting_at WHERE planted_at IS NULL AND planting_at IS NOT NULL');
        
        // Restore the original crop_batches view
        DB::statement('DROP VIEW IF EXISTS crop_batches');
        DB::statement('
            CREATE OR REPLACE VIEW crop_batches AS
            SELECT 
                MIN(id) as id,
                recipe_id,
                DATE(planted_at) as planting_date,
                current_stage,
                COUNT(*) as tray_count
            FROM crops
            WHERE planted_at IS NOT NULL
            GROUP BY recipe_id, DATE(planted_at), current_stage
        ');
    }
};
