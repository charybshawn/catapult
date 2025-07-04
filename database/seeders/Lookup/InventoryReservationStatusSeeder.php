<?php

namespace Database\Seeders\Lookup;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventoryReservationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'pending',
                'name' => 'Pending',
                'description' => 'Reservation is awaiting confirmation and holds inventory temporarily',
                'color' => 'warning',
                'is_active' => true,
                'sort_order' => 1,
                'is_final' => false,
                'allows_modifications' => true,
                'auto_release_hours' => 24, // Auto-release after 24 hours
            ],
            [
                'code' => 'confirmed',
                'name' => 'Confirmed',
                'description' => 'Reservation is confirmed and inventory is allocated',
                'color' => 'info',
                'is_active' => true,
                'sort_order' => 2,
                'is_final' => false,
                'allows_modifications' => true,
                'auto_release_hours' => null, // No auto-release for confirmed
            ],
            [
                'code' => 'fulfilled',
                'name' => 'Fulfilled',
                'description' => 'Reservation has been fulfilled and inventory released',
                'color' => 'success',
                'is_active' => true,
                'sort_order' => 3,
                'is_final' => true,
                'allows_modifications' => false,
                'auto_release_hours' => null,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'Reservation was cancelled and inventory released',
                'color' => 'gray',
                'is_active' => true,
                'sort_order' => 4,
                'is_final' => true,
                'allows_modifications' => false,
                'auto_release_hours' => null,
            ],
        ];

        foreach ($statuses as $status) {
            \App\Models\InventoryReservationStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}