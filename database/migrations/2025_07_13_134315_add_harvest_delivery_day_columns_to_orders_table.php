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
            if (!Schema::hasColumn('orders', 'harvest_day')) {
                $table->enum('harvest_day', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])->nullable()->after('delivery_date');
            }
            if (!Schema::hasColumn('orders', 'delivery_day')) {
                $table->enum('delivery_day', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])->nullable()->after('harvest_day');
            }
        });
        
        // Migrate existing JSON data to new columns
        $orders = DB::table('orders')->where('is_recurring', true)->get();
        
        foreach ($orders as $order) {
            if ($order->recurring_days_of_week) {
                $daysOfWeek = json_decode($order->recurring_days_of_week, true);
                
                if (is_array($daysOfWeek)) {
                    $harvestDay = $daysOfWeek['harvest_day'] ?? null;
                    $deliveryDay = $daysOfWeek['delivery_day'] ?? null;
                    
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'harvest_day' => $harvestDay,
                            'delivery_day' => $deliveryDay
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['harvest_day', 'delivery_day']);
        });
    }
};
