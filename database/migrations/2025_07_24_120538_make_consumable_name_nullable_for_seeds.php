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
        Schema::table('consumables', function (Blueprint $table) {
            // Make name field nullable for seed consumables (computed via accessor)
            $table->string('name')->nullable()->change();
        });
        
        // Clear existing names for seed consumables so they use computed values
        DB::table('consumables')
            ->whereIn('consumable_type_id', function($query) {
                $query->select('id')
                      ->from('consumable_types')
                      ->where('code', 'seed');
            })
            ->update(['name' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore names for seed consumables before making field required
        DB::table('consumables')
            ->whereIn('consumable_type_id', function($query) {
                $query->select('id')
                      ->from('consumable_types')
                      ->where('code', 'seed');
            })
            ->whereNull('name')
            ->update(['name' => DB::raw("CONCAT(
                COALESCE((SELECT common_name FROM master_seed_catalog WHERE id = master_seed_catalog_id), 'Unknown'),
                ' (',
                COALESCE(cultivar, 'Unknown'),
                ')'
            )")]);
            
        Schema::table('consumables', function (Blueprint $table) {
            // Make name field required again
            $table->string('name')->nullable(false)->change();
        });
    }
};
