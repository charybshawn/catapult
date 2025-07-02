<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CropStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            [
                'code' => 'germination',
                'name' => 'Germination',
                'description' => 'Seeds are sprouting and developing initial roots and shoots',
                'color' => 'warning',
                'is_active' => true,
                'sort_order' => 1,
                'typical_duration_days' => 2,
                'requires_light' => false,
                'requires_watering' => true,
            ],
            [
                'code' => 'blackout',
                'name' => 'Blackout',
                'description' => 'Plants are grown in darkness to encourage stem elongation',
                'color' => 'secondary',
                'is_active' => true,
                'sort_order' => 2,
                'typical_duration_days' => 3,
                'requires_light' => false,
                'requires_watering' => true,
            ],
            [
                'code' => 'light',
                'name' => 'Light',
                'description' => 'Plants are exposed to light for photosynthesis and leaf development',
                'color' => 'info',
                'is_active' => true,
                'sort_order' => 3,
                'typical_duration_days' => 7,
                'requires_light' => true,
                'requires_watering' => true,
            ],
            [
                'code' => 'harvested',
                'name' => 'Harvested',
                'description' => 'Crop has been harvested and is ready for processing',
                'color' => 'success',
                'is_active' => true,
                'sort_order' => 4,
                'typical_duration_days' => null,
                'requires_light' => false,
                'requires_watering' => false,
            ],
        ];

        foreach ($stages as $stage) {
            \App\Models\CropStage::updateOrCreate(
                ['code' => $stage['code']],
                $stage
            );
        }
    }
}
