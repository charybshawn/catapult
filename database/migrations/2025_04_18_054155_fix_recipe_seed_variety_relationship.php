<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Recipe;
use App\Models\SeedVariety;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check for recipes with null seed_variety_id
        $recipesWithoutVariety = Recipe::whereNull('seed_variety_id')->get();
        
        if ($recipesWithoutVariety->count() > 0) {
            // Create a default seed variety if needed
            $defaultVariety = SeedVariety::firstOrCreate(
                ['name' => 'Default Variety'],
                [
                    'crop_type' => 'Mixed',
                    'supplier_id' => 1,
                    'is_active' => true,
                ]
            );
            
            // Update recipes with null seed_variety_id
            foreach ($recipesWithoutVariety as $recipe) {
                $recipe->seed_variety_id = $defaultVariety->id;
                $recipe->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as it fixes data integrity
    }
};
