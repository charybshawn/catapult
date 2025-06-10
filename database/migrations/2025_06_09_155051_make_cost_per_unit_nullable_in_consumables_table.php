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
            // Make cost_per_unit nullable and set default to null
            // This deprecates the cost tracking without breaking existing data
            $table->decimal('cost_per_unit', 10, 2)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            // Restore the previous constraint (not null)
            $table->decimal('cost_per_unit', 10, 2)->nullable(false)->default(0)->change();
        });
    }
};
