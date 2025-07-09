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
        Schema::table('consumables', function (Blueprint $table) {
            // Add initial_stock column and copy data from current_stock
            $table->decimal('initial_stock', 10, 3)->default(0)->after('cultivar');
        });
        
        // Copy current_stock values to initial_stock for existing records
        DB::statement('UPDATE consumables SET initial_stock = current_stock WHERE current_stock IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropColumn('initial_stock');
        });
    }
};
