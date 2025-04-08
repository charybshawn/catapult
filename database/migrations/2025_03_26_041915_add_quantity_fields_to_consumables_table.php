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
        Schema::table('consumables', function (Blueprint $table) {
            $table->decimal('quantity_per_unit', 10, 2)->nullable()->after('cost_per_unit');
            $table->string('quantity_unit', 20)->nullable()->after('quantity_per_unit');
            $table->decimal('total_quantity', 12, 2)->nullable()->after('quantity_unit')
                ->comment('Calculated field: current_stock * quantity_per_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropColumn(['quantity_per_unit', 'quantity_unit', 'total_quantity']);
        });
    }
};
