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
        Schema::table('crops', function (Blueprint $table) {
            // Add a column to store the calculated minutes to next stage for sorting purposes
            $table->unsignedInteger('time_to_next_stage_minutes')->nullable()->after('current_stage')
                ->comment('Stores the calculated minutes to the next growth stage for sorting');
            
            // Add text status column to store the display version (e.g., "Ready to advance", "3d 4h")
            $table->string('time_to_next_stage_status', 50)->nullable()->after('time_to_next_stage_minutes')
                ->comment('Stores the human-readable time to next stage status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn('time_to_next_stage_minutes');
            $table->dropColumn('time_to_next_stage_status');
        });
    }
};
