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
        Schema::table('recipes', function (Blueprint $table) {
            // Add the new field that references the consumables table
            $table->foreignId('soil_consumable_id')->nullable()->after('supplier_soil_id')
                  ->constrained('consumables')->nullOnDelete();
            
            // Add field for seed consumable id (previously was seed_variety_id)
            $table->foreignId('seed_consumable_id')->nullable()->after('seed_variety_id');
            
            // We're keeping the old fields for backward compatibility,
            // but will phase them out later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['soil_consumable_id']);
            $table->dropColumn('soil_consumable_id');
            
            $table->dropColumn('seed_consumable_id');
        });
    }
};
