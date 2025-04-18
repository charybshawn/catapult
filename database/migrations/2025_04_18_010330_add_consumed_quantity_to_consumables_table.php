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
        // First, update any NULL values in total_quantity
        DB::table('consumables')
            ->whereNull('total_quantity')
            ->update(['total_quantity' => 0]);
            
        // Make all changes within a transaction
        Schema::table('consumables', function (Blueprint $table) {
            // Add consumed_quantity if it doesn't exist
            if (!Schema::hasColumn('consumables', 'consumed_quantity')) {
                $table->decimal('consumed_quantity', 12, 2)->default(0)->after('current_stock')
                    ->comment('Amount that has been consumed or used');
            }
            
            // Update total_quantity definition
            $table->decimal('total_quantity', 12, 2)->default(0)->change();
            
            // Rename current_stock to initial_stock if it hasn't been renamed yet
            if (Schema::hasColumn('consumables', 'current_stock') && !Schema::hasColumn('consumables', 'initial_stock')) {
                $table->renameColumn('current_stock', 'initial_stock');
            }
        });
        
        // Add DB view/computed column logic if your DB supports it
        // For now, we'll handle this at the model level with accessors/mutators
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            // Rename back to original column name if it exists
            if (Schema::hasColumn('consumables', 'initial_stock') && !Schema::hasColumn('consumables', 'current_stock')) {
                $table->renameColumn('initial_stock', 'current_stock');
            }
            
            // No need to drop consumed_quantity as we're only removing it if we added it
            if (Schema::hasColumn('consumables', 'consumed_quantity')) {
                $table->dropColumn('consumed_quantity');
            }
        });
    }
};
