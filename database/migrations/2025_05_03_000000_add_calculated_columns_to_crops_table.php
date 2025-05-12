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
        Schema::table('crops', function (Blueprint $table) {
            // Only add stage_age_minutes if it doesn't exist
            if (!Schema::hasColumn('crops', 'stage_age_minutes')) {
                $table->unsignedInteger('stage_age_minutes')->nullable()->after('current_stage')
                    ->comment('Minutes spent in current stage');
            }
            
            // Rename stage_age_status to stage_age_display if it exists
            if (Schema::hasColumn('crops', 'stage_age_status') && !Schema::hasColumn('crops', 'stage_age_display')) {
                $table->renameColumn('stage_age_status', 'stage_age_display');
            } elseif (!Schema::hasColumn('crops', 'stage_age_display')) {
                // Add stage_age_display if it doesn't exist
                $table->string('stage_age_display', 50)->nullable()->after('stage_age_minutes')
                    ->comment('Human readable stage age (e.g., "2d 4h")');
            }
            
            // Only add time_to_next_stage_minutes if it doesn't exist
            if (!Schema::hasColumn('crops', 'time_to_next_stage_minutes')) {
                $table->unsignedInteger('time_to_next_stage_minutes')->nullable()->after('stage_age_display')
                    ->comment('Minutes until next stage');
            }
            
            // Rename time_to_next_stage_status to time_to_next_stage_display if it exists
            if (Schema::hasColumn('crops', 'time_to_next_stage_status') && !Schema::hasColumn('crops', 'time_to_next_stage_display')) {
                $table->renameColumn('time_to_next_stage_status', 'time_to_next_stage_display');
            } elseif (!Schema::hasColumn('crops', 'time_to_next_stage_display')) {
                // Add time_to_next_stage_display if it doesn't exist
                $table->string('time_to_next_stage_display', 50)->nullable()->after('time_to_next_stage_minutes')
                    ->comment('Human readable time to next stage (e.g., "3d 2h")');
            }
            
            // Only add total_age_minutes if it doesn't exist
            if (!Schema::hasColumn('crops', 'total_age_minutes')) {
                $table->unsignedInteger('total_age_minutes')->nullable()->after('time_to_next_stage_display')
                    ->comment('Total minutes since planting');
            }
            
            // Rename total_age_status to total_age_display if it exists
            if (Schema::hasColumn('crops', 'total_age_status') && !Schema::hasColumn('crops', 'total_age_display')) {
                $table->renameColumn('total_age_status', 'total_age_display');
            } elseif (!Schema::hasColumn('crops', 'total_age_display')) {
                // Add total_age_display if it doesn't exist
                $table->string('total_age_display', 50)->nullable()->after('total_age_minutes')
                    ->comment('Human readable total age (e.g., "5d 3h")');
            }
            
            // Add column for expected harvest date
            if (!Schema::hasColumn('crops', 'expected_harvest_at')) {
                $table->timestamp('expected_harvest_at')->nullable()->after('total_age_display')
                    ->comment('Expected harvest date based on recipe and current stage');
            }
            
            // Add column for tray count
            if (!Schema::hasColumn('crops', 'tray_count')) {
                $table->unsignedInteger('tray_count')->default(1)->after('expected_harvest_at')
                    ->comment('Number of trays in this batch');
            }
            
            // Add column for tray numbers
            if (!Schema::hasColumn('crops', 'tray_numbers')) {
                $table->string('tray_numbers', 255)->nullable()->after('tray_count')
                    ->comment('Comma-separated list of tray numbers');
            }
        });

        // Create an index for the frequently used columns if it doesn't exist
        Schema::table('crops', function (Blueprint $table) {
            $indexes = DB::select("SHOW INDEXES FROM crops WHERE Key_name = 'crops_calc_times_index'");
            if (empty($indexes)) {
                $table->index(
                    ['stage_age_minutes', 'time_to_next_stage_minutes', 'total_age_minutes'],
                    'crops_calc_times_index'
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            // Drop the index if it exists
            $indexes = DB::select("SHOW INDEXES FROM crops WHERE Key_name = 'crops_calc_times_index'");
            if (!empty($indexes)) {
                $table->dropIndex('crops_calc_times_index');
            }
            
            // Rename *_display columns back to *_status
            if (Schema::hasColumn('crops', 'stage_age_display')) {
                $table->renameColumn('stage_age_display', 'stage_age_status');
            }
            
            if (Schema::hasColumn('crops', 'time_to_next_stage_display')) {
                $table->renameColumn('time_to_next_stage_display', 'time_to_next_stage_status');
            }
            
            if (Schema::hasColumn('crops', 'total_age_display')) {
                $table->renameColumn('total_age_display', 'total_age_status');
            }
            
            // Drop columns if they exist
            $columnsToDrop = [];
            $possibleColumns = [
                'expected_harvest_at',
                'tray_count',
                'tray_numbers'
            ];
            
            foreach ($possibleColumns as $column) {
                if (Schema::hasColumn('crops', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
}; 