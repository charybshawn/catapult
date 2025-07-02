<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerTypes = [
            [
                'code' => 'retail',
                'name' => 'Retail',
                'description' => 'Individual retail customers purchasing for personal use',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'wholesale',
                'name' => 'Wholesale',
                'description' => 'Bulk purchasing customers with wholesale discount pricing',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'farmers_market',
                'name' => 'Farmers Market',
                'description' => 'Farmers market vendors and stalls with wholesale pricing',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($customerTypes as $type) {
            \App\Models\CustomerType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
