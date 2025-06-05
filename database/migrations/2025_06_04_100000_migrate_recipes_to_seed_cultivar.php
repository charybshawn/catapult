<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add the new seed_cultivar_id column
        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('seed_cultivar_id')->nullable()->constrained('seed_cultivars')->onDelete('cascade');
        });
        
        // Migrate data from seed_varieties to seed_cultivars
        $this->migrateSeedVarietiesToCultivars();
        
        // Remove the old seed_variety_id column
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['seed_variety_id']);
            $table->dropColumn('seed_variety_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the seed_variety_id column
        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('seed_variety_id')->nullable()->constrained('seed_varieties')->onDelete('cascade');
        });
        
        // Remove the seed_cultivar_id column
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['seed_cultivar_id']);
            $table->dropColumn('seed_cultivar_id');
        });
    }
    
    /**
     * Migrate data from seed_varieties to seed_cultivars
     */
    private function migrateSeedVarietiesToCultivars(): void
    {
        // Get all recipes with seed varieties
        $recipes = DB::table('recipes')
            ->join('seed_varieties', 'recipes.seed_variety_id', '=', 'seed_varieties.id')
            ->select('recipes.id as recipe_id', 'seed_varieties.name', 'seed_varieties.crop_type')
            ->get();
            
        foreach ($recipes as $recipe) {
            // Find or create corresponding seed cultivar
            $cultivar = DB::table('seed_cultivars')
                ->where('name', $recipe->name)
                ->first();
                
            if (!$cultivar) {
                // Create new cultivar
                $cultivarId = DB::table('seed_cultivars')->insertGetId([
                    'name' => $recipe->name,
                    'description' => "Migrated from seed variety: {$recipe->name} ({$recipe->crop_type})",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $cultivarId = $cultivar->id;
            }
            
            // Update recipe to use cultivar
            DB::table('recipes')
                ->where('id', $recipe->recipe_id)
                ->update(['seed_cultivar_id' => $cultivarId]);
        }
    }
};