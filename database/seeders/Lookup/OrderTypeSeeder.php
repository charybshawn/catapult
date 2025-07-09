<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing order types first
        \App\Models\OrderType::query()->delete();
        
        $orderTypes = [
            [
                'code' => 'b2b',
                'name' => 'B2B',
                'description' => 'Business-to-business bulk order',
                'color' => 'purple',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'website_order',
                'name' => 'Website Order',
                'description' => 'Order placed through the website',
                'color' => 'blue',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'farmers_market',
                'name' => 'Farmer\'s Market',
                'description' => 'Order for farmer\'s market sales',
                'color' => 'green',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($orderTypes as $type) {
            \App\Models\OrderType::create($type);
        }
    }
}
