<?php

use App\Models\Consumable;
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
        // Map old unit types to new simplified ones
        $unitMap = [
            'bag' => 'unit',
            'box' => 'unit',
            'bale' => 'unit',
            'pcs' => 'unit',
            'packet' => 'unit',
            'packets' => 'unit',
            // Keep these as is
            'kg' => 'kg',
            'g' => 'g',
            'oz' => 'oz',
            'l' => 'l',
            'liter' => 'l',
            'liters' => 'l',
            'ounce' => 'oz',
            'ounces' => 'oz',
        ];
        
        // Get all consumables
        $consumables = DB::table('consumables')->get();
        
        // Update each consumable with simplified unit type
        foreach ($consumables as $consumable) {
            $oldUnit = $consumable->unit;
            $newUnit = $unitMap[$oldUnit] ?? 'unit'; // Default to unit if not found
            
            if ($oldUnit !== $newUnit) {
                DB::table('consumables')
                    ->where('id', $consumable->id)
                    ->update(['unit' => $newUnit]);
                    
                // Log the change
                DB::table('activity_log')->insert([
                    'log_name' => 'default',
                    'description' => 'Updated unit type from ' . $oldUnit . ' to ' . $newUnit,
                    'subject_type' => 'App\\Models\\Consumable',
                    'subject_id' => $consumable->id,
                    'causer_type' => null,
                    'causer_id' => null,
                    'properties' => json_encode(['old' => ['unit' => $oldUnit], 'attributes' => ['unit' => $newUnit]]),
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
        // Not reversible as we don't know the original values
    }
};
