<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderTypes = [
            [
                'code' => 'standard',
                'name' => 'Standard',
                'description' => 'Regular one-time order',
                'color' => 'blue',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'subscription',
                'name' => 'Subscription',
                'description' => 'Recurring subscription order',
                'color' => 'green',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'b2b',
                'name' => 'B2B',
                'description' => 'Business-to-business bulk order',
                'color' => 'purple',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($orderTypes as $type) {
            \App\Models\OrderType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
