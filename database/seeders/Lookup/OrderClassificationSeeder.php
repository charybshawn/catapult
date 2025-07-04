<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderClassifications = [
            [
                'code' => 'scheduled',
                'name' => 'Scheduled',
                'description' => 'Order planned and scheduled in advance',
                'color' => 'blue',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'ondemand',
                'name' => 'On Demand',
                'description' => 'Order placed for immediate fulfillment',
                'color' => 'yellow',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'overflow',
                'name' => 'Overflow',
                'description' => 'Additional order beyond regular capacity',
                'color' => 'orange',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'priority',
                'name' => 'Priority',
                'description' => 'High-priority order requiring expedited handling',
                'color' => 'red',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($orderClassifications as $classification) {
            \App\Models\OrderClassification::updateOrCreate(
                ['code' => $classification['code']],
                $classification
            );
        }
    }
}
