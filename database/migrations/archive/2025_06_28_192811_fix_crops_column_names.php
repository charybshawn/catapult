<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check current column names and rename if they don't match expected names
        Schema::table('crops', function (Blueprint $table) {
            // Check if old column names exist and rename them
            if (Schema::hasColumn('crops', 'time_to_next_stage_status') && !Schema::hasColumn('crops', 'time_to_next_stage_display')) {
                $table->renameColumn('time_to_next_stage_status', 'time_to_next_stage_display');
            }
            
            if (Schema::hasColumn('crops', 'stage_age_status') && !Schema::hasColumn('crops', 'stage_age_display')) {
                $table->renameColumn('stage_age_status', 'stage_age_display');
            }
            
            if (Schema::hasColumn('crops', 'total_age_status') && !Schema::hasColumn('crops', 'total_age_display')) {
                $table->renameColumn('total_age_status', 'total_age_display');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            // Reverse the column renames
            if (Schema::hasColumn('crops', 'time_to_next_stage_display')) {
                $table->renameColumn('time_to_next_stage_display', 'time_to_next_stage_status');
            }
            
            if (Schema::hasColumn('crops', 'stage_age_display')) {
                $table->renameColumn('stage_age_display', 'stage_age_status');
            }
            
            if (Schema::hasColumn('crops', 'total_age_display')) {
                $table->renameColumn('total_age_display', 'total_age_status');
            }
        });
    }
};