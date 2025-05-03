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
            // Add a column to store the calculated minutes in stage for sorting purposes
            $table->integer('stage_age_minutes')->nullable()->after('time_to_next_stage_status')
                ->comment('Stores the calculated minutes in current stage for sorting');
            
            // Add text status column to store the display version (e.g., "2d 4h 30m")
            $table->string('stage_age_status', 50)->nullable()->after('stage_age_minutes')
                ->comment('Stores the human-readable time in stage status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn('stage_age_minutes');
            $table->dropColumn('stage_age_status');
        });
    }
};
