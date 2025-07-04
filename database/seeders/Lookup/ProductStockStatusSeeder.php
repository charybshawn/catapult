<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductStockStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stockStatuses = [
            [
                'code' => 'in_stock',
                'name' => 'In Stock',
                'description' => 'Product is available and in stock',
                'color' => 'success',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'low_stock',
                'name' => 'Low Stock',
                'description' => 'Product is available but stock is running low',
                'color' => 'warning',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'out_of_stock',
                'name' => 'Out of Stock',
                'description' => 'Product is temporarily unavailable',
                'color' => 'danger',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'discontinued',
                'name' => 'Discontinued',
                'description' => 'Product is no longer available and will not be restocked',
                'color' => 'gray',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($stockStatuses as $status) {
            \App\Models\ProductStockStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
