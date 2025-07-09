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
        // Add index for consumables.lot_no
        // This will improve query performance when searching by lot number
        Schema::table('consumables', function (Blueprint $table) {
            // Check if index doesn't already exist before adding
            if (!$this->indexExists('consumables', 'consumables_lot_no_index')) {
                $table->index('lot_no', 'consumables_lot_no_index');
            }
        });

        // Note: crops.current_stage_id already has an index from the foreign key constraint
        // Note: recipes.lot_number already has indexes (composite index with is_active)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove index from consumables.lot_no
        Schema::table('consumables', function (Blueprint $table) {
            if ($this->indexExists('consumables', 'consumables_lot_no_index')) {
                $table->dropIndex('consumables_lot_no_index');
            }
        });
    }

    /**
     * Check if an index exists on a table.
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        return count($indexes) > 0;
    }
};
