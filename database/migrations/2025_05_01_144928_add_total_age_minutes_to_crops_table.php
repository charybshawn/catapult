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
            // Add a column to store the calculated minutes since planting for sorting purposes
            $table->integer('total_age_minutes')->nullable()->after('stage_age_status')
                ->comment('Stores the calculated minutes since planting for sorting');
            
            // Add text status column to store the display version (e.g., "5d 3h")
            $table->string('total_age_status', 50)->nullable()->after('total_age_minutes')
                ->comment('Stores the human-readable total age status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn('total_age_minutes');
            $table->dropColumn('total_age_status');
        });
    }
};
