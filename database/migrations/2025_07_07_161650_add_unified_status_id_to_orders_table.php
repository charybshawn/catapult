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
            $table->foreignId('unified_status_id')
                ->nullable()
                ->after('order_status_id')
                ->constrained('unified_order_statuses')
                ->onDelete('restrict');
                
            // Add index for performance
            $table->index('unified_status_id');
        });
        
        // Migrate existing order statuses to unified statuses
        $this->migrateExistingStatuses();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['unified_status_id']);
            $table->dropColumn('unified_status_id');
        });
    }
    
    /**
     * Migrate existing order statuses to the new unified status system.
     */
    private function migrateExistingStatuses(): void
    {
        // Map existing order statuses to unified statuses
        $statusMapping = [
            'draft' => 'draft',
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'processing' => 'growing',
            'completed' => 'delivered',
            'cancelled' => 'cancelled',
            'template' => 'template',
        ];
        
        // Get all unified statuses for lookup
        $unifiedStatuses = DB::table('unified_order_statuses')
            ->pluck('id', 'code')
            ->toArray();
        
        // Update orders based on their current order_status
        foreach ($statusMapping as $oldCode => $newCode) {
            if (isset($unifiedStatuses[$newCode])) {
                DB::table('orders')
                    ->join('order_statuses', 'orders.order_status_id', '=', 'order_statuses.id')
                    ->where('order_statuses.code', $oldCode)
                    ->update(['orders.unified_status_id' => $unifiedStatuses[$newCode]]);
            }
        }
        
        // Handle orders that might have crop or fulfillment statuses
        // For orders with crops in production
        DB::table('orders')
            ->join('crop_statuses', 'orders.crop_status_id', '=', 'crop_statuses.id')
            ->where('crop_statuses.code', 'growing')
            ->update(['orders.unified_status_id' => $unifiedStatuses['growing'] ?? null]);
            
        DB::table('orders')
            ->join('crop_statuses', 'orders.crop_status_id', '=', 'crop_statuses.id')
            ->where('crop_statuses.code', 'ready_to_harvest')
            ->update(['orders.unified_status_id' => $unifiedStatuses['ready_to_harvest'] ?? null]);
            
        DB::table('orders')
            ->join('crop_statuses', 'orders.crop_status_id', '=', 'crop_statuses.id')
            ->where('crop_statuses.code', 'harvested')
            ->update(['orders.unified_status_id' => $unifiedStatuses['harvesting'] ?? null]);
        
        // For orders in fulfillment stages
        DB::table('orders')
            ->join('fulfillment_statuses', 'orders.fulfillment_status_id', '=', 'fulfillment_statuses.id')
            ->where('fulfillment_statuses.code', 'packing')
            ->update(['orders.unified_status_id' => $unifiedStatuses['packing'] ?? null]);
            
        DB::table('orders')
            ->join('fulfillment_statuses', 'orders.fulfillment_status_id', '=', 'fulfillment_statuses.id')
            ->where('fulfillment_statuses.code', 'ready_for_delivery')
            ->update(['orders.unified_status_id' => $unifiedStatuses['ready_for_delivery'] ?? null]);
            
        DB::table('orders')
            ->join('fulfillment_statuses', 'orders.fulfillment_status_id', '=', 'fulfillment_statuses.id')
            ->where('fulfillment_statuses.code', 'out_for_delivery')
            ->update(['orders.unified_status_id' => $unifiedStatuses['out_for_delivery'] ?? null]);
            
        DB::table('orders')
            ->join('fulfillment_statuses', 'orders.fulfillment_status_id', '=', 'fulfillment_statuses.id')
            ->where('fulfillment_statuses.code', 'delivered')
            ->update(['orders.unified_status_id' => $unifiedStatuses['delivered'] ?? null]);
        
        // Set default status for any orders without a unified status
        DB::table('orders')
            ->whereNull('unified_status_id')
            ->update(['unified_status_id' => $unifiedStatuses['pending'] ?? null]);
    }
};