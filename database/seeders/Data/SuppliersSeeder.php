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
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Germina Seeds',
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Britelands',
                'type' => 'packaging',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'William Dam Seeds',
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Ecoline',
                'type' => 'soil',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Buckerfields',
                'type' => 'soil',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => "Johnny's Seeds",
                'type' => 'seed',
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
            [
                'name' => 'True Leaf Market',
                'type' => 'seed',
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