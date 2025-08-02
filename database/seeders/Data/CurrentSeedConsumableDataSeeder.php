<?php

namespace Database\Seeders\Data;

use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class CurrentSeedConsumableDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding current inventory consumables...');

        // Get required foreign keys once
        $seedTypeId = ConsumableType::where('code', 'seed')->value('id');
        $soilTypeId = ConsumableType::where('code', 'soil')->value('id');
        $gramUnitId = ConsumableUnit::where('code', 'gram')->value('id');
        $literUnitId = ConsumableUnit::where('code', 'liter')->value('id');
        $seedSupplierId = Supplier::where('name', "Mumm's Sprouting Seeds")->value('id');
        $soilSupplierId = Supplier::where('name', 'Ecoline')->value('id');

        $consumables = [
            // Soil consumables
            [
                'name' => 'Pro Mix HP',
                'consumable_type_id' => $soilTypeId,
                'consumable_unit_id' => $literUnitId,
                'supplier_id' => $soilSupplierId,
                'initial_stock' => 428,
                'consumed_quantity' => 0,
                'total_quantity' => 428,
                'quantity_unit' => 'l',
                'restock_threshold' => 50,
                'restock_quantity' => 250,
            ],

            // Seed consumables
            [
                'name' => 'Arugula (Arugula)',
                'lot_no' => 'AR2-01',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 502,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Borage (Borage)',
                'lot_no' => 'BOR0Y',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 540,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Kale (Red)',
                'lot_no' => 'KR3Y-01',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 538,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Kohlrabi (Purple)',
                'lot_no' => 'KOH3-01',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 591,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Kale (Green)',
                'lot_no' => 'KG2N2',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 388,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Beet (Ruby)',
                'lot_no' => 'BER2L',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 0,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Mustard (Oriental)',
                'lot_no' => 'MO12',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 5000,
                'consumed_quantity' => 2578,
                'total_quantity' => 5000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Radish (Red)',
                'lot_no' => 'RR4196LL',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 636,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Broccoli (Broccoli)',
                'lot_no' => 'RR4196LL',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 5000,
                'consumed_quantity' => 4834,
                'total_quantity' => 5000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Broccoli (Broccoli)',
                'lot_no' => 'RR4196LL-2',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 849,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Broccoli (Raab Rapini)',
                'lot_no' => 'BR9',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 900,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Beet (Ruby)',
                'lot_no' => 'BER2L-2',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 800,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Swiss Chard (Yellow)',
                'lot_no' => '38124',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 2000,
                'consumed_quantity' => 0,
                'total_quantity' => 2000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Basil (Genovese)',
                'lot_no' => 'BAS8Y',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 520,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Basil (Thai)',
                'lot_no' => 'BAST7L',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 1000,
                'consumed_quantity' => 20,
                'total_quantity' => 1000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Amaranth (Red)',
                'lot_no' => '38637',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 450,
                'consumed_quantity' => 110,
                'total_quantity' => 450,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Cress (Curly Garden)',
                'lot_no' => 'CC9257SG',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 125,
                'consumed_quantity' => 85,
                'total_quantity' => 125,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Fenugreek (Fenugreek)',
                'lot_no' => 'F91S',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 125,
                'consumed_quantity' => 45,
                'total_quantity' => 125,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Mustard (Oriental)',
                'lot_no' => 'MO12-2',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 5000,
                'consumed_quantity' => 2150,
                'total_quantity' => 5000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Sunflower (Black Oilseed)',
                'lot_no' => 'SFR16',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 10000,
                'consumed_quantity' => 4812,
                'total_quantity' => 10000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Coriander (Coriander)',
                'lot_no' => 'COR3',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 5000,
                'consumed_quantity' => 750,
                'total_quantity' => 5000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Peas (Speckled)',
                'lot_no' => 'PS4M',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 25000,
                'consumed_quantity' => 16660,
                'total_quantity' => 25000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
            [
                'name' => 'Radish (Ruby Stem)',
                'consumable_type_id' => $seedTypeId,
                'consumable_unit_id' => $gramUnitId,
                'supplier_id' => $seedSupplierId,
                'initial_stock' => 10000,
                'consumed_quantity' => 500,
                'total_quantity' => 10000,
                'quantity_unit' => 'g',
                'restock_threshold' => 250,
                'restock_quantity' => 1000,
            ],
        ];

        $timestamp = now();
        $created = 0;

        foreach ($consumables as $data) {
            $data['quantity_per_unit'] = 1;
            $data['is_active'] = true;
            $data['created_at'] = $timestamp;
            $data['updated_at'] = $timestamp;

            Consumable::updateOrCreate(
                [
                    'name' => $data['name'],
                    'lot_no' => $data['lot_no'] ?? null,
                ],
                $data
            );

            $created++;
        }

        $this->command->info("Processed {$created} consumables");
    }
}
