<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any NULL values in consumed_quantity to 0
        DB::table('consumables')
            ->whereNull('consumed_quantity')
            ->update(['consumed_quantity' => 0]);
            
        Schema::table('consumables', function (Blueprint $table) {
            // Modify consumed_quantity to have a default value of 0
            $table->decimal('consumed_quantity', 12, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            // Remove the default value
            $table->decimal('consumed_quantity', 12, 2)->default(null)->change();
        });
    }
};
