<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SeedCultivar;
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
            [
                'name' => 'Germina',
                'type' => 'seed',
                'contact_name' => 'Rafael Dostie Blais',
                'contact_email' => 'info@germina.ca',
                'contact_phone' => '438-809-5197',
                'address' => '9250, Avenue du Parc, #201, Montréal, QC H2N 1Z2, Canada',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplierData) {
            $supplier = Supplier::create($supplierData);

            // Common microgreen cultivars
            $cultivars = [
                [
                    'name' => 'Sunflower (Black Oil)',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                [
                    'name' => 'Pea - Speckled',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                [
                    'name' => 'Radish - Daikon',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                [
                    'name' => 'Broccoli - Calabrese',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                [
                    'name' => 'Kale - Red Russian',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                [
                    'name' => 'Arugula - Wild',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                [
                    'name' => 'Mustard - Red Giant',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                [
                    'name' => 'Amaranth - Red Garnet',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                [
                    'name' => 'Basil - Genovese',
                    'crop_type' => 'herbs',
                    'is_active' => true,
                ],
                [
                    'name' => 'Beet - Detroit Dark Red',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
            ];

            foreach ($cultivars as $cultivarData) {
                SeedCultivar::create($cultivarData);
            }
        }
    }
} 