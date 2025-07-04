<?php

namespace Database\Seeders;

use App\Models\PackagingType;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PackagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a packaging supplier
        $supplier = Supplier::create([
            'name' => 'Uline',
            'type' => 'consumable',
            'contact_name' => 'Customer Service',
            'contact_email' => 'customerservice@uline.com',
            'contact_phone' => '1-800-295-5510',
            'address' => '12575 Uline Drive, Pleasant Prairie, WI 53158, USA',
            'is_active' => true,
        ]);

        // Common clamshell sizes
        $clamshells = [
            [
                'name' => '16oz Clamshell',
                'volume' => 16,
                'is_active' => true,
            ],
            [
                'name' => '24oz Clamshell',
                'volume' => 24,
                'is_active' => true,
            ],
            [
                'name' => '32oz Clamshell',
                'volume' => 32,
                'is_active' => true,
            ],
            [
                'name' => '48oz Clamshell',
                'volume' => 48,
                'is_active' => true,
            ],
            [
                'name' => '64oz Clamshell',
                'volume' => 64,
                'is_active' => true,
            ],
        ];

        foreach ($clamshells as $clamshell) {
            $packagingType = PackagingType::create($clamshell);

            // Create a consumable entry for each packaging type
            \App\Models\Consumable::create([
                'name' => $packagingType->name,
                'type' => 'packaging',
                'supplier_id' => $supplier->id,
                'packaging_type_id' => $packagingType->id,
                'current_stock' => 100,
                'consumed_quantity' => 0,
                'unit' => 'unit',
                'restock_threshold' => 10,
                'restock_quantity' => 50,
                'cost_per_unit' => match($clamshell['volume']) {
                    16 => 0.35,
                    24 => 0.45,
                    32 => 0.55,
                    48 => 0.65,
                    64 => 0.75,
                },
                'quantity_per_unit' => 1,
                'quantity_unit' => 'l',
                'total_quantity' => 0,
                'is_active' => true,
            ]);
        }
    }
} 