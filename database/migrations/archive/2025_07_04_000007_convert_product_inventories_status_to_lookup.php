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
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->foreignId('product_inventory_status_id')->nullable()->after('status');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE product_inventories 
            SET product_inventory_status_id = (
                SELECT id FROM product_inventory_statuses 
                WHERE product_inventory_statuses.code = product_inventories.status
            )
            WHERE status IS NOT NULL
        ");

        // Make the foreign key non-nullable and add constraint
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->foreignId('product_inventory_status_id')->nullable(false)->change();
            $table->foreign('product_inventory_status_id')->references('id')->on('product_inventory_statuses');
        });

        // Drop old enum column
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back enum column
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->enum('status', ['active', 'depleted', 'expired', 'damaged'])->default('active')->after('product_inventory_status_id');
        });

        // Copy data back from foreign keys to enum
        DB::statement("
            UPDATE product_inventories 
            SET status = (
                SELECT code FROM product_inventory_statuses 
                WHERE product_inventory_statuses.id = product_inventories.product_inventory_status_id
            )
            WHERE product_inventory_status_id IS NOT NULL
        ");

        // Drop foreign key and column
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->dropForeign(['product_inventory_status_id']);
            $table->dropColumn('product_inventory_status_id');
        });
    }
};