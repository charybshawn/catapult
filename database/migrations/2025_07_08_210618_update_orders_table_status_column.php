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
        Schema::table('orders', function (Blueprint $table) {
            // Copy unified_status_id to status_id if it exists
            if (Schema::hasColumn('orders', 'unified_status_id')) {
                $table->unsignedBigInteger('status_id')->nullable();
            }
        });
        
        // Copy data if unified_status_id exists
        if (Schema::hasColumn('orders', 'unified_status_id')) {
            DB::statement('UPDATE orders SET status_id = unified_status_id WHERE unified_status_id IS NOT NULL');
            
            // Drop the old column
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('unified_status_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('unified_status_id')->nullable();
            // Copy data back
            DB::statement('UPDATE orders SET unified_status_id = status_id WHERE status_id IS NOT NULL');
            $table->dropColumn('status_id');
        });
    }
};
