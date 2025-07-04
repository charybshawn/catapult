<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CropPlanStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'draft',
                'name' => 'Draft',
                'description' => 'Plan is being created and can be freely modified',
                'color' => 'gray',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'active',
                'name' => 'Active',
                'description' => 'Plan is approved and crops can be created from it',
                'color' => 'success',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'completed',
                'name' => 'Completed',
                'description' => 'All planned crops have been successfully harvested',
                'color' => 'primary',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'Plan was cancelled and will not be executed',
                'color' => 'danger',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($statuses as $status) {
            \App\Models\CropPlanStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
