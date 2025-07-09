<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing transaction types used in the code
        $missingTypes = [
            ['code' => 'production', 'name' => 'Production', 'description' => 'Inventory created from production', 'color' => 'green', 'sort_order' => 7],
            ['code' => 'expiration', 'name' => 'Expiration', 'description' => 'Inventory expired and removed', 'color' => 'red', 'sort_order' => 8],
            ['code' => 'reservation', 'name' => 'Reservation', 'description' => 'Inventory reserved for order', 'color' => 'orange', 'sort_order' => 9],
            ['code' => 'release', 'name' => 'Release', 'description' => 'Reserved inventory released', 'color' => 'cyan', 'sort_order' => 10],
        ];

        foreach ($missingTypes as $type) {
            DB::table('inventory_transaction_types')->insertOrIgnore($type);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inventory_transaction_types')
            ->whereIn('code', ['production', 'expiration', 'reservation', 'release'])
            ->delete();
    }
};