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
        // Add the new foreign key column
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('supplier_type_id')->nullable()->after('type');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE suppliers 
            SET supplier_type_id = (
                SELECT id FROM supplier_types 
                WHERE supplier_types.code = suppliers.type
            )
        ");

        // Make the foreign key required and add constraint
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('supplier_type_id')->nullable(false)->change();
            $table->foreign('supplier_type_id')->references('id')->on('supplier_types');
        });

        // Drop the old enum column
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum column
        Schema::table('suppliers', function (Blueprint $table) {
            $table->enum('type', ['soil', 'seed', 'consumable', 'other', 'packaging'])->default('consumable')->after('supplier_type_id');
        });

        // Copy data back from foreign key to enum
        DB::statement("
            UPDATE suppliers 
            SET type = (
                SELECT code FROM supplier_types 
                WHERE supplier_types.id = suppliers.supplier_type_id
            )
        ");

        // Drop foreign key and column
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['supplier_type_id']);
            $table->dropColumn('supplier_type_id');
        });
    }
};
