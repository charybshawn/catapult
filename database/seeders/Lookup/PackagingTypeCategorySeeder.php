<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackagingTypeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'clamshell',
                'name' => 'Clamshell',
                'description' => 'Clear plastic containers that open like a clam shell, ideal for showcasing products',
                'color' => 'info',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'bag',
                'name' => 'Bag',
                'description' => 'Flexible packaging including plastic bags, paper bags, and mesh bags',
                'color' => 'primary',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'box',
                'name' => 'Box',
                'description' => 'Rigid rectangular containers made from cardboard, plastic, or wood',
                'color' => 'warning',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'jar',
                'name' => 'Jar',
                'description' => 'Glass or plastic containers with lids, typically cylindrical',
                'color' => 'success',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'tray',
                'name' => 'Tray',
                'description' => 'Flat containers with raised edges, often used for displaying multiple items',
                'color' => 'purple',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'bulk',
                'name' => 'Bulk',
                'description' => 'Large containers or loose packaging for wholesale quantities',
                'color' => 'secondary',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'code' => 'other',
                'name' => 'Other',
                'description' => 'Miscellaneous packaging types not covered by standard categories',
                'color' => 'gray',
                'is_active' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $category) {
            \App\Models\PackagingTypeCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}
