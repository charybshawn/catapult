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
            // Add new status columns
            $table->enum('crop_status', [
                'not_started',
                'planted', 
                'growing',
                'ready_to_harvest',
                'harvested',
                'na' // Not applicable for non-crop orders
            ])->default('not_started')->after('status');
            
            $table->enum('fulfillment_status', [
                'pending',
                'processing',
                'packing',
                'packed',
                'ready_for_delivery',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ])->default('pending')->after('crop_status');
        });
        
        // Migrate existing data
        DB::statement("
            UPDATE orders 
            SET crop_status = CASE
                WHEN status = 'planted' THEN 'planted'
                WHEN status = 'harvested' THEN 'harvested'
                WHEN status IN ('pending', 'confirmed', 'processing') THEN 'not_started'
                WHEN status IN ('delivered', 'completed', 'cancelled', 'template') THEN 'na'
                ELSE 'not_started'
            END,
            fulfillment_status = CASE
                WHEN status = 'processing' THEN 'processing'
                WHEN status = 'packed' THEN 'packed'
                WHEN status = 'delivered' THEN 'delivered'
                WHEN status = 'cancelled' THEN 'cancelled'
                WHEN status IN ('planted', 'harvested') THEN 'pending'
                ELSE 'pending'
            END
        ");
        
        // Update the status column to only contain order management statuses
        DB::statement("
            UPDATE orders 
            SET status = CASE
                WHEN status IN ('planted', 'harvested', 'packed') THEN 'processing'
                WHEN status = 'delivered' THEN 'completed'
                ELSE status
            END
        ");
        
        // Now we can safely modify the status enum to only include order management statuses
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'pending',
                'confirmed',
                'processing',
                'completed',
                'cancelled',
                'template'
            ])->default('pending')->after('delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First restore the original combined status values
        DB::statement("
            UPDATE orders 
            SET status = CASE
                WHEN crop_status = 'planted' THEN 'planted'
                WHEN crop_status = 'harvested' THEN 'harvested'
                WHEN fulfillment_status = 'packed' THEN 'packed'
                WHEN fulfillment_status = 'delivered' THEN 'delivered'
                ELSE status
            END
        ");
        
        // Restore the original status enum
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'confirmed', 
                'processing',
                'planted',
                'harvested',
                'packed',
                'delivered',
                'cancelled',
                'completed',
                'template'
            ])->default('pending')->after('delivery_date');
        });
        
        // Remove the new columns
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['crop_status', 'fulfillment_status']);
        });
    }
};