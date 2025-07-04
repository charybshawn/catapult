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
            // Add new foreign key columns
            $table->unsignedBigInteger('consumable_type_id')->nullable()->after('name');
            $table->unsignedBigInteger('consumable_unit_id')->nullable()->after('consumable_type_id');
            
            // Add foreign key constraints
            $table->foreign('consumable_type_id')->references('id')->on('consumable_types');
            $table->foreign('consumable_unit_id')->references('id')->on('consumable_units');
        });
        
        // Migrate data from enum 'type' column to consumable_type_id
        $this->migrateExistingData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropForeign(['consumable_type_id']);
            $table->dropForeign(['consumable_unit_id']);
            $table->dropColumn(['consumable_type_id', 'consumable_unit_id']);
        });
    }
    
    /**
     * Migrate existing enum values to foreign key references
     */
    private function migrateExistingData(): void
    {
        $consumableTypes = [
            'packaging' => 1,
            'soil' => 2,
            'seed' => 3,
            'label' => 4,
            'other' => 5,
        ];
        
        foreach ($consumableTypes as $enumValue => $typeId) {
            DB::table('consumables')
                ->where('type', $enumValue)
                ->update(['consumable_type_id' => $typeId]);
        }
        
        // Set a default consumable_unit_id (we'll create a 'unit' type)
        $defaultUnitId = DB::table('consumable_units')->where('code', 'unit')->value('id');
        if (!$defaultUnitId) {
            $defaultUnitId = DB::table('consumable_units')->insertGetId([
                'code' => 'unit',
                'name' => 'Unit',
                'symbol' => 'unit',
                'description' => 'Generic unit for consumables',
                'category' => 'count',
                'conversion_factor' => 1.0,
                'base_unit' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        DB::table('consumables')
            ->whereNull('consumable_unit_id')
            ->update(['consumable_unit_id' => $defaultUnitId]);
    }
};
