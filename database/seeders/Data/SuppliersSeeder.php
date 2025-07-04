<?php

namespace Database\Seeders\Data;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuppliersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => "Mumm's Sprouting Seeds",
                'supplier_type_id' => 2, // Seeds
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Germina Seeds',
                'supplier_type_id' => 2, // Seeds
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Britelands',
                'supplier_type_id' => 4, // Packaging
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'William Dam Seeds',
                'supplier_type_id' => 2, // Seeds
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Ecoline',
                'supplier_type_id' => 1, // Soil
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Buckerfields',
                'supplier_type_id' => 1, // Soil
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => "Johnny's Seeds",
                'supplier_type_id' => 2, // Seeds
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'True Leaf Market',
                'supplier_type_id' => 2, // Seeds
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::firstOrCreate(
                ['name' => $supplierData['name']],
                $supplierData
            );
        }
    }
}