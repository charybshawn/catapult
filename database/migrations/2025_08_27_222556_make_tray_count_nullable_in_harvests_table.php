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
            // Make tray_count and average_weight_per_tray nullable since we're no longer using them
            // in the simplified cultivar-based harvest approach
            $table->decimal('tray_count', 8, 2)->nullable()->change();
            $table->decimal('average_weight_per_tray', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            // Restore original non-nullable constraints
            $table->decimal('tray_count', 8, 2)->nullable(false)->change();
            $table->decimal('average_weight_per_tray', 10, 2)->nullable(false)->change();
        });
    }
};
