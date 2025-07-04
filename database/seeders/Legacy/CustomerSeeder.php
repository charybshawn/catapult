<?php

namespace Database\Seeders\Legacy;

use App\Models\Customer;
use App\Models\CustomerType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customer type IDs for reference
        $retailTypeId = CustomerType::where('code', 'retail')->first()?->id;
        $wholesaleTypeId = CustomerType::where('code', 'wholesale')->first()?->id;
        $farmersMarketTypeId = CustomerType::where('code', 'farmers_market')->first()?->id;

        $customers = [
            [
                'contact_name' => 'Chris',
                'email' => 'chris@timbershuswap.ca',
                'customer_type_id' => $retailTypeId,
                'business_name' => 'Timber Restaurant',
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
            [
                'contact_name' => 'Mike',
                'email' => 'hanoi36sa@gmail.com',
                'customer_type_id' => $retailTypeId,
                'business_name' => 'Hanoi 36',
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
            [
                'contact_name' => 'Justin',
                'email' => 'justin@pvbbg.ca',
                'customer_type_id' => $wholesaleTypeId,
                'business_name' => 'Village Grocer',
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
            [
                'contact_name' => 'Toni',
                'email' => 'buckerfieldssa@gmail.com',
                'customer_type_id' => $wholesaleTypeId,
                'business_name' => 'Buckerfields Salmon Arm',
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
            [
                'contact_name' => 'Christine',
                'email' => 'safarmersmarket@gmail.com',
                'customer_type_id' => $farmersMarketTypeId,
                'business_name' => 'Salmon Arm Farmer\'s Market',
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
            [
                'contact_name' => 'Connie',
                'email' => 'celistamarket@gmail.com',
                'customer_type_id' => $farmersMarketTypeId,
                'business_name' => 'Celista Farmer\'s Market',
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
            [
                'contact_name' => 'Emily',
                'email' => 'naturedelivered@gmail.com',
                'customer_type_id' => $wholesaleTypeId,
                'business_name' => 'Nature Delivered',
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
            [
                'contact_name' => 'Debbie Stangland',
                'email' => 'dstang@gmail.com',
                'customer_type_id' => $retailTypeId,
                'business_name' => null,
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
            [
                'contact_name' => 'Nipa',
                'email' => 'nipa@chiangmai.ca',
                'customer_type_id' => $retailTypeId,
                'business_name' => 'Chiang Mai',
                'wholesale_discount_percentage' => '0.00',
                'province' => 'BC',
                'country' => 'CA',
            ],
        ];

        foreach ($customers as $customerData) {
            Customer::firstOrCreate(
                ['email' => $customerData['email']], // Use email as unique identifier
                $customerData
            );
        }

        $this->command->info('Customer seeder completed successfully!');
        $this->command->info('Created ' . count($customers) . ' customers:');
        $this->command->info('- 4 Retail customers');
        $this->command->info('- 3 Wholesale customers');
        $this->command->info('- 2 Farmers Market customers');
    }
}