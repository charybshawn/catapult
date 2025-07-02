<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderStatuses = [
            [
                'code' => 'draft',
                'name' => 'Draft',
                'description' => 'Order is being prepared and not yet confirmed',
                'color' => 'gray',
                'is_active' => true,
                'is_final' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'pending',
                'name' => 'Pending',
                'description' => 'Order is waiting for confirmation',
                'color' => 'yellow',
                'is_active' => true,
                'is_final' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'confirmed',
                'name' => 'Confirmed',
                'description' => 'Order has been confirmed and is ready for production',
                'color' => 'blue',
                'is_active' => true,
                'is_final' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'in_production',
                'name' => 'In Production',
                'description' => 'Order is currently being grown/produced',
                'color' => 'purple',
                'is_active' => true,
                'is_final' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'ready_for_harvest',
                'name' => 'Ready for Harvest',
                'description' => 'Products are ready to be harvested',
                'color' => 'orange',
                'is_active' => true,
                'is_final' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'harvested',
                'name' => 'Harvested',
                'description' => 'Products have been harvested and are being prepared',
                'color' => 'teal',
                'is_active' => true,
                'is_final' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'packed',
                'name' => 'Packed',
                'description' => 'Order has been packed and is ready for delivery',
                'color' => 'indigo',
                'is_active' => true,
                'is_final' => false,
                'sort_order' => 7,
            ],
            [
                'code' => 'delivered',
                'name' => 'Delivered',
                'description' => 'Order has been delivered to customer',
                'color' => 'green',
                'is_active' => true,
                'is_final' => true,
                'sort_order' => 8,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'Order has been cancelled',
                'color' => 'red',
                'is_active' => true,
                'is_final' => true,
                'sort_order' => 9,
            ],
            [
                'code' => 'template',
                'name' => 'Template',
                'description' => 'Order template for recurring orders',
                'color' => 'gray',
                'is_active' => true,
                'is_final' => false,
                'sort_order' => 10,
            ],
        ];

        foreach ($orderStatuses as $status) {
            \App\Models\OrderStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
