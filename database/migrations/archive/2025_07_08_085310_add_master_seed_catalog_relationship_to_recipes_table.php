<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // Add foreign key relationships
            $table->unsignedBigInteger('master_seed_catalog_id')->nullable()->after('name');
            $table->unsignedBigInteger('master_cultivar_id')->nullable()->after('master_seed_catalog_id');
            
            $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog')->onDelete('set null');
            $table->foreign('master_cultivar_id')->references('id')->on('master_cultivars')->onDelete('set null');
            
            // Add indexes for better query performance
            $table->index(['common_name', 'cultivar_name']);
        });
        
        // Migrate existing data to use the relationships
        $this->migrateExistingData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['master_seed_catalog_id']);
            $table->dropForeign(['master_cultivar_id']);
            $table->dropColumn(['master_seed_catalog_id', 'master_cultivar_id']);
            $table->dropIndex(['common_name', 'cultivar_name']);
        });
    }
    
    /**
     * Migrate existing recipe data to use master catalog relationships
     */
    private function migrateExistingData(): void
    {
        $recipes = DB::table('recipes')->whereNotNull('common_name')->get();
        
        foreach ($recipes as $recipe) {
            // Find or create master seed catalog entry
            $masterSeedCatalog = MasterSeedCatalog::firstOrCreate(
                ['common_name' => $recipe->common_name],
                [
                    'is_active' => true,
                    'category' => 'microgreens' // Default category
                ]
            );
            
            // Find or create master cultivar if cultivar_name exists
            $masterCultivarId = null;
            if ($recipe->cultivar_name) {
                $masterCultivar = MasterCultivar::firstOrCreate(
                    [
                        'master_seed_catalog_id' => $masterSeedCatalog->id,
                        'cultivar_name' => $recipe->cultivar_name
                    ],
                    ['is_active' => true]
                );
                $masterCultivarId = $masterCultivar->id;
            }
            
            // Update recipe with foreign keys
            DB::table('recipes')
                ->where('id', $recipe->id)
                ->update([
                    'master_seed_catalog_id' => $masterSeedCatalog->id,
                    'master_cultivar_id' => $masterCultivarId
                ]);
        }
    }
};
