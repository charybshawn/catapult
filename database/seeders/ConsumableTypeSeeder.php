<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConsumableTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $consumableTypes = [
            [
                'code' => 'packaging',
                'name' => 'Packaging',
                'description' => 'Containers, bags, labels, and packaging materials',
                'color' => 'blue',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'soil',
                'name' => 'Soil',
                'description' => 'Growing medium, soil amendments, and substrates',
                'color' => 'yellow',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'seed',
                'name' => 'Seeds',
                'description' => 'Seeds and planting materials',
                'color' => 'green',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'label',
                'name' => 'Labels',
                'description' => 'Product labels, stickers, and identification materials',
                'color' => 'purple',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'other',
                'name' => 'Other',
                'description' => 'Miscellaneous consumable items',
                'color' => 'gray',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($consumableTypes as $type) {
            \App\Models\ConsumableType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
