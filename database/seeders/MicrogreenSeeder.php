<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SeedVariety;
use Illuminate\Database\Seeder;

class MicrogreenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific seed suppliers
        $suppliers = [
            [
                'name' => 'Mumm\'s Sprouting Seeds',
                'type' => 'seed',
                'contact_name' => 'Customer Service',
                'contact_email' => 'info@mumms.com',
                'contact_phone' => '1-800-665-1931',
                'address' => 'Box 120, Parkside, SK, Canada S0N 2A0',
                'is_active' => true,
            ],
            [
                'name' => 'True Leaf Market',
                'type' => 'seed',
                'contact_name' => 'Customer Service',
                'contact_email' => 'support@trueleafmarket.com',
                'contact_phone' => '1-800-620-7628',
                'address' => '352 W 900 N, Lindon, UT 84042, USA',
                'is_active' => true,
            ],
            [
                'name' => 'Johnny\'s Seeds',
                'type' => 'seed',
                'contact_name' => 'Customer Service',
                'contact_email' => 'customerservice@johnnyseeds.com',
                'contact_phone' => '1-877-564-6697',
                'address' => '955 Benton Ave, Winslow, ME 04901, USA',
                'is_active' => true,
            ],
            [
                'name' => 'William Damm Seeds',
                'type' => 'seed',
                'contact_name' => 'Customer Service',
                'contact_email' => 'info@williamdammseeds.com',
                'contact_phone' => '1-800-123-4567',
                'address' => '123 Seed Street, Portland, OR 97201, USA',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplierData) {
            $supplier = Supplier::create($supplierData);

            // Common microgreen varieties
            $varieties = [
                [
                    'name' => 'Sunflower - Black Oil',
                    'is_active' => true,
                ],
                [
                    'name' => 'Pea - Speckled',
                    'is_active' => true,
                ],
                [
                    'name' => 'Radish - Daikon',
                    'is_active' => true,
                ],
                [
                    'name' => 'Broccoli - Calabrese',
                    'is_active' => true,
                ],
                [
                    'name' => 'Kale - Red Russian',
                    'is_active' => true,
                ],
                [
                    'name' => 'Arugula - Wild',
                    'is_active' => true,
                ],
                [
                    'name' => 'Mustard - Red Giant',
                    'is_active' => true,
                ],
                [
                    'name' => 'Amaranth - Red Garnet',
                    'is_active' => true,
                ],
                [
                    'name' => 'Basil - Genovese',
                    'is_active' => true,
                ],
                [
                    'name' => 'Beet - Detroit Dark Red',
                    'is_active' => true,
                ],
            ];

            foreach ($varieties as $varietyData) {
                SeedVariety::create($varietyData);
            }
        }
    }
} 