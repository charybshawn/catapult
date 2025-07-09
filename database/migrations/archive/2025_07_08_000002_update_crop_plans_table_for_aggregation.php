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
        Schema::table('crop_plans', function (Blueprint $table) {
            // Add new columns
            $table->foreignId('aggregated_crop_plan_id')
                ->nullable()
                ->after('id')
                ->constrained('aggregated_crop_plans')
                ->onDelete('set null');
            
            $table->date('seed_soak_date')
                ->nullable()
                ->after('plant_by_date');
            
            $table->foreignId('variety_id')
                ->nullable()
                ->after('recipe_id')
                ->constrained('master_seed_catalog')
                ->onDelete('restrict');
            
            // Add indexes for performance
            $table->index('aggregated_crop_plan_id');
            $table->index('variety_id');
            $table->index('seed_soak_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crop_plans', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['aggregated_crop_plan_id']);
            $table->dropIndex(['variety_id']);
            $table->dropIndex(['seed_soak_date']);
            
            // Drop foreign key constraints
            $table->dropForeign(['aggregated_crop_plan_id']);
            $table->dropForeign(['variety_id']);
            
            // Drop columns
            $table->dropColumn(['aggregated_crop_plan_id', 'seed_soak_date', 'variety_id']);
        });
    }
};