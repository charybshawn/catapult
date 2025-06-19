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
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Change percentage from decimal(5,2) to decimal(6,2) for 2 decimal precision
            $table->decimal('percentage', 6, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Revert back to 4 decimal precision
            $table->decimal('percentage', 8, 4)->change();
        });
    }
};