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
        // First, update the existing data to normalize it
        // Update any NULL values to avoid errors
        DB::table('consumables')
            ->whereNull('quantity_per_unit')
            ->update(['quantity_per_unit' => 0]);
            
        DB::table('consumables')
            ->whereNull('quantity_unit')
            ->update(['quantity_unit' => 'unit']);
            
        Schema::table('consumables', function (Blueprint $table) {
            // Update column comments to match our new structure
            
            // Update initial_stock and consumed_quantity comments
            $table->decimal('initial_stock', 12, 2)->comment('Quantity - Number of units in stock')->change();
            $table->decimal('consumed_quantity', 12, 2)->comment('Used quantity - Number of units consumed')->change();
            
            // Update quantity_per_unit to reflect it's the unit size
            $table->decimal('quantity_per_unit', 12, 2)
                ->default(0)
                ->comment('Unit size - Capacity or size of each unit (e.g., 107L per bag)')
                ->change();
                
            // Update quantity_unit to reflect it's the unit of measurement
            $table->string('quantity_unit', 20)
                ->default('l')
                ->comment('Unit of measurement (e.g., g, kg, l, ml, oz, lb)')
                ->change();
                
            // Update total_quantity comment to reflect calculation
            $table->decimal('total_quantity', 12, 2)
                ->default(0)
                ->comment('Calculated total: (initial_stock - consumed_quantity) * quantity_per_unit')
                ->change();
                
            // We're not using units_quantity anymore as it's redundant with our new structure
            if (Schema::hasColumn('consumables', 'units_quantity')) {
                $table->dropColumn('units_quantity');
            }
        });
        
        // Update the unit column separately
        Schema::table('consumables', function (Blueprint $table) {
            // Create a temporary column
            $table->string('unit_new')->nullable()->after('unit')->comment('Packaging type (e.g., bag, box, bottle)');
        });
        
        // Copy data
        DB::statement('UPDATE consumables SET unit_new = unit');
        
        // Remove old column and rename new one
        Schema::table('consumables', function (Blueprint $table) {
            // Drop the old column
            $table->dropColumn('unit');
        });
        
        Schema::table('consumables', function (Blueprint $table) {
            // Rename the new column
            $table->renameColumn('unit_new', 'unit');
        });
        
        // Set defaults and constraints
        Schema::table('consumables', function (Blueprint $table) {
            // Add the constraint back but without using enum
            // We'll validate this at the application level instead
            $table->string('unit')
                ->default('unit')
                ->comment('Packaging type (e.g., bag, box, bottle)')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            // Revert column comments
            $table->string('unit')->comment('Unit of measure')->change();
            $table->decimal('initial_stock', 12, 2)->comment('Initial quantity in stock')->change();
            $table->decimal('consumed_quantity', 12, 2)->comment('Amount that has been consumed or used')->change();
            $table->decimal('quantity_per_unit', 12, 2)->nullable()->comment('Weight of each unit')->change();
            $table->string('quantity_unit', 20)->nullable()->comment('Unit of measurement (g, kg, l, oz)')->change();
            $table->decimal('total_quantity', 12, 2)->default(0)->comment('Calculated field: current_stock * quantity_per_unit')->change();
            
            // Add back units_quantity if it was removed
            if (!Schema::hasColumn('consumables', 'units_quantity')) {
                $table->integer('units_quantity')->default(1)->after('unit')
                    ->comment('How many units are in each package');
            }
        });
    }
};
