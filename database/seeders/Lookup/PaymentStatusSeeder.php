<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentStatuses = [
            [
                'code' => 'pending',
                'name' => 'Pending',
                'description' => 'Payment is awaiting processing or confirmation',
                'color' => 'warning',
                'is_active' => true,
                'sort_order' => 1,
                'is_final' => false,
                'allows_modifications' => true,
            ],
            [
                'code' => 'completed',
                'name' => 'Completed',
                'description' => 'Payment has been successfully processed and confirmed',
                'color' => 'success',
                'is_active' => true,
                'sort_order' => 2,
                'is_final' => true,
                'allows_modifications' => false,
            ],
            [
                'code' => 'failed',
                'name' => 'Failed',
                'description' => 'Payment processing failed or was rejected',
                'color' => 'danger',
                'is_active' => true,
                'sort_order' => 3,
                'is_final' => false,
                'allows_modifications' => true,
            ],
            [
                'code' => 'refunded',
                'name' => 'Refunded',
                'description' => 'Payment has been refunded to the customer',
                'color' => 'info',
                'is_active' => true,
                'sort_order' => 4,
                'is_final' => true,
                'allows_modifications' => false,
            ],
        ];

        foreach ($paymentStatuses as $status) {
            \App\Models\PaymentStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
