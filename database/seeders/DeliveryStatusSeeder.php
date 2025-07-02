<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeliveryStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deliveryStatuses = [
            [
                'code' => 'pending',
                'name' => 'Pending',
                'description' => 'Delivery is scheduled but not yet started',
                'color' => 'gray',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'scheduled',
                'name' => 'Scheduled',
                'description' => 'Delivery has been scheduled',
                'color' => 'blue',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'in_transit',
                'name' => 'In Transit',
                'description' => 'Order is on its way to the customer',
                'color' => 'yellow',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'delivered',
                'name' => 'Delivered',
                'description' => 'Order has been delivered successfully',
                'color' => 'green',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'failed',
                'name' => 'Failed',
                'description' => 'Delivery attempt failed',
                'color' => 'red',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($deliveryStatuses as $status) {
            \App\Models\DeliveryStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
