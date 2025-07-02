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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('customer_type_id')->nullable()->after('customer_type');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE customers 
            SET customer_type_id = (
                SELECT id FROM customer_types 
                WHERE customer_types.code = customers.customer_type
            )
        ");

        // Make the foreign key required and add constraint
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('customer_type_id')->nullable(false)->change();
            $table->foreign('customer_type_id')->references('id')->on('customer_types');
        });

        // Drop the old enum column
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum column
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_type', ['retail', 'wholesale', 'farmers_market'])->default('retail')->after('customer_type_id');
        });

        // Copy data back from foreign key to enum
        DB::statement("
            UPDATE customers 
            SET customer_type = (
                SELECT code FROM customer_types 
                WHERE customer_types.id = customers.customer_type_id
            )
        ");

        // Drop foreign key and column
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['customer_type_id']);
            $table->dropColumn('customer_type_id');
        });
    }
};
