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
        Schema::create('unified_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color');
            $table->string('badge_color')->nullable();
            $table->enum('stage', ['pre_production', 'production', 'fulfillment', 'final']);
            $table->boolean('requires_crops')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_final')->default(false);
            $table->boolean('allows_modifications')->default(true);
            $table->integer('sort_order');
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('code');
            $table->index('stage');
            $table->index('is_active');
            $table->index('sort_order');
        });
        
        // Seed the initial statuses
        $this->seedUnifiedOrderStatuses();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_order_statuses');
    }
    
    /**
     * Seed the unified order statuses table with initial data.
     */
    private function seedUnifiedOrderStatuses(): void
    {
        $statuses = [
            [
                'code' => 'draft',
                'name' => 'Draft',
                'description' => 'Order is being prepared and not yet finalized',
                'color' => 'gray',
                'badge_color' => 'gray',
                'stage' => 'pre_production',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'pending',
                'name' => 'Pending',
                'description' => 'Order is awaiting confirmation or payment',
                'color' => 'yellow',
                'badge_color' => 'yellow',
                'stage' => 'pre_production',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'confirmed',
                'name' => 'Confirmed',
                'description' => 'Order has been confirmed and ready for production',
                'color' => 'blue',
                'badge_color' => 'blue',
                'stage' => 'pre_production',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => true,
                'sort_order' => 30,
            ],
            [
                'code' => 'growing',
                'name' => 'Growing',
                'description' => 'Crops are being grown for this order',
                'color' => 'green',
                'badge_color' => 'green',
                'stage' => 'production',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 40,
            ],
            [
                'code' => 'ready_to_harvest',
                'name' => 'Ready to Harvest',
                'description' => 'Crops are mature and ready for harvesting',
                'color' => 'lime',
                'badge_color' => 'lime',
                'stage' => 'production',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 50,
            ],
            [
                'code' => 'harvesting',
                'name' => 'Harvesting',
                'description' => 'Crops are being harvested',
                'color' => 'emerald',
                'badge_color' => 'emerald',
                'stage' => 'production',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 60,
            ],
            [
                'code' => 'packing',
                'name' => 'Packing',
                'description' => 'Order is being packed for delivery',
                'color' => 'indigo',
                'badge_color' => 'indigo',
                'stage' => 'fulfillment',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 70,
            ],
            [
                'code' => 'ready_for_delivery',
                'name' => 'Ready for Delivery',
                'description' => 'Order is packed and ready to be delivered',
                'color' => 'purple',
                'badge_color' => 'purple',
                'stage' => 'fulfillment',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 80,
            ],
            [
                'code' => 'out_for_delivery',
                'name' => 'Out for Delivery',
                'description' => 'Order is on the way to the customer',
                'color' => 'violet',
                'badge_color' => 'violet',
                'stage' => 'fulfillment',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 90,
            ],
            [
                'code' => 'delivered',
                'name' => 'Delivered',
                'description' => 'Order has been successfully delivered',
                'color' => 'green',
                'badge_color' => 'green',
                'stage' => 'final',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => true,
                'allows_modifications' => false,
                'sort_order' => 100,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'Order has been cancelled',
                'color' => 'red',
                'badge_color' => 'red',
                'stage' => 'final',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => true,
                'allows_modifications' => false,
                'sort_order' => 110,
            ],
            [
                'code' => 'template',
                'name' => 'Template',
                'description' => 'Recurring order template',
                'color' => 'gray',
                'badge_color' => 'gray',
                'stage' => 'pre_production',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => true,
                'sort_order' => 120,
            ],
        ];
        
        // Insert all statuses with timestamps
        $timestamp = now();
        foreach ($statuses as &$status) {
            $status['created_at'] = $timestamp;
            $status['updated_at'] = $timestamp;
        }
        
        DB::table('unified_order_statuses')->insert($statuses);
    }
};