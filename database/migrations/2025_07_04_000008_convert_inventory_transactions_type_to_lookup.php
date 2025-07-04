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
        // Add new foreign key column
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('inventory_transaction_type_id')->nullable()->after('type');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE inventory_transactions 
            SET inventory_transaction_type_id = (
                SELECT id FROM inventory_transaction_types 
                WHERE inventory_transaction_types.code = inventory_transactions.type
            )
            WHERE type IS NOT NULL
        ");

        // Make the foreign key non-nullable and add constraint
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('inventory_transaction_type_id')->nullable(false)->change();
            $table->foreign('inventory_transaction_type_id')->references('id')->on('inventory_transaction_types');
        });

        // Drop old enum column
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back enum column
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return', 'damage', 'transfer'])->after('inventory_transaction_type_id');
        });

        // Copy data back from foreign keys to enum
        DB::statement("
            UPDATE inventory_transactions 
            SET type = (
                SELECT code FROM inventory_transaction_types 
                WHERE inventory_transaction_types.id = inventory_transactions.inventory_transaction_type_id
            )
            WHERE inventory_transaction_type_id IS NOT NULL
        ");

        // Drop foreign key and column
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['inventory_transaction_type_id']);
            $table->dropColumn('inventory_transaction_type_id');
        });
    }
};