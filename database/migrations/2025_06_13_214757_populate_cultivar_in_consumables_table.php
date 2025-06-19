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
        // Get all seed consumables
        $consumables = DB::table('consumables')
            ->where('type', 'seed')
            ->whereNotNull('master_seed_catalog_id')
            ->whereNull('cultivar')
            ->get();

        foreach ($consumables as $consumable) {
            $cultivar = null;
            
            // First, check if there's a master_cultivar_id
            if ($consumable->master_cultivar_id) {
                $masterCultivar = DB::table('master_cultivars')
                    ->where('id', $consumable->master_cultivar_id)
                    ->first();
                
                if ($masterCultivar) {
                    $cultivar = $masterCultivar->cultivar_name;
                }
            }
            
            // If still no cultivar, try to extract from name
            if (!$cultivar && preg_match('/\(([^)]+)\)/', $consumable->name, $matches)) {
                $cultivar = $matches[1];
            }
            
            // If still no cultivar, check the master seed catalog's cultivars array
            if (!$cultivar) {
                $catalog = DB::table('master_seed_catalog')
                    ->where('id', $consumable->master_seed_catalog_id)
                    ->first();
                
                if ($catalog && $catalog->cultivars) {
                    $cultivars = json_decode($catalog->cultivars, true);
                    if (is_array($cultivars) && count($cultivars) === 1) {
                        $cultivar = $cultivars[0];
                    }
                }
            }
            
            // Update the consumable if we found a cultivar
            if ($cultivar) {
                DB::table('consumables')
                    ->where('id', $consumable->id)
                    ->update(['cultivar' => $cultivar]);
                    
                echo "Updated consumable '{$consumable->name}' with cultivar '{$cultivar}'\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all cultivar values back to null
        DB::table('consumables')
            ->where('type', 'seed')
            ->update(['cultivar' => null]);
    }
};