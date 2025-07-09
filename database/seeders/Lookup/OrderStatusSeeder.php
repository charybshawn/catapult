<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing order statuses first
        \App\Models\OrderStatus::query()->delete();

        $orderStatuses = [
            [
                'code' => 'draft',
                'name' => 'Draft',
                'description' => 'Order is being prepared and not yet confirmed',
                'color' => 'gray',
                'badge_color' => 'gray',
                'stage' => 'pre_production',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'pending',
                'name' => 'Pending',
                'description' => 'Order is waiting for confirmation',
                'color' => 'yellow',
                'badge_color' => 'warning',
                'stage' => 'pre_production',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'confirmed',
                'name' => 'Confirmed',
                'description' => 'Order has been confirmed and is ready for production',
                'color' => 'blue',
                'badge_color' => 'primary',
                'stage' => 'pre_production',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'in_production',
                'name' => 'In Production',
                'description' => 'Order is currently being grown/produced',
                'color' => 'purple',
                'badge_color' => 'secondary',
                'stage' => 'production',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'ready_for_harvest',
                'name' => 'Ready for Harvest',
                'description' => 'Products are ready to be harvested',
                'color' => 'orange',
                'badge_color' => 'warning',
                'stage' => 'production',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'harvested',
                'name' => 'Harvested',
                'description' => 'Products have been harvested and are being prepared',
                'color' => 'teal',
                'badge_color' => 'info',
                'stage' => 'fulfillment',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'packed',
                'name' => 'Packed',
                'description' => 'Order has been packed and is ready for delivery',
                'color' => 'indigo',
                'badge_color' => 'primary',
                'stage' => 'fulfillment',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => false,
                'allows_modifications' => false,
                'sort_order' => 7,
            ],
            [
                'code' => 'delivered',
                'name' => 'Delivered',
                'description' => 'Order has been delivered to customer',
                'color' => 'green',
                'badge_color' => 'success',
                'stage' => 'final',
                'requires_crops' => true,
                'is_active' => true,
                'is_final' => true,
                'allows_modifications' => false,
                'sort_order' => 8,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'Order has been cancelled',
                'color' => 'red',
                'badge_color' => 'danger',
                'stage' => 'final',
                'requires_crops' => false,
                'is_active' => true,
                'is_final' => true,
                'allows_modifications' => false,
                'sort_order' => 9,
            ],
        ];

        foreach ($orderStatuses as $status) {
            \App\Models\OrderStatus::create($status);
        }
    }
}