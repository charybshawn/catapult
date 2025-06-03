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
        // Add missing foreign key constraint for consumables.packaging_type_id
        Schema::table('consumables', function (Blueprint $table) {
            // Only add if the constraint doesn't already exist
            if (!$this->foreignKeyExists('consumables', 'consumables_packaging_type_id_foreign')) {
                $table->foreign('packaging_type_id')
                      ->references('id')
                      ->on('packaging_types')
                      ->nullOnDelete();
            }
        });

        // Add foreign key constraint for seed_variations.consumable_id if missing
        Schema::table('seed_variations', function (Blueprint $table) {
            if (!$this->foreignKeyExists('seed_variations', 'seed_variations_consumable_id_foreign')) {
                $table->foreign('consumable_id')
                      ->references('id')
                      ->on('consumables')
                      ->nullOnDelete();
            }
        });

        // Add foreign key constraint for seed_price_history.seed_variation_id if missing
        Schema::table('seed_price_history', function (Blueprint $table) {
            if (!$this->foreignKeyExists('seed_price_history', 'seed_price_history_seed_variation_id_foreign')) {
                $table->foreign('seed_variation_id')
                      ->references('id')
                      ->on('seed_variations')
                      ->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropForeign(['packaging_type_id']);
        });

        Schema::table('seed_variations', function (Blueprint $table) {
            $table->dropForeign(['consumable_id']);
        });

        Schema::table('seed_price_history', function (Blueprint $table) {
            $table->dropForeign(['seed_variation_id']);
        });
    }

    /**
     * Helper method to check if foreign key exists
     */
    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $exists = $connection->table('information_schema.table_constraints')
            ->where('constraint_schema', $databaseName)
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();

        return $exists;
    }
};
