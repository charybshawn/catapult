<?php

namespace Database\Seeders\Legacy;

use App\Models\SupplierType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('suppliers')->truncate();

        // Get supplier type IDs
        $seedTypeId = SupplierType::findByCode('seed')?->id;
        $soilTypeId = SupplierType::findByCode('soil')?->id;
        $packagingTypeId = SupplierType::findByCode('packaging')?->id;

        // Insert data
        DB::table('suppliers')->insert([
            [
                'id' => 1,
                'name' => 'Mumm\'s Sprouting Seeds',
                'supplier_type_id' => $seedTypeId,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-23 03:01:59',
                'updated_at' => '2025-06-24 18:01:46',
            ],
            [
                'id' => 2,
                'name' => 'Germina Seeds',
                'supplier_type_id' => $seedTypeId,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 17:51:32',
                'updated_at' => '2025-06-24 18:01:28',
            ],
            [
                'id' => 3,
                'name' => 'Britelands',
                'supplier_type_id' => $packagingTypeId,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:01:03',
                'updated_at' => '2025-06-24 18:06:10',
            ],
            [
                'id' => 4,
                'name' => 'William Dam Seeds',
                'supplier_type_id' => $seedTypeId,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:01:09',
                'updated_at' => '2025-06-24 18:01:09',
            ],
            [
                'id' => 5,
                'name' => 'Ecoline',
                'supplier_type_id' => $soilTypeId,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:06:28',
                'updated_at' => '2025-06-24 18:06:28',
            ],
            [
                'id' => 6,
                'name' => 'Buckerfields',
                'supplier_type_id' => $soilTypeId,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:06:47',
                'updated_at' => '2025-06-24 18:07:02',
            ],
            [
                'id' => 7,
                'name' => 'Johnny\'s Seeds',
                'supplier_type_id' => $seedTypeId,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:07:22',
                'updated_at' => '2025-06-24 18:07:22',
            ],
            [
                'id' => 8,
                'name' => 'True Leaf Market',
                'supplier_type_id' => $seedTypeId,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => 1,
                'created_at' => '2025-06-24 18:07:43',
                'updated_at' => '2025-06-24 18:07:43',
            ],
        ]);
    }
}