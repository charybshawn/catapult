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
            // Remove the existing generic stage_updated_at column
            $table->dropColumn('stage_updated_at');
            
            // Add specific timestamp columns for each stage
            $table->timestamp('planting_at')->nullable()->after('current_stage');
            $table->timestamp('germination_at')->nullable()->after('planting_at');
            $table->timestamp('blackout_at')->nullable()->after('germination_at');
            $table->timestamp('light_at')->nullable()->after('blackout_at');
            $table->timestamp('harvested_at')->nullable()->after('light_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            // Remove the specific stage timestamp columns
            $table->dropColumn([
                'planting_at',
                'germination_at',
                'blackout_at',
                'light_at',
                'harvested_at',
            ]);
            
            // Add back the generic stage_updated_at column
            $table->timestamp('stage_updated_at')->nullable()->after('current_stage');
        });
    }
};
