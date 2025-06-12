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
            $table->string('pricing_unit', 20)->default('per_item')->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropColumn('pricing_unit');
        });
    }
};