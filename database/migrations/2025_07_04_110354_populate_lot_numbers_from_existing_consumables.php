<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Populate lot_number from existing seed_consumable_id relationships
        \Illuminate\Support\Facades\DB::statement("
            UPDATE recipes 
            SET lot_number = (
                SELECT consumables.lot_no 
                FROM consumables 
                WHERE consumables.id = recipes.seed_consumable_id
                AND consumables.lot_no IS NOT NULL
            )
            WHERE recipes.seed_consumable_id IS NOT NULL
            AND recipes.lot_number IS NULL
        ");
        
        // Log the migration results
        $updatedCount = \Illuminate\Support\Facades\DB::table('recipes')
            ->whereNotNull('lot_number')
            ->count();
            
        \Illuminate\Support\Facades\Log::info("Populated lot_number for {$updatedCount} recipes from existing consumable data");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear lot_number field and lot_depleted_at to revert to consumable-based system
        \Illuminate\Support\Facades\DB::table('recipes')
            ->update([
                'lot_number' => null,
                'lot_depleted_at' => null
            ]);
            
        \Illuminate\Support\Facades\Log::info("Cleared lot_number data from recipes table");
    }
};
