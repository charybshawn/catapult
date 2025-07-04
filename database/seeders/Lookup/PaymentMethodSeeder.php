<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'code' => 'stripe',
                'name' => 'Credit Card (Stripe)',
                'description' => 'Online credit card payments processed through Stripe',
                'color' => 'blue',
                'is_active' => true,
                'requires_processing' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'e-transfer',
                'name' => 'E-Transfer',
                'description' => 'Interac e-Transfer electronic payment',
                'color' => 'green',
                'is_active' => true,
                'requires_processing' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'cash',
                'name' => 'Cash',
                'description' => 'Cash payment on delivery or pickup',
                'color' => 'yellow',
                'is_active' => true,
                'requires_processing' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'invoice',
                'name' => 'Invoice',
                'description' => 'Payment by invoice with net terms',
                'color' => 'purple',
                'is_active' => true,
                'requires_processing' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($paymentMethods as $method) {
            \App\Models\PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
