<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConsumableUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $consumableUnits = [
            // Count units (base unit: unit)
            [
                'code' => 'unit',
                'name' => 'Unit',
                'symbol' => 'ea',
                'description' => 'Individual items or pieces',
                'category' => 'count',
                'conversion_factor' => 1.0,
                'base_unit' => 'unit',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'bag',
                'name' => 'Bag',
                'symbol' => 'bag',
                'description' => 'Items packaged in bags',
                'category' => 'count',
                'conversion_factor' => 1.0,
                'base_unit' => 'unit',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'tray',
                'name' => 'Tray',
                'symbol' => 'tray',
                'description' => 'Items packaged in trays',
                'category' => 'count',
                'conversion_factor' => 1.0,
                'base_unit' => 'unit',
                'is_active' => true,
                'sort_order' => 3,
            ],
            
            // Weight units (base unit: gram)
            [
                'code' => 'gram',
                'name' => 'Gram',
                'symbol' => 'g',
                'description' => 'Metric weight unit',
                'category' => 'weight',
                'conversion_factor' => 1.0,
                'base_unit' => 'gram',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'kilogram',
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'description' => 'Metric weight unit (1000 grams)',
                'category' => 'weight',
                'conversion_factor' => 1000.0,
                'base_unit' => 'gram',
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'code' => 'ounce',
                'name' => 'Ounce',
                'symbol' => 'oz',
                'description' => 'Imperial weight unit',
                'category' => 'weight',
                'conversion_factor' => 28.3495,
                'base_unit' => 'gram',
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'code' => 'pound',
                'name' => 'Pound',
                'symbol' => 'lb',
                'description' => 'Imperial weight unit (16 ounces)',
                'category' => 'weight',
                'conversion_factor' => 453.592,
                'base_unit' => 'gram',
                'is_active' => true,
                'sort_order' => 13,
            ],
            
            // Volume units (base unit: millilitre)
            [
                'code' => 'millilitre',
                'name' => 'Millilitre',
                'symbol' => 'mL',
                'description' => 'Metric volume unit',
                'category' => 'volume',
                'conversion_factor' => 1.0,
                'base_unit' => 'millilitre',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'litre',
                'name' => 'Litre',
                'symbol' => 'L',
                'description' => 'Metric volume unit (1000 mL)',
                'category' => 'volume',
                'conversion_factor' => 1000.0,
                'base_unit' => 'millilitre',
                'is_active' => true,
                'sort_order' => 21,
            ],
            [
                'code' => 'gallon',
                'name' => 'Gallon',
                'symbol' => 'gal',
                'description' => 'Imperial volume unit',
                'category' => 'volume',
                'conversion_factor' => 4546.09,
                'base_unit' => 'millilitre',
                'is_active' => true,
                'sort_order' => 22,
            ],
        ];

        foreach ($consumableUnits as $unit) {
            \App\Models\ConsumableUnit::updateOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }
    }
}
