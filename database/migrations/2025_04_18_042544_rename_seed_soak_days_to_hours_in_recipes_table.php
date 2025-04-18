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
        // First add the new column
        Schema::table('recipes', function (Blueprint $table) {
            $table->integer('seed_soak_hours')->after('seed_soak_days')->default(0);
        });
        
        // Convert existing values (multiply days by 24 to get hours)
        DB::statement('UPDATE recipes SET seed_soak_hours = ROUND(seed_soak_days * 24)');
        
        // Drop the old column
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('seed_soak_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First add the old column back
        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('seed_soak_days', 8, 3)->after('seed_soak_hours')->default(0);
        });
        
        // Convert hours back to days
        DB::statement('UPDATE recipes SET seed_soak_days = seed_soak_hours / 24');
        
        // Drop the new column
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('seed_soak_hours');
        });
    }
};
