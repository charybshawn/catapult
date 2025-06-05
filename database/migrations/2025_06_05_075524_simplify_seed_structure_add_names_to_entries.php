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
        // Step 1: Add new columns to seed_entries
        Schema::table('seed_entries', function (Blueprint $table) {
            $table->string('cultivar_name')->nullable()->after('seed_cultivar_id');
            $table->string('common_name')->nullable()->after('cultivar_name');
            $table->index(['common_name', 'cultivar_name']);
        });
        
        // Step 2: Migrate data from seed_cultivars to seed_entries
        $this->migrateCultivarData();
        
        // Step 3: Update recipes table structure
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('common_name')->nullable()->after('seed_cultivar_id');
            $table->string('cultivar_name')->nullable()->after('common_name');
            $table->index(['common_name', 'cultivar_name']);
        });
        
        // Step 4: Migrate recipe data
        $this->migrateRecipeData();
        
        // Step 5: Remove old foreign key constraints and columns (in separate migration)
        // This will be done in the cleanup migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove new columns from recipes
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['common_name', 'cultivar_name']);
            $table->dropColumn(['common_name', 'cultivar_name']);
        });
        
        // Remove new columns from seed_entries
        Schema::table('seed_entries', function (Blueprint $table) {
            $table->dropIndex(['common_name', 'cultivar_name']);
            $table->dropColumn(['cultivar_name', 'common_name']);
        });
    }
    
    /**
     * Migrate cultivar data from seed_cultivars to seed_entries
     */
    private function migrateCultivarData(): void
    {
        // Get all seed entries with their cultivars
        $entries = DB::table('seed_entries')
            ->join('seed_cultivars', 'seed_entries.seed_cultivar_id', '=', 'seed_cultivars.id')
            ->select('seed_entries.id', 'seed_cultivars.name as cultivar_name')
            ->get();
            
        foreach ($entries as $entry) {
            $commonName = $this->extractCommonName($entry->cultivar_name);
            
            DB::table('seed_entries')
                ->where('id', $entry->id)
                ->update([
                    'cultivar_name' => $entry->cultivar_name,
                    'common_name' => $commonName,
                ]);
        }
    }
    
    /**
     * Migrate recipe data from seed_cultivar relationship to direct names
     */
    private function migrateRecipeData(): void
    {
        // Get all recipes with their cultivars
        $recipes = DB::table('recipes')
            ->join('seed_cultivars', 'recipes.seed_cultivar_id', '=', 'seed_cultivars.id')
            ->select('recipes.id', 'seed_cultivars.name as cultivar_name')
            ->get();
            
        foreach ($recipes as $recipe) {
            $commonName = $this->extractCommonName($recipe->cultivar_name);
            
            DB::table('recipes')
                ->where('id', $recipe->id)
                ->update([
                    'cultivar_name' => $recipe->cultivar_name,
                    'common_name' => $commonName,
                ]);
        }
    }
    
    /**
     * Extract common name from full cultivar name
     */
    private function extractCommonName(string $cultivarName): string
    {
        if (empty($cultivarName) || $cultivarName === 'Unknown Cultivar') {
            return 'Unknown';
        }
        
        // Remove common suffixes and prefixes
        $cleaned = trim($cultivarName);
        
        // Remove organic/non-gmo/heirloom suffixes
        $cleaned = preg_replace('/\s*-\s*(Organic|Non-GMO|Heirloom|Certified).*$/i', '', $cleaned);
        
        // If there's a dash, take everything before the first dash as the common name
        if (strpos($cleaned, ' - ') !== false) {
            $parts = explode(' - ', $cleaned, 2);
            return trim($parts[0]);
        }
        
        // If there's a comma, take everything before the first comma
        if (strpos($cleaned, ',') !== false) {
            $parts = explode(',', $cleaned, 2);
            return trim($parts[0]);
        }
        
        // For patterns like "Green Forage Pea", "Brussels Winter Vertissimo", etc.
        // Try to extract the main vegetable name
        $words = explode(' ', $cleaned);
        
        // Simple heuristics for common vegetables
        $commonVegetables = [
            'pea', 'peas' => 'Pea',
            'beet', 'beets' => 'Beet', 
            'basil' => 'Basil',
            'brussels' => 'Brussels Sprouts',
            'broccoli' => 'Broccoli',
            'cabbage' => 'Cabbage',
            'carrot', 'carrots' => 'Carrot',
            'lettuce' => 'Lettuce',
            'spinach' => 'Spinach',
            'arugula' => 'Arugula',
            'kale' => 'Kale',
            'chard' => 'Chard',
            'fennel' => 'Fennel',
            'onion', 'onions' => 'Onion',
            'leek', 'leeks' => 'Leek',
            'radish' => 'Radish',
            'turnip' => 'Turnip',
            'mustard' => 'Mustard',
            'cilantro' => 'Cilantro',
            'parsley' => 'Parsley',
            'dill' => 'Dill',
            'thyme' => 'Thyme',
            'oregano' => 'Oregano',
        ];
        
        // Check each word against common vegetables
        foreach ($words as $word) {
            $lowerWord = strtolower($word);
            foreach ($commonVegetables as $key => $value) {
                if (is_array($key)) {
                    if (in_array($lowerWord, $key)) {
                        return $value;
                    }
                } else {
                    if ($lowerWord === $key) {
                        return $value;
                    }
                }
            }
        }
        
        // If no match found, take the first 1-2 words as likely common name
        if (count($words) >= 2) {
            return trim($words[0] . ' ' . $words[1]);
        }
        
        // Return the whole name if no separators found
        return $cleaned;
    }
};