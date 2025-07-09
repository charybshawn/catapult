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
        // Only run if the columns exist (they may not in fresh test environments)
        if (Schema::hasColumn('consumables', 'consumable_type_id') && 
            Schema::hasColumn('consumables', 'consumable_unit_id')) {
            Schema::table('consumables', function (Blueprint $table) {
                // Make the foreign key columns required
                $table->foreignId('consumable_type_id')->nullable(false)->change();
                $table->foreignId('consumable_unit_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run if the columns exist
        if (Schema::hasColumn('consumables', 'consumable_type_id') && 
            Schema::hasColumn('consumables', 'consumable_unit_id')) {
            Schema::table('consumables', function (Blueprint $table) {
                // Make the foreign key columns nullable again
                $table->foreignId('consumable_type_id')->nullable()->change();
                $table->foreignId('consumable_unit_id')->nullable()->change();
            });
        }
    }
};
