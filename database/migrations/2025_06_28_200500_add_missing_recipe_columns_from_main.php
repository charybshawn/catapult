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
        Schema::table('recipes', function (Blueprint $table) {
            // Add seed_consumable_id column to match main branch
            if (!Schema::hasColumn('recipes', 'seed_consumable_id')) {
                $table->unsignedBigInteger('seed_consumable_id')->nullable()->after('soil_consumable_id');
            }
            
            // Add notes column
            if (!Schema::hasColumn('recipes', 'notes')) {
                $table->text('notes')->nullable();
            }
            
            // Add suspend_water_hours column
            if (!Schema::hasColumn('recipes', 'suspend_water_hours')) {
                $table->integer('suspend_water_hours')->default(24);
            }
            
            // Add common_name column
            if (!Schema::hasColumn('recipes', 'common_name')) {
                $table->string('common_name')->nullable();
            }
            
            // Add cultivar_name column
            if (!Schema::hasColumn('recipes', 'cultivar_name')) {
                $table->string('cultivar_name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['seed_consumable_id', 'notes', 'suspend_water_hours', 'common_name', 'cultivar_name']);
        });
    }
};