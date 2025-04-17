<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all consumables with non-null lot_no
        $consumables = DB::table('consumables')
            ->whereNotNull('lot_no')
            ->get();
        
        // Update each consumable's lot_no to uppercase
        foreach ($consumables as $consumable) {
            $oldLotNo = $consumable->lot_no;
            $newLotNo = strtoupper($oldLotNo);
            
            // Only update if there's an actual change
            if ($oldLotNo !== $newLotNo) {
                DB::table('consumables')
                    ->where('id', $consumable->id)
                    ->update(['lot_no' => $newLotNo]);
                    
                // Log the change
                DB::table('activity_log')->insert([
                    'log_name' => 'default',
                    'description' => 'Updated lot_no from ' . $oldLotNo . ' to ' . $newLotNo,
                    'subject_type' => 'App\\Models\\Consumable',
                    'subject_id' => $consumable->id,
                    'causer_type' => null,
                    'causer_id' => null,
                    'properties' => json_encode(['old' => ['lot_no' => $oldLotNo], 'attributes' => ['lot_no' => $newLotNo]]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible as we don't know the original case
    }
};
