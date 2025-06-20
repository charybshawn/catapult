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
        // Check if seed_variety_id column exists before proceeding
        if (!Schema::hasColumn('product_mix_components', 'seed_variety_id')) {
            // Column doesn't exist, skip this migration
            return;
        }
        
        // Add new seed_cultivar_id column if it doesn't exist
        if (!Schema::hasColumn('product_mix_components', 'seed_cultivar_id')) {
            Schema::table('product_mix_components', function (Blueprint $table) {
                $table->foreignId('seed_cultivar_id')->nullable()->constrained('seed_cultivars')->onDelete('cascade');
            });
        }
        
        // Migrate data from seed_varieties to seed_cultivars
        $components = DB::table('product_mix_components')
            ->join('seed_varieties', 'product_mix_components.seed_variety_id', '=', 'seed_varieties.id')
            ->select('product_mix_components.id as component_id', 'seed_varieties.name')
            ->get();
            
        foreach ($components as $component) {
            // Find corresponding cultivar
            $cultivar = DB::table('seed_cultivars')
                ->where('name', $component->name)
                ->first();
                
            if ($cultivar) {
                DB::table('product_mix_components')
                    ->where('id', $component->component_id)
                    ->update(['seed_cultivar_id' => $cultivar->id]);
            }
        }
        
        // Drop the old foreign key and column if they exist
        if (Schema::hasColumn('product_mix_components', 'seed_variety_id')) {
            // Check if foreign key exists before dropping
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'product_mix_components' 
                AND CONSTRAINT_NAME LIKE '%seed_variety_id%'
            ");
            
            if (!empty($foreignKeys)) {
                Schema::table('product_mix_components', function (Blueprint $table) {
                    $table->dropForeign(['seed_variety_id']);
                });
            }
            
            Schema::table('product_mix_components', function (Blueprint $table) {
                $table->dropColumn('seed_variety_id');
            });
        }
        
        // Make seed_cultivar_id required and add unique constraint if it doesn't exist
        Schema::table('product_mix_components', function (Blueprint $table) {
            $table->foreignId('seed_cultivar_id')->nullable(false)->change();
            
            // Check if unique constraint already exists
            $indexes = DB::select("SHOW INDEX FROM product_mix_components WHERE Key_name = 'product_mix_components_product_mix_id_seed_cultivar_id_unique'");
            if (empty($indexes)) {
                $table->unique(['product_mix_id', 'seed_cultivar_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new structure
        Schema::table('product_mix_components', function (Blueprint $table) {
            $table->dropForeign(['seed_cultivar_id']);
            $table->dropUnique(['product_mix_id', 'seed_cultivar_id']);
            $table->dropColumn('seed_cultivar_id');
        });
        
        // Restore old structure
        Schema::table('product_mix_components', function (Blueprint $table) {
            $table->foreignId('seed_variety_id')->constrained()->onDelete('cascade');
            $table->unique(['product_mix_id', 'seed_variety_id']);
        });
    }
};