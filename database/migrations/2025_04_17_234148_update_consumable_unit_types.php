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
        // Map old unit types to new standardized ones
        $unitMap = [
            'bags' => 'bag',
            'bag' => 'bag',
            'packets' => 'packet',
            'packet' => 'packet',
            'pieces' => 'box',
            'boxes' => 'box',
            'box' => 'box',
            'rolls' => 'box',
            'bales' => 'bale',
            'bale' => 'bale',
            'unit' => 'box',
            'units' => 'box',
        ];
        
        // Get all consumables
        $consumables = DB::table('consumables')->get();
        
        // Update each consumable with standardized unit type
        foreach ($consumables as $consumable) {
            $oldUnit = $consumable->unit;
            $newUnit = $unitMap[$oldUnit] ?? 'box'; // Default to box if not found
            
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
