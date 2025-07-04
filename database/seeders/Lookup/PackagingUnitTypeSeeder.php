<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackagingUnitTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitTypes = [
            [
                'code' => 'count',
                'name' => 'Count',
                'description' => 'Packaging sold by individual units or pieces',
                'color' => 'primary',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'weight',
                'name' => 'Weight',
                'description' => 'Packaging sold by weight measurement (grams, kilograms, etc.)',
                'color' => 'warning',
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($unitTypes as $unitType) {
            \App\Models\PackagingUnitType::updateOrCreate(
                ['code' => $unitType['code']],
                $unitType
            );
        }
    }
}
