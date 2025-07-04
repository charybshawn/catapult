<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supplierTypes = [
            [
                'code' => 'soil',
                'name' => 'Soil',
                'description' => 'Suppliers providing growing medium, soil amendments, and growing substrates',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'seed',
                'name' => 'Seeds',
                'description' => 'Suppliers providing seeds, seedlings, and planting materials',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'consumable',
                'name' => 'Consumables',
                'description' => 'Suppliers providing general consumable items and supplies',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'packaging',
                'name' => 'Packaging',
                'description' => 'Suppliers providing packaging materials, containers, and labeling supplies',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'other',
                'name' => 'Other',
                'description' => 'Suppliers providing miscellaneous items not covered by other categories',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($supplierTypes as $type) {
            \App\Models\SupplierType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
