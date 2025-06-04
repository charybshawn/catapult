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
            $table->decimal('fill_weight_grams', 8, 2)->nullable()->after('weight')->comment('Actual product weight in grams that goes into the packaging');
            $table->dropColumn(['unit', 'weight']);
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
