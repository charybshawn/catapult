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
        Schema::table('price_variations', function (Blueprint $table) {
            // Add fill_weight_grams if it doesn't exist
            if (!Schema::hasColumn('price_variations', 'fill_weight_grams')) {
                $table->decimal('fill_weight_grams', 8, 2)->nullable()->comment('Actual product weight in grams that goes into the packaging');
            }
            
            // Drop old columns if they exist
            if (Schema::hasColumn('price_variations', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('price_variations', 'weight')) {
                $table->dropColumn('weight');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropColumn('fill_weight_grams');
            $table->string('unit', 50)->nullable();
            $table->decimal('weight', 8, 2)->default(0);
        });
    }
};
