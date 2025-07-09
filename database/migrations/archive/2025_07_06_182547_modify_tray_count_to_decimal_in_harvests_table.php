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
        Schema::table('harvests', function (Blueprint $table) {
            // Drop the computed column first
            $table->dropColumn('average_weight_per_tray');
        });
        
        Schema::table('harvests', function (Blueprint $table) {
            // Change tray_count to decimal
            $table->decimal('tray_count', 8, 2)->change();
        });
        
        Schema::table('harvests', function (Blueprint $table) {
            // Recreate the computed column with decimal support
            $table->decimal('average_weight_per_tray', 10, 2)->storedAs('total_weight_grams / NULLIF(tray_count, 0)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            // Drop the computed column first
            $table->dropColumn('average_weight_per_tray');
        });
        
        Schema::table('harvests', function (Blueprint $table) {
            // Change tray_count back to integer
            $table->integer('tray_count')->change();
        });
        
        Schema::table('harvests', function (Blueprint $table) {
            // Recreate the computed column with integer
            $table->decimal('average_weight_per_tray', 10, 2)->storedAs('total_weight_grams / NULLIF(tray_count, 0)');
        });
    }
};
