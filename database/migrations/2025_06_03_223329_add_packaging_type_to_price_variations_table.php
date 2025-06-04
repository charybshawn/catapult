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
            $table->foreignId('packaging_type_id')->nullable()->after('product_id')->constrained()->onDelete('set null');
            $table->index(['product_id', 'packaging_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'packaging_type_id']);
            $table->dropForeign(['packaging_type_id']);
            $table->dropColumn('packaging_type_id');
        });
    }
};
