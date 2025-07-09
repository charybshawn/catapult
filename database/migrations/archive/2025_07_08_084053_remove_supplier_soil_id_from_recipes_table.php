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
            // Drop foreign key constraint if it exists
            if (Schema::hasColumn('recipes', 'supplier_soil_id')) {
                $table->dropForeign(['supplier_soil_id']);
                $table->dropColumn('supplier_soil_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_soil_id')->nullable()->after('name');
            $table->foreign('supplier_soil_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }
};
